<?php
include(__DIR__ . '/session.php');
app_session_start();

header('Content-Type: application/json');

include(__DIR__ . '/../config/database.php');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Login required.']);
    exit();
}

$sessionUserId = (int)$_SESSION['user_id'];
$sessionUserName = $_SESSION['name'] ?? 'Team Member';
$sessionUserRole = $_SESSION['role'] ?? 'user';
session_write_close();

mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS team_chat_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        user_name VARCHAR(150) NOT NULL,
        user_role VARCHAR(50) NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

mysqli_query($conn, "
    DELETE FROM team_chat_messages
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
");

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    $result = mysqli_query($conn, "
        SELECT
            id,
            user_id,
            user_name,
            user_role,
            message,
            created_at,
            CASE
                WHEN user_id = '$sessionUserId'
                AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                THEN 1
                ELSE 0
            END AS can_delete
        FROM team_chat_messages
        ORDER BY id DESC
        LIMIT 50
    ");

    $messages = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $messages[] = $row;
    }

    $messages = array_reverse($messages);

    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'current_user_id' => $sessionUserId
    ]);
    exit();
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = json_decode(file_get_contents('php://input'), true);
    $messageId = (int)($raw['id'] ?? 0);
    $userId = (int)$_SESSION['user_id'];

    if ($messageId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Invalid message.']);
        exit();
    }

    mysqli_query($conn, "
        DELETE FROM team_chat_messages
        WHERE id = '$messageId'
        AND user_id = '$userId'
        AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    ");

    echo json_encode([
        'success' => mysqli_affected_rows($conn) > 0,
        'message' => mysqli_affected_rows($conn) > 0 ? 'Message deleted.' : 'Delete time expired.'
    ]);
    exit();
}

if ($action === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = json_decode(file_get_contents('php://input'), true);
    $message = trim((string)($raw['message'] ?? ''));

    if ($message === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Message is required.']);
        exit();
    }

    if (strlen($message) > 1000) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Message is too long.']);
        exit();
    }

    $userId = $sessionUserId;
    $userName = mysqli_real_escape_string($conn, $sessionUserName);
    $userRole = mysqli_real_escape_string($conn, $sessionUserRole);
    $safeMessage = mysqli_real_escape_string($conn, $message);

    mysqli_query($conn, "
        INSERT INTO team_chat_messages (user_id, user_name, user_role, message)
        VALUES ('$userId', '$userName', '$userRole', '$safeMessage')
    ");

    echo json_encode(['success' => true]);
    exit();
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid action.']);
