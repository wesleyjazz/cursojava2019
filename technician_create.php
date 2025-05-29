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

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validação
        $required = ['username', 'password', 'email'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("O campo " . ucfirst($field) . " é obrigatório");
            }
        }

        // Verificar se usuário já existe
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$_POST['username'], $_POST['email']]);
        if ($stmt->fetch()) {
            throw new Exception("Usuário ou e-mail já cadastrado");
        }

        // Criar hash da senha
        $passwordHash = password_hash($_POST['password'], PASSWORD_BCRYPT);

        // Inserir técnico
        $stmt = $pdo->prepare("INSERT INTO users 
            (username, password, email, phone, role)
            VALUES (?, ?, ?, ?, 'technician')");

        if ($stmt->execute([
            htmlspecialchars($_POST['username']),
            $passwordHash,
            htmlspecialchars($_POST['email']),
            htmlspecialchars($_POST['phone'] ?? null)
        ])) {
            $_SESSION['success'] = "Técnico cadastrado com sucesso!";
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
                <h3 class="mb-0"><i class="bi bi-person-plus"></i> Cadastrar Técnico</h3>
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
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" 
                               class="form-control" required
                               pattern="[a-zA-Z0-9_]{4,20}"
                               title="4-20 caracteres (letras, números ou _)">
                        <div class="invalid-feedback">
                            Insira um nome de usuário válido
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="col-md-6">
                        <label class="form-label">E-mail <span class="text-danger">*</span></label>
                        <input type="email" name="email" 
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                               class="form-control" required>
                        <div class="invalid-feedback">
                            Insira um e-mail válido
                        </div>
                    </div>

                    <!-- Telefone -->
                    <div class="col-md-6">
                        <label class="form-label">Telefone (WhatsApp)</label>
                        <input type="tel" name="phone" 
                               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" 
                               class="form-control"
                               placeholder="Ex: 21912345678">
                        <div class="invalid-feedback">
                            Insira um número de telefone válido
                        </div>
                    </div>

                    <!-- Senha -->
                    <div class="col-md-6">
                        <label class="form-label">Senha <span class="text-danger">*</span></label>
                        <input type="password" name="password" 
                               class="form-control" required
                               minlength="6"
                               pattern="^(?=.*[A-Za-z])(?=.*\d).{6,}$"
                               title="Mínimo 6 caracteres com letras e números">
                        <div class="invalid-feedback">
                            A senha deve ter pelo menos 6 caracteres com letras e números
                        </div>
                    </div>

                    <!-- Confirmação de Senha -->
                    <div class="col-md-6">
                        <label class="form-label">Confirmar Senha <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirm" 
                               class="form-control" required>
                        <div class="invalid-feedback">
                            As senhas devem coincidir
                        </div>
                    </div>

                    <!-- Botões -->
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-save"></i> Cadastrar Técnico
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Validação de formulário e confirmação de senha
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
