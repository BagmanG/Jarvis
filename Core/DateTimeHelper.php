<?php

class DateTimeHelper {
    
    /**
     * Получить текущую дату и время в различных форматах
     */
    public static function getCurrentDateTime(): array {
        $now = new DateTime('now', new DateTimeZone('Europe/Moscow')); // UTC+3
        
        return [
            'date' => $now->format('Y-m-d'),
            'time' => $now->format('H:i:s'),
            'datetime' => $now->format('Y-m-d H:i:s'),
            'timestamp' => $now->getTimestamp(),
            'year' => $now->format('Y'),
            'month' => $now->format('m'),
            'day' => $now->format('d'),
            'weekday' => $now->format('l'), // Monday, Tuesday, etc.
            'weekday_ru' => self::getWeekdayRussian($now->format('N')),
            'month_name' => $now->format('F'),
            'month_name_ru' => self::getMonthRussian($now->format('n')),
            'formatted_date' => $now->format('d.m.Y'),
            'formatted_datetime' => $now->format('d.m.Y H:i'),
            'iso' => $now->format('c')
        ];
    }
    
    /**
     * Получить информацию о дате в естественном формате для GPT
     */
    public static function getDateInfoForGPT(): string {
        $dateInfo = self::getCurrentDateTime();
        
        return "Текущая дата и время: {$dateInfo['formatted_datetime']} ({$dateInfo['weekday_ru']}, {$dateInfo['day']} {$dateInfo['month_name_ru']} {$dateInfo['year']} года). " .
               "Сегодня: {$dateInfo['date']}, завтра: " . self::getTomorrowDate() . ", вчера: " . self::getYesterdayDate() . ".";
    }
    
    /**
     * Получить завтрашнюю дату
     */
    public static function getTomorrowDate(): string {
        $tomorrow = new DateTime('tomorrow', new DateTimeZone('Europe/Moscow'));
        return $tomorrow->format('Y-m-d');
    }
    
    /**
     * Получить вчерашнюю дату
     */
    public static function getYesterdayDate(): string {
        $yesterday = new DateTime('yesterday', new DateTimeZone('Europe/Moscow'));
        return $yesterday->format('Y-m-d');
    }
    
    /**
     * Получить название дня недели на русском
     */
    private static function getWeekdayRussian(int $weekday): string {
        $weekdays = [
            1 => 'Понедельник',
            2 => 'Вторник', 
            3 => 'Среда',
            4 => 'Четверг',
            5 => 'Пятница',
            6 => 'Суббота',
            7 => 'Воскресенье'
        ];
        
        return $weekdays[$weekday] ?? 'Неизвестно';
    }
    
    /**
     * Получить название месяца на русском
     */
    private static function getMonthRussian(int $month): string {
        $months = [
            1 => 'января',
            2 => 'февраля',
            3 => 'марта',
            4 => 'апреля',
            5 => 'мая',
            6 => 'июня',
            7 => 'июля',
            8 => 'августа',
            9 => 'сентября',
            10 => 'октября',
            11 => 'ноября',
            12 => 'декабря'
        ];
        
        return $months[$month] ?? 'неизвестно';
    }
    
    /**
     * Проверить, является ли дата сегодняшней
     */
    public static function isToday(string $date): bool {
        $today = self::getCurrentDateTime()['date'];
        return $date === $today;
    }
    
    /**
     * Проверить, является ли дата завтрашней
     */
    public static function isTomorrow(string $date): bool {
        $tomorrow = self::getTomorrowDate();
        return $date === $tomorrow;
    }
    
    /**
     * Проверить, является ли дата вчерашней
     */
    public static function isYesterday(string $date): bool {
        $yesterday = self::getYesterdayDate();
        return $date === $yesterday;
    }
    
    /**
     * Получить относительное описание даты (сегодня, завтра, вчера, или конкретную дату)
     */
    public static function getRelativeDate(string $date): string {
        if (self::isToday($date)) {
            return 'сегодня';
        } elseif (self::isTomorrow($date)) {
            return 'завтра';
        } elseif (self::isYesterday($date)) {
            return 'вчера';
        } else {
            // Форматируем дату в читаемый вид
            $dateObj = new DateTime($date);
            return $dateObj->format('d.m.Y');
        }
    }
}
