<?php

namespace App\Foundation\Auth\Repository;

use App\Foundation\Auth\Constant\DisplayPreference;
use App\Foundation\Auth\Intent\CreateUserIntent;
use App\Foundation\Auth\Intent\UpdateUserIntent;
use App\Foundation\Auth\Model\User;
use App\Foundation\Framework\Enum\Skin;
use App\Foundation\Setting\Registry\UserSettingRegistry;
use App\Foundation\Shared\Exception\ResourceNotFoundException;

class UserRepository
{
    public function loginTaken(string $login): bool
    {
        return User::where('user_id', $login)->exists();
    }

    public function find(string $uuid): ?User
    {
        return User::find($uuid);
    }

    /**
     * Opens on the application's display defaults, leaving the account holder's preferences to them.
     */
    public function create(CreateUserIntent $intent): User
    {
        $record = $this->started($intent->login);

        if ($intent->uuid !== null) {
            $record->uuid = $intent->uuid;
        }

        $record->password = $intent->password;
        $record->reserved = (int) $intent->reserved;
        $record->inactive = 0;
        $this->fill($record, $intent);
        $record->save();

        return $record;
    }

    /**
     * Overwrites a user's details, leaving their display preferences alone.
     *
     * @throws ResourceNotFoundException if $intent->uuid names no user
     */
    public function update(UpdateUserIntent $intent): User
    {
        $record = User::find($intent->uuid) ?? throw ResourceNotFoundException::for('User', $intent->uuid);

        if ($intent->password !== null) {
            $record->password = $intent->password;
        }

        $this->fill($record, $intent);
        $record->save();

        return $record;
    }

    public function delete(User $user): void
    {
        $user->delete();
    }

    public function setStatus(User $user, bool $inactive): void
    {
        User::whereKey($user->getKey())->update(['inactive' => (int) $inactive]);
    }

    /**
     * Through the model rather than a bulk update, so the password reaches the column hashed.
     */
    public function setPassword(User $record, string $password): void
    {
        $record->password = $password;
        $record->save();
    }

    /**
     * A new account opens on the application's own defaults; a preference the defaults say nothing
     * about is left to the column.
     */
    private function started(string $login): User
    {
        $record = new User;
        $record->user_id = $login;

        $defaults = UserSettingRegistry::defaults();

        foreach (DisplayPreference::all() as $column) {
            if (($defaults[$column] ?? null) !== null) {
                $record->setAttribute($column, $defaults[$column]);
            }
        }

        return $record;
    }

    private function fill(User $record, CreateUserIntent|UpdateUserIntent $intent): void
    {
        $record->real_name = $intent->realName;
        $record->phone = $intent->phone;
        $record->email = $intent->email;
        $record->role_uuid = $intent->roleUuid;
        $record->pos = $intent->pos;
    }

    public function saveSkin(User $user, Skin $skin): void
    {
        User::query()->whereKey($user->getKey())->update(['skin' => $skin->value]);
    }
}
