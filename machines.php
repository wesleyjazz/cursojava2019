<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$pdo = Database::getInstance();

// Verificar se usuário é técnico
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$is_technician = ($user['role'] ?? '') === 'technician';

// Processar ações (apenas para não-técnicos)
if (!$is_technician && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM machines WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $_SESSION['success'] = "Máquina excluída com sucesso!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Erro ao excluir: " . $e->getMessage();
        }
    }
    header('Location: machines.php');
    exit();
}

// Buscar máquinas com paginação
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$search = isset($_GET['search']) ? "%{$_GET['search']}%" : '%';
$stmt = $pdo->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM machines 
                      WHERE name LIKE ? ORDER BY name LIMIT ? OFFSET ?");
$stmt->execute([$search, $perPage, $offset]);
$machines = $stmt->fetchAll();

$totalStmt = $pdo->query("SELECT FOUND_ROWS()");
$total = $totalStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

include 'header.php';
?>

<div class="container">
    <div class="card shadow-lg mt-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between">
            <h3><i class="bi bi-cpu"></i> Máquinas Cadastradas</h3>
            <?php if (!$is_technician): ?>
                <a href="machine_create.php" class="btn btn-light">
                    <i class="bi bi-plus-lg"></i> Nova Máquina
                </a>
            <?php endif; ?>
        </div>

        <div class="card-body">
            <!-- Barra de Pesquisa -->
            <form class="mb-4">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Pesquisar por nome ou modelo..." 
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>

            <!-- Tabela de Máquinas -->
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nome</th>
                            <th>Modelo</th>
                            <th>Fabricante</th>
                            <th>Data Aquisição</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($machines as $machine): ?>
                        <tr>
                            <td><?= htmlspecialchars($machine['name']) ?></td>
                            <td><?= htmlspecialchars($machine['model']) ?></td>
                            <td><?= htmlspecialchars($machine['manufacturer']) ?></td>
                            <td><?= date('d/m/Y', strtotime($machine['acquisition_date'])) ?></td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="machine_view.php?id=<?= $machine['id'] ?>" 
                                       class="btn btn-sm btn-info">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if (!$is_technician): ?>
                                        <a href="machine_edit.php?id=<?= $machine['id'] ?>" 
                                           class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="id" value="<?= $machine['id'] ?>">
                                            <button type="submit" name="delete" 
                                                    class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Excluir permanentemente esta máquina?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page-1 ?>&search=<?= $_GET['search'] ?? '' ?>">
                            Anterior
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= $_GET['search'] ?? '' ?>">
                            <?= $i ?>
                        </a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page+1 ?>&search=<?= $_GET['search'] ?? '' ?>">
                            Próxima
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
