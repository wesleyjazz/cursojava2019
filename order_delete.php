<?php
session_start();
require_once 'db.php';

// Verificar autenticação e permissões
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') === 'technician') {
    $_SESSION['error'] = "Acesso não autorizado";
    header('Location: login.php');
    exit();
}

$pdo = Database::getInstance();

// Verificar se é uma requisição POST e se o ID foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    try {
        $orderId = (int)$_POST['id'];
        
        if ($orderId <= 0) {
            throw new Exception("ID de ordem inválido");
        }

        // Verificar se a ordem existe
        $stmt = $pdo->prepare("SELECT id FROM service_orders WHERE id = ?");
        $stmt->execute([$orderId]);
        
        if (!$stmt->fetch()) {
            throw new Exception("Ordem não encontrada");
        }

        // Iniciar transação para garantir integridade
        $pdo->beginTransaction();

        try {
            // 1. Excluir notificações relacionadas
            $pdo->prepare("DELETE FROM notifications WHERE order_id = ?")->execute([$orderId]);
            
            // 2. Excluir anexos físicos e registros
            $attachments = $pdo->prepare("SELECT file_path FROM attachments WHERE order_id = ?");
            $attachments->execute([$orderId]);
            
            foreach ($attachments->fetchAll() as $file) {
                if (file_exists($file['file_path'])) {
                    unlink($file['file_path']);
                }
            }
            $pdo->prepare("DELETE FROM attachments WHERE order_id = ?")->execute([$orderId]);
            
            // 3. Excluir comentários
            $pdo->prepare("DELETE FROM order_comments WHERE order_id = ?")->execute([$orderId]);
            
            // 4. Excluir a ordem principal
            $pdo->prepare("DELETE FROM service_orders WHERE id = ?")->execute([$orderId]);

            $pdo->commit();
            
            $_SESSION['success'] = "Ordem #$orderId excluída com sucesso!";
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e; // Re-lança a exceção para ser capturada no bloco externo
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Erro ao excluir ordem: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Requisição inválida";
}

header('Location: orders.php');
exit();
?>
