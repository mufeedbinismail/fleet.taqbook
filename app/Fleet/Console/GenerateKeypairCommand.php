<?php

namespace App\Fleet\Console;

use App\Trust\Entity\PrivateKey;
use App\Trust\Enum\SignatureAlgorithm;
use Illuminate\Console\Command;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateKeypairCommand extends Command
{
    protected $signature = 'fleet:keypair:generate
        {--algorithm=ed25519 : The signature algorithm the keypair is for}';

    protected $description = 'Generate a root keypair and print it';

    protected $help = <<<'TEXT'
        Generates a new root keypair and prints both halves. Nothing is
        written anywhere, so the printed secret is the only copy.

        Run it on an offline machine, never on the fleet server. Generate one
        root for development and one for production.

        Examples:

          fleet:keypair:generate
          fleet:keypair:generate --algorithm=ed25519

        Each key is printed as <algorithm>:<base64url>. Prints a JSON object:

          secret       the root's private key. Keep it offline, on paper or a
                       hardware token; fleet:keypair:install asks for it.
          public       the root's public key. Set it as FLEET_ROOT_KEY in
                       every tenant's .env, exactly as printed.
          fingerprint  a short identifier of the public key, for checking it
                       by eye.
        TEXT;

    public function handle(): int
    {
        $algorithm = SignatureAlgorithm::tryFrom($this->option('algorithm'));

        if ($algorithm === null) {
            $this->output->getErrorStyle()->error("Unknown algorithm [{$this->option('algorithm')}].");

            return self::FAILURE;
        }

        $secret = PrivateKey::generate($algorithm);
        $public = $secret->publicKey();

        $this->output->writeln(json_encode([
            'secret' => $secret->toEncodedString(),
            'public' => $public->toEncodedString(),
            'fingerprint' => $public->fingerprint(),
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), OutputInterface::OUTPUT_RAW);

        return self::SUCCESS;
    }
}
