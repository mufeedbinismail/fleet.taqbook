<?php

namespace App\Fleet\Repository;

use App\Fleet\Model\Deployment;
use App\Fleet\Model\SupportEntry as SupportEntryRecord;
use App\Fleet\ValueObject\SupportLink;

class SupportEntryRepository
{
    /**
     * Written from the signed link alone, so the row says exactly what was handed out.
     */
    public function recordEntry(Deployment $deployment, SupportLink $link): SupportEntryRecord
    {
        return $deployment->supportEntries()->create([
            'employee_uuid' => $link->entry()->employeeUuid,
            'employee_name' => $link->entry()->employeeName,
            'target_login' => $link->entry()->targetLogin,
            'jti' => $link->message()->id,
            'delivery' => $link->delivery,
            'issued_at' => $link->message()->issuedAt,
            'expires_at' => $link->expiresAt(),
        ]);
    }

    public function hasEntriesFor(Deployment $deployment): bool
    {
        return $deployment->supportEntries()->exists();
    }
}
