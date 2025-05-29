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
$setores = $pdo->query("SELECT DISTINCT id, nome FROM setores ORDER BY nome")->fetchAll();
$maquinas = $pdo->query("SELECT id, nome FROM maquinas ORDER BY nome")->fetchAll();

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validação dos campos obrigatórios
        $required = ['nome', 'setor_id', 'maquina_id', 'descricao'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("O campo " . ucfirst(str_replace('_', ' ', $field)) . " é obrigatório");
            }
        }

        // Inserir chamado
        $stmt = $pdo->prepare("INSERT INTO chamados 
            (setor_id, maquina_id, descricao, status, criado_por, criado_em)
            VALUES (:setor_id, :maquina_id, :descricao, 'aberto', :user_id, NOW())");

        $data = [
            ':setor_id' => (int)$_POST['setor_id'],
            ':maquina_id' => (int)$_POST['maquina_id'],
            ':descricao' => "Solicitante: ".htmlspecialchars($_POST['nome'])."\n\n".htmlspecialchars($_POST['descricao']),
            ':user_id' => $_SESSION['user_id']
        ];

        if ($stmt->execute($data)) {
            $chamadoId = $pdo->lastInsertId();
            
            // Notificar técnicos e administradores
            $tecnicos = $pdo->query("SELECT id, email, username FROM users WHERE role IN ('technician', 'admin')")->fetchAll();
            
            foreach ($tecnicos as $tecnico) {
                $stmtNotif = $pdo->prepare("INSERT INTO notifications 
                    (user_id, title, message, order_id, is_read) 
                    VALUES (?, ?, ?, ?, 0)");
                $stmtNotif->execute([
                    $tecnico['id'],
                    "Novo Chamado #$chamadoId",
                    "Um novo chamado foi aberto no sistema",
                    $chamadoId
                ]);
            }

            $_SESSION['success'] = "Chamado aberto com sucesso! Número: #$chamadoId";
            header('Location: chamados.php');
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
                <h3 class="mb-0"><i class="bi bi-megaphone"></i> Abrir Novo Chamado</h3>
                <a href="chamados.php" class="btn btn-light">
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
                    <!-- Campo Nome -->
                    <div class="col-md-6">
                        <label class="form-label">Nome do Solicitante <span class="text-danger">*</span></label>
                        <input type="text" name="nome" class="form-control" 
                               value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" required>
                        <div class="invalid-feedback">
                            Por favor insira seu nome
                        </div>
                    </div>

                    <!-- Campo Setor -->
                    <div class="col-md-6">
                        <label class="form-label">Setor <span class="text-danger">*</span></label>
                        <select name="setor_id" class="form-select" required>
                            <option value="">Selecione o setor</option>
                            <?php foreach ($setores as $setor): ?>
                            <option value="<?= $setor['id'] ?>" 
                                <?= isset($_POST['setor_id']) && $_POST['setor_id'] == $setor['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($setor['nome']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">
                            Por favor selecione o setor
                        </div>
                    </div>

                    <!-- Campo Máquina -->
                    <div class="col-md-6">
                        <label class="form-label">Máquina <span class="text-danger">*</span></label>
                        <select name="maquina_id" class="form-select" required>
                            <option value="">Selecione a máquina</option>
                            <?php foreach ($maquinas as $maquina): ?>
                            <option value="<?= $maquina['id'] ?>" 
                                <?= isset($_POST['maquina_id']) && $_POST['maquina_id'] == $maquina['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($maquina['nome']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">
                            Por favor selecione a máquina
                        </div>
                    </div>

                    <!-- Campo Descrição -->
                    <div class="col-12">
                        <label class="form-label">Descrição do Problema <span class="text-danger">*</span></label>
                        <textarea name="descricao" class="form-control" rows="5" required
                                  placeholder="Descreva detalhadamente o problema..."><?= htmlspecialchars($_POST['descricao'] ?? '') ?></textarea>
                        <div class="invalid-feedback">
                            Por favor descreva o problema
                        </div>
                    </div>

                    <!-- Botões -->
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-send"></i> Abrir Chamado
                        </button>
                        <button type="reset" class="btn btn-secondary btn-lg">
                            <i class="bi bi-eraser"></i> Limpar
                        </button>
                    </div>
                </div>
            </form>

            <!-- Rodapé com contato -->
            <div class="mt-4 pt-3 border-top">
                <h5>Contato</h5>
                <p class="mb-1">Suporte: wesley.firmino@filiperson.com.br</p>
                <p>+55 (21) 99209-9041</p>
            </div>
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
