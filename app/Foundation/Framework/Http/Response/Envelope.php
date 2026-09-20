<?php

namespace App\Foundation\Framework\Http\Response;

use App\Foundation\Framework\Enum\ResponseStatus;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use stdClass;

/**
 * The shape every JSON answer takes, so a client written against one endpoint reads them all.
 */
final class Envelope implements Responsable
{
    /**
     * @param  array<string, mixed>  $payload
     */
    private function __construct(
        private readonly ResponseStatus $status,
        private readonly string $message,
        private readonly array $payload,
        private readonly int $code,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function ok(string $message = '', array $data = [], int $code = 200): self
    {
        return new self(ResponseStatus::Ok, $message, $data, $code);
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    public static function failed(string $message, array $errors = [], int $code = 422): self
    {
        return new self(ResponseStatus::Error, $message, $errors, $code);
    }

    public function toResponse($request): JsonResponse
    {
        // Empty, a PHP array encodes as `[]` — and a client reading `data.x` would meet a list.
        $payload = $this->payload === [] ? new stdClass : $this->payload;

        return response()->json([
            'status' => $this->status->value,
            'message' => $this->message,
            ...$this->status === ResponseStatus::Ok ? ['data' => $payload] : ['errors' => $payload],
        ], $this->code);
    }
}
