<?php
namespace MiniApp\Controllers;

use MiniApp\Repositories\TaskRepository;
use MiniApp\Support\ApiResponse;

class CalendarController extends BaseController
{
    public function month(): void
    {
        $user = $this->requireUser();
        $year = (int) $this->request->query('year', date('Y'));
        $month = (int) $this->request->query('month', date('n'));

        if ($year < 1970 || $year > 2100 || $month < 1 || $month > 12) {
            ApiResponse::error('validation_error', 'Некорректный месяц.', 422);
        }

        $summary = (new TaskRepository())->getMonthSummary((int) $user['id'], $year, $month);
        ApiResponse::success([
            'year' => $year,
            'month' => $month,
            'summary' => $summary,
        ]);
    }
}
