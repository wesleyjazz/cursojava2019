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

// Buscar dados do equipamento
try {
    $stmt = $pdo->prepare("SELECT 
        id, sector, equipment, model, manufacturer, acquisition_date, 
        axis, rotor, gasket, motor, hp, rpm, amp, motor_bearing, description
        FROM machines WHERE id = ?");
    $stmt->execute([$machineId]);
    $machine = $stmt->fetch();

    if (!$machine) {
        throw new Exception("Equipamento não encontrado");
    }

    // Buscar ordens de serviço relacionadas
    $orders = $pdo->prepare("SELECT 
        o.id, o.type, o.status, o.created_at, o.completed_date, 
        u.username as technician
        FROM service_orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.machine_id = ?
        ORDER BY o.created_at DESC");
    $orders->execute([$machineId]);
    $orders = $orders->fetchAll();

    // Buscar histórico de manutenções
    $maintenance = $pdo->prepare("SELECT 
        type, next_date, notes, created_at
        FROM maintenance_schedules
        WHERE machine_id = ?
        ORDER BY next_date DESC");
    $maintenance->execute([$machineId]);
    $maintenance = $maintenance->fetchAll();

} catch (PDOException $e) {
    die("Erro ao carregar equipamento: " . $e->getMessage());
}

include 'header.php';
?>

<div class="container">
    <div class="card shadow-lg mt-4">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0">
                    <i class="bi bi-gear"></i> Equipamento: <?= htmlspecialchars($machine['sector']) ?> - <?= htmlspecialchars($machine['equipment']) ?>
                </h3>
                <a href="machines.php" class="btn btn-light">
                    <i class="bi bi-arrow-left"></i> Voltar
                </a>
            </div>
        </div>

        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <div class="row">
                <!-- Detalhes do Equipamento -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informações Técnicas</h5>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-4">SETOR</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($machine['sector']) ?></dd>

                                <dt class="col-sm-4">EQUIPAMENTO</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($machine['equipment']) ?></dd>

                                <dt class="col-sm-4">MODELO</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($machine['model']) ?></dd>

                                <dt class="col-sm-4">FABRICANTE</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($machine['manufacturer']) ?></dd>

                                <dt class="col-sm-4">EIXO</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($machine['axis'] ?? 'N/A') ?></dd>

                                <dt class="col-sm-4">ROTOR</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($machine['rotor'] ?? 'N/A') ?></dd>

                                <dt class="col-sm-4">GAXETA</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($machine['gasket'] ?? 'N/A') ?></dd>

                                <dt class="col-sm-4">MOTOR</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($machine['motor'] ?? 'N/A') ?></dd>

                                <dt class="col-sm-4">CV</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($machine['hp'] ?? 'N/A') ?></dd>

                                <dt class="col-sm-4">RPM</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($machine['rpm'] ?? 'N/A') ?></dd>

                                <dt class="col-sm-4">AMP</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($machine['amp'] ?? 'N/A') ?></dd>

                                <dt class="col-sm-4">ROLAMENTO</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($machine['motor_bearing'] ?? 'N/A') ?></dd>

                                <dt class="col-sm-4">AQUISIÇÃO</dt>
                                <dd class="col-sm-8"><?= date('d/m/Y', strtotime($machine['acquisition_date'])) ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Ordens de Serviço -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-clipboard-check"></i> Ordens de Serviço</h5>
                                <a href="order_create.php?machine_id=<?= $machineId ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus"></i> Nova OS
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($orders)): ?>
                                <div class="alert alert-info">Nenhuma ordem de serviço registrada.</div>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($orders as $order): ?>
                                        <a href="order_view.php?id=<?= $order['id'] ?>" 
                                           class="list-group-item list-group-item-action">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <strong>OS #<?= $order['id'] ?></strong>
                                                    <span class="badge bg-<?= match($order['status']) {
                                                        'aberta' => 'danger',
                                                        'em_andamento' => 'warning',
                                                        'concluida' => 'success',
                                                        default => 'secondary'
                                                    } ?> ms-2">
                                                        <?= ucfirst(str_replace('_', ' ', $order['status'])) ?>
                                                    </span>
                                                </div>
                                                <small class="text-muted">
                                                    <?= date('d/m/Y', strtotime($order['created_at'])) ?>
                                                </small>
                                            </div>
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    Técnico: <?= $order['technician'] ?? 'Não atribuído' ?>
                                                </small>
                                                <?php if ($order['completed_date']): ?>
                                                    <small class="text-muted ms-2">
                                                        Concluída em: <?= date('d/m/Y', strtotime($order['completed_date'])) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Descrição e Histórico -->
            <div class="row">
                <!-- Descrição -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-card-text"></i> Descrição</h5>
                        </div>
                        <div class="card-body">
                            <?= nl2br(htmlspecialchars($machine['description'] ?? 'Sem descrição adicional')) ?>
                        </div>
                    </div>
                </div>

                <!-- Histórico de Manutenções -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-calendar-check"></i> Histórico de Manutenções</h5>
                                <a href="order_create.php?machine_id=<?= $machineId ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus"></i> Nova Manutenção
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($maintenance)): ?>
                                <div class="alert alert-info">Nenhuma manutenção agendada.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Tipo</th>
                                                <th>Próxima Data</th>
                                                <th>Observações</th>
                                                <th>Agendado em</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($maintenance as $item): ?>
                                                <tr>
                                                    <td><?= ucfirst($item['type']) ?></td>
                                                    <td>
                                                        <?= date('d/m/Y', strtotime($item['next_date'])) ?>
                                                        <?php if (strtotime($item['next_date']) < time()): ?>
                                                            <span class="badge bg-danger ms-2">Atrasada</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= nl2br(htmlspecialchars($item['notes'])) ?></td>
                                                    <td><?= date('d/m/Y H:i', strtotime($item['created_at'])) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
