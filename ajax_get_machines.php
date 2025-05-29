<?php
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_GET['setor'])) {
    echo json_encode([]);
    exit();
}

$pdo = Database::getInstance();
$setor = $_GET['setor'];

try {
    $stmt = $pdo->prepare("SELECT id, equipment FROM machines WHERE sector = ? ORDER BY equipment");
    $stmt->execute([$setor]);
    $maquinas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($maquinas);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
