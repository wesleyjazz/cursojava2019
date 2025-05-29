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

    // Buscar listas para dropdowns
    $machines = $pdo->query("SELECT id, name FROM machines ORDER BY name")->fetchAll();
    $technicians = $pdo->query("SELECT id, username FROM users WHERE role = 'technician'")->fetchAll();

    // Buscar anexos existentes
    $attachments = $pdo->prepare("SELECT * FROM attachments WHERE order_id = ?");
    $attachments->execute([$orderId]);
    $attachments = $attachments->fetchAll();

} catch (PDOException $e) {
    die("Erro ao carregar ordem: " . $e->getMessage());
}

// Processar formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validação
        $required = ['machine_id', 'type', 'work_type', 'priority', 'status'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("O campo " . ucfirst($field) . " é obrigatório");
            }
        }

        // Atualizar ordem
        $stmt = $pdo->prepare("UPDATE service_orders SET
            machine_id = :machine_id,
            type = :type,
            work_type = :work_type,
            description = :description,
            priority = :priority,
            status = :status,
            scheduled_date = :scheduled_date,
            user_id = :user_id
            WHERE id = :id");

        $data = [
            ':machine_id' => $_POST['machine_id'],
            ':type' => $_POST['type'],
            ':work_type' => $_POST['work_type'],
            ':description' => htmlspecialchars($_POST['description']),
            ':priority' => $_POST['priority'],
            ':status' => $_POST['status'],
            ':scheduled_date' => $_POST['scheduled_date'],
            ':user_id' => $_POST['technician'],
            ':id' => $orderId
        ];

        if ($stmt->execute($data)) {
            // Processar upload de arquivos
            if (!empty($_FILES['attachments']['name'][0])) {
                $uploadDir = "uploads/";

                // Verificar se o diretório existe
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true); // Criar o diretório se não existir
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
            }

            $_SESSION['success'] = "Ordem atualizada com sucesso!";
            header("Location: order_view.php?id=$orderId");
            exit();
        } else {
            throw new Exception("Erro ao atualizar ordem");
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
                    <i class="bi bi-pencil-square"></i> Editar Ordem #<?= $orderId ?>
                </h3>
                <a href="order_view.php?id=<?= $orderId ?>" class="btn btn-light">
                    <i class="bi bi-arrow-left"></i> Voltar
                </a>
            </div>
        </div>

        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <div class="row g-3">
                    <!-- Seleção de Máquina -->
                    <div class="col-md-6">
                        <label class="form-label">Máquina <span class="text-danger">*</span></label>
                        <select name="machine_id" class="form-select" required>
                            <option value="">Selecione uma máquina</option>
                            <?php foreach ($machines as $machine): ?>
                                <option value="<?= $machine['id'] ?>" 
                                    <?= $machine['id'] == $order['machine_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($machine['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">
                            Selecione uma máquina
                        </div>
                    </div>

                    <!-- Tipo de Ordem -->
                    <div class="col-md-6">
                        <label class="form-label">Tipo <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            <option value="preventiva" <?= $order['type'] === 'preventiva' ? 'selected' : '' ?>>Preventiva</option>
                            <option value="corretiva" <?= $order['type'] === 'corretiva' ? 'selected' : '' ?>>Corretiva</option>
                            <option value="preditiva" <?= $order['type'] === 'preditiva' ? 'selected' : '' ?>>Preditiva</option>
                        </select>
                    </div>

                    <!-- Tipo de Trabalho -->
                    <div class="col-md-6">
                        <label class="form-label">Tipo de Trabalho <span class="text-danger">*</span></label>
                        <select name="work_type" class="form-select" required>
                            <option value="elétrico" <?= $order['work_type'] === 'elétrico' ? 'selected' : '' ?>>Elétrico</option>
                            <option value="mecânico" <?= $order['work_type'] === 'mecânico' ? 'selected' : '' ?>>Mecânico</option>
                            <option value="civil" <?= $order['work_type'] === 'civil' ? 'selected' : '' ?>>Civil</option>
                            <option value="outros" <?= $order['work_type'] === 'outros' ? 'selected' : '' ?>>Outros</option>
                        </select>
                    </div>

                    <!-- Prioridade -->
                    <div class="col-md-6">
                        <label class="form-label">Prioridade <span class="text-danger">*</span></label>
                        <select name="priority" class="form-select" required>
                            <option value="baixa" <?= $order['priority'] === 'baixa' ? 'selected' : '' ?>>Baixa</option>
                            <option value="media" <?= $order['priority'] === 'media' ? 'selected' : '' ?>>Média</option>
                            <option value="alta" <?= $order['priority'] === 'alta' ? 'selected' : '' ?>>Alta</option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div class="col-md-4">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="aberta" <?= $order['status'] === 'aberta' ? 'selected' : '' ?>>Aberta</option>
                            <option value="em_andamento" <?= $order['status'] === 'em_andamento' ? 'selected' : '' ?>>Em Andamento</option>
                            <option value="concluida" <?= $order['status'] === 'concluida' ? 'selected' : '' ?>>Concluída</option>
                        </select>
                    </div>

                    <!-- Data Agendada -->
                    <div class="col-md-4">
                        <label class="form-label">Data Agendada</label>
                        <input type="datetime-local" name="scheduled_date" 
                               value="<?= date('Y-m-d\TH:i', strtotime($order['scheduled_date'])) ?>" 
                               class="form-control">
                    </div>

                    <!-- Técnico Responsável -->
                    <div class="col-md-4">
                        <label class="form-label">Técnico</label>
                        <select name="technician" class="form-select">
                            <option value="">Não atribuído</option>
                            <?php foreach ($technicians as $tech): ?>
                                <option value="<?= $tech['id'] ?>" 
                                    <?= $tech['id'] == $order['user_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tech['username']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Descrição -->
                    <div class="col-12">
                        <label class="form-label">Descrição <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="5" required><?= htmlspecialchars($order['description']) ?></textarea>
                    </div>

                    <!-- Anexar Arquivos -->
                    <div class="col-12">
                        <label class="form-label">Anexar Arquivos</label>
                        <input type="file" name="attachments[]" class="form-control" multiple>
                        <small class="form-text text-muted">Selecione um ou mais arquivos para anexar.</small>
                        
                        <!-- Mostrar anexos existentes -->
                        <?php if (!empty($attachments)): ?>
                            <div class="mt-3">
                                <h6>Anexos Existentes:</h6>
                                <ul class="list-group">
                                    <?php foreach ($attachments as $attachment): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <a href="uploads/<?= htmlspecialchars($attachment['file_path']) ?>" target="_blank">
                                                <?= htmlspecialchars($attachment['file_path']) ?>
                                            </a>
                                            <a href="attachment_delete.php?id=<?= $attachment['id'] ?>&order_id=<?= $orderId ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Tem certeza que deseja excluir este anexo?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Botões -->
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-save"></i> Salvar Alterações
                        </button>
                        <a href="order_view.php?id=<?= $orderId ?>" class="btn btn-secondary btn-lg">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Validação do formulário
(() => {
  'use strict'
  const forms = document.querySelectorAll('.needs-validation')
  Array.from(forms).forEach(form => {
    form.addEventListener('submit', event => {
      if (!form.checkValidity()) {
        event.preventDefault()
        event.stopPropagation()
      }
      form.classList.add('was-validated')
    }, false)
  })
})()
</script>

<?php include 'footer.php'; ?>
