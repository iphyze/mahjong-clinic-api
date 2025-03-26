<?php

require 'vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Respect\Validation\Validator as v;
use Dotenv\Dotenv;
require_once 'includes/connection.php';

// Load environment variables
$dotenv = Dotenv::createImmutable('./');
$dotenv->load();

// Ensure the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(["message" => "Bad Request: Only POST method is allowed"]);
    exit;
}

// Get the JSON input
$data = json_decode(file_get_contents("php://input"));

if (!isset($data->email) || !isset($data->password)) {
    http_response_code(400);
    echo json_encode(["message" => "Email and password are required"]);
    exit;
}

$email = trim($data->email);
$password = trim($data->password);

// Define validators
$emailValidator = v::email()->notEmpty();
$passwordValidator = v::stringType()->length(6, null);

// Validate email
if (!$emailValidator->validate($email)) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid email format"]);
    exit;
}

// Validate password
if (!$passwordValidator->validate($password)) {
    http_response_code(400);
    echo json_encode(["message" => "Password must be at least 6 characters long"]);
    exit;
}

$email = mysqli_real_escape_string($conn, $email);

$sql = "SELECT * FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(401);
    echo json_encode(["message" => "Invalid email or password"]);
    exit;
}

$user = $result->fetch_assoc();

// Verify password
if (!password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(["message" => "Invalid email or password"]);
    exit;
}

// Generate JWT
$secretKey = $_ENV["JWT_SECRET"] ?: "your_default_secret";
$tokenPayload = [
    "id" => $user['id'],
    "email" => $user['email'],
    "role" => $user['role'],
    "exp" => time() + ($_ENV["JWT_EXPIRES_IN"] ?: 5 * 24 * 60 * 60) // 5 days expiration
];
$token = JWT::encode($tokenPayload, $secretKey, 'HS256');

// Response
echo json_encode([
    "message" => "Login successful",
    "data" => [
        "id" => $user['id'],
        "firstName" => $user['firstName'],
        "lastName" => $user['lastName'],
        "email" => $user['email'],
        "userName" => $user['userName'],
        "image" => $user['image'],
        "skillLevel" => $user['skillLevel'],
        "role" => $user['role'],
        "isEmailVerified" => $user['isEmailVerified'] == 1,
        "payments" => [
            "membership" => [
                "membershipPayment" => $user['membershipPayment'],
                "membershipPaymentAmount" => $user['membershipPaymentAmount'],
                "membershipPaymentDate" => $user['membershipPaymentDate'],
                "membershipPaymentDuration" => $user['membershipPaymentDuration'],
            ],
            "tutorship" => [
                "tutorshipPayment" => $user['tutorshipPayment'],
                "tutorshipPaymentAmount" => $user['tutorshipPaymentAmount'],
                "tutorshipPaymentDate" => $user['tutorshipPaymentDate'],
                "tutorshipPaymentDuration" => $user['tutorshipPaymentDuration'],
            ]
        ],
        "emailVerification" => [
            "emailCode" => $user['emailCode'],
            "expiresAt" => $user['expiresAt']
        ],
        "token" => $token,
        "country_code" => $user['country_code'],
        "number" => $user['number'],
        "createdAt" => $user['createdAt'],
        "updatedBy" => $user['updatedBy'],
    ]
]);
exit;
