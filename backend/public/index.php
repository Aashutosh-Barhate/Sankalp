<?php

require_once __DIR__ . '/../src/Controllers/ApiController.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$controller = new ApiController();
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

try {
    if ($method === 'POST' && $path === '/task') {
        $controller->createTask();
        exit;
    }

    if ($method === 'POST' && $path === '/start-session') {
        $controller->startSession();
        exit;
    }

    if ($method === 'POST' && $path === '/complete-session') {
        $controller->completeSession();
        exit;
    }

    if ($method === 'GET' && $path === '/stats') {
        $controller->stats();
        exit;
    }

    http_response_code(404);
    echo json_encode(['error' => 'Route not found']);
} catch (InvalidArgumentException $exception) {
    http_response_code(422);
    echo json_encode(['error' => $exception->getMessage()]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
