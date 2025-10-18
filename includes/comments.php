<?php
function getComments($pdo, $report_id)
{
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, u.username 
            FROM comments c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.report_id = ? 
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([$report_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function addComment($pdo, $report_id, $user_id, $comment)
{
    try {
        $stmt = $pdo->prepare("
            INSERT INTO comments (report_id, user_id, comment) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$report_id, $user_id, $comment]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function deleteComment($pdo, $comment_id, $user_id, $user_role)
{
    try {
        if ($user_role == 'admin') {
            $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
            $stmt->execute([$comment_id]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ? AND user_id = ?");
            $stmt->execute([$comment_id, $user_id]);
        }
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
