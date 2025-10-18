<?php
ob_start();
session_start();
require_once 'config.php';

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

ob_clean();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Silakan login terlebih dahulu!']);
        exit();
    }

    $comment_id = $_POST['comment_id'] ?? '';
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role'] ?? 'user';

    if (empty($comment_id)) {
        echo json_encode(['success' => false, 'error' => 'Data tidak lengkap!']);
        exit();
    }

    try {
        if ($user_role == 'admin') {
            $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
            $stmt->execute([$comment_id]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ? AND user_id = ?");
            $stmt->execute([$comment_id, $user_id]);
        }

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        error_log("Delete Comment Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Terjadi kesalahan sistem!']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Method tidak diizinkan!']);
}

exit();
