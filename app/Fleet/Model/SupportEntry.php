<?php

namespace App\Fleet\Model;

use App\Fleet\Enum\SupportEntryDelivery;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportEntry extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'employee_uuid',
        'employee_name',
        'target_login',
        'jti',
        'delivery',
        'issued_at',
        'expires_at',
    ];

    protected $casts = [
        'delivery' => SupportEntryDelivery::class,
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
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
