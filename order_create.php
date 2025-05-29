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

// Buscar dados para os selects
$machines = $pdo->query("SELECT id, name FROM machines")->fetchAll();
$technicians = $pdo->query("SELECT id, username FROM users WHERE role = 'technician'")->fetchAll();
$setores = $pdo->query("SELECT id, nome FROM setores ORDER BY nome")->fetchAll();

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validação
        $required = ['machine_id', 'type', 'work_type', 'description', 'priority', 'department'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("O campo " . ucfirst($field) . " é obrigatório");
            }
        }

        // Inserir ordem
        $stmt = $pdo->prepare("INSERT INTO service_orders 
            (machine_id, type, work_type, description, priority, status, 
             user_id, scheduled_date, department, requester_name)
            VALUES (:machine_id, :type, :work_type, :description, :priority, 'aberta', 
                    :user_id, :scheduled_date, :department, :requester_name)");

        $data = [
            ':machine_id' => $_POST['machine_id'],
            ':type' => $_POST['type'],
            ':work_type' => $_POST['work_type'],
            ':description' => htmlspecialchars($_POST['description']),
            ':priority' => $_POST['priority'],
            ':user_id' => !empty($_POST['technician']) ? $_POST['technician'] : null,
            ':scheduled_date' => $_POST['scheduled_date'],
            ':department' => $_POST['department'],
            ':requester_name' => $_POST['requester_name'] ?? null
        ];
    
        if ($stmt->execute($data)) {
            $orderId = $pdo->lastInsertId();

            // Processar anexos
            if (!empty($_FILES['attachments']['name'][0])) {
                $uploadDir = "uploads/";
                
                foreach ($_FILES['attachments']['tmp_name'] as $key => $tmpName) {
                    $fileName = uniqid() . '_' . basename($_FILES['attachments']['name'][$key]);
                    $targetPath = $uploadDir . $fileName;

                    if (move_uploaded_file($tmpName, $targetPath)) {
                        $stmt = $pdo->prepare("INSERT INTO attachments (order_id, file_path) VALUES (?, ?)");
                        $stmt->execute([$orderId, $fileName]);
                    }
                }
            }

            $_SESSION['success'] = "Ordem criada com sucesso!";
            header('Location: orders.php');
            exit();
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
                <h3 class="mb-0"><i class="bi bi-clipboard-plus"></i> Nova Ordem de Serviço</h3>
                <a href="orders.php" class="btn btn-light">
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
                                <?= isset($_POST['machine_id']) && $_POST['machine_id'] == $machine['id'] ? 'selected' : '' ?>>
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
                            <option value="">Selecione o tipo</option>
                            <option value="preventiva" <?= isset($_POST['type']) && $_POST['type'] === 'preventiva' ? 'selected' : '' ?>>Preventiva</option>
                            <option value="corretiva" <?= isset($_POST['type']) && $_POST['type'] === 'corretiva' ? 'selected' : '' ?>>Corretiva</option>
                            <option value="preditiva" <?= isset($_POST['type']) && $_POST['type'] === 'preditiva' ? 'selected' : '' ?>>Preditiva</option>
                        </select>
                        <div class="invalid-feedback">
                            Selecione o tipo de ordem
                        </div>
                    </div>

                    <!-- Tipo de Trabalho -->
                    <div class="col-md-6">
                        <label class="form-label">Tipo de Trabalho <span class="text-danger">*</span></label>
                        <select name="work_type" class="form-select" required>
                            <option value="">Selecione o tipo de trabalho</option>
                            <option value="elétrico" <?= isset($_POST['work_type']) && $_POST['work_type'] === 'elétrico' ? 'selected' : '' ?>>Elétrico</option>
                            <option value="mecânico" <?= isset($_POST['work_type']) && $_POST['work_type'] === 'mecânico' ? 'selected' : '' ?>>Mecânico</option>
                            <option value="civil" <?= isset($_POST['work_type']) && $_POST['work_type'] === 'civil' ? 'selected' : '' ?>>Civil</option>
                            <option value="outros" <?= isset($_POST['work_type']) && $_POST['work_type'] === 'outros' ? 'selected' : '' ?>>Outros</option>
                        </select>
                        <div class="invalid-feedback">
                            Selecione o tipo de trabalho
                        </div>
                    </div>

                    <!-- Prioridade -->
                    <div class="col-md-6">
                        <label class="form-label">Prioridade <span class="text-danger">*</span></label>
                        <select name="priority" class="form-select" required>
                            <option value="">Selecione a prioridade</option>
                            <option value="baixa" <?= isset($_POST['priority']) && $_POST['priority'] === 'baixa' ? 'selected' : '' ?>>Baixa</option>
                            <option value="media" <?= isset($_POST['priority']) && $_POST['priority'] === 'media' ? 'selected' : '' ?>>Média</option>
                            <option value="alta" <?= isset($_POST['priority']) && $_POST['priority'] === 'alta' ? 'selected' : '' ?>>Alta</option>
                        </select>
                        <div class="invalid-feedback">
                            Selecione a prioridade
                        </div>
                    </div>


			
                    <!-- Campo Setor -->
                    <div class="col-md-6">
                        <label class="form-label">Setor <span class="text-danger">*</span></label>
                        <select name="department" class="form-select" required>
                            <option value="">Selecione o setor</option>
                            <?php foreach ($setores as $setor): ?>
                            <option value="<?= $setor['id'] ?>" 
                                <?= isset($_POST['department']) && $_POST['setor_id'] == $setor['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($setor['nome']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">
                            Por favor selecione o setor
                        </div>
                    </div>



                    <!-- Solicitante -->
                    <div class="col-md-6">
                        <label class="form-label">Solicitante</label>
                        <input type="text" name="requester_name" class="form-control"
                               value="<?= htmlspecialchars($_POST['requester_name'] ?? '') ?>">
                    </div>

                    <!-- Técnico Responsável -->
                    <div class="col-md-6">
                        <label class="form-label">Técnico</label>
                        <select name="technician" class="form-select">
                            <option value="">Não atribuído</option>
                            <?php foreach ($technicians as $tech): ?>
                            <option value="<?= $tech['id'] ?>" 
                                <?= isset($_POST['technician']) && $_POST['technician'] == $tech['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tech['username']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Data Agendada -->
                    <div class="col-md-6">
                        <label class="form-label">Data Agendada</label>
                        <input type="datetime-local" name="scheduled_date" 
                               value="<?= $_POST['scheduled_date'] ?? '' ?>" 
                               class="form-control">
                    </div>

                    <!-- Descrição -->
                    <div class="col-12">
                        <label class="form-label">Descrição <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="5" required
                                  placeholder="Descreva detalhadamente o serviço a ser realizado..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        <div class="invalid-feedback">
                            Insira uma descrição para a ordem
                        </div>
                    </div>

                    <!-- Anexos -->
                    <div class="col-12">
                        <label class="form-label">Anexos</label>
                        <input type="file" name="attachments[]" multiple 
                               class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted">Formatos permitidos: PDF, JPG, PNG (Máx. 5MB cada)</small>
                    </div>

                    <!-- Botões -->
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-save"></i> Criar Ordem
                        </button>
                        <button type="reset" class="btn btn-secondary btn-lg">
                            <i class="bi bi-eraser"></i> Limpar
                        </button>
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
