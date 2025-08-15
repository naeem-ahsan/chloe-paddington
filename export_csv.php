<?php
// export_csv.php
declare(strict_types=1);

// Load .env via your config.php
require __DIR__ . '/config.php';

// Read config from env
$host        = $_ENV['DB_HOST']      ?? $_SERVER['DB_HOST'];
$db          = $_ENV['DB_NAME']      ?? $_SERVER['DB_NAME'];
$user        = $_ENV['DB_USER']      ?? $_SERVER['DB_USER'];
$pass        = $_ENV['DB_PASS']      ?? $_SERVER['DB_PASS'];
$zipPassword = $_ENV['ZIP_PASSWORD'] ?? $_SERVER['ZIP_PASSWORD'];

if ($zipPassword === '') {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "ZIP password not configured. Set ZIP_PASSWORD in your .env.";
  exit;
}
if ($db === '') {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "DB_NAME not configured in .env.";
  exit;
}

// Connect via PDO
try {
  $pdo = new PDO(
    "mysql:host={$host};dbname={$db};charset=utf8mb4",
    $user,
    $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
  );
} catch (PDOException $e) {
  error_log('[DB ERROR] ' . $e->getMessage());
  http_response_code(500);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['success' => false, 'message' => 'Server error. Please try again later.']);
  exit;
}

// --- Fetching Rows ---
$stmt = $pdo->query("
  SELECT id, title, first_name, last_name, phone, email, preferred_colors, created_at
  FROM chloe_waitlist
  ORDER BY created_at DESC
");

// Build CSV
$csvTmp = tempnam(sys_get_temp_dir(), 'csv_');
if ($csvTmp === false) {
  http_response_code(500);
  echo "Unable to create temp CSV file.";
  exit;
}
$csv = fopen($csvTmp, 'w');
if ($csv === false) {
  @unlink($csvTmp);
  http_response_code(500);
  echo "Unable to open temp CSV file for writing.";
  exit;
}

// Column headers
fputcsv($csv, [
  'ID','Title','First Name','Last Name','Phone','Email','Preferred Colors','Submitted At'
], ',', '"', '\\');

// Data rows
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  fputcsv($csv, [
    $row['id'],
    $row['title'],
    $row['first_name'],
    $row['last_name'],
    $row['phone'],
    $row['email'],
    $row['preferred_colors'],
    $row['created_at'],
  ], ',', '"', '\\');
}
fclose($csv);

// Zip the CSV with password
$zipTmp = tempnam(sys_get_temp_dir(), 'zip_');
if ($zipTmp === false) {
  @unlink($csvTmp);
  http_response_code(500);
  echo "Unable to create temp ZIP file.";
  exit;
}

$zip = new ZipArchive();
if ($zip->open($zipTmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
  @unlink($csvTmp);
  @unlink($zipTmp);
  http_response_code(500);
  echo "Unable to create ZIP archive.";
  exit;
}

// timestamp the CSV name
$zipInnerName = 'chloe-waitlist_' . date('Y-m-d_H-i') . '.csv';

if (!$zip->addFile($csvTmp, $zipInnerName)) {
  $zip->close();
  @unlink($csvTmp);
  @unlink($zipTmp);
  http_response_code(500);
  echo "Unable to add CSV to ZIP.";
  exit;
}

// prefer AES-256, but force ZipCrypto for Windows
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$forceWindowsCompat = stripos($ua, 'Windows NT') !== false   // auto-detect Windows
                   || ($_ENV['ZIP_WINDOWS_COMPAT'] ?? '') === '1'   // or toggle via .env
                   || (isset($_GET['compat']) && $_GET['compat'] === '1');

$encryptionMethod = null;
if ($forceWindowsCompat && defined('ZipArchive::EM_TRAD_PKWARE')) {
    // ZipCrypto for Windows Explorer compatibility
    $encryptionMethod = ZipArchive::EM_TRAD_PKWARE;
} else {
    // If AES-256 if available, else fall back to ZipCrypto
    if (defined('ZipArchive::EM_AES_256')) {
        $encryptionMethod = ZipArchive::EM_AES_256;
    } elseif (defined('ZipArchive::EM_TRAD_PKWARE')) {
        $encryptionMethod = ZipArchive::EM_TRAD_PKWARE;
    }
}

if ($encryptionMethod === null) {
    $zip->close();
    @unlink($csvTmp);
    @unlink($zipTmp);
    http_response_code(500);
    echo "Encryption not supported by your PHP/Zip library.";
    exit;
}

$zip->setPassword($zipPassword);
if (!$zip->setEncryptionName($zipInnerName, $encryptionMethod, $zipPassword)) {
    $zip->close();
    @unlink($csvTmp);
    @unlink($zipTmp);
    http_response_code(500);
    echo "Failed to encrypt ZIP entry.";
    exit;
}

$zip->close();
@unlink($csvTmp);

// Stream the ZIP to the browser
$filename = 'chloe-waitlist_' . date('Y-m-d_H-i') . '.zip';

// Clean any output buffers to avoid corrupting the ZIP
while (ob_get_level() > 0) { ob_end_clean(); }

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($zipTmp));
header('X-Content-Type-Options: nosniff');

$fh = fopen($zipTmp, 'rb');
fpassthru($fh);
fclose($fh);
@unlink($zipTmp);
exit;
