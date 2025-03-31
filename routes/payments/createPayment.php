<?php

require 'vendor/autoload.php'; // Load dependencies
require_once 'includes/connection.php'; // Database connection
require_once 'includes/authMiddleware.php';
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable('./');
$dotenv->load();

require_once 'utils/email_template.php';


header('Content-Type: application/json');

// Authenticate user
$userData = authenticateUser();
$loggedInUserId = intval($userData['id']);
$loggedInUserRole = $userData['role'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(["message" => "Bad Request: Only POST method is allowed"]);
    exit;
}

function validatePaymentData($data) {
    $validators = [
        'email' => v::notEmpty()->email()->setName('Email'),
        'dollar_amount' => v::notEmpty()->number()->setName('Dollar Amount'),
        'rate' => v::notEmpty()->number()->setName('Rate'),
        'amount' => v::notEmpty()->number()->setName('Amount'),
        'payment_type' => v::notEmpty()->setName('Payment Type'),
        'paymentStatus' => v::notEmpty()->setName('Payment Status'),
        'phoneNumber' => v::notEmpty()->setName('Phone Number'),
        'transactionId' => v::notEmpty()->setName('Transaction ID'),
        'userId' => v::notEmpty()->number()->setName('User ID'),
    ];

    $errors = [];

    foreach ($validators as $field => $validator) {
        try {
            $validator->assert($data[$field] ?? null);
        } catch (NestedValidationException $exception) {
            $errors[$field] = $exception->getMessages();
        }
    }

    return $errors;
}


// Assuming you receive JSON data in POST request
$data = json_decode(file_get_contents("php://input"), true);

// Authorization check
if ($loggedInUserRole !== "Admin" && intval($data['userId']) !== $loggedInUserId) {
    http_response_code(403);
    echo json_encode(["status" => "Failed", "message" => "Access denied. You can only update your own payment information."]);
    exit;
}


function createPayment($conn, $data) {
    $errors = validatePaymentData($data);
    if (!empty($errors)) {
        return jsonResponse(400, ['errors' => $errors]);
    }

    try {
        extract($data);
        $sanitizedEmail = strtolower(trim($email));
        $paymentDate = date("Y-m-d H:i:s");
        $createdBy = $sanitizedEmail;
        $updatedBy = $sanitizedEmail;

        // Check if user exists and get their push token
        $stmt = $conn->prepare("SELECT id, expoPushToken FROM users WHERE id = ? AND email = ?");
        $stmt->bind_param("is", $userId, $sanitizedEmail);
        $stmt->execute();
        $userResult = $stmt->get_result();

        if ($userResult->num_rows === 0) {
            return jsonResponse(400, ['message' => 'User not found']);
        }

        $user = $userResult->fetch_assoc();
        $expoPushToken = $user['expoPushToken'];

        // Insert payment details
        $stmt = $conn->prepare("
            INSERT INTO user_payment (userId, email, dollar_amount, rate, amount, payment_type, paymentStatus, 
            paymentDuration, paymentDate, phoneNumber, transactionId, fullname, createdBy, updatedBy, 
            paymentMethod, transactionReference, currency)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issddssssssssssss", $userId, $sanitizedEmail, $dollar_amount, $rate, $amount, 
                          $payment_type, $paymentStatus, $paymentDuration, $paymentDate, $phoneNumber, 
                          $transactionId, $fullname, $createdBy, $updatedBy, $paymentMethod, $transactionReference, $currency);

        if (!$stmt->execute()) {
            return jsonResponse(500, ['message' => 'Error creating payment', 'error' => $stmt->error]);
        }

        $paymentId = $stmt->insert_id;

        // Skip user update if no valid payment type
        if ($payment_type !== 'Membership Payment' && $payment_type !== 'Tutorship Payment') {
            return processPaymentCompletion($conn, $data, $paymentId, $expoPushToken, $paymentDate);
        }

        // Update user membership/tutorship details if needed
        if ($payment_type === 'Membership Payment') {
            $updateUserQuery = "UPDATE users SET membershipPayment = ?, membershipPaymentAmount = ?, 
                                membershipPaymentDate = ?, membershipPaymentDuration = ? WHERE id = ?";
        } else { // Tutorship Payment
            $updateUserQuery = "UPDATE users SET tutorshipPayment = ?, tutorshipPaymentAmount = ?, 
                                tutorshipPaymentDate = ?, tutorshipPaymentDuration = ? WHERE id = ?";
        }

        $stmt = $conn->prepare($updateUserQuery);
        $stmt->bind_param("ssssi", $paymentStatus, $amount, $paymentDate, $paymentDuration, $userId);

        if (!$stmt->execute()) {
            return jsonResponse(500, ['message' => 'Error updating user details', 'error' => $stmt->error]);
        }

        return processPaymentCompletion($conn, $data, $paymentId, $expoPushToken, $paymentDate);

    } catch (Exception $e) {
        return jsonResponse(500, ['message' => 'Server error', 'error' => $e->getMessage()]);
    }
}

