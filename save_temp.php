<?php
$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['image'])) {
    $image_parts = explode(";base64,", $data['image']);
    $image_base64 = base64_decode($image_parts[1]);
    
    if (!file_exists('images')) {
        mkdir('images', 0777, true);
    }

    $fileName = 'temp_' . uniqid() . '.png';
    $filePath = 'images/' . $fileName;

    if (file_put_contents($filePath, $image_base64)) {
        // Kembalikan nama file agar JavaScript bisa mencatatnya untuk dihapus nanti
        echo json_encode(["status" => "success", "filename" => $fileName]);
    } else {
        echo json_encode(["status" => "error"]);
    }
}
?>