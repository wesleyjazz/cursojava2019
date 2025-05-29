<?php
session_start();
require_once 'db.php';
#require_once 'app/controllers/AuthController.php';

#if (!isset($_SESSION['user_id']) {
#    header('Location: login.php');
#    exit();
#}

$pdo = Database::getInstance();
$error = null;
$success = null;

// Buscar máquinas
$machines = $pdo->query("SELECT id, name FROM machines ORDER BY name")->fetchAll();

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validação
        $required = ['machine_id', 'type', 'frequency', 'next_date'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("O campo " . ucfirst($field) . " é obrigatório");
            }
        }

        // Inserir agendamento
        $stmt = $pdo->prepare("INSERT INTO maintenance_schedules 
            (machine_id, type, frequency, next_date, notes)
            VALUES (:machine_id, :type, :frequency, :next_date, :notes)");

        $data = [
            ':machine_id' => $_POST['machine_id'],
            ':type' => $_POST['type'],
            ':frequency' => $_POST['frequency'],
            ':next_date' => $_POST['next_date'],
            ':notes' => htmlspecialchars($_POST['notes'])
        ];

        if ($stmt->execute($data)) {
            $_SESSION['success'] = "Agendamento criado com sucesso!";
            header('Location: schedules.php');
            exit();
        }

    } catch (PDOException $e) {
        $error = "Erro no banco de dados: " . $e->getMessage();
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
                <h3 class="mb-0"><i class="bi bi-calendar-plus"></i> Novo Agendamento</h3>
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

                    <!-- Tipo de Manutenção -->
                    <div class="col-md-6">
                        <label class="form-label">Tipo <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            <option value="">Selecione o tipo</option>
                            <option value="preventiva" <?= isset($_POST['type']) && $_POST['type'] === 'preventiva' ? 'selected' : '' ?>>Preventiva</option>
                            <option value="corretiva" <?= isset($_POST['type']) && $_POST['type'] === 'corretiva' ? 'selected' : '' ?>>Corretiva</option>
                        </select>
                        <div class="invalid-feedback">
                            Selecione o tipo de manutenção
                        </div>
                    </div>

                    <!-- Frequência -->
                    <div class="col-md-6">
                        <label class="form-label">Frequência <span class="text-danger">*</span></label>
                        <select name="frequency" class="form-select" required>
                            <option value="">Selecione a frequência</option>
                            <option value="diaria" <?= isset($_POST['frequency']) && $_POST['frequency'] === 'diaria' ? 'selected' : '' ?>>Diária</option>
                            <option value="semanal" <?= isset($_POST['frequency']) && $_POST['frequency'] === 'semanal' ? 'selected' : '' ?>>Semanal</option>
                            <option value="mensal" <?= isset($_POST['frequency']) && $_POST['frequency'] === 'mensal' ? 'selected' : '' ?>>Mensal</option>
                            <option value="anual" <?= isset($_POST['frequency']) && $_POST['frequency'] === 'anual' ? 'selected' : '' ?>>Anual</option>
                        </select>
                        <div class="invalid-feedback">
                            Selecione a frequência
                        </div>
                    </div>

                    <!-- Próxima Data -->
                    <div class="col-md-6">
                        <label class="form-label">Próxima Data <span class="text-danger">*</span></label>
                        <input type="date" name="next_date" 
                               value="<?= $_POST['next_date'] ?? '' ?>" 
                               class="form-control" required
                               min="<?= date('Y-m-d') ?>">
                        <div class="invalid-feedback">
                            Selecione uma data válida
                        </div>
                    </div>

                    <!-- Observações -->
                    <div class="col-12">
                        <label class="form-label">Observações</label>
                        <textarea name="notes" class="form-control" rows="3"
                                  placeholder="Insira quaisquer observações relevantes..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                    </div>

                    <!-- Botões -->
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-save"></i> Salvar Agendamento
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