function processPaymentCompletion($conn, $data, $paymentId, $expoPushToken, $paymentDate) {
    extract($data);
    $notificationSent = false;
    $notificationTitle = null;
    $notificationMessage = null;

    if (strtolower($paymentStatus) === 'successful') {
        if ($payment_type === 'Membership Payment') {
            $notificationTitle = 'Membership Payment Completed!';
            $notificationMessage = "🎉 Congratulations $fullname! You are now an official member of Mahjong Clinic Nigeria. Welcome aboard! 🚀🔥";
        } elseif ($payment_type === 'Tutorship Payment') {
            $notificationTitle = 'Tutorship Payment Completed!';
            $notificationMessage = "🎉 Congratulations $fullname! You are now a student at Mahjong Clinic Nigeria. Welcome aboard! 🚀🔥";
        } else {
            $notificationTitle = 'Payment Completed!';
            $notificationMessage = "🎉 Thank you $fullname! Your payment of $currency $amount has been successfully processed.";
        }

        try {
            storeNotification($conn, $userId, $notificationTitle, $notificationMessage, $email);

            if (!empty($expoPushToken)) {
                $notificationSent = sendPushNotification($expoPushToken, $notificationTitle, $notificationMessage);
            }
        } catch (Exception $e) {
            // Log the error but continue processing
            error_log("Error with notification: " . $e->getMessage());
        }
    }

    $emailSent = sendPaymentEmail($email, $dollar_amount, $rate, $amount, $payment_type, $paymentStatus, 
                                  $paymentDuration, $paymentDate, $phoneNumber, $transactionId, $fullname);

    // Return all the data in the response like the JavaScript version
    return jsonResponse(200, [
        'message' => $emailSent ? 'Payment recorded successfully, email sent!' 
                                : 'Payment recorded successfully, but email failed to send.',
        'data' => [
            'paymentId' => $paymentId,
            'userId' => $userId,
            'email' => $email,
            'dollar_amount' => $dollar_amount,
            'rate' => $rate,
            'amount' => $amount,
            'payment_type' => $payment_type,
            'paymentStatus' => $paymentStatus,
            'paymentDuration' => $paymentDuration,
            'paymentDate' => $paymentDate,
            'phoneNumber' => $phoneNumber,
            'transactionId' => $transactionId,
            'fullname' => $fullname,
            'createdBy' => $email,
            'updatedBy' => $email,
            'paymentMethod' => $paymentMethod,
            'transactionReference' => $transactionReference,
            'currency' => $currency,
            'notification' => strtolower($paymentStatus) === 'successful' ? [
                'sent' => $notificationSent,
                'title' => $notificationTitle,
                'message' => $notificationMessage
            ] : null
        ]
    ]);
}


function storeNotification($conn, $userId, $title, $message, $email) {
    // Insert notification into the notifications table
    $stmt = $conn->prepare("INSERT INTO notifications (userId, title, message, createdBy, updatedBy) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $userId, $title, $message, $email, $email);
    
    if (!$stmt->execute()) {
        throw new Exception("Database error inserting notification: " . $stmt->error);
    }

    // Get the last inserted notification ID
    $notificationId = $stmt->insert_id;

    // Insert into user_notifications table
    $stmt = $conn->prepare("INSERT INTO user_notifications (notificationId, userId, isRead) VALUES (?, ?, ?)");
    $isRead = 0; // Use 0 instead of false for MySQL
    $stmt->bind_param("iii", $notificationId, $userId, $isRead);

    if (!$stmt->execute()) {
        throw new Exception("Error inserting user notification: " . $stmt->error);
    }

    return $notificationId;
}


function sendPushNotification($expoPushToken, $title, $message) {
    if (!$expoPushToken) {
        error_log('No push token provided, skipping notification');
        return false;
    }

    // Validate token format
    if (!str_starts_with($expoPushToken, 'ExponentPushToken[') && !str_starts_with($expoPushToken, 'ExpoPushToken[')) {
        error_log('Invalid token format: ' . $expoPushToken);
        return false;
    }

    $data = [
        'to' => $expoPushToken,
        'sound' => 'default',
        'title' => $title,
        'body' => $message,
        'data' => [
            'title' => $title,
            'message' => $message,
            'timestamp' => date('c')
        ],
        'priority' => 'high',
        'channelId' => 'default',
        'badge' => 1,
        '_displayInForeground' => true
    ];

    error_log('Sending push notification: ' . json_encode($data));

    $ch = curl_init('https://exp.host/--/api/v2/push/send');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Accept-Encoding: gzip, deflate',
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $responseData = json_decode($response, true);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    error_log('Push notification response: ' . $response);

    // Check if the request was successful
    if ($httpCode >= 200 && $httpCode < 300 && $responseData && !isset($responseData['errors'])) {
        return true;
    } else {
        error_log('Error sending push notification: ' . $response);
        return false;
    }
}

function sendPaymentEmail($to, $dollar_amount, $rate, $amount, $payment_type, $paymentStatus, $paymentDuration, $paymentDate, $phoneNumber, $transactionId, $fullname) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USER'];
        $mail->Password = $_ENV['SMTP_PASS'];
        $mail->SMTPSecure = 'ssl';
        $mail->Port = $_ENV['SMTP_PORT'];
        $mail->setFrom($_ENV['SMTP_USER'], 'Mahjong Nigeria Clinic');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = 'Payment Confirmation';
        $mail->Body = paymentConfirmationTemplate($dollar_amount, $rate, $amount, $payment_type, $paymentStatus, $paymentDuration, $paymentDate, $phoneNumber, $transactionId, $fullname);

        $mail->send();
        error_log("Payment Confirmation email sent to $to");
        return true;
    } catch (Exception $e) {
        error_log("Error sending email to $to: " . $e->getMessage());
        return false;
    }
}

function jsonResponse($status, $data) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Execute the payment creation
createPayment($conn, $data);
?>