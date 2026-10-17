<?php

namespace App\Inventory\Http\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A dimension as a record points at it; null where the record points at none, or at one that no longer exists.
 */
class DimensionResource extends JsonResource
{
    public static function from(?int $id, ?string $reference, ?string $name): self
    {
        return new self($reference === null ? null : (object) ['id' => $id, 'reference' => $reference, 'name' => $name]);
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->resource->id,
            'name' => $this->resource->name,
            'name_formatted' => $this->resource->reference.' - '.$this->resource->name,
        ];
    }
}
