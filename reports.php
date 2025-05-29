<?php
session_start();
require_once 'db.php';
#require_once 'app/controllers/AuthController.php';
require_once 'app/vendor/fpdf/fpdf.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$pdo = Database::getInstance();

// Processar filtro de relatório
$reportType = $_GET['type'] ?? 'daily'; // daily ou monthly
$date = $_GET['date'] ?? date('Y-m-d');

// Buscar dados
try {
    if ($reportType === 'daily') {
        $stmt = $pdo->prepare("SELECT 
            o.*, m.name as machine_name, u.username as technician
            FROM service_orders o
            LEFT JOIN machines m ON o.machine_id = m.id
            LEFT JOIN users u ON o.user_id = u.id
            WHERE DATE(o.created_at) = ?
            ORDER BY o.created_at DESC");
        $stmt->execute([$date]);
    } else {
        $stmt = $pdo->prepare("SELECT 
            o.*, m.name as machine_name, u.username as technician
            FROM service_orders o
            LEFT JOIN machines m ON o.machine_id = m.id
            LEFT JOIN users u ON o.user_id = u.id
            WHERE MONTH(o.created_at) = MONTH(?) AND YEAR(o.created_at) = YEAR(?)
            ORDER BY o.created_at DESC");
        $stmt->execute([$date, $date]);
    }
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erro ao carregar relatório: " . $e->getMessage());
}

// Gerar PDF
if (isset($_GET['export'])) {
    class PDF extends FPDF {
        function Header() {
            $this->SetFont('Arial', 'B', 16);
            $this->Cell(0, 10, 'Relatorio de Ordens de Servico', 0, 1, 'C');
        }

        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);
            $this->Cell(0, 10, 'Pagina ' . $this->PageNo(), 0, 0, 'C');
        }
    }

    $pdf = new PDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 12);

    foreach ($orders as $order) {
        $pdf->Cell(0, 10, "Ordem #{$order['id']} - {$order['machine_name']}", 0, 1);
        $pdf->Cell(0, 10, "Tecnico: {$order['technician']}", 0, 1);
        $pdf->Cell(0, 10, "Status: " . ucfirst($order['status']), 0, 1);
        $pdf->Cell(0, 10, "Data: " . date('d/m/Y H:i', strtotime($order['created_at'])), 0, 1);
        $pdf->Ln(10);
    }

    $pdf->Output('D', 'relatorio.pdf');
    exit();
}

include 'header.php';
?>

<div class="container">
    <div class="card shadow-lg mt-4">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0"><i class="bi bi-file-earmark-pdf"></i> Relatórios</h3>
        </div>
        <div class="card-body">
            <form method="GET" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <select name="type" class="form-select">
                            <option value="daily" <?= $reportType === 'daily' ? 'selected' : '' ?>>Diário</option>
                            <option value="monthly" <?= $reportType === 'monthly' ? 'selected' : '' ?>>Mensal</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="date" name="date" class="form-control" value="<?= $date ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Ordem</th>
                            <th>Máquina</th>
                            <th>Técnico</th>
                            <th>Status</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?= $order['id'] ?></td>
                                <td><?= htmlspecialchars($order['machine_name']) ?></td>
                                <td><?= $order['technician'] ?? 'Não atribuído' ?></td>
                                <td>
                                    <span class="badge bg-<?= match($order['status']) {
                                        'aberta' => 'danger',
                                        'em_andamento' => 'warning',
                                        'concluida' => 'success'
                                    } ?>">
                                        <?= ucfirst($order['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <a href="reports.php?type=<?= $reportType ?>&date=<?= $date ?>&export=1" 
                   class="btn btn-success">
                    <i class="bi bi-download"></i> Exportar PDF
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
