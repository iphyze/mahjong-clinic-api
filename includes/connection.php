<?php

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable('./');
$dotenv->load();

$host = $_ENV['DB_HOST'];
$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASS'];
$database = $_ENV['DB_NAME'];

// $host = 'localhost';
// $username = 'root';
// $password = '';
// $database = 'mahjon_db';

try {
    // Create connection
    $conn = mysqli_connect($host, $username, $password, $database);

    // Check connection
    if (!$conn) {
        throw new Exception("Connection failed: " . mysqli_connect_error());
    }
} catch (Exception $e) {

    error_log("Database Connection Error: " . $e->getMessage());
    http_response_code(500);
    // Ensure JSON response
    header('Content-Type: application/json');
    
    // Send generic error message (avoid exposing sensitive details)
    echo json_encode([
        "status" => "error",
        "message" => "Internal Server Error",
        "details" => "Unable to connect to the database"
    ]);
    
    // Stop further script execution
    exit;
}
?>