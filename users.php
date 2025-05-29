<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

$pdo = Database::getInstance();

// Processar exclusão
if (isset($_POST['delete'])) {
    try {
        // Impedir exclusão do próprio usuário
        if ($_POST['id'] == $_SESSION['user_id']) {
            throw new Exception("Não é possível excluir seu próprio usuário");
        }

        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        $_SESSION['success'] = "Usuário excluído com sucesso!";
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    header('Location: users.php');
    exit();
}

// Paginação e busca
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$search = isset($_GET['search']) ? "%{$_GET['search']}%" : '%';
$stmt = $pdo->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM users 
                      WHERE username LIKE ? OR email LIKE ? OR phone LIKE ?
                      ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->execute([$search, $search, $search, $perPage, $offset]);
$users = $stmt->fetchAll();

$totalStmt = $pdo->query("SELECT FOUND_ROWS()");
$total = $totalStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

include 'header.php';
?>

<div class="container">
    <div class="card shadow-lg mt-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h3 class="mb-0"><i class="bi bi-people"></i> Gerenciamento de Usuários</h3>
            <div>
                <a href="technician_create.php" class="btn btn-light">
                    <i class="bi bi-person-plus"></i> Novo Técnico
                </a>
            </div>
        </div>

        <div class="card-body">
            <!-- Barra de Pesquisa -->
            <form class="mb-4">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Pesquisar usuários..." 
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>

            <!-- Tabela de Usuários -->
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Usuário</th>
                            <th>E-mail</th>
                            <th>Telefone</th>
                            <th>Cargo</th>
                            <th>Cadastrado em</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($user['username']) ?>
                                <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                    <span class="badge bg-info">Você</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['phone']) ?></td>
                            <td>
                                <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : 'primary' ?>">
                                    <?= ucfirst($user['role']) ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <a href="user_edit.php?id=<?= $user['id'] ?>" 
                                       class="btn btn-sm btn-warning"
                                       title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                        <button type="submit" name="delete" 
                                                class="btn btn-sm btn-danger"
                                                title="Excluir"
                                                <?= $user['id'] == $_SESSION['user_id'] ? 'disabled' : '' ?>
                                                onclick="return confirm('Tem certeza que deseja excluir este usuário?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" 
                           href="?page=<?= $page-1 ?>&search=<?= $_GET['search'] ?? '' ?>">
                            Anterior
                        </a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" 
                           href="?page=<?= $i ?>&search=<?= $_GET['search'] ?? '' ?>">
                            <?= $i ?>
                        </a>
                    </li>
                    <?php endfor; ?>

                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" 
                           href="?page=<?= $page+1 ?>&search=<?= $_GET['search'] ?? '' ?>">
                            Próxima
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
