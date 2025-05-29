<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$pdo = Database::getInstance();
$attachmentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$attachmentId) {
    $_SESSION['error'] = "ID de anexo inválido";
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'orders.php'));
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM attachments WHERE id = ?");
    $stmt->execute([$attachmentId]);
    $file = $stmt->fetch();

    if (!$file || !file_exists($file['file_path'])) {
        throw new Exception("Arquivo não encontrado");
    }

    // Determinar tipo MIME
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['file_path']);

    // Configurar headers
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . basename($file['file_path']) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file['file_path']));
    
    // Limpar buffer de saída e enviar arquivo
    ob_clean();
    flush();
    readfile($file['file_path']);
    exit;

} catch (Exception $e) {
    error_log("Download error: " . $e->getMessage());
    $_SESSION['error'] = "Erro ao baixar arquivo: " . $e->getMessage();
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'orders.php'));
    exit();
}
