<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header('Location: login.php');
    exit();
}

$pdo = Database::getInstance();
$id = (int)$_GET['id'];

// Verificar se usuário é técnico ou admin
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!in_array($user['role'], ['technician', 'admin'])) {
    $_SESSION['error'] = "Acesso negado";
    header('Location: chamados.php');
    exit();
}

// Verificar se chamado existe e está aberto
$stmt = $pdo->prepare("SELECT id, status FROM chamados WHERE id = ?");
$stmt->execute([$id]);
$chamado = $stmt->fetch();

if (!$chamado) {
    $_SESSION['error'] = "Chamado não encontrado";
    header('Location: chamados.php');
    exit();
}

if ($chamado['status'] !== 'aberto') {
    $_SESSION['error'] = "Só é possível atribuir chamados abertos";
    header("Location: chamado_view.php?id=$id");
    exit();
}

try {
    // Atualizar chamado
    $pdo->prepare("UPDATE chamados SET 
        status = 'em_andamento',
        atribuido_a = ?,
        atualizado_em = NOW()
        WHERE id = ?")
       ->execute([$_SESSION['user_id'], $id]);

    // Registrar no histórico
    $pdo->prepare("INSERT INTO chamado_historico 
        (chamado_id, usuario_id, acao, comentario)
        VALUES (?, ?, 'atribuição', 'Chamado atribuído ao técnico')")
       ->execute([$id, $_SESSION['user_id']]);

    // Criar notificação
    $user_name = $pdo->query("SELECT username FROM users WHERE id = {$_SESSION['user_id']}")->fetchColumn();
    
    $pdo->prepare("INSERT INTO notifications 
        (user_id, title, message, chamado_id, is_read)
        SELECT id, ?, ?, ?, 0 FROM users WHERE role = 'admin'")
       ->execute([
           "Chamado Atribuído #$id",
           "O chamado #$id foi atribuído a $user_name",
           $id
       ]);

    $_SESSION['success'] = "Chamado atribuído a você com sucesso!";
} catch (PDOException $e) {
    $_SESSION['error'] = "Erro ao atribuir chamado: " . $e->getMessage();
}

header("Location: chamado_view.php?id=$id");
exit();
?>
