// app/controllers/OrderController.php
function uploadAttachments($orderId, $files) {
    $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
    $uploadDir = __DIR__ . '/../../assets/uploads/';

    foreach ($files['tmp_name'] as $key => $tmpName) {
        $fileType = mime_content_type($tmpName);
        if (!in_array($fileType, $allowedTypes)) {
            continue;
        }

        $fileName = uniqid() . '_' . basename($files['name'][$key]);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($tmpName, $targetPath)) {
            $stmt = $pdo->prepare("INSERT INTO attachments (...)");
            $stmt->execute([...]);
        }
    }
}
