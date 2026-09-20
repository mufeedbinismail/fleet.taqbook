<?php

namespace App\Fleet\Model;

use App\Fleet\Enum\DeploymentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeploymentStatusChange extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'status',
        'changed_at',
    ];

    protected $casts = [
        'status' => DeploymentStatus::class,
        'changed_at' => 'datetime',
    ];

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class, 'deployment_uuid', 'uuid');
    }
}
