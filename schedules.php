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

// Processar exclusão (apenas para não-técnicos)
if (!$is_technician && isset($_POST['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM maintenance_schedules WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        $_SESSION['success'] = "Agendamento excluído!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erro ao excluir: " . $e->getMessage();
    }
    header('Location: schedules.php');
    exit();
}

// Buscar agendamentos
$stmt = $pdo->query("SELECT s.*, m.name as machine_name 
                    FROM maintenance_schedules s
                    JOIN machines m ON s.machine_id = m.id
                    ORDER BY s.next_date");
$schedules = $stmt->fetchAll();

include 'header.php';
?>

<div class="container">
    <div class="card shadow-lg mt-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between">
            <h3><i class="bi bi-calendar-check"></i> Agendamentos de Manutenção</h3>
            <?php if (!$is_technician): ?>
                <a href="schedule_create.php" class="btn btn-light">
                    <i class="bi bi-plus-lg"></i> Novo Agendamento
                </a>
            <?php endif; ?>
        </div>

        <div class="card-body">
            <!-- Lista de Agendamentos -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Máquina</th>
                            <th>Tipo</th>
                            <th>Frequência</th>
                            <th>Próxima Data</th>
                            <?php if (!$is_technician): ?>
                                <th>Ações</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules as $schedule): ?>
                        <tr>
                            <td><?= htmlspecialchars($schedule['machine_name']) ?></td>
                            <td><?= ucfirst($schedule['type']) ?></td>
                            <td><?= ucfirst($schedule['frequency']) ?></td>
                            <td><?= date('d/m/Y', strtotime($schedule['next_date'])) ?></td>
                            <?php if (!$is_technician): ?>
                                <td>
                                    <div class="btn-group">
                                        <a href="schedule_edit.php?id=<?= $schedule['id'] ?>" 
                                           class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="id" value="<?= $schedule['id'] ?>">
                                            <button type="submit" name="delete" 
                                                    class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Excluir este agendamento?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Sem agendamentos -->
            <?php if (empty($schedules)): ?>
            <div class="alert alert-info mt-4">
                Nenhum agendamento cadastrado.
                <?php if (!$is_technician): ?>
                    Crie um novo usando o botão acima.
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
