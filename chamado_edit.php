<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header('Location: login.php');
    exit();
}

$pdo = Database::getInstance();
$id = (int)$_GET['id'];

// Verificar se usuário é admin
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($user['role'] !== 'admin') {
    $_SESSION['error'] = "Acesso negado";
    header('Location: chamados.php');
    exit();
}

// Buscar chamado
$stmt = $pdo->prepare("SELECT * FROM chamados WHERE id = ?");
$stmt->execute([$id]);
$chamado = $stmt->fetch();

if (!$chamado) {
    $_SESSION['error'] = "Chamado não encontrado";
    header('Location: chamados.php');
    exit();
}

// Buscar dados para os selects
$setores = $pdo->query("SELECT id, nome FROM setores ORDER BY nome")->fetchAll();
$maquinas = $pdo->query("SELECT id, nome FROM maquinas ORDER BY nome")->fetchAll();
$tecnicos = $pdo->query("SELECT id, username FROM users WHERE role = 'technician' ORDER BY username")->fetchAll();

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $required = ['setor_id', 'maquina_id', 'descricao', 'status'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("O campo " . ucfirst(str_replace('_', ' ', $field)) . " é obrigatório");
            }
        }

        $stmt = $pdo->prepare("UPDATE chamados SET 
            setor_id = :setor_id,
            maquina_id = :maquina_id,
            descricao = :descricao,
            status = :status,
            atribuido_a = :atribuido_a,
            atualizado_em = NOW()
            WHERE id = :id");

        $data = [
            ':setor_id' => (int)$_POST['setor_id'],
            ':maquina_id' => (int)$_POST['maquina_id'],
            ':descricao' => htmlspecialchars($_POST['descricao']),
            ':status' => $_POST['status'],
            ':atribuido_a' => !empty($_POST['atribuido_a']) ? (int)$_POST['atribuido_a'] : null,
            ':id' => $id
        ];

        if ($stmt->execute($data)) {
            // Registrar no histórico
            $pdo->prepare("INSERT INTO chamado_historico 
                (chamado_id, usuario_id, acao, comentario)
                VALUES (?, ?, 'edição', ?)")
               ->execute([
                   $id,
                   $_SESSION['user_id'],
                   "Chamado editado por administrador"
               ]);

            $_SESSION['success'] = "Chamado atualizado com sucesso!";
            header("Location: chamado_view.php?id=$id");
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
                <h3 class="mb-0"><i class="bi bi-pencil-square"></i> Editar Chamado #<?= $chamado['id'] ?></h3>
                <a href="chamado_view.php?id=<?= $chamado['id'] ?>" class="btn btn-light">
                    <i class="bi bi-arrow-left"></i> Voltar
                </a>
            </div>
        </div>

        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="row g-3">
                    <!-- Campo Setor -->
                    <div class="col-md-6">
                        <label class="form-label">Setor <span class="text-danger">*</span></label>
                        <select name="setor_id" class="form-select" required>
                            <option value="">Selecione o setor</option>
                            <?php foreach ($setores as $setor): ?>
                            <option value="<?= $setor['id'] ?>" 
                                <?= ($setor['id'] == $chamado['setor_id'] || (isset($_POST['setor_id']) && $_POST['setor_id'] == $setor['id'])) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($setor['nome']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Campo Máquina -->
                    <div class="col-md-6">
                        <label class="form-label">Máquina <span class="text-danger">*</span></label>
                        <select name="maquina_id" class="form-select" required>
                            <option value="">Selecione a máquina</option>
                            <?php foreach ($maquinas as $maquina): ?>
                            <option value="<?= $maquina['id'] ?>" 
                                <?= ($maquina['id'] == $chamado['maquina_id'] || (isset($_POST['maquina_id']) && $_POST['maquina_id'] == $maquina['id'])) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($maquina['nome']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Campo Status -->
                    <div class="col-md-6">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="aberto" <?= ($chamado['status'] == 'aberto' || (isset($_POST['status']) && $_POST['status'] == 'aberto')) ? 'selected' : '' ?>>Aberto</option>
                            <option value="em_andamento" <?= ($chamado['status'] == 'em_andamento' || (isset($_POST['status']) && $_POST['status'] == 'em_andamento')) ? 'selected' : '' ?>>Em Andamento</option>
                            <option value="concluido" <?= ($chamado['status'] == 'concluido' || (isset($_POST['status']) && $_POST['status'] == 'concluido')) ? 'selected' : '' ?>>Concluído</option>
                        </select>
                    </div>

                    <!-- Campo Atribuir a -->
                    <div class="col-md-6">
                        <label class="form-label">Atribuir a</label>
                        <select name="atribuido_a" class="form-select">
                            <option value="">Não atribuído</option>
                            <?php foreach ($tecnicos as $tecnico): ?>
                            <option value="<?= $tecnico['id'] ?>" 
                                <?= ($tecnico['id'] == $chamado['atribuido_a'] || (isset($_POST['atribuido_a']) && $_POST['atribuido_a'] == $tecnico['id'])) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tecnico['username']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Campo Descrição -->
                    <div class="col-12">
                        <label class="form-label">Descrição do Problema <span class="text-danger">*</span></label>
                        <textarea name="descricao" class="form-control" rows="5" required><?= 
                            htmlspecialchars(isset($_POST['descricao']) ? $_POST['descricao'] : $chamado['descricao']) 
                        ?></textarea>
                    </div>

                    <!-- Botões -->
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Salvar Alterações
                        </button>
                        <a href="chamado_view.php?id=<?= $chamado['id'] ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
