<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$pdo = Database::getInstance();

// Processar envio de notificação manual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notify_technician'])) {
    try {
        $orderId = (int)$_POST['order_id'];
        
        // Buscar dados da ordem
        $stmt = $pdo->prepare("SELECT 
            o.id, o.type, o.priority, o.description, o.status, o.scheduled_at,
            m.name as machine_name,
            u.id as technician_id, u.username as technician_name, u.email as technician_email
            FROM service_orders o
            LEFT JOIN machines m ON o.machine_id = m.id
            LEFT JOIN users u ON o.user_id = u.id
            WHERE o.id = ?");
            
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        
        if ($order && $order['technician_id']) {
            // Enviar e-mail
            $subject = "Lembrete: Ordem de Serviço #{$order['id']}";
            $body = <<<HTML
            <h2>Lembrete de Ordem de Serviço</h2>
            <p><strong>Máquina:</strong> {$order['machine_name']}</p>
            <p><strong>Tipo:</strong> {$order['type']}</p>
            <p><strong>Prioridade:</strong> {$order['priority']}</p>
            <p><strong>Status:</strong> {$order['status']}</p>
            <p><strong>Data/Hora Agendada:</strong> {$order['scheduled_at']}</p>
            <p><strong>Descrição:</strong><br>{$order['description']}</p>
            HTML;
            
            $headers = "From: sistema@empresa.com\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            
            if (mail($order['technician_email'], $subject, $body, $headers)) {
                $_SESSION['success'] = "Notificação enviada para {$order['technician_name']}";
                
                // Marcar como notificado se for disparo manual
                $updateStmt = $pdo->prepare("UPDATE service_orders SET notification_sent = 1 WHERE id = ?");
                $updateStmt->execute([$order['id']]);
            } else {
                $_SESSION['warning'] = "E-mail não pôde ser enviado";
            }
            
            // Registrar notificação no sistema
            $stmt = $pdo->prepare("INSERT INTO notifications 
                (user_id, title, message, order_id, is_read) 
                VALUES (?, ?, ?, ?, 0)");
            $stmt->execute([
                $order['technician_id'],
                "Lembrete: Ordem #{$order['id']}",
                "Você foi lembrado sobre a ordem de serviço para {$order['machine_name']}",
                $order['id']
            ]);
            
            // Notificação no navegador
            $_SESSION['browser_notification'] = [
                'title' => "Notificação enviada",
                'message' => "Técnico {$order['technician_name']} foi notificado"
            ];
        } else {
            $_SESSION['error'] = "Ordem não encontrada ou sem técnico atribuído";
        }
        
        header("Location: orders.php");
        exit();
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erro ao enviar notificação: " . $e->getMessage();
        header("Location: orders.php");
        exit();
    }
}

// Restante do seu código permanece igual...
?>
