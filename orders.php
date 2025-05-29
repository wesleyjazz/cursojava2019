<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$pdo = Database::getInstance();

// Verificar o tipo de usuário
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$is_technician = ($user['role'] ?? '') === 'technician';

// Processar envio de notificação (apenas para não-técnicos)
if (!$is_technician && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notify_technician'])) {
    try {
        $orderId = (int)$_POST['order_id'];
        
        // Buscar dados da ordem e telefone do técnico
        $stmt = $pdo->prepare("SELECT 
            o.id, o.type, o.work_type, o.priority, o.description, o.status,
            m.name as machine_name,
            u.id as technician_id, u.username as technician_name, 
            u.email as technician_email, u.phone as technician_phone
            FROM service_orders o
            LEFT JOIN machines m ON o.machine_id = m.id
            LEFT JOIN users u ON o.user_id = u.id
            WHERE o.id = ?");
            
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        
        if ($order && $order['technician_id']) {
            // 1. Enviar e-mail
            $subject = "Lembrete: Ordem de Serviço #{$order['id']}";
            $body = <<<HTML
            <h2>Lembrete de Ordem de Serviço</h2>
            <p><strong>Máquina:</strong> {$order['machine_name']}</p>
            <p><strong>Tipo:</strong> {$order['type']}</p>
            <p><strong>Tipo de Trabalho:</strong> {$order['work_type']}</p>
            <p><strong>Prioridade:</strong> {$order['priority']}</p>
            <p><strong>Status:</strong> {$order['status']}</p>
            <p><strong>Descrição:</strong><br>{$order['description']}</p>
            <p>Acesse o sistema para mais detalhes.</p>
            HTML;
            
            $headers = "From: filiperson.ddns.net\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            
            $emailSent = mail($order['technician_email'], $subject, $body, $headers);
            
            // 2. Registrar notificação no sistema
            $stmt = $pdo->prepare("INSERT INTO notifications 
                (user_id, title, message, order_id, is_read) 
                VALUES (?, ?, ?, ?, 0)");
            $stmt->execute([
                $order['technician_id'],
                "Lembrete: Ordem #{$order['id']}",
                "Você foi lembrado sobre a ordem de serviço para {$order['machine_name']}",
                $order['id']
            ]);
            
            // 3. Preparar resposta
            if ($emailSent) {
                $_SESSION['success'] = "Notificação por e-mail enviada para {$order['technician_name']}";
                
                // Usar o telefone do técnico ou o número padrão
                $whatsappNumber = !empty($order['technician_phone']) ? 
                    preg_replace('/[^0-9]/', '', $order['technician_phone']) : 
                    '552135653454'; // Número padrão que você forneceu
                
                $whatsappMessage = rawurlencode(
                    "📋 *Nova Ordem de Serviço* #{$order['id']}\n" .
                    "🛠️ *Máquina:* {$order['machine_name']}\n" .
                    "🔧 *Tipo:* " . ucfirst($order['type']) . "\n" .
                    "🚨 *Prioridade:* " . ucfirst($order['priority']) . "\n\n" .
                    "Acesse o sistema para mais detalhes."
                );
                
                $_SESSION['whatsapp_link'] = "https://wa.me/{$whatsappNumber}?text={$whatsappMessage}";
                $_SESSION['last_notified_order'] = $order['id'];
            } else {
                $_SESSION['warning'] = "E-mail não pôde ser enviado, mas a notificação foi registrada";
            }
        } else {
            $_SESSION['error'] = "Ordem não encontrada ou sem técnico atribuído";
        }
        
        header("Location: orders.php");
        exit();
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erro ao enviar notificação: " . $e->getMessage();
        header("Location: orders.php");
        exit();
    }
}

// Filtros
$status = $_GET['status'] ?? 'all';
$search = isset($_GET['search']) ? "%{$_GET['search']}%" : '%';
$month = $_GET['month'] ?? '';

// Consulta com filtros
$query = "SELECT 
    o.id, 
    o.type, 
    o.work_type,
    o.priority, 
    o.status, 
    o.created_at,
    m.name as machine_name,
    u.username as technician,
    u.id as technician_id
    FROM service_orders o
    LEFT JOIN machines m ON o.machine_id = m.id
    LEFT JOIN users u ON o.user_id = u.id
    WHERE (o.description LIKE ? OR m.name LIKE ? OR o.type LIKE ? OR o.work_type LIKE ?)";

$params = [$search, $search, $search, $search];

if ($status !== 'all') {
    $query .= " AND o.status = ?";
    $params[] = $status;
}

// Filtro por mês
if (!empty($month)) {
    $query .= " AND MONTH(o.created_at) = ? AND YEAR(o.created_at) = ?";
    $monthParts = explode('-', $month);
    $params[] = $monthParts[1]; // mês
    $params[] = $monthParts[0]; // ano
}

$query .= " ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

include 'header.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if ('Notification' in window && Notification.permission !== 'denied') {
        Notification.requestPermission();
    }
    
    <?php if (isset($_SESSION['browser_notification'])): ?>
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(
                "<?= $_SESSION['browser_notification']['title'] ?>", 
                {
                    body: "<?= $_SESSION['browser_notification']['message'] ?>",
                    icon: '/path/to/icon.png'
                }
            );
        }
        <?php unset($_SESSION['browser_notification']); ?>
    <?php endif; ?>
});
</script>

