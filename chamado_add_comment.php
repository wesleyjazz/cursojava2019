<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['chamado_id']) || !isset($_POST['comentario'])) {
    header('Location: login.php');
    exit();
}

$pdo = Database::getInstance();
$chamado_id = (int)$_POST['chamado_id'];
$comentario = trim($_POST['comentario']);

if (empty($comentario)) {
    $_SESSION['error'] = "O comentário não pode estar vazio";
    header("Location: chamado_view.php?id=$chamado_id");
    exit();
}

// Verificar se chamado existe e não está concluído
$stmt = $pdo->prepare("SELECT id, status FROM chamados WHERE id = ?");
$stmt->execute([$chamado_id]);
$chamado = $stmt->fetch();

if (!$chamado) {
    $_SESSION['error'] = "Chamado não encontrado";
    header('Location: chamados.php');
    exit();
}

if ($chamado['status'] === 'concluido') {
    $_SESSION['error'] = "Não é possível adicionar comentários a chamados concluídos";
    header("Location: chamado_view.php?id=$chamado_id");
    exit();
}

try {
    // Adicionar ao histórico
    $pdo->prepare("INSERT INTO chamado_historico 
        (chamado_id, usuario_id, acao, comentario)
        VALUES (?, ?, 'comentário', ?)")
       ->execute([$chamado_id, $_SESSION['user_id'], $comentario]);

    // Atualizar data de atualização do chamado
    $pdo->prepare("UPDATE chamados SET atualizado_em = NOW() WHERE id = ?")
       ->execute([$chamado_id]);

    $_SESSION['success'] = "Comentário adicionado com sucesso!";
} catch (PDOException $e) {
    $_SESSION['error'] = "Erro ao adicionar comentário: " . $e->getMessage();
}

header("Location: chamado_view.php?id=$chamado_id");
exit();
?>
