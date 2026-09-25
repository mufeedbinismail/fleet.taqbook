<?php

namespace App\Fleet\Console;

use App\Foundation\Shared\ValueObject\DomainDateTime;
use App\Trust\Entity\PrivateKey;
use App\Trust\Enum\SignatureAlgorithm;
use App\Trust\Exception\TrustException;
use App\Trust\Repository\TrustRepository;
use App\Trust\Service\DelegationService;
use Illuminate\Console\Command;
use Illuminate\Foundation\Application;
use InvalidArgumentException;
use JsonException;
use Laravel\Prompts\PasswordPrompt;
use Laravel\Prompts\Prompt;
use RuntimeException;
use SodiumException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Throwable;

use function Laravel\Prompts\password;

class InstallKeypairCommand extends Command
{
    protected $signature = 'fleet:keypair:install
        {--kid= : A label for the key. Defaults to this year and month}
        {--until= : The last day the delegation holds, as Y-m-d. Defaults to a year out}
        {--algorithm=ed25519 : The signature algorithm of the new operational key}';

    protected $description = 'Give this server a new operational key, vouched for by the root';

    protected $help = <<<'TEXT'
        Gives this server a new operational key and a delegation from the root
        vouching for it, and writes both to .env. Run it on the fleet server
        with the root secret at hand, and again before the delegation's last
        day.

        The first install writes the root's public key as FLEET_ROOT_KEY.
        After that FLEET_ROOT_KEY is never changed, and a root secret that is
        not that root is refused. The root secret is asked for on a hidden
        prompt and is not stored. If anything fails, .env is left as it was.

        Examples:

          fleet:keypair:install
          fleet:keypair:install --kid=2026-09 --until=2027-09-30
          fleet:keypair:install --algorithm=ed25519

        The operational key's algorithm need not be the root's: the root vouches
        for a key of any algorithm it knows.

        Writes FLEET_ROOT_KEY, FLEET_OPERATIONAL_KEY and FLEET_DELEGATION to
        .env, each key as <algorithm>:<base64url>, and prints a JSON object:

          public       the new operational key's public half
          fingerprint  its fingerprint
          kid          its label
          until        the last day the delegation holds
          root_public  the root's public key, as FLEET_ROOT_KEY holds it. It
                       must equal FLEET_ROOT_KEY on every tenant, or they
                       will refuse this server's messages.
          delegation   the delegation, as written to .env
          replaced     the fingerprint of the key this replaced, or null on
                       a first install. Keep it in case that key has to be
                       revoked.
        TEXT;

    public function handle(
        TrustRepository $trustRepository,
        DelegationService $delegator,
        Application $app,
    ): int {
        $stderr = $this->output->getErrorStyle();

        try {
            // Before the prompt, so a refusal here never costs a trip to the safe.
            $until = $this->until();
            $algorithm = $this->algorithm();
            $outgoing = $trustRepository->operationalKey();
            $firstInstall = $trustRepository->rootKey() === null;

            $root = $this->rootFromPrompt();
        } catch (InvalidArgumentException|TrustException $e) {
            $stderr->error([$e->getMessage(), 'Nothing was written.']);

            return self::FAILURE;
        }

        $operational = PrivateKey::generate($algorithm);

        // A first install has no anchor to check the root against, so the root it is given becomes the anchor.
        if ($firstInstall) {
            config(['trust.root_key' => $root->publicKey()->toEncodedString()]);
        }

        try {
            $packed = $delegator->issue(
                $operational->publicKey(),
                $this->option('kid') ?? DomainDateTime::now()->format('Y-m'),
                $until,
                $root,
            );
        } catch (InvalidArgumentException|JsonException|SodiumException|TrustException $e) {
            $stderr->error(["The root secret does not match FLEET_ROOT_KEY: {$e->getMessage()}", 'Nothing was written.']);

            return self::FAILURE;
        }

        $delegation = $packed->statement;
        $anchor = $trustRepository->rootKey();

        $this->writeEnvironmentFile($app->environmentFilePath(), [
            'FLEET_ROOT_KEY' => $anchor->toEncodedString(),
            'FLEET_OPERATIONAL_KEY' => $operational->toEncodedString(),
            'FLEET_DELEGATION' => $packed->toEncodedString(),
        ]);

        $this->output->writeln(json_encode([
            'public' => $delegation->key->toEncodedString(),
            'fingerprint' => $delegation->key->fingerprint(),
            'kid' => $delegation->kid,
            'until' => $delegation->until->toDateString(),
            'root_public' => $anchor->toEncodedString(),
            'delegation' => $packed->toEncodedString(),
            'replaced' => $outgoing === null ? null : ['fingerprint' => $outgoing->publicKey()->fingerprint()],
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), OutputInterface::OUTPUT_RAW);

        $stderr->note('Check root_public against FLEET_ROOT_KEY on a tenant, then put the root secret back in the safe.');

        return self::SUCCESS;
    }

    private function rootFromPrompt(): PrivateKey
    {
        $stderr = $this->output->getErrorStyle();

        // On stderr, so stdout stays one JSON object.
        Prompt::setOutput($stderr);

        // Where the masked field cannot run, hidden or not at all: a root secret echoed back is one
        // left in a scrollback buffer.
        PasswordPrompt::fallbackUsing(fn () => $stderr->askQuestion(
            (new Question('Root secret'))->setHidden(true)->setHiddenFallback(false),
        ));

        $answer = trim(password('Root secret', hint: 'Each character shows as •'));

        try {
            return PrivateKey::fromEncodedString($answer);
        } catch (Throwable) {
            throw new InvalidArgumentException('That is not a root secret.');
        }
    }

    private function until(): DomainDateTime
    {
        $until = $this->option('until');

        return $until === null ? DomainDateTime::now()->addYear() : DomainDateTime::fromDateString($until);
    }

    private function algorithm(): SignatureAlgorithm
    {
        return SignatureAlgorithm::tryFrom($this->option('algorithm'))
            ?? throw new InvalidArgumentException("Unknown algorithm [{$this->option('algorithm')}].");
    }

    /**
     * Every value lands or none does: the new contents are written beside the file and renamed
     * over it, so an interrupted write leaves the old file whole.
     *
     * @param  array<string, string>  $values
     */
    private function writeEnvironmentFile(string $path, array $values): void
    {
        $contents = file_exists($path) ? file_get_contents($path) : '';

        foreach ($values as $key => $value) {
            $contents = $this->replaceValue($contents, $key, $value);
        }

        $staged = $path.'.'.bin2hex(random_bytes(4)).'.tmp';

        if (file_put_contents($staged, $contents) === false) {
            throw new RuntimeException("Could not write {$staged}.");
        }

        if (file_exists($path)) {
            chmod($staged, fileperms($path) & 0777);
        }

        if (! rename($staged, $path)) {
            @unlink($staged);

            throw new RuntimeException("Could not replace {$path}.");
        }
    }

    private function replaceValue(string $contents, string $key, string $value): string
    {
        $line = $key.'='.$value;

        $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

        if (preg_match($pattern, $contents)) {
            return preg_replace_callback($pattern, fn () => $line, $contents, 1);
        }

        return ($contents === '' || str_ends_with($contents, "\n") ? $contents : $contents."\n").$line."\n";
    }
}
