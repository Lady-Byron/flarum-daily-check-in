<?php

namespace Ziven\checkin\Access;

use Carbon\Carbon;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

class UserPolicy extends AbstractPolicy
{
    // 可选：不是必须，因为我们在 extend.php 里已用 modelPolicy 绑了 User
    // protected $model = User::class;

    public function allowCheckIn(User $actor, User $user)
    {
        if (
            $actor->id === $user->id
            && $actor->hasPermission('checkin.allowCheckIn')
            && !$this->isSuspended($user)
        ) {
            return $this->allow();
        }

        return $this->deny();
    }

    protected function isSuspended(User $user): bool
    {
        return $user->suspended_until !== null
            && $user->suspended_until instanceof Carbon
            && $user->suspended_until->isFuture();
    }
}
