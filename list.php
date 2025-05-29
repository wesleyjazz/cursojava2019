<?php
require_once 'db.php';
requireAuth();

$pdo = Database::getInstance();
$stmt = $pdo->query("SELECT 
    o.*, 
    m.name as machine_name,
    u.username as technician
    FROM service_orders o
    LEFT JOIN machines m ON o.machine_id = m.id
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC");
$orders = $stmt->fetchAll();
?>

<div class="card shadow">
    <div class="card-header bg-primary text-white">
        <h3 class="mb-0"><i class="bi bi-clipboard-check"></i> Ordens de Serviço</h3>
    </div>
    
    <div class="card-body">
        <div class="d-flex justify-content-between mb-4">
            <a href="create.php" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Nova Ordem
            </a>
            
            <div class="btn-group">
                <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                    Filtrar por Status
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="?status=aberta">Abertas</a></li>
                    <li><a class="dropdown-item" href="?status=em_andamento">Em Andamento</a></li>
                    <li><a class="dropdown-item" href="?status=concluida">Concluídas</a></li>
                </ul>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Máquina</th>
                        <th>Tipo</th>
                        <th>Prioridade</th>
                        <th>Status</th>
                        <th>Técnico</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= htmlspecialchars($order['machine_name']) ?></td>
                        <td><?= ucfirst($order['type']) ?></td>
                        <td>
                            <span class="badge bg-<?= 
                                match($order['priority']) {
                                    'baixa' => 'success',
                                    'media' => 'warning',
                                    'alta' => 'danger'
                                } ?>">
                                <?= ucfirst($order['priority']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?= 
                                match($order['status']) {
                                    'aberta' => 'secondary',
                                    'em_andamento' => 'primary',
                                    'concluida' => 'success'
                                } ?>">
                                <?= ucfirst($order['status']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($order['technician']) ?? 'Não atribuído' ?></td>
                        <td>
                            <a href="view.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-info">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="edit.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-warning">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
