<?php
declare(strict_types=1);
// Opens a customer's proof of payment. The uploads/pop folder is locked from the
// web, so staff can only see these files through here, signed in as admin.
require_once __DIR__ . '/../../includes/functions.php';
require_auth('admin');

$s = db()->prepare('SELECT pop_file FROM enquiries WHERE id = :id');
$s->execute([':id' => (int) ($_GET['id'] ?? 0)]);
$file = basename((string) $s->fetchColumn());
$path = UPLOAD_PATH . 'pop/' . $file;
if ($file === '' || !is_file($path)) {
    http_response_code(404);
    exit('Not found');
}
$types = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'pdf' => 'application/pdf'];
header('Content-Type: ' . ($types[strtolower(pathinfo($file, PATHINFO_EXTENSION))] ?? 'application/octet-stream'));
header('Content-Disposition: inline; filename="' . $file . '"');
header('X-Content-Type-Options: nosniff');
readfile($path);
