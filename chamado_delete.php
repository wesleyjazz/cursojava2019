<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['id'])) {
    header('Location: login.php');
    exit();
}

$pdo = Database::getInstance();
$id = (int)$_POST['id'];

// Verificar se usuário é admin
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($user['role'] !== 'admin') {
    $_SESSION['error'] = "Acesso negado";
    header('Location: chamados.php');
    exit();
}

// Verificar se chamado existe
$stmt = $pdo->prepare("SELECT id FROM chamados WHERE id = ?");
$stmt->execute([$id]);

if ($stmt->fetch()) {
    try {
        // Registrar no histórico antes de deletar (opcional)
        $pdo->prepare("INSERT INTO chamado_historico 
            (chamado_id, usuario_id, acao, comentario)
            VALUES (?, ?, 'exclusão', 'Chamado excluído por administrador')")
           ->execute([$id, $_SESSION['user_id']]);

        // Deletar chamado
        $pdo->prepare("DELETE FROM chamados WHERE id = ?")->execute([$id]);
        
        $_SESSION['success'] = "Chamado excluído com sucesso!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erro ao excluir chamado: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Chamado não encontrado";
}

header('Location: chamados.php');
exit();
?>
