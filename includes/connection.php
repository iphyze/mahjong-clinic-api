<?php

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable('./');
$dotenv->load();


// $host = $_ENV['DB_HOST'];
// $username = $_ENV['DB_USER'];
// $password = $_ENV['DB_PASS'];
// $database = $_ENV['DB_NAME'];


$host = 'localhost';
$username = 'root';
$password = '';
$database = 'mahjon_db';



// Create connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die(json_encode(["message" => "Connection failed: " . mysqli_connect_error()]));
}
?>