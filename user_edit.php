<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

$pdo = Database::getInstance();
$error = null;
$success = null;

// Buscar dados do usuário
$userId = $_GET['id'] ?? null;
if (!$userId) {
    header('Location: users.php');
    exit();
}

// Carregar dados atuais
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = "Usuário não encontrado";
    header('Location: users.php');
    exit();
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $required = ['username', 'email'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("O campo " . ucfirst($field) . " é obrigatório");
            }
        }

        // Verificar conflitos
        $stmt = $pdo->prepare("SELECT id FROM users 
                             WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([
            $_POST['username'],
            $_POST['email'],
            $userId
        ]);
        
        if ($stmt->fetch()) {
            throw new Exception("Usuário ou e-mail já está em uso");
        }

        // Atualizar senha se fornecida
        $passwordUpdate = '';
        if (!empty($_POST['password'])) {
            if ($_POST['password'] !== $_POST['password_confirm']) {
                throw new Exception("As senhas não coincidem");
            }
            $passwordHash = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $passwordUpdate = ", password = :password";
        }

        // Atualizar dados
        $stmt = $pdo->prepare("UPDATE users SET
            username = :username,
            email = :email,
            phone = :phone,
            role = :role
            $passwordUpdate
            WHERE id = :id");

        $data = [
            ':username' => htmlspecialchars($_POST['username']),
            ':email' => htmlspecialchars($_POST['email']),
            ':phone' => htmlspecialchars($_POST['phone'] ?? null),
            ':role' => $_POST['role'],
            ':id' => $userId
        ];

        if (!empty($passwordUpdate)) {
            $data[':password'] = $passwordHash;
        }

        // Impedir auto-alteração de cargo
        if ($userId == $_SESSION['user_id'] && $_POST['role'] !== 'admin') {
            throw new Exception("Você não pode alterar seu próprio cargo");
        }

        if ($stmt->execute($data)) {
            $_SESSION['success'] = "Usuário atualizado com sucesso!";
            header('Location: users.php');
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
                <h3 class="mb-0"><i class="bi bi-person-gear"></i> Editar Usuário</h3>
                <a href="users.php" class="btn btn-light">
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
                    <!-- Usuário -->
                    <div class="col-md-6">
                        <label class="form-label">Nome de Usuário <span class="text-danger">*</span></label>
                        <input type="text" name="username" 
                               value="<?= htmlspecialchars($user['username']) ?>" 
                               class="form-control" required
                               pattern="[a-zA-Z0-9_]{4,20}">
                        <div class="invalid-feedback">
                            Insira um nome de usuário válido
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="col-md-6">
                        <label class="form-label">E-mail <span class="text-danger">*</span></label>
                        <input type="email" name="email" 
                               value="<?= htmlspecialchars($user['email']) ?>" 
                               class="form-control" required>
                        <div class="invalid-feedback">
                            Insira um e-mail válido
                        </div>
                    </div>

                    <!-- Telefone -->
                    <div class="col-md-6">
                        <label class="form-label">Telefone (WhatsApp)</label>
                        <input type="tel" name="phone" 
                               value="<?= htmlspecialchars($user['phone']) ?>" 
                               class="form-control"
                               placeholder="Ex: 21912345678">
                    </div>

                    <!-- Cargo -->
                    <div class="col-md-6">
                        <label class="form-label">Cargo <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required
                            <?= $user['id'] == $_SESSION['user_id'] ? 'disabled' : '' ?>>
                            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
                            <option value="technician" <?= $user['role'] === 'technician' ? 'selected' : '' ?>>Técnico</option>
                        </select>
                        <?php if ($user['id'] == $_SESSION['user_id']): ?>
                            <small class="text-muted">Não é possível alterar seu próprio cargo</small>
                        <?php endif; ?>
                    </div>

                    <!-- Senha -->
                    <div class="col-md-6">
                        <label class="form-label">Nova Senha</label>
                        <input type="password" name="password" 
                               class="form-control"
                               placeholder="Deixe em branco para manter a atual"
                               minlength="6"
                               pattern="^(?=.*[A-Za-z])(?=.*\d).{6,}$">
                        <div class="invalid-feedback">
                            Mínimo 6 caracteres com letras e números
                        </div>
                    </div>

                    <!-- Confirmação de Senha -->
                    <div class="col-md-6">
                        <label class="form-label">Confirmar Senha</label>
                        <input type="password" name="password_confirm" 
                               class="form-control">
                        <div class="invalid-feedback">
                            As senhas devem coincidir
                        </div>
                    </div>

                    <!-- Botões -->
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-save"></i> Salvar Alterações
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Validação de formulário
(() => {
  'use strict'
  
  const forms = document.querySelectorAll('.needs-validation')
  const password = document.querySelector('input[name="password"]')
  const passwordConfirm = document.querySelector('input[name="password_confirm"]')

  function validatePassword() {
    if (password.value !== passwordConfirm.value) {
      passwordConfirm.setCustomValidity("As senhas não coincidem")
    } else {
      passwordConfirm.setCustomValidity('')
    }
  }

  Array.from(forms).forEach(form => {
    form.addEventListener('submit', event => {
      validatePassword()
      if (!form.checkValidity()) {
        event.preventDefault()
        event.stopPropagation()
      }
      form.classList.add('was-validated')
    }, false)
  })

  password.addEventListener('input', validatePassword)
  passwordConfirm.addEventListener('input', validatePassword)
})()
</script>

<?php include 'footer.php'; ?>