<div class="container">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success mt-3"><?= $_SESSION['success'] ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php elseif (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger mt-3"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php elseif (isset($_SESSION['warning'])): ?>
        <div class="alert alert-warning mt-3"><?= $_SESSION['warning'] ?></div>
        <?php unset($_SESSION['warning']); ?>
    <?php endif; ?>

    <div class="card shadow-lg mt-4">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0"><i class="bi bi-clipboard-checklist"></i> Ordens de Serviço</h3>
                <?php if (!$is_technician): ?>
                    <a href="order_create.php" class="btn btn-light">
                        <i class="bi bi-plus-circle"></i> Nova Ordem
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="card-body">
            <!-- Filtros e Busca -->
            <div class="row mb-4 g-3">
                <div class="col-md-3">
                    <form method="GET" class="input-group">
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Todos Status</option>
                            <option value="aberta" <?= $status === 'aberta' ? 'selected' : '' ?>>Abertas</option>
                            <option value="em_andamento" <?= $status === 'em_andamento' ? 'selected' : '' ?>>Em Andamento</option>
                            <option value="concluida" <?= $status === 'concluida' ? 'selected' : '' ?>>Concluídas</option>
                        </select>
                        <input type="hidden" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                        <input type="hidden" name="month" value="<?= htmlspecialchars($month) ?>">
                    </form>
                </div>
                
                <div class="col-md-2">
                    <form method="GET">
                        <input type="month" name="month" class="form-control" 
                            value="<?= htmlspecialchars($month) ?>"
                            onchange="this.form.submit()">
                        <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    </form>
                </div>
                
                <div class="col-md-7">
                    <form method="GET">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" 
                                placeholder="Pesquisar por descrição ou máquina..."
                                value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i>
                            </button>
                            <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
                            <input type="hidden" name="month" value="<?= htmlspecialchars($month) ?>">
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabela de Ordens -->
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Máquina</th>
                            <th>Tipo</th>
                            <th>Tipo de Trabalho</th>
                            <th>Prioridade</th>
                            <th>Status</th>
                            <th>Técnico</th>
                            <th>Criação</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?= htmlspecialchars($order['machine_name']) ?></td>
                            <td><?= ucfirst($order['type']) ?></td>
                            <td>
                                <span class="badge bg-<?= match($order['work_type']) {
                                    'elétrico' => 'info',
                                    'mecânico' => 'primary',
                                    'civil' => 'warning',
                                    'outros' => 'secondary',
                                    default => 'light'
                                } ?>">
                                    <?= ucfirst($order['work_type']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= match($order['priority']) {
                                    'baixa' => 'success',
                                    'media' => 'warning',
                                    'alta' => 'danger',
                                    default => 'light'
                                } ?>">
                                    <?= ucfirst($order['priority']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= match($order['status']) {
                                    'aberta' => 'secondary',
                                    'em_andamento' => 'primary',
                                    'concluida' => 'success',
                                    default => 'light'
                                } ?>">
                                    <?= ucfirst($order['status']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($order['technician'] ?? 'Não atribuído') ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <a href="order_view.php?id=<?= $order['id'] ?>" 
                                       class="btn btn-sm btn-info"
                                       title="Visualizar">
                                        <i class="bi bi-eye"></i>
                                    </a>	
                                    
                                    <?php if (!$is_technician): ?>
                                        <a href="order_edit.php?id=<?= $order['id'] ?>" 
                                           class="btn btn-sm btn-warning"
                                           title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        
                                        <?php if ($order['technician_id']): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <button type="submit" name="notify_technician" 
                                                    class="btn btn-sm btn-primary"
                                                    title="Notificar Técnico"
                                                    onclick="return confirm('Enviar notificação para <?= htmlspecialchars($order['technician']) ?>?')">
                                                <i class="bi bi-bell"></i>
                                            </button>
                                        </form>
                                        
                                        <!-- Botão do WhatsApp (aparece após enviar notificação) -->
                                        <?php if (isset($_SESSION['whatsapp_link']) && $_SESSION['last_notified_order'] == $order['id']): ?>
                                            <a href="<?= $_SESSION['whatsapp_link'] ?>" 
                                               target="_blank"
                                               class="btn btn-sm btn-success"
                                               title="Enviar WhatsApp">
                                                <i class="bi bi-whatsapp"></i>
                                            </a>
                                            <?php unset($_SESSION['whatsapp_link']); ?>
                                        <?php endif; ?>
                                        <?php endif; ?>

                                        <a href="attachments_view.php?id=<?= $order['id'] ?>" 
                                           class="btn btn-sm btn-secondary"
                                           title="Anexos">
                                            <i class="bi bi-paperclip"></i>
                                        </a>

                                        <form method="POST" action="order_delete.php" style="display: inline;">
                                            <input type="hidden" name="id" value="<?= $order['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger"
                                                    title="Excluir"
                                                    onclick="return confirm('Tem certeza que deseja excluir esta ordem?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>

                                        <a href="order_print.php?id=<?= $order['id'] ?>" 
                                           class="btn btn-sm btn-outline-dark"
                                           title="Imprimir"
                                           target="_blank">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (empty($orders)): ?>
                <div class="alert alert-info mt-4">Nenhuma ordem encontrada</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
