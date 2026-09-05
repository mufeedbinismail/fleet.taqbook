<?php

namespace App\Foundation\Component\Table\Http\Response;

use App\Foundation\Component\Table\Enum\ExportFormat;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Responsable;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * A written export, handed over as a download and then deleted.
 *
 * Takes a file that is already complete rather than writing one, which is what lets the answer
 * carry a length.
 */
final class TableExportResponse implements Responsable
{
    private function __construct(
        private readonly string $path,
        private readonly ExportFormat $format,
        private readonly string $name,
    ) {}

    /**
     * @param  string  $path  a written file the response takes ownership of
     * @param  string  $name  what the download is called, without an extension or a date
     */
    public static function of(string $path, ExportFormat $format, string $name): self
    {
        return new self($path, $format, $name);
    }

    public function toResponse($request): BinaryFileResponse
    {
        $response = new BinaryFileResponse($this->path, 200, [
            'Content-Type' => $this->format->contentType(),
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);

        $response->setContentDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $this->name.'-'.Carbon::now()->format('Y-m-d').'.'.$this->format->extension(),
        );

        return $response->deleteFileAfterSend();
    }
}
