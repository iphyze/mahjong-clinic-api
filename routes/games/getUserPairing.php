<?php
require_once 'includes/connection.php';
require_once 'includes/authMiddleware.php';

header('Content-Type: application/json');

try {
    // Ensure request method is GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception("Bad Request, route wasn't found!", 404);
    }

    // Authenticate user
    $userData = authenticateUser();
    $loggedInUserId = $userData['id'];
    $loggedInUserRole = $userData['role'];

    // Get userId from URL parameters
    if (!isset($_GET['params'])) {
        throw new Exception("User ID is required.", 400);
    }

    $userId = intval($_GET['params']);

    // Prevent access if the logged-in user is not the owner of the data
    if ($loggedInUserRole !== 'Admin' && $userId !== $loggedInUserId) {
        throw new Exception("Access denied. You can only view your own pairings.", 403);
    }

    // Fetch all games where the user is a player
    $userGamesQuery = "
        SELECT g.id, g.groupName, g.scheduleDate, g.gameStatus, u.image AS userImage, u.skillLevel
        FROM games g
        JOIN pairs p ON g.id = p.gameId
        JOIN users u ON p.userId = u.id
        WHERE p.userId = ?
    ";

    $stmt = $conn->prepare($userGamesQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $gamesResult = $stmt->get_result();

    if ($gamesResult->num_rows === 0) {
        throw new Exception("No games found for this user.", 404);
    }

    $games = [];
    while ($row = $gamesResult->fetch_assoc()) {
        $games[] = $row;
    }

    // Extract game IDs
    $gameIds = array_column($games, 'id');

    // Fetch pair members for these games
    if (empty($gameIds)) {
        throw new Exception("No paired members found for this user.", 404);
    }

    $placeholders = implode(',', array_fill(0, count($gameIds), '?'));
    $pairMembersQuery = "
        SELECT p.gameId, p.dataId, u.image, u.userName, u.firstName, u.lastName, u.email, u.id AS userId
        FROM pairs p
        JOIN users u ON p.userId = u.id
        WHERE p.gameId IN ($placeholders)
    ";

    $stmt = $conn->prepare($pairMembersQuery);
    $stmt->bind_param(str_repeat('i', count($gameIds)), ...$gameIds);
    $stmt->execute();
    $pairMembersResult = $stmt->get_result();

    $pairMembers = [];
    while ($row = $pairMembersResult->fetch_assoc()) {
        $pairMembers[] = $row;
    }

    // Format the response
    $formattedData = array_map(function ($game) use ($pairMembers, $userId) {
        return [
            "id" => $game['id'],
            "groupName" => $game['groupName'],
            "userImage" => $game['userImage'] ?? null,
            "skillLevel" => $game['skillLevel'] ?? "Not specified",
            "scheduledDate" => $game['scheduleDate'],
            "gameStatus" => $game['gameStatus'],
            "pairMembersData" => array_values(array_filter($pairMembers, function ($member) use ($game, $userId) {
                return $member['gameId'] === $game['id'] && $member['userId'] !== $userId; // Exclude current user
            }))
        ];
    }, $games);

    // Success response
    http_response_code(200);
    echo json_encode($formattedData);
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        "status" => "Failed",
        "message" => $e->getMessage()
    ]);
}
?>
