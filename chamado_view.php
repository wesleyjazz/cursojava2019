<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: chamados.php');
    exit();
}

$pdo = Database::getInstance();
$id = (int)$_GET['id'];

// Buscar detalhes do chamado
$stmt = $pdo->prepare("SELECT 
    c.*, 
    s.nome as setor_nome,
    m.nome as maquina_nome,
    u.username as criado_por_nome,
    ua.username as atribuido_a_nome
    FROM chamados c
    LEFT JOIN setores s ON c.setor_id = s.id
    LEFT JOIN maquinas m ON c.maquina_id = m.id
    LEFT JOIN users u ON c.criado_por = u.id
    LEFT JOIN users ua ON c.atribuido_a = ua.id
    WHERE c.id = ?");
$stmt->execute([$id]);
$chamado = $stmt->fetch();

if (!$chamado) {
    $_SESSION['error'] = "Chamado não encontrado";
    header('Location: chamados.php');
    exit();
}

// Buscar histórico/comentários
$comentarios = $pdo->prepare("SELECT 
    h.*, 
    u.username as usuario_nome
    FROM chamado_historico h
    LEFT JOIN users u ON h.usuario_id = u.id
    WHERE h.chamado_id = ?
    ORDER BY h.data DESC");
$comentarios->execute([$id]);

include 'header.php';
?>

<div class="container">
    <div class="card shadow-lg mt-4">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0"><i class="bi bi-ticket-detailed"></i> Chamado #<?= $chamado['id'] ?></h3>
                <a href="chamados.php" class="btn btn-light">
                    <i class="bi bi-arrow-left"></i> Voltar
                </a>
            </div>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5>Informações Básicas</h5>
                    <ul class="list-group list-group-flush mb-4">
                        <li class="list-group-item">
                            <strong>Status:</strong> 
                            <span class="badge bg-<?= 
                                $chamado['status'] === 'aberto' ? 'danger' : 
                                ($chamado['status'] === 'em_andamento' ? 'warning' : 'success') 
                            ?>">
                                <?= ucfirst($chamado['status']) ?>
                            </span>
                        </li>
                        <li class="list-group-item"><strong>Setor:</strong> <?= htmlspecialchars($chamado['setor_nome']) ?></li>
                        <li class="list-group-item"><strong>Máquina:</strong> <?= htmlspecialchars($chamado['maquina_nome']) ?></li>
                        <li class="list-group-item"><strong>Aberto por:</strong> <?= htmlspecialchars($chamado['criado_por_nome']) ?></li>
                        <li class="list-group-item"><strong>Data abertura:</strong> <?= date('d/m/Y H:i', strtotime($chamado['criado_em'])) ?></li>
                        <?php if ($chamado['atribuido_a_nome']): ?>
                        <li class="list-group-item"><strong>Atribuído a:</strong> <?= htmlspecialchars($chamado['atribuido_a_nome']) ?></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="col-md-6">
                    <h5>Descrição Detalhada</h5>
                    <div class="card mb-4">
                        <div class="card-body">
                            <?= nl2br(htmlspecialchars($chamado['descricao'])) ?>
                        </div>
                    </div>
                </div>
            </div>

            <h5 class="mt-4">Histórico</h5>
            <div class="list-group">
                <?php while ($comentario = $comentarios->fetch()): ?>
                <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1"><?= htmlspecialchars($comentario['usuario_nome']) ?></h6>
                        <small><?= date('d/m/Y H:i', strtotime($comentario['data'])) ?></small>
                    </div>
                    <p class="mb-1"><?= nl2br(htmlspecialchars($comentario['comentario'])) ?></p>
                    <small class="text-muted">Ação: <?= ucfirst($comentario['acao']) ?></small>
                </div>
                <?php endwhile; ?>
                
                <?php if ($comentarios->rowCount() === 0): ?>
                <div class="list-group-item text-muted">
                    Nenhum histórico registrado para este chamado
                </div>
                <?php endif; ?>
            </div>

            <!-- Formulário para adicionar comentário -->
            <?php if ($chamado['status'] !== 'concluido'): ?>
            <form method="POST" action="chamado_add_comment.php" class="mt-4">
                <input type="hidden" name="chamado_id" value="<?= $chamado['id'] ?>">
                <div class="mb-3">
                    <label for="comentario" class="form-label">Adicionar Comentário</label>
                    <textarea class="form-control" id="comentario" name="comentario" rows="3" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Enviar</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
