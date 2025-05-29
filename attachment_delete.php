<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$pdo = Database::getInstance();
$attachmentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$orderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);

if (!$attachmentId || !$orderId) {
    $_SESSION['error'] = "Parâmetros inválidos";
    header('Location: orders.php');
    exit();
}

try {
    // Verificar existência do arquivo
    $stmt = $pdo->prepare("SELECT * FROM attachments WHERE id = ?");
    $stmt->execute([$attachmentId]);
    $file = $stmt->fetch();

    if (!$file) {
        throw new Exception("Anexo não encontrado");
    }

    // Excluir do banco de dados
    $deleteStmt = $pdo->prepare("DELETE FROM attachments WHERE id = ?");
    $deleteStmt->execute([$attachmentId]);

    // Excluir arquivo físico
    if (file_exists($file['file_path'])) {
        if (!unlink($file['file_path'])) {
            throw new Exception("Falha ao excluir arquivo físico");
        }
    }

    $_SESSION['success'] = "Anexo excluído com sucesso";

} catch (Exception $e) {
    error_log("Delete attachment error: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
}

header("Location: order_view.php?id=$orderId");
exit();
