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

// Obter ID do equipamento
$machineId = $_GET['id'] ?? null;
if (!$machineId) {
    header('Location: machines.php');
    exit();
}

// Buscar dados atuais do equipamento
try {
    $stmt = $pdo->prepare("SELECT * FROM machines WHERE id = ?");
    $stmt->execute([$machineId]);
    $machine = $stmt->fetch();

    if (!$machine) {
        throw new Exception("Equipamento não encontrado");
    }
} catch (PDOException $e) {
    die("Erro ao carregar equipamento: " . $e->getMessage());
}

// Processar formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validação dos campos obrigatórios
        $required = ['sector', 'equipment', 'model', 'manufacturer', 'acquisition_date'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("O campo " . strtoupper($field) . " é obrigatório");
            }
        }

        // Atualizar equipamento
        $stmt = $pdo->prepare("UPDATE machines SET
            sector = :sector,
            equipment = :equipment,
            model = :model,
            manufacturer = :manufacturer,
            acquisition_date = :acquisition_date,
            axis = :axis,
            rotor = :rotor,
            gasket = :gasket,
            motor = :motor,
            hp = :hp,
            rpm = :rpm,
            amp = :amp,
            motor_bearing = :motor_bearing,
            description = :description
            WHERE id = :id");

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
            ':description' => htmlspecialchars($_POST['description'] ?? null),
            ':id' => $machineId
        ];

        if ($stmt->execute($data)) {
            $_SESSION['success'] = "Equipamento atualizado com sucesso!";
            header('Location: machine_view.php?id=' . $machineId);
            exit();
        } else {
            throw new Exception("Erro ao atualizar equipamento");
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
                <h3 class="mb-0"><i class="bi bi-pencil"></i> Editar Equipamento</h3>
                <a href="machines_view.php?id=<?= $machineId ?>" class="btn btn-light">
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
                        <label for="sector" class="form-label">SETOR <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="sector" name="sector" 
                               value="<?= htmlspecialchars($machine['sector']) ?>" required>
                        <div class="invalid-feedback">
                            Por favor insira o SETOR
                        </div>
                    </div>

                    <!-- EQUIPAMENTO -->
                    <div class="col-md-6">
                        <label for="equipment" class="form-label">EQUIPAMENTO <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="equipment" name="equipment" 
                               value="<?= htmlspecialchars($machine['equipment']) ?>" required>
                        <div class="invalid-feedback">
                            Por favor insira o EQUIPAMENTO
                        </div>
                    </div>

                    <!-- MODELO -->
                    <div class="col-md-6">
                        <label for="model" class="form-label">MODELO <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="model" name="model" 
                               value="<?= htmlspecialchars($machine['model']) ?>" required>
                        <div class="invalid-feedback">
                            Por favor insira o MODELO
                        </div>
                    </div>

                    <!-- FABRICANTE -->
                    <div class="col-md-6">
                        <label for="manufacturer" class="form-label">FABRICANTE <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="manufacturer" name="manufacturer" 
                               value="<?= htmlspecialchars($machine['manufacturer']) ?>" required>
                        <div class="invalid-feedback">
                            Por favor insira o FABRICANTE
                        </div>
                    </div>

                    <!-- EIXO -->
                    <div class="col-md-6">
                        <label for="axis" class="form-label">EIXO</label>
                        <input type="text" class="form-control" id="axis" name="axis" 
                               value="<?= htmlspecialchars($machine['axis']) ?>">
                    </div>

                    <!-- ROTOR -->
                    <div class="col-md-6">
                        <label for="rotor" class="form-label">ROTOR</label>
                        <input type="text" class="form-control" id="rotor" name="rotor" 
                               value="<?= htmlspecialchars($machine['rotor']) ?>">
                    </div>

                    <!-- GAXETA -->
                    <div class="col-md-6">
                        <label for="gasket" class="form-label">GAXETA</label>
                        <input type="text" class="form-control" id="gasket" name="gasket" 
                               value="<?= htmlspecialchars($machine['gasket']) ?>">
                    </div>

                    <!-- MOTOR -->
                    <div class="col-md-6">
                        <label for="motor" class="form-label">MOTOR</label>
                        <input type="text" class="form-control" id="motor" name="motor" 
                               value="<?= htmlspecialchars($machine['motor']) ?>">
                    </div>

                    <!-- CV -->
                    <div class="col-md-4">
                        <label for="hp" class="form-label">CV</label>
                        <input type="text" class="form-control" id="hp" name="hp" 
                               value="<?= htmlspecialchars($machine['hp']) ?>">
                    </div>

                    <!-- RPM -->
                    <div class="col-md-4">
                        <label for="rpm" class="form-label">RPM</label>
                        <input type="text" class="form-control" id="rpm" name="rpm" 
                               value="<?= htmlspecialchars($machine['rpm']) ?>">
                    </div>

                    <!-- AMP -->
                    <div class="col-md-4">
                        <label for="amp" class="form-label">AMP</label>
                        <input type="text" class="form-control" id="amp" name="amp" 
                               value="<?= htmlspecialchars($machine['amp']) ?>">
                    </div>

                    <!-- ROLAMENTO DO MOTOR -->
                    <div class="col-md-6">
                        <label for="motor_bearing" class="form-label">ROLAMENTO DO MOTOR</label>
                        <input type="text" class="form-control" id="motor_bearing" name="motor_bearing" 
                               value="<?= htmlspecialchars($machine['motor_bearing']) ?>">
                    </div>

                    <!-- Data de Aquisição -->
                    <div class="col-md-6">
                        <label for="acquisition_date" class="form-label">Data de Aquisição <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="acquisition_date" name="acquisition_date" 
                               value="<?= htmlspecialchars($machine['acquisition_date']) ?>" required>
                        <div class="invalid-feedback">
                            Selecione a data de aquisição
                        </div>
                    </div>

                    <!-- Descrição -->
                    <div class="col-12">
                        <label for="description" class="form-label">Descrição</label>
                        <textarea class="form-control" id="description" name="description" 
                                  rows="3"><?= htmlspecialchars($machine['description']) ?></textarea>
                    </div>

                    <!-- Botões -->
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-save"></i> Salvar Alterações
                        </button>
                        <a href="machine_view.php?id=<?= $machineId ?>" class="btn btn-secondary btn-lg">
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
