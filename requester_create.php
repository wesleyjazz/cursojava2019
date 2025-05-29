{{output_file_name:"requester_create.php"}}
<?php
session_start();
require_once 'db.php';

// Only admins can create new requesters
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
    } else {
        $_SESSION['error'] = "Acesso não autorizado.";
        header('Location: index.php');
    }
    exit();
}

$pdo = Database::getInstance();
$error = null;
$success = null; // Not used due to redirect, but good for potential future use

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $required = ['username', 'password', 'email']; // 'fullname' could be added here if table is altered
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                $fieldName = $field === 'username' ? 'Nome de Usuário' : ucfirst($field);
                throw new Exception("O campo " . $fieldName . " é obrigatório.");
            }
        }

        // Validate email format
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Formato de e-mail inválido.");
        }

        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
        $stmt->execute([':username' => $_POST['username'], ':email' => $_POST['email']]);
        if ($stmt->fetch()) {
            throw new Exception("Nome de usuário ou e-mail já cadastrado no sistema.");
        }

        // Create password hash
        $passwordHash = password_hash($_POST['password'], PASSWORD_BCRYPT);

        $phone = !empty($_POST['phone']) ? htmlspecialchars(trim($_POST['phone'])) : null;
        // $fullname = !empty($_POST['fullname']) ? htmlspecialchars(trim($_POST['fullname'])) : null; // If fullname column added

        $stmt = $pdo->prepare("INSERT INTO users
            (username, password, email, role, phone) -- Add , fullname if column exists
            VALUES (:username, :password, :email, 'requester', :phone)"); // Add , :fullname if column exists

        $params = [
            ':username' => htmlspecialchars(trim($_POST['username'])),
            ':password' => $passwordHash,
            ':email' => htmlspecialchars(trim($_POST['email'])),
            ':phone' => $phone
            // ':fullname' => $fullname // Add if column exists
        ];

        if ($stmt->execute($params)) {
            $_SESSION['success'] = "Funcionário (solicitante) cadastrado com sucesso!";
            header('Location: users.php'); // Redirect to user list
            exit();
        } else {
            throw new Exception("Não foi possível cadastrar o funcionário. Por favor, tente novamente.");
        }

    } catch (PDOException $e) {
        // Check for duplicate entry specifically (though the check above should catch it)
        if ($e->getCode() == 23000) { // Integrity constraint violation (e.g., unique key)
            $error = "Erro: Nome de usuário ou e-mail já existe.";
        } else {
            $error = "Erro no banco de dados: " . $e->getMessage();
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

include 'header.php';
?>

<div class="container">
    <div class="card shadow-lg mt-4">
        <div class="card-header bg-info text-white"> <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0"><i class="bi bi-person-plus"></i> Cadastrar Funcionário (Solicitante de Chamado)</h3>
                <a href="users.php" class="btn btn-light">
                    <i class="bi bi-arrow-left"></i> Voltar para Usuários
                </a>
            </div>
        </div>

        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" class="needs-validation" novalidate>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="username" class="form-label">Nome de Usuário (para login) <span class="text-danger">*</span></label>
                        <input type="text" name="username" id="username"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                               class="form-control" required
                               pattern="[a-zA-Z0-9_.-]{4,50}"
                               title="4-50 caracteres (letras, números, _, ., -)">
                        <div class="invalid-feedback">
                            Insira um nome de usuário válido (4-50 caracteres, sem espaços).
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label">E-mail <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="email"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                               class="form-control" required>
                        <div class="invalid-feedback">
                            Insira um e-mail válido (ex: nome@dominio.com).
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="phone" class="form-label">Telefone</label>
                        <input type="text" name="phone" id="phone"
                               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                               class="form-control" placeholder="(XX) XXXXX-XXXX">
                    </div>
                    
                    <div class="col-md-6">
                        <label for="password" class="form-label">Senha <span class="text-danger">*</span></label>
                        <input type="password" name="password" id="password"
                               class="form-control" required
                               minlength="6"
                               title="Mínimo 6 caracteres.">
                        <div class="invalid-feedback">
                            A senha deve ter pelo menos 6 caracteres.
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="password_confirm" class="form-label">Confirmar Senha <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirm" id="password_confirm"
                               class="form-control" required>
                        <div class="invalid-feedback">
                            As senhas não coincidem ou campo obrigatório.
                        </div>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-info btn-lg"> <i class="bi bi-person-check-fill"></i> Cadastrar Funcionário
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
// Validação de formulário e confirmação de senha
(() => {
  'use strict'

  const forms = document.querySelectorAll('.needs-validation')
  const passwordInput = document.getElementById('password')
  const passwordConfirmInput = document.getElementById('password_confirm')

  function validatePasswords() {
    if (passwordInput.value !== passwordConfirmInput.value) {
      passwordConfirmInput.setCustomValidity("As senhas não coincidem");
    } else {
      passwordConfirmInput.setCustomValidity('');
    }
  }

  if (passwordInput && passwordConfirmInput) {
    passwordInput.addEventListener('input', validatePasswords);
    passwordConfirmInput.addEventListener('input', validatePasswords);
  }

  Array.from(forms).forEach(form => {
    form.addEventListener('submit', event => {
      if (passwordInput && passwordConfirmInput) { // Ensure inputs exist
        validatePasswords(); // Ensure validation runs on submit
      }
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
