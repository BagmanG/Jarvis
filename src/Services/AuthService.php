<?php
namespace MiniApp\Services;

use MiniApp\Repositories\UserRepository;
use MiniApp\Repositories\SettingsRepository;
use MiniApp\Repositories\TaskRepository;
use MiniApp\Support\Token;

class AuthService
{
    private UserRepository $users;
    private SettingsRepository $settings;
    private TaskRepository $tasks;

    public function __construct()
    {
        $this->users = new UserRepository();
        $this->settings = new SettingsRepository();
        $this->tasks = new TaskRepository();
    }

    public function bootstrapForUser(array $user, array $settings, string $selectedDate): array
    {
        $year = (int) date('Y', strtotime($selectedDate));
        $month = (int) date('n', strtotime($selectedDate));

        return [
            'token' => Token::issue((int) $user['id']),
            'profile' => $this->users->serialize($user),
            'settings' => $settings,
            'selected_date' => $selectedDate,
            'month' => [
                'year' => $year,
                'month' => $month,
                'summary' => $this->tasks->getMonthSummary((int) $user['id'], $year, $month),
            ],
            'tasks' => $this->tasks->getByDate((int) $user['id'], $selectedDate),
        ];
    }

    public function getBootstrap(int $userId, ?string $selectedDate = null): array
    {
        $selectedDate = $selectedDate ?: date('Y-m-d');
        $user = $this->users->findById($userId);
        $settings = $this->settings->getByUserId($userId);
        return $this->bootstrapForUser($user, $settings, $selectedDate);
    }
}
