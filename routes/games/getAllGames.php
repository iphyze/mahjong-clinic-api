<?php
require_once 'includes/connection.php';
require_once 'includes/authMiddleware.php';

header('Content-Type: application/json');

try {
    // Ensure request method is GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception("Bad Request, route wasn't found!", 404);
    }


    $userData = authenticateUser();
    $loggedInUserRole = $userData['role'];


    if($loggedInUserRole !== 'Admin'){
        throw new Exception("Unauthorized access", 401);
    }

    
    $getGames = "SELECT * FROM games ORDER BY createdAt DESC";
    $stmt = $conn->prepare($getGames);

    if(!$stmt){
        throw new Exception("Database Praparation Error" . $conn->error, 500);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    if(!$result){
        throw new Exception("Database Fetch Error" . $stmt->error, 500);
    }

    $data = $result->fetch_all(MYSQLI_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        "status" => "Success",
        "message" => "Successfully fetched all games",
        "data" => $data
    ]);

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        "status" => "Failed",
        "message" => $e->getMessage()
    ]);
}
?>
