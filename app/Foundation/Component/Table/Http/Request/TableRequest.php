<?php

namespace App\Foundation\Component\Table\Http\Request;

use App\Foundation\Component\Table\Enum\ExportFormat;
use App\Foundation\Component\Table\Support\SortExpression;
use App\Foundation\Component\Table\ValueObject\TableState;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The one place raw table input is read.
 *
 * Almost nothing here is validated, and that is the decision: a bookmarked address outlives the
 * columns it names, so a value making no sense is the absence of a value rather than an error. The
 * export format is the exception, being an action rather than a remembered position.
 */
class TableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'export' => ['nullable', Rule::in(array_column(ExportFormat::cases(), 'value'))],
        ];
    }

    /**
     * Every key is read from under the table's name — `users[page]`, `users[filter][role]` — so
     * that two tables on one page never read each other's state. The export format is the one
     * exception and stays unnested.
     */
    public function toState(string $name): TableState
    {
        $own = $this->query($name);
        $own = is_array($own) ? $own : [];

        $perPage = (int) $this->stringOrNull($own, 'per_page');

        return new TableState(
            page: max(1, (int) $this->stringOrNull($own, 'page')),
            perPage: $perPage > 0 ? $perPage : null,
            search: $this->searchTerm($own),
            filters: $this->filters($own),
            sort: SortExpression::parse($this->stringOrNull($own, 'sort')),
            export: ExportFormat::tryFrom((string) $this->query('export')),
        );
    }

    /**
     * @param  array<string, mixed>  $own
     */
    private function searchTerm(array $own): ?string
    {
        $term = trim((string) $this->stringOrNull($own, 'q'));

        return $term === '' ? null : $term;
    }

    /**
     * Whether a value is a control nobody touched, which an address carries as an empty value.
     *
     * Nought is not nothing: it is an id, and it is a boolean answered "no".
     */
    private static function carriesNothing(mixed $value): bool
    {
        return $value === null || $value === '';
    }

    /**
     * Kept only where a value is a scalar or a flat list of scalars — the two shapes a filter can
     * be declared to read. A deeper structure is dropped rather than walked any further in, and a
     * value carrying nothing is dropped with it.
     *
     * @param  array<string, mixed>  $own
     * @return array<string, mixed>
     */
    private function filters(array $own): array
    {
        $raw = $own['filter'] ?? null;

        if (! is_array($raw)) {
            return [];
        }

        $filters = [];

        foreach ($raw as $key => $value) {
            if (! is_array($value)) {
                if (! self::carriesNothing($value)) {
                    $filters[$key] = $value;
                }

                continue;
            }

            $flat = array_filter(
                $value,
                fn ($item) => ! is_array($item) && ! is_object($item) && ! self::carriesNothing($item),
            );

            if ($flat !== []) {
                $filters[$key] = $flat;
            }
        }

        return $filters;
    }

    /**
     * A repeated parameter arrives as an array, which would otherwise reach a string cast and
     * surface as "Array" — a term nobody typed.
     *
     * @param  array<string, mixed>  $own
     */
    private function stringOrNull(array $own, string $key): ?string
    {
        $value = $own[$key] ?? null;

        return is_scalar($value) ? (string) $value : null;
    }
}
