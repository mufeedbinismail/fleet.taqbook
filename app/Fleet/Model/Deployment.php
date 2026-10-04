<?php

namespace App\Fleet\Model;

use App\Fleet\Enum\DeploymentStatus;
use App\Fleet\Enum\Hosting;
use App\Trade\Sale\Model\Customer;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class Deployment extends Model
{
    use HasApiTokens, HasUuids, SoftDeletes;

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'debtor_no',
        'number',
        'alias',
        'status',
        'hosting',
        'url',
        'instance_created_date',
        'last_pushed_at',
        'identity_ver',
        'credential_ver',
        'identity_issued_at',
    ];

    protected $casts = [
        'number' => 'integer',
        'status' => DeploymentStatus::class,
        'hosting' => Hosting::class,
        'instance_created_date' => 'date',
        'last_pushed_at' => 'datetime',
        'identity_ver' => 'integer',
        'credential_ver' => 'integer',
        'identity_issued_at' => 'datetime',
    ];

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'debtor_no', 'debtor_no');
    }

    public function statusChanges(): HasMany
    {
        return $this->hasMany(DeploymentStatusChange::class, 'deployment_uuid', 'uuid');
    }

    public function supportEntries(): HasMany
    {
        return $this->hasMany(SupportEntry::class, 'deployment_uuid', 'uuid');
    }
}
