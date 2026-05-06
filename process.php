<?php
header('Content-Type: application/json'); // Format wajib untuk komunikasi dengan JavaScript

// Membaca data JSON yang dikirim dari HTML
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "Tidak ada data yang diterima."]);
    exit;
}

$action = isset($data['action']) ? $data['action'] : '';

// ====================================================================
// AKSI 1: SIMPAN FOTO SEMENTARA PER JEPRETAN
// ====================================================================
if ($action === 'save_temp') {
    if (isset($data['image'])) {
        $image_parts = explode(";base64,", $data['image']);
        if (count($image_parts) < 2) {
             echo json_encode(["status" => "error", "message" => "Format base64 salah."]);
             exit;
        }
        $image_base64 = base64_decode($image_parts[1]);
        
        // Buat folder images jika belum ada
        if (!file_exists('images')) {
            mkdir('images', 0777, true);
        }

        $fileName = 'temp_' . uniqid() . '.png';
        $filePath = 'images/' . $fileName;

        // Simpan gambar mentah ke folder images/
        if (file_put_contents($filePath, $image_base64)) {
            echo json_encode(["status" => "success", "filename" => $fileName]);
        } else {
            echo json_encode(["status" => "error", "message" => "Gagal menyimpan foto ke folder images."]);
        }
    }
} 

// ====================================================================
// AKSI 2: FINALISASI (CETAK & EMAIL) - TANPA HAPUS OTOMATIS
// ====================================================================
elseif ($action === 'finalize') {
    if (isset($data['image'])) {
        $image_parts = explode(";base64,", $data['image']);
        $image_base64 = base64_decode($image_parts[1]);
        $email = isset($data['email']) ? $data['email'] : '';
        
        // Variabel $tempFiles tidak lagi diproses untuk dihapus

        // 1. Simpan gambar FINAL dalam format PNG (Siap Cetak)
        $finalName = 'cetak_' . date('Y-m-d_H-i-s') . '.png';
        $finalPath = __DIR__ . '/' . $finalName;
        file_put_contents($finalPath, $image_base64);

        // 2. Proses Kirim Email (Opsional)
        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $subject = "Hasil Fotobox Pastel Anda!";
            $message = "Halo! Terlampir adalah file digital hasil sesi foto Anda.";
            $boundary = md5(time());
            $headers = "From: noreply@fotobox.com\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"";

            $body = "--{$boundary}\r\n";
            $body .= "Content-Type: text/plain; charset=ISO-8859-1\r\n";
            $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $body .= $message . "\r\n\r\n";

            $body .= "--{$boundary}\r\n";
            $body .= "Content-Type: image/png; name=\"{$finalName}\"\r\n";
            $body .= "Content-Disposition: attachment; filename=\"{$finalName}\"\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode(file_get_contents($finalPath))) . "\r\n";
            $body .= "--{$boundary}--";

            @mail($email, $subject, $body, $headers);
        }

        // --- BAGIAN PENGHAPUSAN OTOMATIS TELAH DIHILANGKAN ---

        echo json_encode(["status" => "success", "message" => "Cetak selesai! Foto mentah tetap aman tersimpan di folder images."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Aksi tidak dikenal."]);
}
?>