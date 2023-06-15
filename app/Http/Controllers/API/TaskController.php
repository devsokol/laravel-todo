<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskService;
use App\Services\PermissionService;
use App\Constants\TaskConstants;

class TaskController extends Controller
{
    /**
     * @var User|null The user instance. Set to null by default.
     */
    private ?User $user = null;

    /**
     * @var PermissionService The permission service instance.
     */
    private PermissionService $permissionService;

    /**
     * Create a new instance.
     *
     * @param Request $request The request instance.
     * @param PermissionService $permissionService The permission service instance.
     */
    public function __construct(Request $request, PermissionService $permissionService)
    {
        $this->user = User::findOrFail(
            $request->route('userId')
        );

        $this->permissionService = $permissionService;
        $this->permissionService->setUser($this->user);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request The request instance.
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $status = $request->input('status') ?? null;
        $priority = $request->input('priority') ?? null;
        $title = $request->input('title') ?? null;
        $sortField = $request->input('sort_field') ?? null;
        $sortType = $request->input('sort_type') ?? 'asc';

        $query = Task::query()->where('user_id', $this->user->id);

        if ($status) {
            $query->where('status', $status);
        }

        if ($priority) {
            $priorityRange = explode(',', $priority);
            $query->whereBetween('priority', $priorityRange);
        }

        if ($title) {
            $query->where('title', 'like', "%$title%");
        }

        if ($sortField) {
            $sortType = strtolower($sortType) === 'desc' ? 'desc' : 'asc';

            if ($sortField === 'created_at') {
                $query->orderBy('created_at', $sortType);
            } elseif ($sortField === 'priority') {
                $query->orderBy('priority', $sortType);
            }
        }

        $tasks = $query->get();

        return response()->json($tasks);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request The request instance.
     * @param TaskService $service The task service instance.
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, TaskService $service) {
        $dataTasks = $service->saveTasks(
            $request,
            $this->user
        );

        if (!$dataTasks['success']) {
            return response()->json([], 201);
        }

        return response()->json($dataTasks, 201);
    }

    /**
     * Delete a specific resource.
     *
     * @param int $userId The user ID.
     * @param int $taskId The task ID.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($userId, $taskId)
    {
        $task = Task::findOrFail($taskId);

        if ($task->status === 'done') {
            return response()->json([
                'message' => 'Task is done! We can`t delete it!'
            ], 201);
        }

        if (!$this->permissionService->check($task)) {
            return $this->permissionDeniedResponse();
        }

        $task->delete();

        return response()->json([
            'message' => 'deleted'
        ], 201);
    }

    /**
     * Update a specific resource.
     *
     * @param Request $request The request instance.
     * @param int $userId The user ID.
     * @param int $taskId The task ID.
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Contracts\Validation\Validator|array
     */
    public function update(Request $request, $userId, $taskId)
    {
        $task = Task::findOrFail($taskId);

        if (!$this->permissionService->check($task)) {
            return $this->permissionDeniedResponse();
        }

        $validator = Validator::make($request->all(), TaskConstants::TASK_VALIDATION_RULES);

        if ($validator->fails()) {
            return $validator->errors();
        }

        $task->update(
            $request->only(['status', 'priority', 'title', 'description'])
        );

        return response()->json($task, 201);
    }

    /**
     * Mark a task as done.
     *
     * @param Request $request The request instance.
     * @param int $userId The user ID.
     * @param int $taskId The task ID.
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Contracts\Validation\Validator|array
     */
    public function markAsDone(Request $request, $userId, $taskId)
    {
        $task = Task::findOrFail($taskId);

        if (!$this->permissionService->check($task)) {
            return $this->permissionDeniedResponse();
        }

        $validator = Validator::make($request->all(), [
            'is_done' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $validator->errors();
        }

        if ($request->get('is_done')) {
            $task->status = 'done';
            $task->save();

            return response()->json([
                'message' => 'Task marked as done.'
            ]);
        }

        return response()->json([], 201);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function permissionDeniedResponse()
    {
        return response()->json([
            'message' => 'Permission denied'
        ], 400);
    }
}
