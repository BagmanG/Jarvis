<?php

class StatsImageGenerator {
    
    /**
     * Генерирует изображение статистики задач
     */
    public static function generateStatsImage($stats, $outputPath): bool {
        try {
            // Проверяем наличие GD библиотеки
            if (!function_exists('imagecreatetruecolor')) {
                return false;
            }
            
            // Параметры изображения
            $width = 1200;
            $height = 1600;
            $padding = 40;
            
            // Создаём изображение
            $image = imagecreatetruecolor($width, $height);
            
            // Цвета
            $bgColor = imagecolorallocate($image, 30, 30, 47); // Тёмный фон
            $textColor = imagecolorallocate($image, 255, 255, 255); // Белый текст
            $accentColor = imagecolorallocate($image, 79, 70, 229); // Акцентный цвет
            $successColor = imagecolorallocate($image, 16, 185, 129); // Зелёный
            $warningColor = imagecolorallocate($image, 245, 158, 11); // Жёлтый
            $errorColor = imagecolorallocate($image, 239, 68, 68); // Красный
            
            // Заливаем фон
            imagefill($image, 0, 0, $bgColor);
            
            // Используем встроенные шрифты (они не поддерживают кириллицу, но для цифр и латиницы подойдут)
            $fontSize = 5;
            $fontSizeLarge = 6;
            $y = $padding;
            
            // Заголовок (используем латиницу для совместимости)
            $title = "STATISTICS FOR MONTH";
            imagestring($image, $fontSizeLarge, ($width - strlen($title) * 10) / 2, $y, $title, $textColor);
            $y += 60;
            
            // Линия-разделитель
            imageline($image, $padding, $y, $width - $padding, $y, $accentColor);
            $y += 40;
            
            // Общая статистика
            $totalTasks = $stats['total_tasks'] ?? 0;
            $completedTasks = $stats['completed_tasks'] ?? 0;
            $pendingTasks = $stats['pending_tasks'] ?? 0;
            $overdueTasks = $stats['overdue_tasks'] ?? 0;
            $completionRate = $stats['completion_rate'] ?? 0;
            
            // Всего задач
            $text = "Total tasks: " . $totalTasks;
            imagestring($image, $fontSize, $padding, $y, $text, $textColor);
            $y += 50;
            
            // Выполнено
            $text = "Completed: " . $completedTasks;
            imagestring($image, $fontSize, $padding, $y, $text, $successColor);
            $y += 50;
            
            // В работе
            $text = "In progress: " . $pendingTasks;
            imagestring($image, $fontSize, $padding, $y, $text, $warningColor);
            $y += 50;
            
            // Просрочено
            $text = "Overdue: " . $overdueTasks;
            imagestring($image, $fontSize, $padding, $y, $text, $errorColor);
            $y += 50;
            
            // Процент выполнения
            $text = "Completion rate: " . $completionRate . "%";
            imagestring($image, $fontSize, $padding, $y, $text, $accentColor);
            $y += 60;
            
            // Линия-разделитель
            imageline($image, $padding, $y, $width - $padding, $y, $accentColor);
            $y += 40;
            
            // Статистика по приоритетам
            $highPriority = $stats['high_priority'] ?? 0;
            $mediumPriority = $stats['medium_priority'] ?? 0;
            $lowPriority = $stats['low_priority'] ?? 0;
            
            imagestring($image, $fontSize, $padding, $y, "By priority:", $textColor);
            $y += 50;
            
            $text = "High: " . $highPriority;
            imagestring($image, $fontSize, $padding + 20, $y, $text, $errorColor);
            $y += 50;
            
            $text = "Medium: " . $mediumPriority;
            imagestring($image, $fontSize, $padding + 20, $y, $text, $warningColor);
            $y += 50;
            
            $text = "Low: " . $lowPriority;
            imagestring($image, $fontSize, $padding + 20, $y, $text, $successColor);
            $y += 60;
            
            // Линия-разделитель
            imageline($image, $padding, $y, $width - $padding, $y, $accentColor);
            $y += 40;
            
            // Примеры выполненных задач
            if (!empty($stats['completed_examples'])) {
                imagestring($image, $fontSize, $padding, $y, "Completed examples:", $successColor);
                $y += 50;
                
                $count = 0;
                foreach ($stats['completed_examples'] as $task) {
                    if ($count >= 3) break;
                    // Используем только первые символы, которые могут быть в ASCII
                    $taskTitle = substr($task['title'], 0, 40);
                    if (strlen($task['title']) > 40) {
                        $taskTitle .= "...";
                    }
                    imagestring($image, 3, $padding + 20, $y, "+ " . $taskTitle, $successColor);
                    $y += 40;
                    $count++;
                }
                $y += 20;
            }
            
            // Примеры задач в работе
            if (!empty($stats['pending_examples'])) {
                imagestring($image, $fontSize, $padding, $y, "In progress examples:", $warningColor);
                $y += 50;
                
                $count = 0;
                foreach ($stats['pending_examples'] as $task) {
                    if ($count >= 3) break;
                    // Используем только первые символы, которые могут быть в ASCII
                    $taskTitle = substr($task['title'], 0, 40);
                    if (strlen($task['title']) > 40) {
                        $taskTitle .= "...";
                    }
                    imagestring($image, 3, $padding + 20, $y, "o " . $taskTitle, $warningColor);
                    $y += 40;
                    $count++;
                }
            }
            
            // Сохраняем изображение
            $result = imagejpeg($image, $outputPath, 90);
            imagedestroy($image);
            
            return $result !== false;
            
        } catch (Exception $e) {
            return false;
        }
    }
}

