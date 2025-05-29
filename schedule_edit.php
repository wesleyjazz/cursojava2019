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
$success = null;

// Obter ID do agendamento
$scheduleId = $_GET['id'] ?? null;
if (!$scheduleId) {
    header('Location: schedules.php');
    exit();
}

// Buscar dados do agendamento
try {
    $stmt = $pdo->prepare("SELECT 
        s.*, m.name as machine_name
        FROM maintenance_schedules s
        LEFT JOIN machines m ON s.machine_id = m.id
        WHERE s.id = ?");
    $stmt->execute([$scheduleId]);
    $schedule = $stmt->fetch();

    if (!$schedule) {
        throw new Exception("Agendamento não encontrado");
    }

    // Buscar lista de máquinas
    $machines = $pdo->query("SELECT id, name FROM machines ORDER BY name")->fetchAll();
} catch (PDOException $e) {
    die("Erro ao carregar agendamento: " . $e->getMessage());
}

// Processar formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validação dos campos
        $required = ['machine_id', 'type', 'frequency', 'next_date'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("O campo " . ucfirst($field) . " é obrigatório");
            }
        }

        // Atualizar agendamento
        $stmt = $pdo->prepare("UPDATE maintenance_schedules SET
            machine_id = :machine_id,
            type = :type,
            frequency = :frequency,
            next_date = :next_date,
            notes = :notes
            WHERE id = :id");

        $data = [
            ':machine_id' => $_POST['machine_id'],
            ':type' => $_POST['type'],
            ':frequency' => $_POST['frequency'],
            ':next_date' => $_POST['next_date'],
            ':notes' => htmlspecialchars($_POST['notes']),
            ':id' => $scheduleId
        ];

        if ($stmt->execute($data)) {
            $_SESSION['success'] = "Agendamento atualizado com sucesso!";
            header('Location: schedules.php');
            exit();
        } else {
            throw new Exception("Erro ao atualizar agendamento");
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
                <h3 class="mb-0"><i class="bi bi-calendar-event"></i> Editar Agendamento</h3>
                <a href="schedules.php" class="btn btn-light">
                    <i class="bi bi-arrow-left"></i> Voltar
                </a>
            </div>
        </div>

        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" class="needs-validation" novalidate>
                <div class="row g-3">
                    <!-- Máquina -->
                    <div class="col-md-6">
                        <label class="form-label">Máquina <span class="text-danger">*</span></label>
                        <select name="machine_id" class="form-select" required>
                            <option value="">Selecione uma máquina</option>
                            <?php foreach ($machines as $machine): ?>
                                <option value="<?= $machine['id'] ?>" 
                                    <?= $schedule['machine_id'] == $machine['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($machine['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">
                            Selecione uma máquina.
                        </div>
                    </div>

                    <!-- Tipo de Manutenção -->
                    <div class="col-md-6">
                        <label class="form-label">Tipo <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            <option value="preventiva" <?= $schedule['type'] === 'preventiva' ? 'selected' : '' ?>>Preventiva</option>
                            <option value="corretiva" <?= $schedule['type'] === 'corretiva' ? 'selected' : '' ?>>Corretiva</option>
                        </select>
                        <div class="invalid-feedback">
                            Selecione o tipo de manutenção.
                        </div>
                    </div>

                    <!-- Frequência -->
                    <div class="col-md-6">
                        <label class="form-label">Frequência <span class="text-danger">*</span></label>
                        <select name="frequency" class="form-select" required>
                            <option value="diaria" <?= $schedule['frequency'] === 'diaria' ? 'selected' : '' ?>>Diária</option>
                            <option value="semanal" <?= $schedule['frequency'] === 'semanal' ? 'selected' : '' ?>>Semanal</option>
                            <option value="mensal" <?= $schedule['frequency'] === 'mensal' ? 'selected' : '' ?>>Mensal</option>
                            <option value="anual" <?= $schedule['frequency'] === 'anual' ? 'selected' : '' ?>>Anual</option>
                        </select>
                        <div class="invalid-feedback">
                            Selecione a frequência.
                        </div>
                    </div>

                    <!-- Próxima Data -->
                    <div class="col-md-6">
                        <label class="form-label">Próxima Data <span class="text-danger">*</span></label>
                        <input type="date" name="next_date" class="form-control" 
                               value="<?= htmlspecialchars($schedule['next_date']) ?>" required>
                        <div class="invalid-feedback">
                            Selecione a próxima data.
                        </div>
                    </div>

                    <!-- Observações -->
                    <div class="col-12">
                        <label class="form-label">Observações</label>
                        <textarea name="notes" class="form-control" rows="4"><?= htmlspecialchars($schedule['notes']) ?></textarea>
                    </div>

                    <!-- Botões -->
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-save"></i> Salvar Alterações
                        </button>
                        <a href="schedules.php" class="btn btn-secondary btn-lg">
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
