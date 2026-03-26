<?php
require __DIR__ . '/../../../autoload.php';

use Application\Database;
use Application\Verifier;
use PDO;

header("Content-Type: application/json");

// 1. Connect to DB
$database = new Database('prod');
$db = $database->getDb();

// 2. Authenticate using Verifier
$verifier = new Verifier();
$verifier->decode($_SERVER['HTTP_AUTHORIZATION'] ?? '');

if (!$verifier->isValid()) {
    http_response_code(401);
    echo json_encode(["error" => "Invalid or missing token"]);
    exit;
}

$userId = $verifier->userId;
$role   = $verifier->role;

// 3. Handle GET /api/mail/
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($role === 'admin') {
        // Admin sees all mail
        $stmt = $db->query("SELECT id, name, message, userId FROM mail ORDER BY id");
    } else {
        // Regular user sees only their own mail
        $stmt = $db->prepare("SELECT id, name, message, userId FROM mail WHERE userId = :uid ORDER BY id");
        $stmt->execute([':uid' => $userId]);
    }

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows);
    exit;
}

// 4. Handle POST /api/mail/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['name']) || !isset($data['message'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing name or message"]);
        exit;
    }

    // Admin and user both create mail under their own userId
    $insertUserId = $userId;

    $stmt = $db->prepare(
        "INSERT INTO mail (name, message, userId) VALUES (:n, :m, :uid)"
    );
    $stmt->execute([
        ':n'   => $data['name'],
        ':m'   => $data['message'],
        ':uid' => $insertUserId
    ]);

    echo json_encode(["success" => true]);
    exit;
}

// 5. Any other method → 405
http_response_code(405);
echo json_encode(["error" => "Method not allowed"]);
