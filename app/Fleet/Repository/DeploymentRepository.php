<?php

namespace App\Fleet\Repository;

use App\Fleet\Enum\DeploymentStatus;
use App\Fleet\Intent\ChangeDeploymentStatusIntent;
use App\Fleet\Intent\EditDeploymentIntent;
use App\Fleet\Intent\RegisterDeploymentIntent;
use App\Fleet\Model\Deployment;
use App\Foundation\Shared\Enum\SystemType;
use App\Foundation\Shared\Repository\SequenceRepository;
use App\Foundation\Shared\ValueObject\DomainDateTime;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DeploymentRepository
{
    public function __construct(private readonly SequenceRepository $sequenceRepository) {}

    /**
     * The number is taken inside the same transaction that writes the row it numbers, so a
     * registration that fails hands it back instead of leaving a hole in the register.
     */
    public function save(RegisterDeploymentIntent $intent): Deployment
    {
        return DB::transaction(function () use ($intent) {
            $deployment = Deployment::create([
                'debtor_no' => $intent->debtorNo,
                'number' => $this->sequenceRepository->allocateNumber(SystemType::Deployment),
                'alias' => $intent->alias,
                'status' => $intent->status,
                'hosting' => $intent->hosting,
                'url' => $intent->url,
                'instance_created_date' => $intent->instanceCreatedDate,
            ]);

            $this->recordStatusChange($deployment, $intent->status, DomainDateTime::now());

            return $deployment;
        });
    }

    public function edit(Deployment $deployment, EditDeploymentIntent $intent): Deployment
    {
        $deployment->fill([
            'debtor_no' => $intent->debtorNo,
            'hosting' => $intent->hosting,
            'url' => $intent->url,
            'instance_created_date' => $intent->instanceCreatedDate,
        ])->save();

        return $deployment;
    }

    /**
     * The status the register answers with and the entry saying when it got there are one write.
     */
    public function changeStatus(Deployment $deployment, ChangeDeploymentStatusIntent $intent): Deployment
    {
        return DB::transaction(function () use ($deployment, $intent) {
            $deployment->fill(['status' => $intent->status])->save();

            $this->recordStatusChange($deployment, $intent->status, $intent->changedAt);

            return $deployment;
        });
    }

    /**
     * Trashed rows count: a name is held by whatever was ever called it, which is what the schema's
     * own unique index says too — and what the model's global scope would otherwise hide.
     */
    public function aliasTaken(string $alias, Deployment $except): bool
    {
        return Deployment::withTrashed()
            ->where('alias', $alias)
            ->whereKeyNot($except->getKey())
            ->exists();
    }

    public function findByKey(string $key): ?Deployment
    {
        return Deployment::query()->find($key)
            ?? (ctype_digit($key) ? Deployment::query()->where('number', (int) $key)->first() : null)
            ?? Deployment::query()->where('alias', $key)->first();
    }

    /**
     * @return Collection<int, Deployment>
     */
    public function pingableDeployments(): Collection
    {
        return Deployment::query()
            ->whereNotNull('url')
            ->where('url', '!=', '')
            ->where('status', '!=', DeploymentStatus::Retired)
            ->get();
    }

    /**
     * Reaching a deployment is not editing it, so the row's own updated_at stays where it was.
     */
    public function recordPush(Deployment $deployment, DomainDateTime $at): Deployment
    {
        Deployment::withoutTimestamps(fn () => $deployment->fill(['last_pushed_at' => $at])->save());

        return $deployment;
    }

    /**
     * Issues only from the versions the caller read: one issued or retired since then is left as it
     * is, and null comes back.
     */
    public function recordIdentityIssue(Deployment $deployment, DomainDateTime $at): ?Deployment
    {
        return DB::transaction(function () use ($deployment, $at) {
            $updated = Deployment::query()
                ->whereKey($deployment->uuid)
                ->where('identity_ver', $deployment->identity_ver)
                ->where('credential_ver', $deployment->credential_ver)
                ->where('status', '!=', DeploymentStatus::Retired)
                ->update([
                    'identity_ver' => $deployment->identity_ver + 1,
                    'credential_ver' => $deployment->credential_ver + 1,
                    'identity_issued_at' => $at,
                ]);

            return $updated === 0 ? null : Deployment::query()->find($deployment->uuid);
        });
    }

    /**
     * One token per deployment: the previous one stops authenticating the moment this returns.
     */
    public function replaceToken(Deployment $deployment): string
    {
        return DB::transaction(function () use ($deployment) {
            $deployment->tokens()->delete();

            return $deployment->createToken('tenant')->plainTextToken;
        });
    }

    public function rename(Deployment $deployment, string $alias): Deployment
    {
        $deployment->fill(['alias' => $alias])->save();

        return $deployment;
    }

    public function remove(Deployment $deployment): void
    {
        $deployment->delete();
    }

    /**
     * The row itself, not just its place on the register: its name is free to be used again the
     * moment this returns, which is the whole of the difference and why it is asked for twice.
     */
    public function erase(Deployment $deployment): void
    {
        $deployment->forceDelete();
    }

    private function recordStatusChange(Deployment $deployment, DeploymentStatus $status, DomainDateTime $moment): void
    {
        $deployment->statusChanges()->create(['status' => $status, 'changed_at' => $moment]);
    }
}
