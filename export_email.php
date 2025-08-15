<?php
// export_email.php
declare(strict_types=1);

// Load .env via config.php
require __DIR__ . '/config.php';
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Validate input email
if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    exit('Invalid email address.');
}
$emailTo = $_POST['email'];

// Read config
$host        = $_SERVER['DB_HOST'];
$db          = $_SERVER['DB_NAME'];
$user        = $_SERVER['DB_USER'];
$pass        = $_SERVER['DB_PASS'];
$zipPassword = $_ENV['ZIP_PASSWORD'] ?? $_SERVER['ZIP_PASSWORD'];

$smtpHost    = $_SERVER['SMTP_HOST'];
$smtpPort    = $_SERVER['SMTP_PORT'] ?? 587;
$smtpUser    = $_SERVER['SMTP_USER'];
$smtpPass    = $_SERVER['SMTP_PASS'];
$smtpFrom    = $_SERVER['SMTP_FROM'];
$smtpFromNm  = $_ENV['SMTP_FROM_NAME'] ?? $_SERVER['SMTP_FROM_NAME'] ?? 'Export Bot';

if ($zipPassword === '' || $db === '' || $host === '' || $user === '') {
    http_response_code(500);
    exit('Configuration error. Check DB_*, ZIP_PASSWORD in .env.');
}
if ($smtpHost === '' || $smtpUser === '' || $smtpFrom === '') {
    http_response_code(500);
    exit('SMTP not configured. Check SMTP_* in .env.');
}

// Connect DB
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
    exit('Server error: DB connection failed.');
}


$stmt = $pdo->query("
  SELECT id, title, first_name, last_name, phone, email, preferred_colors, created_at
  FROM chloe_waitlist
  ORDER BY created_at DESC
");

// Build CSV
$csvTmp = tempnam(sys_get_temp_dir(), 'csv_');
if ($csvTmp === false) {
    http_response_code(500);
    exit('Unable to create temp CSV.');
}
$csv = fopen($csvTmp, 'w');
if ($csv === false) {
    @unlink($csvTmp);
    http_response_code(500);
    exit('Unable to open temp CSV for writing.');
}
fputcsv($csv, [
    'ID','Title','First Name','Last Name','Phone','Email','Preferred Colors','Submitted At'
], ',', '"', '\\');

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

// Zip with password
$zipTmp = tempnam(sys_get_temp_dir(), 'zip_');
if ($zipTmp === false) {
    @unlink($csvTmp);
    http_response_code(500);
    exit('Unable to create temp ZIP.');
}

$zip = new ZipArchive();
if ($zip->open($zipTmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    @unlink($csvTmp);
    @unlink($zipTmp);
    http_response_code(500);
    exit('Unable to create ZIP archive.');
}

$zipInnerName = 'chloe-waitlist_' . date('Y-m-d_H-i') . '.csv';
if (!$zip->addFile($csvTmp, $zipInnerName)) {
    $zip->close();
    @unlink($csvTmp);
    @unlink($zipTmp);
    http_response_code(500);
    exit('Unable to add CSV to ZIP.');
}

// Windows Explorer compat toggle
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$forceWindowsCompat = stripos($ua, 'Windows NT') !== false
                   || (($_ENV['ZIP_WINDOWS_COMPAT'] ?? '') === '1')
                   || (isset($_GET['compat']) && $_GET['compat'] === '1');

$encryptionMethod = null;
if ($forceWindowsCompat && defined('ZipArchive::EM_TRAD_PKWARE')) {
    $encryptionMethod = ZipArchive::EM_TRAD_PKWARE;   // ZipCrypto
} else {
    if (defined('ZipArchive::EM_AES_256')) {
        $encryptionMethod = ZipArchive::EM_AES_256;   // AES-256
    } elseif (defined('ZipArchive::EM_TRAD_PKWARE')) {
        $encryptionMethod = ZipArchive::EM_TRAD_PKWARE;
    }
}
if ($encryptionMethod === null) {
    $zip->close();
    @unlink($csvTmp);
    @unlink($zipTmp);
    http_response_code(500);
    exit('ZIP encryption not supported by server.');
}

$zip->setPassword($zipPassword);
if (!$zip->setEncryptionName($zipInnerName, $encryptionMethod, $zipPassword)) {
    $zip->close();
    @unlink($csvTmp);
    @unlink($zipTmp);
    http_response_code(500);
    exit('Failed to encrypt ZIP entry.');
}
$zip->close();
@unlink($csvTmp);

// Email the ZIP ----------
try {
    $mail = new PHPMailer(true);
    $mail->CharSet   = 'UTF-8';
    $mail->isSMTP();
    $mail->Host       = $smtpHost;
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtpUser;   
    $mail->Password   = $smtpPass;   
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $smtpPort ?: 587;

    $mail->setFrom($smtpFrom, $smtpFromNm);
    $mail->addAddress($emailTo);

    $mail->Subject = 'Chloé Waitlist Export (Password-Protected ZIP)';
    $mail->isHTML(true);
    $mail->Body    = nl2br(
        "Hi,\n\n".
        "Attached is the latest waitlist export.\n".
        "ZIP filename: chloe-waitlist_".date('Y-m-d_H-i').".zip\n".
        "Password: {$zipPassword}\n\n".
        "Regards,\nChloé"
    );
    $mail->AltBody = "Attached is the latest waitlist export.\nPassword: {$zipPassword}";

    $mail->addAttachment($zipTmp, 'chloe-waitlist_' . date('Y-m-d_H-i') . '.zip');

    $mail->send();
    echo "Email sent to {$emailTo}";
} catch (Exception $e) {
    error_log('MAIL ERROR: ' . $e->getMessage());
    http_response_code(500);
    echo "Email failed: " . $e->getMessage();
} finally {
    @unlink($zipTmp);
}
