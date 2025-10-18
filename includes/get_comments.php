<?php

ob_start();
require_once 'config.php';


error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');


ob_clean();

$report_id = $_GET['report_id'] ?? '';

if (empty($report_id)) {
    echo json_encode([]);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.username 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.report_id = ? 
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$report_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($comments);
} catch (PDOException $e) {
    echo json_encode([]);
}

exit();
