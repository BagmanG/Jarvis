<?
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
if(isset($_GET['name'])){
    $taskName = $_GET['name'];
    $taskTime = $_GET['time'] ?? "12:00";
    $taskDate = $_GET['date'] ?? date('Y-m-d');
    $user_id = $_GET['user_id'] ?? 0;
    $createdDate = date('Y-m-d H:i:s');
    $sql = "INSERT INTO `Tasks` (`id`, `user_id`, `title`, `description`, `due_date`, `due_time`, `priority`, `reminder`, `status`, `created_at`, `updated_at`, `reminder_sent`) VALUES
(0, $user_id, '$taskName', '', '$$taskDate', '$$taskTime', 'medium', '5min', 'pending', '$createdDate', '$createdDate', 0);";
query($sql);
}


function query($sql) {
    $host = 'localhost';
    $user = DB_NAME;
    $pass = DB_PASSWORD;
    $db = DB_NAME;
    
    // Подключаемся к базе
    $mysqli = new mysqli($host, $user, $pass, $db);
    
    // Проверяем подключение
    if ($mysqli->connect_error) {
        die("Ошибка подключения: " . $mysqli->connect_error);
    }
    
    // Выполняем запрос
    $result = $mysqli->query($sql);
    
    // Если это SELECT - возвращаем данные
    if (is_object($result)) {
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    } 
    // Если это INSERT, UPDATE, DELETE - возвращаем результат
    else {
        return $result;
    }
    
    // Закрываем соединение
    $mysqli->close();
}
?>