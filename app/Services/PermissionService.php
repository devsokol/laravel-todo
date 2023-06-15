<?php

namespace App\Services;

use App\Models\User;
use App\Models\Task;

class PermissionService
{
    /**
     * @var User|null The user instance. Set to null by default.
     */
    private ?User $user = null;

    /**
     * Set the user property of the current object to the provided user.
     *
     * @param mixed $user The user to be set.
     */
    public function setUser($user)
    {
        return $this->user = $user;
    }

    /**
     * Check if the provided task belongs to the currently set user.
     *
     * @param Task $task The task to check ownership for.
     * @return bool Returns true if the task belongs to the current user, false otherwise.
     */
    public function check($task)
    {
        return $task->user_id === $this->user->id;
    }
}
