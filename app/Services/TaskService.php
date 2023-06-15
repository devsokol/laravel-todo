<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Models\Task;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Constants\TaskConstants;

class TaskService
{
    /**
     * @var User|null The user instance. Set to null by default.
     */
    public ?User $user = null;

    /**
     * Handle a request to create a new Task and its associated Children (if any) for the provided user.
     * Validates the incoming request data against predefined task validation rules.
     * If validation fails, it returns an array with success set to false and error messages.
     * If validation passes, it creates a new task, associates it with the user, and saves any associated children.
     * Finally, it returns an array with success set to true and the created task data.
     *
     * @param Request $request The incoming HTTP request containing task data.
     * @param User $user The user for which the task will be created.
     * @return array An associative array containing a 'success' key, and either 'data' or 'errors'.
     */
    public function saveTasks(Request $request, $user)
    {
        $this->user = $user;
        $validator = Validator::make($request->all(), TaskConstants::TASK_VALIDATION_RULES);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->errors()
            ];
        }

        $taskData = $request->only(['status', 'priority', 'title', 'description']);

        $childrenData = $request->get('children', []);

        $task = Task::create([
            ...$taskData,
            'user_id' => $this->user->id
        ]);

        $this->saveChildren($task, $childrenData);

        return [
            'success' => true,
            'data' => $task
        ];
    }

    /**
     * This function is used to save the children tasks associated with a parent task.
     * It loops through each child task data, validates it using predefined task validation rules.
     * If validation fails, it skips the current child and moves to the next.
     * If validation passes, it creates the child task, associates it with the user,
     * attaches it to the parent task with a given depth, and then calls itself recursively if the child task has its own children.
     *
     * @param Task $parentTask The parent task to which children tasks will be attached.
     * @param array $childrenData The data of the children tasks to be created.
     * @param int $depth The depth of the child task in the task hierarchy (default is 0).
     * @return void
     */
    public function saveChildren(Task $parentTask, array $childrenData, int $depth = 0)
    {
        foreach ($childrenData as $childData) {
            $validator = Validator::make($childData, TaskConstants::TASK_VALIDATION_RULES);

            if ($validator->fails()) {
                continue;
            }

            $childTaskData = Arr::only($childData, ['status', 'priority', 'title', 'description']);

            $childTask = Task::create([
                ...$childTaskData,
                'user_id' => $this->user->id
            ]);

            $parentTask->childTasks()->attach($childTask, ['depth' => $depth]);

            $this->saveChildren($childTask, $childData['children'] ?? [], $depth + 1);
        }
    }
}
