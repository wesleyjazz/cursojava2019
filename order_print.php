<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$orderId = $_GET['id'] ?? null;
if (!$orderId) {
    header('Location: orders.php');
    exit();
}

$pdo = Database::getInstance();

try {
    // Consulta principal da ordem de serviço
    $stmt = $pdo->prepare("SELECT 
        o.id, o.type, o.work_type, o.priority, o.status, 
        o.description, o.created_at, o.completed_date, o.scheduled_date,
        m.sector, m.equipment, m.model, m.manufacturer,
        m.axis, m.rotor, m.gasket, m.motor, m.hp, m.rpm, m.amp, m.motor_bearing,
        u.username as technician, u.role as technician_role,
        m.name as machine_name
        FROM service_orders o
        LEFT JOIN machines m ON o.machine_id = m.id
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.id = ?");
    
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        throw new Exception("Ordem de serviço não encontrada");
    }

    // Consulta dos anexos com conversão para base64
    $attachments = $pdo->prepare("SELECT * FROM attachments WHERE order_id = ?");
    $attachments->execute([$orderId]);
    $attachments = $attachments->fetchAll();

    // Converter imagens para base64
    $attachmentsWithBase64 = [];
    foreach ($attachments as $attachment) {
        $filePath = 'uploads/' . basename($attachment['file_path']);
        $base64 = '';
        
        if (file_exists($filePath)) {
            $mimeType = mime_content_type($filePath);
            if (strpos($mimeType, 'image/') === 0) {
                $fileData = file_get_contents($filePath);
                $base64 = 'data:' . $mimeType . ';base64,' . base64_encode($fileData);
            }
        }
        
        $attachment['base64'] = $base64;
        $attachmentsWithBase64[] = $attachment;
    }

    $history = $pdo->prepare("SELECT 
        h.action, h.details, h.created_at,
        u.username as user
        FROM order_history h
        LEFT JOIN users u ON h.user_id = u.id
        WHERE h.order_id = ?
        ORDER BY h.created_at DESC");
    $history->execute([$orderId]);
    $history = $history->fetchAll();

} catch (PDOException $e) {
    die("Erro ao carregar ordem de serviço: " . $e->getMessage());
}

header("Content-Type: text/html; charset=UTF-8");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordem de Serviço #<?= $order['id'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        /* Estilos para tela */
        @media screen {
            body {
                background-color: #f8f9fa;
                padding: 20px;
            }
            .print-container {
                max-width: 800px;
                margin: 0 auto;
                background: white;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
                padding: 20px;
            }
            .no-print {
                display: block;
            }
        }
        
        /* Estilos para impressão */
        @media print {
            @page {
                size: A4 portrait;
                margin: 1cm;
            }
            body {
                background: white;
                font-size: 11pt;
                line-height: 1.3;
                padding: 0;
            }
            .print-container {
                width: 100%;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
            .no-print {
                display: none !important;
            }
            .section {
                page-break-inside: avoid;
                margin-bottom: 15px;
            }
            .attachment-thumbnail {
                max-width: 150px !important;
                max-height: 100px !important;
                display: block !important;
            }
            .attachment-card {
                page-break-inside: avoid;
            }
        }
        
        /* Estilos compartilhados */
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 18pt;
            color: #007bff;
            margin-bottom: 5px;
        }
        .header p {
            font-size: 11pt;
            color: #6c757d;
            margin-bottom: 0;
        }
        .card {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            padding: 8px 15px;
            font-weight: bold;
        }
        .card-body {
            padding: 15px;
        }
        .info-label {
            font-weight: 500;
            color: #495057;
        }
        .signature-area {
            margin-top: 30px;
            border-top: 1px dashed #6c757d;
            padding-top: 15px;
        }
        .badge {
            font-size: 0.85em;
            padding: 4px 8px;
        }
        .attachments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .attachment-card {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            text-align: center;
        }
        .attachment-thumbnail {
            max-width: 100%;
            max-height: 120px;
            margin-bottom: 8px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        .file-icon {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 8px;
        }
        .attachment-filename {
            word-break: break-word;
            font-size: 0.8rem;
        }
        .attachment-date {
            font-size: 0.7rem;
            color: #6c757d;
        }
        .missing-file {
            color: #dc3545;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="print-container">
        <!-- Cabeçalho -->
        <div class="header">
            <h1>Ordem de Serviço #<?= $order['id'] ?></h1>
            <p>Sistema de Gestão de Manutenção - <?= date('d/m/Y H:i') ?></p>
        </div>

        <!-- Botão de impressão (não aparece na impressão) -->
        <div class="no-print text-end mb-3">
            <button onclick="printWithImages()" class="btn btn-primary">
                <i class="bi bi-printer"></i> Imprimir
            </button>
            <a href="order_view.php?id=<?= $order['id'] ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <!-- Seção 1: Informações Básicas -->
        <div class="section">
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="bi bi-info-circle"></i> Informações da OS
                        </div>
                        <div class="card-body">
                            <div class="row mb-2">
                                <div class="col-5 info-label">Status:</div>
                                <div class="col-7">
                                    <span class="badge bg-<?= match($order['status']) {
                                        'aberta' => 'secondary',
                                        'em_andamento' => 'primary',
                                        'concluida' => 'success',
                                        default => 'light'
                                    } ?>">
                                        <?= ucfirst($order['status']) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 info-label">Tipo:</div>
                                <div class="col-7"><?= ucfirst($order['type']) ?></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 info-label">Tipo de Trabalho:</div>
                                <div class="col-7"><?= ucfirst($order['work_type']) ?></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 info-label">Prioridade:</div>
                                <div class="col-7">
                                    <span class="badge bg-<?= match($order['priority']) {
                                        'baixa' => 'success',
                                        'media' => 'warning',
                                        'alta' => 'danger',
                                        default => 'light'
                                    } ?>">
                                        <?= ucfirst($order['priority']) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 info-label">Data Abertura:</div>
                                <div class="col-7"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></div>
                            </div>
                            <?php if ($order['scheduled_date']): ?>
                            <div class="row mb-2">
                                <div class="col-5 info-label">Data Agendada:</div>
                                <div class="col-7"><?= date('d/m/Y', strtotime($order['scheduled_date'])) ?></div>
                            </div>
                            <?php endif; ?>
                            <?php if ($order['completed_date']): ?>
                            <div class="row mb-2">
                                <div class="col-5 info-label">Data Conclusão:</div>
                                <div class="col-7"><?= date('d/m/Y', strtotime($order['completed_date'])) ?></div>
                            </div>
                            <?php endif; ?>
                            <div class="row">
                                <div class="col-5 info-label">Técnico:</div>
                                <div class="col-7"><?= $order['technician'] ?? 'Não atribuído' ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="bi bi-gear"></i> Equipamento
                        </div>
                        <div class="card-body">
                            <div class="row mb-2">
                                <div class="col-5 info-label">Nome:</div>
                                <div class="col-7"><?= htmlspecialchars($order['machine_name']) ?></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 info-label">Setor:</div>
                                <div class="col-7"><?= htmlspecialchars($order['sector']) ?></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 info-label">Equipamento:</div>
                                <div class="col-7"><?= htmlspecialchars($order['equipment']) ?></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 info-label">Modelo:</div>
                                <div class="col-7"><?= htmlspecialchars($order['model']) ?></div>
                            </div>
                            <div class="row">
                                <div class="col-5 info-label">Fabricante:</div>
                                <div class="col-7"><?= htmlspecialchars($order['manufacturer']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Seção 2: Descrição -->
        <div class="section">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-card-text"></i> Descrição do Problema
                </div>
                <div class="card-body">
                    <?= nl2br(htmlspecialchars($order['description'])) ?>
                </div>
            </div>
        </div>

        <!-- Seção 3: Especificações Técnicas -->
        <div class="section">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-tools"></i> Especificações Técnicas
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="row mb-2">
                                <div class="col-5 info-label">Eixo:</div>
                                <div class="col-7"><?= htmlspecialchars($order['axis'] ?? 'N/A') ?></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 info-label">Rotor:</div>
                                <div class="col-7"><?= htmlspecialchars($order['rotor'] ?? 'N/A') ?></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 info-label">Gaxeta:</div>
                                <div class="col-7"><?= htmlspecialchars($order['gasket'] ?? 'N/A') ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="row mb-2">
                                <div class="col-5 info-label">Motor:</div>
                                <div class="col-7"><?= htmlspecialchars($order['motor'] ?? 'N/A') ?></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 info-label">CV:</div>
                                <div class="col-7"><?= htmlspecialchars($order['hp'] ?? 'N/A') ?></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 info-label">RPM:</div>
                                <div class="col-7"><?= htmlspecialchars($order['rpm'] ?? 'N/A') ?></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 info-label">AMP:</div>
                                <div class="col-7"><?= htmlspecialchars($order['amp'] ?? 'N/A') ?></div>
                            </div>
                            <div class="row">
                                <div class="col-5 info-label">Rolamento:</div>
                                <div class="col-7"><?= htmlspecialchars($order['motor_bearing'] ?? 'N/A') ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Seção 4: Anexos - VERSÃO FUNCIONANDO NA IMPRESSÃO -->
        <?php if (!empty($attachmentsWithBase64)): ?>
        <div class="section">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-paperclip"></i> Anexos (<?= count($attachmentsWithBase64) ?>)
                </div>
                <div class="card-body">
                    <div class="attachments-grid">
                        <?php foreach ($attachmentsWithBase64 as $attachment): 
                            $fileExt = strtolower(pathinfo($attachment['file_name'], PATHINFO_EXTENSION));
                            $isImage = !empty($attachment['base64']);
                            
                            $fileIcons = [
                                'pdf' => 'file-earmark-pdf',
                                'doc' => 'file-earmark-word',
                                'docx' => 'file-earmark-word',
                                'xls' => 'file-earmark-excel',
                                'xlsx' => 'file-earmark-excel',
                                'zip' => 'file-earmark-zip',
                                'rar' => 'file-earmark-zip',
                                'txt' => 'file-earmark-text',
                            ];
                            $iconClass = $fileIcons[$fileExt] ?? 'file-earmark';
                        ?>
                            <div class="attachment-card">
                                <?php if ($isImage): ?>
                                    <img src="<?= $attachment['base64'] ?>" 
                                         class="attachment-thumbnail" 
                                         alt="<?= htmlspecialchars($attachment['file_name']) ?>">
                                <?php else: ?>
                                    <i class="bi bi-<?= $iconClass ?> file-icon"></i>
                                <?php endif; ?>
                                
                                <div class="attachment-filename">
                                    <?= htmlspecialchars($attachment['file_name']) ?>
                                </div>
                                <div class="attachment-date">
                                    <?= date('d/m/Y H:i', strtotime($attachment['created_at'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Seção 5: Histórico -->
        <?php if (!empty($history)): ?>
        <div class="section">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-clock-history"></i> Histórico de Atualizações
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm compact-table">
                            <thead>
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Ação</th>
                                    <th>Responsável</th>
                                    <th>Detalhes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $item): ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i', strtotime($item['created_at'])) ?></td>
                                        <td><?= ucfirst($item['action']) ?></td>
                                        <td><?= htmlspecialchars($item['user']) ?></td>
                                        <td><?= nl2br(htmlspecialchars($item['details'] ?? '')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Seção 6: Assinaturas -->
        <div class="section">
            <div class="row">
                <div class="col-md-6">
                    <div class="signature-area">
                        <p class="text-center mb-1"><strong>Assinatura do Técnico</strong></p>
                        <p class="text-center mb-1">Nome: <?= $order['technician'] ?? '_________________________' ?></p>
                        <p class="text-center">Data: <?= date('d/m/Y') ?></p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="signature-area">
                        <p class="text-center mb-1"><strong>Assinatura do Responsável</strong></p>
                        <p class="text-center mb-1">Nome: _________________________</p>
                        <p class="text-center">Data: <?= date('d/m/Y') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function printWithImages() {
            const images = document.querySelectorAll('img');
            let imagesToLoad = images.length;
            
            if (imagesToLoad === 0) {
                window.print();
                return;
            }
            
            // Verificar quais imagens já estão carregadas
            images.forEach(img => {
                if (img.complete) {
                    imagesToLoad--;
                } else {
                    img.addEventListener('load', imageLoaded);
                    img.addEventListener('error', imageLoaded); // Mesmo se der erro, continuamos
                }
            });
            
            function imageLoaded() {
                imagesToLoad--;
                if (imagesToLoad <= 0) {
                    window.print();
                }
            }
            
            if (imagesToLoad <= 0) {
                window.print();
            }
        }
    </script>
</body>
</html>
