<?php
require_once 'includes/connection.php';
require_once 'includes/authMiddleware.php';

header('Content-Type: application/json');


try{

$req = $_SERVER['REQUEST_METHOD'] === "DELETE";

if(!$req){
    throw new Exception("Bad Request, route wasn't found!", 404);
}


$userData = authenticateUser();
$loggedInUserRole = $userData['role'];

if($loggedInUserRole !== 'Admin'){
    throw new Exception("Unauthorized access!", 401);
}

$gameId = trim($_GET['params']);


if(empty($gameId)){
    throw new Exception("Game ID is required", 400);
}

if(!is_numeric($gameId)){
    throw new Exception("Game ID must be a number", 400);
}

$fetchGame = "SELECT * FROM games WHERE id = ?";
$gamestmt = $conn->prepare($fetchGame);

if(!$gamestmt){
    throw new Exception("Database Preparation Failure" . $conn->error, 500);
}

$gamestmt->bind_param("i", $gameId);
$gamestmt->execute();
$gameResult = $gamestmt->get_result();

if(!$gameResult){
    throw new Exception("Database Fetch Error" . $gamestmt->error, 500);
}

if($gameResult->num_rows === 0){
    throw new Exception("Game not found." . $gamestmt->error, 404);
}

$gamestmt->close();

$deletePairsQuery = "DELETE FROM pairs WHERE gameId = ?";
$stmt = $conn->prepare($deletePairsQuery);

if(!$stmt){
    throw new Exception("Database Preparation Failure" . $conn->error, 500);
}

$stmt->bind_param("i", $gameId);
$stmt->execute();


$deleteGamesQuery = "DELETE FROM games WHERE id = ?";
$stmt = $conn->prepare($deleteGamesQuery);

if(!$stmt){
    throw new Exception("Database Preparation Failure" . $conn->error, 500);
}

$stmt->bind_param("i", $gameId);
$result = $stmt->execute();

if(!$result){
    throw new Exception("Error deleting game" . $conn->error, 500);
}

http_response_code(200);
echo json_encode([
    "status" => "Success!",
    "message" => "Game and related pairs deleted successfully!",
]);

}catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        "status" => "Failed",
        "message" => $e->getMessage()
    ]);
}

?>