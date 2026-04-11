<?php
namespace MiniApp\Controllers;

use MiniApp\Repositories\TaskRepository;
use MiniApp\Support\ApiResponse;
use MiniApp\Support\Validator;

class TaskController extends BaseController
{
    private TaskRepository $tasks;

    public function __construct($request)
    {
        parent::__construct($request);
        $this->tasks = new TaskRepository();
    }

    public function list(): void
    {
        $user = $this->requireUser();
        $date = (string) $this->request->query('date', '');
        if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            ApiResponse::error('validation_error', 'Укажите дату в формате YYYY-MM-DD.', 422);
        }

        ApiResponse::success([
            'date' => $date,
            'items' => $this->tasks->getByDate((int) $user['id'], $date),
        ]);
    }

    public function range(): void
    {
        $user = $this->requireUser();
        $from = (string) $this->request->query('from', '');
        $to = (string) $this->request->query('to', '');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            ApiResponse::error('validation_error', 'Диапазон дат должен быть в формате YYYY-MM-DD.', 422);
        }

        ApiResponse::success([
            'items' => $this->tasks->getRange((int) $user['id'], $from, $to),
        ]);
    }

    public function show(int $taskId): void
    {
        $user = $this->requireUser();
        $task = $this->tasks->findById((int) $user['id'], $taskId);
        if (!$task) {
            ApiResponse::error('not_found', 'Задача не найдена.', 404);
        }

        ApiResponse::success($task);
    }

    public function create(): void
    {
        $user = $this->requireUser();
        list($errors, $data) = Validator::validateTask($this->request->input(), false);
        if ($errors) {
            ApiResponse::error('validation_error', 'Проверьте поля задачи.', 422, $errors);
        }

        $task = $this->tasks->create((int) $user['id'], $data);
        ApiResponse::success($task, [], 201);
    }

    public function update(int $taskId): void
    {
        $user = $this->requireUser();
        list($errors, $data) = Validator::validateTask($this->request->input(), true);
        if ($errors) {
            ApiResponse::error('validation_error', 'Проверьте поля задачи.', 422, $errors);
        }

        $task = $this->tasks->update((int) $user['id'], $taskId, $data);
        if (!$task) {
            ApiResponse::error('not_found', 'Задача не найдена.', 404);
        }

        ApiResponse::success($task);
    }

    public function updateStatus(int $taskId): void
    {
        $user = $this->requireUser();
        $status = (string) $this->request->input('status', '');
        if (!in_array($status, ['active', 'completed', 'archived'], true)) {
            ApiResponse::error('validation_error', 'Недопустимый статус.', 422);
        }

        $task = $this->tasks->updateStatus((int) $user['id'], $taskId, $status);
        if (!$task) {
            ApiResponse::error('not_found', 'Задача не найдена.', 404);
        }

        ApiResponse::success($task);
    }

    public function delete(int $taskId): void
    {
        $user = $this->requireUser();
        $deleted = $this->tasks->softDelete((int) $user['id'], $taskId);
        if (!$deleted) {
            ApiResponse::error('not_found', 'Задача не найдена.', 404);
        }

        ApiResponse::success(['deleted' => true]);
    }
}
