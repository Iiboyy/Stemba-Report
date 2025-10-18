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

    $report_id = $_POST['report_id'] ?? '';
    $comment = trim($_POST['comment'] ?? '');
    $user_id = $_SESSION['user_id'];

    if (empty($report_id) || empty($comment)) {
        echo json_encode(['success' => false, 'error' => 'Data tidak lengkap!']);
        exit();
    }

    if (strlen($comment) > 500) {
        echo json_encode(['success' => false, 'error' => 'Komentar terlalu panjang! Maksimal 500 karakter.']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT id FROM reports WHERE id = ?");
        $stmt->execute([$report_id]);

        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Laporan tidak ditemukan!']);
            exit();
        }

        // Insert comment
        $stmt = $pdo->prepare("INSERT INTO comments (report_id, user_id, comment) VALUES (?, ?, ?)");
        $result = $stmt->execute([$report_id, $user_id, $comment]);

        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Gagal menyimpan komentar!']);
        }
    } catch (PDOException $e) {
        error_log("Comment Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Terjadi kesalahan sistem!']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Method tidak diizinkan!']);
}
exit();
