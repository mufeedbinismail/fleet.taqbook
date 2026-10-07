<?php

use App\Foundation\Auth\Action\SeedSystemUserAction;
use App\Foundation\Auth\Enum\SystemUser;
use App\Foundation\Auth\Repository\RoleRepository;
use App\Foundation\Auth\Repository\UserRepository;
use App\Foundation\Shared\Exception\ResourceNotFoundException;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(SeedSystemUserAction::class)->execute(SystemUser::Support);
    }

    public function down(): void
    {
        $systemUser = app(UserRepository::class)->find(SystemUser::Support->value)
            ?? throw ResourceNotFoundException::for('User', SystemUser::Support->value);

        app(UserRepository::class)->delete($systemUser);
        app(RoleRepository::class)->delete($systemUser->role_uuid);
    }
};
