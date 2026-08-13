<?php

namespace App\Foundation\Component\Select\Http\Request;

use App\Foundation\Component\Select\Contract\NarrowsOptions;
use App\Foundation\Component\Select\Contract\SelectDefinition;
use App\Foundation\Component\Select\Exception\SelectNotDefinedException;
use App\Foundation\Component\Select\Intent\OptionSearchIntent;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Every option endpoint's input: the term, the page, and the values a client wants a verdict on.
 *
 * A screen that narrows its list further declares those rules on its definition rather than here,
 * so the rules and the query reading them stay in one file while raw input is still read in one
 * place.
 */
final class OptionSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $select = $this->select();

        return [
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.OptionSearchIntent::MAX_PER_PAGE],

            // Capped because the list is a round trip the user is waiting on, and a client holding
            // more values than a page can show is asking a question the control cannot draw.
            'selected' => ['nullable', 'array', 'max:'.OptionSearchIntent::MAX_PER_PAGE],
            'selected.*' => ['string', 'max:255'],
            ...($select instanceof NarrowsOptions ? $select->filterRules() : []),
        ];
    }

    /**
     * What this request is a search of, which decides both the narrowing that is legal and where
     * the rows come from.
     *
     * @throws SelectNotDefinedException if nothing named one
     */
    public function select(): SelectDefinition
    {
        $named = $this->route()?->defaults['select'] ?? null;

        if ($named === null) {
            throw SelectNotDefinedException::onRoute($this->path());
        }

        return app($named);
    }

    public function toIntent(): OptionSearchIntent
    {
        $validated = $this->validated();
        $select = $this->select();

        return new OptionSearchIntent(
            search: trim((string) ($validated['search'] ?? '')),
            page: (int) ($validated['page'] ?? 1),
            perPage: (int) ($validated['per_page'] ?? OptionSearchIntent::PER_PAGE),

            // Re-indexed and de-duplicated: the validator keeps whatever keys the query string
            // arrived with, and a repeated value would be answered on twice.
            selected: array_values(array_unique(array_map(
                static fn ($value) => (string) $value,
                $validated['selected'] ?? [],
            ))),
            filters: $select instanceof NarrowsOptions
                ? array_intersect_key($validated, $select->filterRules())
                : [],
        );
    }
}
