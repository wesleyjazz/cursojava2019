<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Role is set in header.php, but double check if header.php might not have run (e.g. direct access attempt)
if (!isset($_SESSION['role'])) {
    // Attempt to fetch role again if not in session for some reason
    $pdoCheck = Database::getInstance();
    $stmtCheck = $pdoCheck->prepare("SELECT role FROM users WHERE id = ?");
    $stmtCheck->execute([$_SESSION['user_id']]);
    $userCheck = $stmtCheck->fetch();
    if ($userCheck) {
        $_SESSION['role'] = $userCheck['role'];
    } else {
        // Could not verify user, critical error
        session_destroy();
        header('Location: login.php?error=auth_failed');
        exit();
    }
}


// If the user is a 'requester', their "dashboard" is to create a chamado or view their chamados.
// Redirect them to "Abrir Chamado" page as the primary landing.
if ($_SESSION['role'] === 'requester') {
    header('Location: chamado_create.php'); // Or chamados.php if you want them to see their list first
    exit();
}

// --- Existing dashboard logic for admin/technician below this line ---
$pdo = Database::getInstance();
$dashboard_error = null; // Initialize error variable

try {
    // Últimas ordens de serviço
    $stmtOrders = $pdo->query("SELECT
        o.id, o.description, o.status, o.created_at, m.name as machine_name
        FROM service_orders o
        LEFT JOIN machines m ON o.machine_id = m.id
        ORDER BY o.created_at DESC LIMIT 5");
    $latestOrders = $stmtOrders->fetchAll();

    // Estatísticas
    $stmtStats = $pdo->query("SELECT
        COUNT(CASE WHEN status = 'aberta' THEN 1 END) as abertas,
        COUNT(CASE WHEN status = 'em_andamento' THEN 1 END) as em_andamento,
        COUNT(CASE WHEN status = 'concluida' THEN 1 END) as concluidas
        FROM service_orders");
    $stats = $stmtStats->fetch();

    // Dados para gráfico mensal
    $stmtMonthly = $pdo->query("SELECT
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as total,
        COUNT(CASE WHEN status = 'aberta' THEN 1 END) as abertas,
        COUNT(CASE WHEN status = 'em_andamento' THEN 1 END) as em_andamento,
        COUNT(CASE WHEN status = 'concluida' THEN 1 END) as concluidas
        FROM service_orders
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC");
    $monthlyData = $stmtMonthly->fetchAll();

    // Preparar dados para o gráfico
    $labels = [];
    $totalData = [];
    $abertasData = [];
    $andamentoData = [];
    $concluidasData = [];

    if($monthlyData){
        foreach ($monthlyData as $row) {
            $labels[] = date('M/Y', strtotime($row['month']));
            $totalData[] = $row['total'];
            $abertasData[] = $row['abertas'];
            $andamentoData[] = $row['em_andamento'];
            $concluidasData[] = $row['concluidas'];
        }
    }

    // Data for Gantt Chart (Service Orders Timeline)
    $stmtGantt = $pdo->prepare("SELECT
        o.id, o.description, o.status,
        COALESCE(o.scheduled_date, o.created_at) as start_date_source,
        o.completed_date,
        o.created_at,
        m.name as machine_name
        FROM service_orders o
        LEFT JOIN machines m ON o.machine_id = m.id
        WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY) -- Look at orders from last 90 days
           OR o.status IN ('aberta', 'em_andamento') -- Always include active ones
        ORDER BY COALESCE(o.scheduled_date, o.created_at) DESC
        LIMIT 15");
    $stmtGantt->execute();
    $ganttOrders = $stmtGantt->fetchAll(PDO::FETCH_ASSOC);

    $ganttChartData = [];
    $ganttChartLabels = [];
    $ganttChartColors = [];

    $statusColorsGantt = [
        'aberta' => 'rgba(220, 53, 69, 0.7)',      // Bootstrap's danger (red)
        'em_andamento' => 'rgba(255, 193, 7, 0.7)', // Bootstrap's warning (yellow)
        'concluida' => 'rgba(25, 135, 84, 0.7)',   // Bootstrap's success (green)
        'default' => 'rgba(108, 117, 125, 0.7)'    // Bootstrap's secondary (grey)
    ];
    $today = date('Y-m-d');

    foreach ($ganttOrders as $order) {
        $taskLabel = "OS #" . $order['id'] . ": " . ($order['machine_name'] ? htmlspecialchars($order['machine_name']) : 'N/A');
        if (strlen($order['description'] ?? '') > 0) {
             $taskLabel .= " - " . htmlspecialchars(substr($order['description'], 0, 30)) . (strlen($order['description'] ?? '') > 30 ? '...' : '');
        }
        $ganttChartLabels[] = $taskLabel;

        $startDate = date('Y-m-d', strtotime($order['start_date_source']));
        $endDate = $startDate; // Default end date to start date for minimal bar

        if ($order['status'] === 'concluida' && $order['completed_date']) {
            $endDate = date('Y-m-d', strtotime($order['completed_date']));
        } elseif ($order['status'] === 'em_andamento') {
            $endDate = $today;
        } elseif ($order['status'] === 'aberta') {
            // If 'aberta' and start date is in the past, extend to today
            // If start date is today or future, show a nominal duration from start date
            if ($startDate < $today) {
                $endDate = $today;
            } else { // Start date is today or in the future
                $endDate = date('Y-m-d', strtotime($startDate . ' + 2 day')); // Nominal 2-day visible bar
            }
        }

        // Ensure endDate is at least the startDate. If they are same, make endDate next day for visibility.
        if (strtotime($endDate) <= strtotime($startDate)) {
            $endDate = date('Y-m-d', strtotime($startDate . ' + 1 day'));
        }
        
        $ganttChartData[] = [$startDate, $endDate];
        $ganttChartColors[] = $statusColorsGantt[$order['status']] ?? $statusColorsGantt['default'];
    }

    // Determine min/max dates for the Gantt chart X-axis
    $allGanttDates = [];
    foreach ($ganttChartData as $dates) {
        if (isset($dates[0])) $allGanttDates[] = $dates[0];
        if (isset($dates[1])) $allGanttDates[] = $dates[1];
    }
    $minGanttDate = !empty($allGanttDates) ? min($allGanttDates) : date("Y-m-d", strtotime("-30 days"));
    $maxGanttDate = !empty($allGanttDates) ? max($allGanttDates) : date("Y-m-d", strtotime("+7 days"));

    if (strtotime($minGanttDate) >= strtotime($maxGanttDate)) { // If min and max are too close or inverted
        $maxGanttDate = date('Y-m-d', strtotime($minGanttDate . ' + 7 day'));
    }
     // Add some padding to the min and max dates for better visualization
    $minGanttDate = date('Y-m-d', strtotime($minGanttDate . ' - 2 days'));
    $maxGanttDate = date('Y-m-d', strtotime($maxGanttDate . ' + 2 days'));


    // For Gantt: Newest tasks (by start date) at the top of the chart.
    // SQL sorts DESC (newest first), Chart.js plots first item at bottom of Y-axis. So, reverse.
    $ganttChartLabels = array_reverse($ganttChartLabels);
    $ganttChartData = array_reverse($ganttChartData);
    $ganttChartColors = array_reverse($ganttChartColors);


} catch (PDOException $e) {
    $dashboard_error = "Erro ao carregar dados do dashboard: " . $e->getMessage();
}


include 'header.php';
?>

<?php if(isset($dashboard_error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($dashboard_error) ?></div>
<?php else: ?>
<div class="row">
    <div class="col-12 col-md-6 col-xl-3 mb-4">
        <div class="card bg-primary text-white shadow">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-exclamation-circle"></i> OS Abertas</h5>
                <p class="display-4 mb-0"><?= $stats['abertas'] ?? 0 ?></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3 mb-4">
        <div class="card bg-warning text-dark shadow">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-gear"></i> OS Em Andamento</h5>
                <p class="display-4 mb-0"><?= $stats['em_andamento'] ?? 0 ?></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3 mb-4">
        <div class="card bg-success text-white shadow">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-check-circle"></i> OS Concluídas</h5>
                <p class="display-4 mb-0"><?= $stats['concluidas'] ?? 0 ?></p>
            </div>
        </div>
    </div>
     <div class="col-12 col-md-6 col-xl-3 mb-4">
        <div class="card bg-secondary text-white shadow">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-journal-check"></i> Total OS (Últimos 12m)</h5>
                <p class="display-4 mb-0"><?= array_sum($totalData ?? [0]) ?></p>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-lg-8 mb-4">
        <div class="card shadow">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-bar-chart-line"></i> Estatísticas Mensais (Ordens de Serviço)</h5>
            </div>
            <div class="card-body">
                <canvas id="monthlyChart" style="min-height: 300px; max-height:400px;"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-4 mb-4">
        <div class="card shadow">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Últimas Ordens de Serviço</h5>
            </div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                <?php if (!empty($latestOrders)): ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($latestOrders as $order): ?>
                    <a href="order_view.php?id=<?= $order['id'] ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between">
                            <h6 class="mb-1 text-primary"><?= htmlspecialchars($order['machine_name']) ?> (OS #<?= $order['id'] ?>)</h6>
                            <small class="badge bg-<?=
                                match($order['status']) {
                                    'aberta' => 'danger',
                                    'em_andamento' => 'warning',
                                    'concluida' => 'success',
                                    default => 'secondary'
                                } ?> text-white rounded-pill">
                                <?= ucfirst(str_replace('_', ' ', $order['status'])) ?>
                            </small>
                        </div>
                        <p class="mb-1 text-muted small"><?= htmlspecialchars(substr($order['description'], 0, 100)) . (strlen($order['description']) > 100 ? '...' : '') ?></p>
                        <small class="text-muted">Criada em: <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></small>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                    <p class="text-center text-muted mt-3">Nenhuma ordem de serviço recente para exibir.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-lg-12 mb-4">
        <div class="card shadow">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-stack"></i> Status das Ordens de Serviço por Mês</h5>
            </div>
            <div class="card-body">
                <canvas id="statusChart" style="min-height: 300px; max-height:400px;"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-lg-12 mb-4">
        <div class="card shadow">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-bar-chart-steps"></i> Linha do Tempo das Ordens de Serviço</h5>
            </div>
            <div class="card-body">
                 <?php if (!empty($ganttChartData)): ?>
                    <canvas id="ganttChartCanvas" style="min-height: 300px; max-height: <?= max(300, count($ganttChartLabels) * 35) ?>px;"></canvas>
                <?php else: ?>
                    <p class="text-center text-muted mt-3">Nenhuma ordem de serviço para exibir na linha do tempo.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; // end of admin/technician dashboard content ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script> <script>
    document.addEventListener('DOMContentLoaded', function () {
        const chartExists = (id) => document.getElementById(id) !== null;

        if (chartExists('monthlyChart')) {
            const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
            new Chart(monthlyCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($labels ?? []) ?>,
                    datasets: [{
                        label: 'Total de OS',
                        data: <?= json_encode($totalData ?? []) ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true, title: { display: true, text: 'Número de OS' }}, x: { title: { display: true, text: 'Mês/Ano' }}},
                    plugins: { tooltip: { callbacks: { label: c => `${c.dataset.label}: ${c.raw}` }}, legend: { position: 'top' }}
                }
            });
        }

        if (chartExists('statusChart')) {
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            new Chart(statusCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($labels ?? []) ?>,
                    datasets: [
                        { label: 'Abertas', data: <?= json_encode($abertasData ?? []) ?>, backgroundColor: 'rgba(220, 53, 69, 0.7)', borderWidth: 1 },
                        { label: 'Em Andamento', data: <?= json_encode($andamentoData ?? []) ?>, backgroundColor: 'rgba(255, 193, 7, 0.7)', borderWidth: 1 },
                        { label: 'Concluídas', data: <?= json_encode($concluidasData ?? []) ?>, backgroundColor: 'rgba(25, 135, 84, 0.7)', borderWidth: 1 }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true, stacked: true, title: { display: true, text: 'Número de OS' }}, x: { stacked: true, title: { display: true, text: 'Mês/Ano' }}},
                    plugins: { tooltip: { callbacks: { label: c => `${c.dataset.label}: ${c.raw}` }}, legend: { position: 'top' }}
                }
            });
        }

        // Gantt Chart Initialization
        if (chartExists('ganttChartCanvas') && <?= !empty($ganttChartData) ? 'true' : 'false' ?>) {
            const ganttCtx = document.getElementById('ganttChartCanvas').getContext('2d');
            new Chart(ganttCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($ganttChartLabels ?? []) ?>,
                    datasets: [{
                        label: 'Duração da OS', // This label might not be very visible with legend off
                        data: <?= json_encode($ganttChartData ?? []) ?>,
                        backgroundColor: <?= json_encode($ganttChartColors ?? []) ?>,
                        borderColor: <?= json_encode(array_map(fn($c) => str_replace('0.7', '1', $c), $ganttChartColors ?? [])) ?>,
                        borderWidth: 1,
                        borderSkipped: false, // Render border on all sides
                        borderRadius: 4,      // Slightly rounded bars
                        barPercentage: 0.6,
                        categoryPercentage: 0.7
                    }]
                },
                options: {
                    indexAxis: 'y', // Key for horizontal bar chart
                    responsive: true,
                    maintainAspectRatio: false, // Important for custom height
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                unit: 'day',
                                tooltipFormat: 'dd/MM/yyyy', // Date format for tooltips
                                displayFormats: {
                                    day: 'dd/MM' // Date format on the axis
                                }
                            },
                            min: '<?= $minGanttDate ?? date("Y-m-d", strtotime("-30 days")) ?>',
                            max: '<?= $maxGanttDate ?? date("Y-m-d") ?>',
                            title: {
                                display: true,
                                text: 'Linha do Tempo'
                            },
                            grid: {
                                display: true,
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        y: {
                            title: {
                                display: false, // Title can be in the card header
                                text: 'Ordens de Serviço'
                            },
                            ticks: {
                                autoSkip: false, // Try to show all labels
                                font: { size: 10 }
                            },
                            grid: {
                                display: false // Cleaner look for Y-axis grid
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false // Legend is not very useful here as colors are per-bar
                        },
                        tooltip: {
                            callbacks: {
                                title: function(tooltipItems) {
                                    return tooltipItems[0].label;
                                },
                                label: function(context) {
                                    const barData = context.raw;
                                    const startDate = new Date(barData[0]);
                                    const endDate = new Date(barData[1]);
                                    // Adjust end date for display: if it's next day of start for visibility, show as "ending" on start day
                                    let displayEndDate = endDate;
                                    if ( (endDate.getTime() - startDate.getTime()) === (24 * 60 * 60 * 1000) && barData[0] === barData[1].substring(0,10) ) {
                                        // This condition might be tricky if original end date was indeed just 1 day after start
                                    } else if( (endDate.getTime() - startDate.getTime()) <= (24 * 60 * 60 * 1000) ) {
                                        // if the original bar was made 1 day long just for visibility (e.g. created today),
                                        // we can say "Início: date" or "Previsto: date"
                                        const sDate = startDate.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
                                        return `Início: ${sDate}`;
                                    }


                                    const sDate = startDate.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
                                    const eDate = displayEndDate.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
                                    
                                    if (sDate === eDate) return `Data: ${sDate}`; // For single day events if logic is adjusted
                                    return `Período: ${sDate} - ${eDate}`;
                                }
                            }
                        }
                    }
                }
            });
        }
    });
</script>

<?php include 'footer.php'; ?>
