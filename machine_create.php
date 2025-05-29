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

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar dados
        $required = ['sector', 'equipment', 'model', 'manufacturer', 'acquisition_date'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("O campo " . strtoupper($field) . " é obrigatório");
            }
        }

        // Inserir no banco
        $stmt = $pdo->prepare("INSERT INTO machines 
            (sector, equipment, model, manufacturer, acquisition_date, 
             axis, rotor, gasket, motor, hp, rpm, amp, motor_bearing, description)
            VALUES 
            (:sector, :equipment, :model, :manufacturer, :acquisition_date,
             :axis, :rotor, :gasket, :motor, :hp, :rpm, :amp, :motor_bearing, :description)");

        $data = [
            ':sector' => htmlspecialchars($_POST['sector']),
            ':equipment' => htmlspecialchars($_POST['equipment']),
            ':model' => htmlspecialchars($_POST['model']),
            ':manufacturer' => htmlspecialchars($_POST['manufacturer']),
            ':acquisition_date' => $_POST['acquisition_date'],
            ':axis' => htmlspecialchars($_POST['axis'] ?? null),
            ':rotor' => htmlspecialchars($_POST['rotor'] ?? null),
            ':gasket' => htmlspecialchars($_POST['gasket'] ?? null),
            ':motor' => htmlspecialchars($_POST['motor'] ?? null),
            ':hp' => htmlspecialchars($_POST['hp'] ?? null),
            ':rpm' => htmlspecialchars($_POST['rpm'] ?? null),
            ':amp' => htmlspecialchars($_POST['amp'] ?? null),
            ':motor_bearing' => htmlspecialchars($_POST['motor_bearing'] ?? null),
            ':description' => htmlspecialchars($_POST['description'] ?? null)
        ];

        if ($stmt->execute($data)) {
            $_SESSION['success'] = "Equipamento cadastrado com sucesso!";
            header('Location: machines.php');
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
                <h3 class="mb-0"><i class="bi bi-plus-circle"></i> Novo Equipamento</h3>
                <a href="machines.php" class="btn btn-light">
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
                    <!-- SETOR -->
                    <div class="col-md-6">
                        <label for="sector" class="form-label">Setor <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="sector" name="sector" 
                               value="<?= htmlspecialchars($_POST['sector'] ?? '') ?>" required>
                        <div class="invalid-feedback">
                            Por favor insira o Setor
                        </div>
                    </div>

                    <!-- EQUIPAMENTO -->
                    <div class="col-md-6">
                        <label for="equipment" class="form-label">Equipamento <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="equipment" name="equipment" 
                               value="<?= htmlspecialchars($_POST['equipment'] ?? '') ?>" required>
                        <div class="invalid-feedback">
                            Por favor insira o Equipamento
                        </div>
                    </div>

                    <!-- MODELO -->
                    <div class="col-md-6">
                        <label for="model" class="form-label">Modelo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="model" name="model" 
                               value="<?= htmlspecialchars($_POST['model'] ?? '') ?>" required>
                        <div class="invalid-feedback">
                            Por favor insira o Modelo
                        </div>
                    </div>

                    <!-- FABRICANTE -->
                    <div class="col-md-6">
                        <label for="manufacturer" class="form-label">Fabricante<span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="manufacturer" name="manufacturer" 
                               value="<?= htmlspecialchars($_POST['manufacturer'] ?? '') ?>" required>
                        <div class="invalid-feedback">
                            Por favor insira o Fabricante
                        </div>
                    </div>

                    <!-- EIXO -->
                    <div class="col-md-6">
                        <label for="axis" class="form-label">Eixo</label>
                        <input type="text" class="form-control" id="axis" name="axis" 
                               value="<?= htmlspecialchars($_POST['axis'] ?? '') ?>">
                    </div>

                    <!-- ROTOR -->
                    <div class="col-md-6">
                        <label for="rotor" class="form-label">Rotor</label>
                        <input type="text" class="form-control" id="rotor" name="rotor" 
                               value="<?= htmlspecialchars($_POST['rotor'] ?? '') ?>">
                    </div>

                    <!-- GAXETA -->
                    <div class="col-md-6">
                        <label for="gasket" class="form-label">Gaxeta</label>
                        <input type="text" class="form-control" id="gasket" name="gasket" 
                               value="<?= htmlspecialchars($_POST['gasket'] ?? '') ?>">
                    </div>

                    <!-- MOTOR -->
                    <div class="col-md-6">
                        <label for="motor" class="form-label">Motor</label>
                        <input type="text" class="form-control" id="motor" name="motor" 
                               value="<?= htmlspecialchars($_POST['motor'] ?? '') ?>">
                    </div>

                    <!-- CV -->
                    <div class="col-md-4">
                        <label for="hp" class="form-label">CV</label>
                        <input type="text" class="form-control" id="hp" name="hp" 
                               value="<?= htmlspecialchars($_POST['hp'] ?? '') ?>">
                    </div>

                    <!-- RPM -->
                    <div class="col-md-4">
                        <label for="rpm" class="form-label">RPM</label>
                        <input type="text" class="form-control" id="rpm" name="rpm" 
                               value="<?= htmlspecialchars($_POST['rpm'] ?? '') ?>">
                    </div>

                    <!-- AMP -->
                    <div class="col-md-4">
                        <label for="amp" class="form-label">AMP</label>
                        <input type="text" class="form-control" id="amp" name="amp" 
                               value="<?= htmlspecialchars($_POST['amp'] ?? '') ?>">
                    </div>

                    <!-- ROLAMENTO DO MOTOR -->
                    <div class="col-md-6">
                        <label for="motor_bearing" class="form-label">Rolamento do Motor</label>
                        <input type="text" class="form-control" id="motor_bearing" name="motor_bearing" 
                               value="<?= htmlspecialchars($_POST['motor_bearing'] ?? '') ?>">
                    </div>

                    <!-- Data de Aquisição -->
                    <div class="col-md-6">
                        <label for="acquisition_date" class="form-label">Data de Aquisição <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="acquisition_date" name="acquisition_date" 
                               value="<?= htmlspecialchars($_POST['acquisition_date'] ?? '') ?>" required>
                        <div class="invalid-feedback">
                            Selecione a data de aquisição
                        </div>
                    </div>

                    <!-- Descrição -->
                    <div class="col-12">
                        <label for="description" class="form-label">Outros</label>
                        <textarea class="form-control" id="description" name="description" 
                                  rows="3"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <!-- Botões -->
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-save"></i> Salvar Equipamento
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
