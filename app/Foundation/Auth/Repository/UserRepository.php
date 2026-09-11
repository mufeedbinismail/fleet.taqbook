<?php

namespace App\Foundation\Auth\Repository;

use App\Foundation\Auth\Constant\DisplayPreference;
use App\Foundation\Auth\Intent\SaveUserIntent;
use App\Foundation\Auth\Model\User;
use App\Foundation\Shared\Enum\Skin;
use App\Foundation\Shared\Exception\ResourceNotFoundException;
use App\Foundation\Shared\Setting\UserSetting;

class UserRepository
{
    public function loginTaken(string $login): bool
    {
        return User::where('user_id', $login)->exists();
    }

    /**
     * Creates or overwrites a user's details, leaving their display preferences alone.
     *
     * @throws ResourceNotFoundException if $intent->userId names no user
     */
    public function save(SaveUserIntent $intent): User
    {
        $record = $intent->isEditing()
            ? User::find($intent->userId) ?? throw ResourceNotFoundException::for('User', $intent->userId)
            : $this->started((string) $intent->login);

        $record->real_name = $intent->realName;
        $record->phone = $intent->phone;
        $record->email = $intent->email;
        $record->role_id = $intent->roleId;
        $record->pos = $intent->pos;

        if ($intent->password !== null) {
            $record->password = $intent->password;
        }

        $record->save();

        return $record;
    }

    public function delete(int $id): void
    {
        User::destroy($id);
    }

    public function setStatus(int $id, bool $inactive): void
    {
        User::whereKey($id)->update(['inactive' => (int) $inactive]);
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

        $defaults = UserSetting::defaults();

        foreach (DisplayPreference::all() as $column) {
            if (($defaults[$column] ?? null) !== null) {
                $record->setAttribute($column, $defaults[$column]);
            }
        }

        return $record;
    }

    public function saveSkin(int $userId, Skin $skin): void
    {
        User::query()->whereKey($userId)->update(['skin' => $skin->value]);
    }
}
