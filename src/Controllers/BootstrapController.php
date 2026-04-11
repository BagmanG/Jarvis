<?php
namespace MiniApp\Controllers;

use MiniApp\Repositories\SettingsRepository;
use MiniApp\Repositories\TaskRepository;
use MiniApp\Support\ApiResponse;

class BootstrapController extends BaseController
{
    public function get(): void
    {
        $user = $this->requireUser();
        $settings = new SettingsRepository();
        $tasks = new TaskRepository();
        $selectedDate = (string) $this->request->query('selected_date', date('Y-m-d'));

        $year = (int) date('Y', strtotime($selectedDate));
        $month = (int) date('n', strtotime($selectedDate));

        ApiResponse::success([
            'profile' => $this->users->serialize($user),
            'settings' => $settings->getByUserId((int) $user['id']),
            'selected_date' => $selectedDate,
            'month' => [
                'year' => $year,
                'month' => $month,
                'summary' => $tasks->getMonthSummary((int) $user['id'], $year, $month),
            ],
            'tasks' => $tasks->getByDate((int) $user['id'], $selectedDate),
        ]);
    }
}
