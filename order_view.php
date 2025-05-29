<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$pdo = Database::getInstance();
$error = null;
$success = null;

// Verificar o tipo de usuário
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$is_technician = ($user['role'] ?? '') === 'technician';

// Obter ID da ordem
$orderId = $_GET['id'] ?? null;
if (!$orderId) {
    header('Location: orders.php');
    exit();
}

// Buscar dados da ordem
try {
    $stmt = $pdo->prepare("SELECT 
        o.*, 
        m.name as machine_name,
        u.username as technician
        FROM service_orders o
        LEFT JOIN machines m ON o.machine_id = m.id
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        throw new Exception("Ordem não encontrada");
    }

    // Buscar anexos
    $attachments = $pdo->prepare("SELECT * FROM attachments WHERE order_id = ?");
    $attachments->execute([$orderId]);
    $attachments = $attachments->fetchAll();

    // Buscar comentários
    $comments = $pdo->prepare("SELECT 
        c.*, u.username 
        FROM order_comments c
        LEFT JOIN users u ON c.user_id = u.id
        WHERE c.order_id = ?
        ORDER BY c.created_at DESC");
    $comments->execute([$orderId]);
    $comments = $comments->fetchAll();

} catch (PDOException $e) {
    die("Erro ao carregar ordem: " . $e->getMessage());
}

// Processar novo comentário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    try {
        $comment = trim($_POST['comment']);
        if (empty($comment)) {
            throw new Exception("O comentário não pode estar vazio");
        }

        $stmt = $pdo->prepare("INSERT INTO order_comments 
            (order_id, user_id, comment)
            VALUES (?, ?, ?)");
        $stmt->execute([
            $orderId,
            $_SESSION['user_id'],
            htmlspecialchars($comment)
        ]);

        $_SESSION['success'] = "Comentário adicionado com sucesso!";
        header("Location: order_view.php?id=$orderId");
        exit();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Processar upload de arquivos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_files'])) {
    try {
        if (!empty($_FILES['attachments']['name'][0])) {
            $uploadDir = "uploads/";

            // Verificar se o diretório existe
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Tipos de arquivo permitidos
            $allowedTypes = ['pdf', 'jpg', 'jpeg', 'png'];
            $maxFileSize = 5 * 1024 * 1024; // 5MB

            foreach ($_FILES['attachments']['tmp_name'] as $key => $tmpName) {
                $fileName = $_FILES['attachments']['name'][$key];
                $fileSize = $_FILES['attachments']['size'][$key];
                $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                // Verificar tipo de arquivo
                if (!in_array($fileType, $allowedTypes)) {
                    throw new Exception("Tipo de arquivo não permitido: $fileName. Apenas PDF, JPG, JPEG e PNG são aceitos.");
                }

                // Verificar tamanho do arquivo
                if ($fileSize > $maxFileSize) {
                    throw new Exception("Arquivo muito grande: $fileName. O tamanho máximo permitido é 5MB.");
                }

                // Gerar nome único para o arquivo
                $uniqueFileName = uniqid() . '_' . basename($fileName);
                $targetPath = $uploadDir . $uniqueFileName;

                // Mover o arquivo para o diretório de upload
                if (move_uploaded_file($tmpName, $targetPath)) {
                    $stmt = $pdo->prepare("INSERT INTO attachments (order_id, file_path) VALUES (?, ?)");
                    $stmt->execute([$orderId, $uniqueFileName]);
                } else {
                    throw new Exception("Erro ao mover o arquivo: $fileName.");
                }
            }

            $_SESSION['success'] = "Arquivo(s) enviado(s) com sucesso!";
            header("Location: order_view.php?id=$orderId");
            exit();
        } else {
            throw new Exception("Nenhum arquivo selecionado para upload.");
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

include 'header.php';
?>

<div class="container">
    <div class="card shadow-lg mt-4">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0">
                    <i class="bi bi-clipboard-check"></i> Ordem #<?= $orderId ?>
                    <span class="badge bg-<?= match($order['status']) {
                        'aberta' => 'danger',
                        'em_andamento' => 'warning',
                        'concluida' => 'success'
                    } ?> ms-2">
                        <?= ucfirst($order['status']) ?>
                    </span>
                </h3>
                <div>
                    <?php if (!$is_technician): ?>
                        <a href="order_edit.php?id=<?= $orderId ?>" class="btn btn-warning me-2">
                            <i class="bi bi-pencil"></i> Editar
                        </a>
                    <?php endif; ?>
                    <a href="orders.php" class="btn btn-light">
                        <i class="bi bi-arrow-left"></i> Voltar
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <!-- Detalhes da Ordem -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-info-circle"></i> Detalhes da Ordem</h5>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-4">Máquina</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($order['machine_name']) ?></dd>

                                <dt class="col-sm-4">Tipo</dt>
                                <dd class="col-sm-8"><?= ucfirst($order['type']) ?></dd>

                                <dt class="col-sm-4">Prioridade</dt>
                                <dd class="col-sm-8">
                                    <span class="badge bg-<?= match($order['priority']) {
                                        'baixa' => 'success',
                                        'media' => 'warning',
                                        'alta' => 'danger'
                                    } ?>">
                                        <?= ucfirst($order['priority']) ?>
                                    </span>
                                </dd>

                                <dt class="col-sm-4">Descrição</dt>
                                <dd class="col-sm-8"><?= nl2br(htmlspecialchars($order['description'])) ?></dd>

                                <dt class="col-sm-4">Técnico</dt>
                                <dd class="col-sm-8"><?= $order['technician'] ?? 'Não atribuído' ?></dd>

                                <dt class="col-sm-4">Datas</dt>
                                <dd class="col-sm-8">
                                    <ul class="list-unstyled">
                                        <li>Criação: <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></li>
                                        <?php if ($order['scheduled_date']): ?>
                                        <li>Agendada: <?= date('d/m/Y H:i', strtotime($order['scheduled_date'])) ?></li>
                                        <?php endif; ?>
                                        <?php if ($order['completed_date']): ?>
                                        <li>Conclusão: <?= date('d/m/Y H:i', strtotime($order['completed_date'])) ?></li>
                                        <?php endif; ?>
                                    </ul>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Anexos -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-paperclip"></i> Anexos</h5>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                                <i class="bi bi-plus"></i> Adicionar
                            </button>
                        </div>
                        <div class="card-body">
                            <?php if (empty($attachments)): ?>
                                <div class="alert alert-info">Nenhum anexo encontrado.</div>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($attachments as $file): 
                                        $filePath = "uploads/" . $file['file_path'];
                                        $isImage = in_array(pathinfo($filePath, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif']);
                                    ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="bi <?= $isImage ? 'bi-image' : 'bi-file-earmark' ?> me-2"></i>
                                                <a href="<?= $filePath ?>" target="_blank"><?= $file['file_path'] ?></a>
                                            </div>
                                            <a href="attachment_delete.php?id=<?= $file['id'] ?>&order_id=<?= $orderId ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Tem certeza que deseja excluir este anexo?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Comentários -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-chat-dots"></i> Comentários</h5>
                </div>
                <div class="card-body">
                    <!-- Formulário para adicionar comentário -->
                    <form method="POST" class="mb-4">
                        <div class="mb-3">
                            <textarea name="comment" class="form-control" rows="3" 
                                      placeholder="Adicione um comentário..." required></textarea>
                        </div>
                        <button type="submit" name="add_comment" class="btn btn-primary">
                            <i class="bi bi-send"></i> Adicionar Comentário
                        </button>
                    </form>

                    <!-- Lista de comentários -->
                    <?php if (empty($comments)): ?>
                        <div class="alert alert-info">Nenhum comentário encontrado.</div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($comments as $comment): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong><?= htmlspecialchars($comment['username']) ?></strong>
                                            <small class="text-muted ms-2">
                                                <?= date('d/m/Y H:i', strtotime($comment['created_at'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                    <p class="mt-2 mb-0"><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para upload de arquivos -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Anexos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Selecione os arquivos</label>
                        <input type="file" name="attachments[]" class="form-control" multiple>
                        <small class="text-muted">Formatos permitidos: PDF, JPG, JPEG, PNG (Máx. 5MB cada)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="upload_files" class="btn btn-primary">Enviar Arquivos</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
