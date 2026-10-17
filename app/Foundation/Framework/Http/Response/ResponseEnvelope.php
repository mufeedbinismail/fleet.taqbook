<?php

namespace App\Foundation\Framework\Http\Response;

use App\Foundation\Framework\Enum\ResponseStatus;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use stdClass;

/**
 * The shape every JSON answer takes, so a client written against one endpoint reads them all.
 */
final class ResponseEnvelope implements Responsable
{
    /**
     * Encodes the data as a JSON list, an empty one included, where it would otherwise be a JSON object.
     */
    public const AS_LIST = 1;

    /**
     * @param  array<string, mixed>  $payload
     */
    private function __construct(
        private readonly ResponseStatus $status,
        private readonly string $message,
        private readonly array $payload,
        private readonly int $code,
        private readonly int $flags = 0,
    ) {}

    /**
     * @param  array<mixed>  $data
     * @param  int-mask-of<self::AS_LIST>  $flags
     */
    public static function ok(string $message = '', array $data = [], int $code = 200, int $flags = 0): self
    {
        return new self(ResponseStatus::Ok, $message, $data, $code, $flags);
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
        // An empty PHP array encodes as `[]` whichever it meant, so it is an object unless a list was asked for.
        $payload = match (true) {
            ($this->flags & self::AS_LIST) !== 0 => array_values($this->payload),
            $this->payload === [] => new stdClass,
            default => $this->payload,
        };

        return response()->json([
            'status' => $this->status->value,
            'message' => $this->message,
            ...$this->status === ResponseStatus::Ok ? ['data' => $payload] : ['errors' => $payload],
        ], $this->code);
    }
}
