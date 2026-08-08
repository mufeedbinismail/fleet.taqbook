<?php

namespace App\Legacy\Navigation\ValueObject;

use App\Legacy\Navigation\Enum\Query;
use App\Foundation\Navigation\Contract\Target;
use Illuminate\Http\Request;

/**
 * A FrontAccounting page: a script path plus the query string that selects its mode.
 *
 * All of FA's addressing weirdness is confined here — that one script serves several destinations
 * depending on its query, and that the empty path means index.php.
 */
final class LegacyPageTarget implements Target
{
    public readonly string $script;

    /**
     * @param  array<string, scalar|Query>  $query
     */
    public function __construct(string $script, public readonly array $query = [])
    {
        $this->script = self::normalize($script);
    }

    /**
     * @param  array<string, scalar|Query>  $query
     */
    public static function at(string $script, array $query = []): self
    {
        return new self($script, $query);
    }

    /**
     * Null once any parameter is a wildcard. The record such a parameter stands for is most of the
     * address, and there is no particular one of those to build.
     */
    public function url(): ?string
    {
        if (in_array(Query::ANY, $this->query, true)) {
            return null;
        }

        return legacy_url($this->script, $this->query);
    }

    /**
     * Claims a request when the script matches and every query parameter this target names is
     * present — carrying the same value, or any value at all where the target says so. Extra
     * parameters on the request are ignored, so a paginated or filtered view still resolves, but
     * the same script in a *different* mode does not.
     *
     * A parameter named with a value counts double, so a target that pins one outranks a target
     * that only requires it to be there.
     */
    public function matches(Request $request): ?int
    {
        if (self::normalize($request->path()) !== $this->script) {
            return null;
        }

        $rank = 1;

        foreach ($this->query as $key => $value) {
            if (! $request->query->has($key)) {
                return null;
            }

            if ($value === Query::ANY) {
                $rank++;

                continue;
            }

            if ((string) $request->query($key) !== (string) $value) {
                return null;
            }

            $rank += 2;
        }

        return $rank;
    }

    public function signature(): string
    {
        $query = $this->query;
        ksort($query);

        $parts = [];

        foreach ($query as $key => $value) {
            // Values are encoded, so a parameter genuinely holding "*" cannot read as a wildcard.
            $parts[] = rawurlencode($key).'='.($value === Query::ANY ? '*' : rawurlencode((string) $value));
        }

        return 'legacy:'.$this->script.($parts === [] ? '' : '?'.implode('&', $parts));
    }

    private static function normalize(string $script): string
    {
        $script = trim(parse_url($script, PHP_URL_PATH) ?: '', '/');

        return $script === '' ? 'index.php' : $script;
    }
}
