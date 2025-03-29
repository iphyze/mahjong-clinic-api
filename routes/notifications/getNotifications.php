<?php
require_once 'includes/connection.php';
require_once 'includes/authMiddleware.php';

header("Content-Type: application/json");



// Ensure the request method is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(400);
    echo json_encode(["message" => "Bad Request"]);
    exit;
}


// Ensure params exist
if (!isset($_GET['params'])) {
    http_response_code(400);
    echo json_encode(["message" => "Missing user ID in request"]);
    exit;
}

// Ensure 'params' is a valid numeric ID
$userId = $_GET['params'];
if (!is_numeric($userId)) {
    http_response_code(400);
    echo json_encode(["message" => "Valid user ID is required"]);
    exit;
}

$userId = intval($userId); // Convert to integer

// Authenticate user
$userData = authenticateUser();
$loggedInUserId = intval($userData['id']);
$loggedInUserRole = $userData['role'];

// Prevent unauthorized access
if ($loggedInUserRole !== "Admin" && $userId !== $loggedInUserId) {
    http_response_code(403);
    echo json_encode(["message" => "Access denied. You can only view your own details."]);
    exit;
}

// Check if the user exists and get registration date
$userCheckQuery = "SELECT id, createdAt as registrationDate FROM users WHERE id = ?";
$stmt = $conn->prepare($userCheckQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$userResult = $stmt->get_result();

if ($userResult->num_rows === 0) {
    http_response_code(404);
    echo json_encode(["status" => "Failed", "message" => "User not found"]);
    exit;
}

$userData = $userResult->fetch_assoc();
$registrationDate = $userData['registrationDate'];

$query = "
    SELECT 
        n.id AS notificationId, 
        n.title, 
        n.message, 
        n.createdAt, 
        n.userId AS notificationUserId,
        COALESCE(un.isRead, 0) AS isRead
    FROM notifications n
    LEFT JOIN user_notifications un ON n.id = un.notificationId AND un.userId = ?
    WHERE 
        (n.userId = ? OR 
        (n.userId = 'All' AND n.createdAt >= ?))
    ORDER BY n.createdAt DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("iss", $userId, $userId, $registrationDate);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}


if (empty($notifications)) {
    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "No notifications available yet.",
        "data" => []
    ]);
} else {
    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Notifications retrieved successfully.",
        "data" => $notifications
    ]);
}



$stmt->close();
$conn->close();
?>
