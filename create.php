<?php
require_once 'db.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = Database::getInstance();
    
    $data = [
        ':name' => $_POST['name'],
        ':model' => $_POST['model'],
        ':manufacturer' => $_POST['manufacturer'],
        ':acquisition_date' => $_POST['acquisition_date'],
        ':description' => $_POST['description']
    ];

    try {
        $stmt = $pdo->prepare("INSERT INTO machines 
            (name, model, manufacturer, acquisition_date, description)
            VALUES (:name, :model, :manufacturer, :acquisition_date, :description)");
        
        $stmt->execute($data);
        header('Location: list.php?success=1');
        exit();
    } catch (PDOException $e) {
        $error = "Erro ao cadastrar máquina: " . $e->getMessage();
    }
}
?>

<div class="card shadow">
    <div class="card-header bg-primary text-white">
        <h3 class="mb-0"><i class="bi bi-plus-circle"></i> Cadastrar Nova Máquina</h3>
    </div>
    
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nome da Máquina</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Modelo</label>
                    <input type="text" name="model" class="form-control" required>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Fabricante</label>
                    <input type="text" name="manufacturer" class="form-control" required>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Data de Aquisição</label>
                    <input type="date" name="acquisition_date" class="form-control" required>
                </div>
                
                <div class="col-12">
                    <label class="form-label">Descrição</label>
                    <textarea name="description" class="form-control" rows="4"></textarea>
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Salvar Máquina
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
