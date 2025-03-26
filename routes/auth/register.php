<?php

require 'vendor/autoload.php'; // Load dependencies
require_once 'includes/connection.php'; // Database connection
use Respect\Validation\Validator as v;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable('./');
$dotenv->load();


require_once 'utils/email_template.php';


header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(["message" => "Bad Request: Only POST method is allowed"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['firstName'], $data['lastName'], $data['email'], $data['password'], $data['country_code'], $data['number'])) {
    http_response_code(400);
    echo json_encode(["message" => "All fields are required"]);
    exit;
}

$firstName = trim($data['firstName']);
$lastName = trim($data['lastName']);
$email = trim(strtolower($data['email']));
$password = trim($data['password']);
$country_code = trim($data['country_code']);
$number = trim($data['number']);


// Validation
$emailValidator = v::email()->notEmpty();
$passwordValidator = v::stringType()->length(6, null);
if (!$emailValidator->validate($email)) {
    http_response_code(405);
    echo json_encode(["message" => "Invalid email format"]);
    exit;
}
if (!$passwordValidator->validate($password)) {
    http_response_code(400);
    echo json_encode(["message" => "Password must be at least 6 characters long"]);
    exit;
}


// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$timestamp = date('Y-m-d H:i:s');
$emailCode = rand(1000, 9999);
$expiresAt = date('Y-m-d H:i:s', strtotime('+2 hours'));
$role = 'User';

// Generate unique username
list($baseName, $domain) = explode('@', $email);
$userName = $baseName . strtolower($domain[0]) . rand(1000, 9999);


// Check if user exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR number = ?");
$stmt->bind_param("ss", $email, $number);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    http_response_code(409);
    echo json_encode(["message" => "User already exists"]);
    exit;
}


// Insert new user
$stmt = $conn->prepare("INSERT INTO users (firstName, lastName, email, userName, password, country_code, number, role, isEmailVerified, emailCode, expiresAt, createdBy, updatedBy, createdAt, updatedAt) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?)");

// Make sure the number of placeholders matches the number of bind parameters
$stmt->bind_param("ssssssssssssss", $firstName, $lastName, $email, $userName, $hashedPassword, $country_code, $number, $role, $emailCode, $expiresAt, $email, $email, $timestamp, $timestamp);


if (!$stmt) {
    http_response_code(500); // 500 Internal Server Error
    echo json_encode(["message" => "Database error: Failed to prepare statement"]);
    exit;
}


if ($stmt->execute()) {
    $userId = $stmt->insert_id;
    $emailSent = sendVerificationEmail($email, $emailCode, $expiresAt, $firstName);
    $smsSent = sendVerificationSMS($country_code . $number, $emailCode);

    $membershipPayment = false;
    $membershipPaymentAmount = 0;
    $membershipPaymentDate = null;
    $membershipPaymentDuration = null;

    $tutorshipPayment = false;
    $tutorshipPaymentAmount = 0;
    $tutorshipPaymentDate = null;
    $tutorshipPaymentDuration = null;

    $secretKey = $_ENV["JWT_SECRET"] ?: "your_default_secret";
    $payload = [
        "userId" => $userId,
        "email" => $email,
        "role" => $role,
        "exp" => time() + ($_ENV["JWT_EXPIRES_IN"] ?: 5 * 24 * 60 * 60)
    ];
    $token = JWT::encode($payload, $secretKey, 'HS256');
    
        http_response_code(200);
        echo json_encode([
        "message" => $emailSent && $smsSent
            ? "User registration was successful. Kindly verify your email!"
            : "User registration was successful, however we were not able to verify your email!",
        "data" => [
            "id" => $userId,
            "firstName" => $firstName,
            "lastName" => $lastName,
            "email" => $email,
            "userName" => $userName,
            "isEmailVerified" => false,
            "emailVerification" => [
                "emailCode" => $emailCode,
                "expiresAt" => $expiresAt
            ],
            "payments" => [
                "membership" => [
                    "membershipPayment" => $membershipPayment,
                    "membershipPaymentAmount" => $membershipPaymentAmount,
                    "membershipPaymentDate" => $membershipPaymentDate,
                    "membershipPaymentDuration" => $membershipPaymentDuration,
                ],
                "tutorship" => [
                    "tutorshipPayment" => $tutorshipPayment,
                    "tutorshipPaymentAmount" => $tutorshipPaymentAmount,
                    "tutorshipPaymentDate" => $tutorshipPaymentDate,
                    "tutorshipPaymentDuration" => $tutorshipPaymentDuration,
                ]
            ],
            "role" => $role,
            "token" => $token,
            "country_code" => $country_code,
            "number" => $number,
            "createdAt" => $timestamp,
            "updatedBy" => $email,
        ],
        "verificationStatus" => [
            "email" => $emailSent,
            "sms" => $smsSent
        ],
    ]);

} else {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error creating user",
        "details" => $stmt->error
    ]);
}

$conn->close();

// Function to send verification email
function sendVerificationEmail($to, $emailCode, $expiresAt, $firstName) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USER'];
        $mail->Password = $_ENV['SMTP_PASS'];
        // $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $_ENV['SMTP_PORT'];
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        $mail->setFrom($_ENV['SMTP_USER'], 'Mahjong Nigeria Clinic');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = 'Welcome / Email Verification';

        $mail->Body = emailVerificationTemplate($firstName, $emailCode, $expiresAt);

        return $mail->send();
    } catch (Exception $e) {
        error_log('Mailer Error: ' . $e->getMessage());
        return false;
    }
}


function sendVerificationSMS($phoneNumber, $emailCode) {
    $apiKey = $_ENV['TERMII_API_KEY'];
    $senderId = $_ENV['TERMII_SENDER_ID'];
    $url = $_ENV['TERMII_API_URL'] ?? "https://api.ng.termii.com/api/sms/send";

    $message = "Your verification code is: $emailCode";
    $payload = json_encode([
        "to" => $phoneNumber,
        "from" => $senderId,
        "sms" => $message,
        "type" => "plain",
        "channel" => "generic",
        "api_key" => $apiKey
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode === 200;
}

?>
