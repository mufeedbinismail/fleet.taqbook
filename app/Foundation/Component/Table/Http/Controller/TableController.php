<?php

namespace App\Foundation\Component\Table\Http\Controller;

use App\Foundation\Component\Table\Builder\TableBuilder;
use App\Foundation\Component\Table\Exception\TableException;
use App\Foundation\Component\Table\Http\Request\TableRequest;
use App\Foundation\Component\Table\Http\Response\TableExportResponse;
use App\Foundation\Component\Table\Http\Response\TablePageResponse;
use App\Foundation\Component\Table\Repository\TableRepository;
use App\Foundation\Component\Table\Service\ExportService;
use App\Foundation\Component\Table\Service\TableService;
use App\Foundation\Component\Table\ValueObject\Table;
use App\Foundation\Framework\Http\Controller\Controller;
use Illuminate\Validation\ValidationException;

class TableController extends Controller
{
    public function __construct(
        private readonly TableRepository $rows,
        private readonly ExportService $exports,
        private readonly TableService $tables,
        private readonly TableBuilder $builder,
    ) {}

    /**
     * @throws ValidationException if a filter was handed a value it cannot read, or the set cannot
     *                             be delivered in the format that was asked for
     */
    public function __invoke(TableRequest $request): TablePageResponse|TableExportResponse
    {
        $table = $this->definition($request);
        $state = $request->toState($table->name);

        // Asked before either answer: a narrowing nobody can be held to is no safer in a file.
        $this->refuse($this->tables->validate($table, $state));

        if (! $state->isExport()) {
            return TablePageResponse::of($this->rows->page($table, $state));
        }

        $set = $this->rows->all($table, $state);

        // Asked before writing, so a set the format cannot carry costs nothing to turn away.
        $this->refuse($this->exports->validate($set, $state->export));

        return TableExportResponse::of(
            $this->exports->write($set, $state->export),
            $state->export,
            $table->name,
        );
    }

    /**
     * A route default is the server's own decision rather than anything that arrived with the
     * request, so it is taken as given rather than validated.
     *
     * @throws TableException if the route named no definition
     */
    private function definition(TableRequest $request): Table
    {
        $named = $request->route()?->defaults['table'] ?? null;

        if ($named === null) {
            throw TableException::notDefinedOnRoute($request->path());
        }

        return app($named)->table($this->builder);
    }
}
