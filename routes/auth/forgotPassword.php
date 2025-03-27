<?php

require 'vendor/autoload.php'; // Load dependencies
require_once 'includes/connection.php'; // Database connection
use Respect\Validation\Validator as v;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable('./');
$dotenv->load();


require_once 'utils/email_template.php';


header("Content-Type: application/json");


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(["message" => "Bad Request"]);
    exit;
}

// Get the request body
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['email'])) {
    http_response_code(400);
    echo json_encode(["message" => "Email is required."]);
    exit;
}

$email = $data['email'];

// Validation
$emailValidator = v::email()->notEmpty();
if (!$emailValidator->validate($email)) {
    http_response_code(405);
    echo json_encode(["message" => "Invalid email format"]);
    exit;
}


// Check if user exists
$query = "SELECT * FROM users WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(["message" => "The provided email does not exist in our system."]);
    exit;
}

$user = $result->fetch_assoc();

// Generate a new password
$newPassword = substr(bin2hex(random_bytes(8)), 0, 7) . "!1"; // 8 random characters + !1

// Hash the password
$hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

// Update password in the database
$updateQuery = "UPDATE users SET password = ? WHERE email = ?";
$updateStmt = $conn->prepare($updateQuery);
$updateStmt->bind_param("ss", $hashedPassword, $email);

if ($updateStmt->execute()) {
    // Send email with new password
    if (sendPasswordResetEmail($email, $newPassword, $user['firstName'])) {
        http_response_code(200);
        echo json_encode(["message" => "A new password has been sent to your email address."]);
    } else {
        http_response_code(200);
        echo json_encode(["message" => "Error sending email, but password was updated."]);
    }
} else {
    http_response_code(500);
    echo json_encode(["message" => "Database error while updating password.", "error" => $updateStmt->error]);
}

$stmt->close();
$updateStmt->close();
$conn->close();



function sendPasswordResetEmail($to, $newPassword, $firstName) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USER'];
        $mail->Password = $_ENV['SMTP_PASS'];
        // $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->SMTPSecure = 'ssl';
        $mail->Port = $_ENV['SMTP_PORT'];
        $mail->setFrom($_ENV['SMTP_USER'], 'Mahjong Nigeria Clinic');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = 'Your Password Has Been Reset';
        $mail->Body = passwordResetTemplate($firstName, $newPassword);

        return $mail->send();
    } catch (Exception $e) {
        error_log('Mailer Error: ' . $mail->ErrorInfo);
        return false;
    }
}

?>