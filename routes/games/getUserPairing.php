<?php
require_once 'includes/connection.php';
require_once 'includes/authMiddleware.php';


header("Content-Type: application/json");

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception("Bad Request", 400);
    }

    $userData = authenticateUser();
    $loggedInUserRole = $userData['role'];

    // Query parameters
    $userId = isset($_GET['userId']) ? intval($_GET['userId']) : null;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $sortBy = $_GET['sortBy'] ?? 'groupName';
    $sortOrder = strtoupper($_GET['sortOrder'] ?? 'DESC');
    $search = $_GET['search'] ?? '';

    $offset = ($page - 1) * $limit;

    $allowedSortFields = ['groupName', 'scheduleDate', 'gameStatus'];
    if (!in_array($sortBy, $allowedSortFields)) {
        $sortBy = 'groupName';
    }

    $params = [];
    $paramTypes = '';
    $searchClause = '';

    // Build the base query
    $query = "
        SELECT g.id, g.groupName, g.scheduleDate, g.gameStatus, u.image AS userImage, u.skillLevel
        FROM games g
        JOIN pairs p ON g.id = p.gameId
        JOIN users u ON p.userId = u.id
        WHERE 1 = 1
    ";

    // Apply userId filtering
    if ($userId !== null && !in_array($loggedInUserRole, ['Admin', 'Super_Admin'])) {
        $query .= " AND u.id = ? ";
        $params[] = $userId;
        $paramTypes .= 'i';
    }

    if ($userId !== null && in_array($loggedInUserRole, ['Admin', 'Super_Admin'])) {
        $query .= " AND (? IS NULL OR u.id = ?) ";
        $params[] = $userId;
        $params[] = $userId;
        $paramTypes .= 'ii';
    }

    // Search clause
    if (!empty($search)) {
        $query .= " AND (g.groupName LIKE ? OR g.scheduleDate LIKE ? OR u.skillLevel LIKE ? OR g.gameStatus LIKE ?) ";
        $searchWildcard = "%$search%";
        $params = array_merge($params, [$searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard]);
        $paramTypes .= 'ssss';
    }

    // Sorting and pagination
    $query .= " ORDER BY g.$sortBy $sortOrder LIMIT ? OFFSET ? ";
    $params[] = $limit;
    $params[] = $offset;
    $paramTypes .= 'ii';

    // Prepare and execute the statement
    $stmt = $conn->prepare($query);
    if (!empty($paramTypes)) {
        $stmt->bind_param($paramTypes, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    // Get total count for pagination
    $countQuery = "
        SELECT COUNT(*) AS total
        FROM games g
        JOIN pairs p ON g.id = p.gameId
        JOIN users u ON p.userId = u.id
        WHERE 1 = 1
    ";

    $countParams = [];
    $countParamTypes = '';

    if ($userId !== null && !in_array($loggedInUserRole, ['Admin', 'Super_Admin'])) {
        $countQuery .= " AND u.id = ? ";
        $countParams[] = $userId;
        $countParamTypes .= 'i';
    }

    if ($userId !== null && in_array($loggedInUserRole, ['Admin', 'Super_Admin'])) {
        $countQuery .= " AND (? IS NULL OR u.id = ?) ";
        $countParams[] = $userId;
        $countParams[] = $userId;
        $countParamTypes .= 'ii';
    }

    if (!empty($search)) {
        $countQuery .= " AND (g.groupName LIKE ? OR g.scheduleDate LIKE ? OR u.skillLevel LIKE ? OR g.gameStatus LIKE ?) ";
        $searchWildcard = "%$search%";
        $countParams = array_merge($countParams, [$searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard]);
        $countParamTypes .= 'ssss';
    }

    $countStmt = $conn->prepare($countQuery);
    if (!empty($countParamTypes)) {
        $countStmt->bind_param($countParamTypes, ...$countParams);
    }
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $total = $countResult->fetch_assoc()['total'];

    echo json_encode([
        "status" => "Success",
        "message" => "Fetched Successfully",
        "data" => $data,
        "meta" => [
            "total" => $total,
            "limit" => $limit,
            "page" => $page,
            "sortBy" => $sortBy,
            "sortOrder" => $sortOrder,
            "search" => $search
        ]
    ]);
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        "status" => "Failed",
        "message" => $e->getMessage()
    ]);
}
