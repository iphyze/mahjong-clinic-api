<?php

require_once __DIR__ . '/vendor/autoload.php';

// Set CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 86400");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

include_once('includes/connection.php');

// Normalize request URI
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = '/mahjong-server/api';
$relativePath = str_replace($basePath, '', $requestUri);


$routes = [
    '/' => function () {
        echo json_encode(["message" => "Welcome to Mahjong API 😊"]);
    },
    '/welcome' => 'routes/welcome.php',
    '/auth/login' => 'routes/auth/login.php',
    '/auth/register' => 'routes/auth/register.php',
    '/auth/sendVerificationCode' => 'routes/auth/sendVerificationCode.php',
    '/auth/forgotPassword' => 'routes/auth/forgotPassword.php',
    '/auth/verifyEmail' => 'routes/auth/verifyEmail.php',
    '/users/getAllUsers' => 'routes/users/getAllUsers.php',
    '/users/deleteUsers' => 'routes/users/deleteUsers.php',
    '/users/updateUserData' => 'routes/users/updateUserData.php',
];


if (array_key_exists($relativePath, $routes)) {
    if (is_callable($routes[$relativePath])) {
        $routes[$relativePath](); // Execute function
    } else {
        include_once($routes[$relativePath]);
    }
    exit;
}

$dynamicRoutes = [
    '/users/getSingleUser/(.+)' => 'routes/users/getSingleUser.php',
];


foreach ($dynamicRoutes as $pattern => $file) {
    if (preg_match('#^' . $pattern . '$#', $relativePath, $matches)) {
        $params = explode('/', $matches[1]);

        // If there's only one parameter, store it as a string, else store as an array
        $_GET['params'] = count($params) === 1 ? $params[0] : $params;
        include_once($file);
        exit;
    }
}

http_response_code(404);
echo json_encode(["message" => "Page not found."]);
exit;

// Close connection
mysqli_close($conn);

?>