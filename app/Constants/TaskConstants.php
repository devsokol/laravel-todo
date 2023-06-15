<?php

namespace App\Constants;

class TaskConstants
{
    public const TASK_VALIDATION_RULES = [
        'status' => 'required|string|in:todo,done',
        'priority' => 'required|integer|min:1|max:5',
        'title' => 'required|string',
        'description' => 'required|string',
    ];
}
