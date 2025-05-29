<?php
session_start();
require_once 'db.php';
#require_once 'app/controllers/AuthController.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$pdo = Database::getInstance();
$error = null;

// Obter ID da ordem
$orderId = $_GET['id'] ?? null;
if (!$orderId) {
    header('Location: orders.php');
    exit();
}

try {
    // Verificar se a ordem existe
    $stmt = $pdo->prepare("SELECT id FROM service_orders WHERE id = ?");
    $stmt->execute([$orderId]);
    if (!$stmt->fetch()) {
        throw new Exception("Ordem não encontrada");
    }

    // Buscar anexos
    $attachments = $pdo->prepare("SELECT * FROM attachments WHERE order_id = ?");
    $attachments->execute([$orderId]);
    $attachments = $attachments->fetchAll();

} catch (Exception $e) {
    $error = $e->getMessage();
}

include 'header.php';
?>

<div class="container">
    <div class="card shadow-lg mt-4">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0">
                    <i class="bi bi-paperclip"></i> Anexos da Ordem #<?= $orderId ?>
                </h3>
                <a href="order_view.php?id=<?= $orderId ?>" class="btn btn-light">
                    <i class="bi bi-arrow-left"></i> Voltar para Ordem
                </a>
            </div>
        </div>

        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <?php if (empty($attachments)): ?>
                <div class="alert alert-info">Nenhum anexo encontrado para esta ordem.</div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($attachments as $file): 
                        $filePath = "uploads/" . $file['file_path'];
                        $isImage = in_array(pathinfo($filePath, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif']);
                    ?>
                        <div class="col-md-4 mb-4">
                            <div class="card">
                                <?php if ($isImage): ?>
                                    <img src="<?= $filePath ?>" 
                                         class="card-img-top preview-image" 
                                         alt="Anexo"
                                         style="height: 200px; object-fit: cover; cursor: pointer"
                                         data-bs-toggle="modal" 
                                         data-bs-target="#imageModal"
                                         data-src="<?= $filePath ?>">
                                <?php else: ?>
                                    <div class="card-body text-center">
                                        <i class="bi bi-file-earmark-text display-4 text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-footer">
                                    <a href="<?= $filePath ?>" 
                                       target="_blank" 
                                       class="btn btn-primary btn-sm w-100">
                                        <i class="bi bi-download"></i> Visualizar
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal para visualização de imagens -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Visualização de Imagem</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img src="" class="img-fluid" id="modalImage">
            </div>
        </div>
    </div>
</div>

<script>
// Atualizar a imagem no modal
document.querySelectorAll('.preview-image').forEach(img => {
    img.addEventListener('click', () => {
        document.getElementById('modalImage').src = img.dataset.src;
    });
});
</script>

<?php include 'footer.php'; ?>
