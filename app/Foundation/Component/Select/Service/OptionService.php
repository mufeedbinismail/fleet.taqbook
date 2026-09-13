<?php

namespace App\Foundation\Component\Select\Service;

use App\Foundation\Component\Select\Contract\SelectDefinition;
use App\Foundation\Component\Select\Exception\SelectException;
use App\Foundation\Component\Select\Repository\OptionRepository;
use App\Foundation\Component\Select\ValueObject\OptionChannel;
use App\Foundation\Component\Select\ValueObject\OptionSource;
use App\Foundation\Component\Select\ValueObject\SelectState;
use Illuminate\Routing\Router;

class OptionService
{
    public function __construct(
        private readonly OptionRepository $options,
        private readonly Router $router,
    ) {}

    /**
     * The list in whichever channel its size under the narrowing calls for: in full while it is
     * small enough to scroll, otherwise as the address it is searched at.
     *
     * @param  array<string, mixed>  $filters  a narrowed definition's own parameters, already typed
     *
     * @throws SelectException if the route the definition names is not registered
     */
    public function channel(SelectDefinition $select, array $filters = []): OptionChannel
    {
        // Checked before any row is read, so a definition with nowhere to fall back to is refused
        // on its first request rather than on the day its set grows.
        $source = $this->source($select, $filters);

        $page = $this->options->page($select, new SelectState(
            search: '',
            page: 1,
            perPage: (int) config('component.select.inline_up_to'),
            selected: [],
            filters: $filters,
        ));

        return $page->hasMore
            ? OptionChannel::fromSource($source)
            : OptionChannel::inline($page->options);
    }

    /**
     * The list as the address it is searched at, whatever its size: a narrowing that changes while
     * the control stands has nowhere else to travel.
     *
     * @param  array<string, mixed>  $filters  narrowing fixed here rather than chosen later
     *
     * @throws SelectException if the route the definition names is not registered
     */
    public function lookup(SelectDefinition $select, array $filters = []): OptionChannel
    {
        return OptionChannel::fromSource($this->source($select, $filters));
    }

    /**
     * @param  array<string, mixed>  $filters
     *
     * @throws SelectException
     */
    private function source(SelectDefinition $select, array $filters): OptionSource
    {
        if (! $this->router->has($select::routeName())) {
            throw SelectException::unreachable($select::class, $select::routeName());
        }

        return new OptionSource(url: $select::routeName(), params: $filters);
    }
}
