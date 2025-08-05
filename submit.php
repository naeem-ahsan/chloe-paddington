<?php
// submit.php

require __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//--- load config file to load the .env variables
require_once __DIR__ . '/config.php'; 

//--- JSON header
header('Content-Type: application/json');

// DB creds
$host = $_SERVER['DB_HOST'];
$db   = $_SERVER['DB_NAME'];
$user = $_SERVER['DB_USER'];
$pass = $_SERVER['DB_PASS'];

if (! $host || ! $db || ! $user) {
    error_log("Missing DB config: HOST={$host}, DB={$db}, USER={$user}");
    echo json_encode(['success'=>false,'message'=>'Configuration error.']);
    exit;
}

//--- Connect to DB
try {
    $pdo = new PDO(
      "mysql:host={$host};dbname={$db};charset=utf8mb4",
      $user,
      $pass,
      [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    // Log and return the real message once, to see what’s wrong
    error_log('[DB ERROR] '.$e->getMessage());
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    exit;
}

//--- Validation
$required = ['title','first_name','last_name','phone','email'];
foreach ($required as $f) {
  if (empty($_POST[$f])) {
    echo json_encode(['success'=>false,'message'=> ucfirst($f)." is required."]);
    exit;
  }
}
$allowedTitles = ['Mrs.','Mr.','Miss'];
$title = trim($_POST['title']);
if (! in_array($title, $allowedTitles, true)) {
  echo json_encode(['success'=>false,'message'=>'Invalid title.']);
  exit;
}
$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
if (! $email) {
  echo json_encode(['success'=>false,'message'=>'Invalid email address.']);
  exit;
}

$allowedColors = ['Black','Cream','Brown','Beige'];
$colors = array_filter((array)($_POST['preferred_colors'] ?? []),
            fn($c)=> in_array($c,$allowedColors,true)
);
$colorList = implode(',', $colors);

//--- Insert
$stmt = $pdo->prepare("
  INSERT INTO chloe_waitlist
    (title, first_name, last_name, phone, email, preferred_colors)
  VALUES
    (:title,:fn,:ln,:ph,:em,:pc)
");
$stmt->execute([
  ':title'=>$title,
  ':fn'=>trim($_POST['first_name']),
  ':ln'=>trim($_POST['last_name']),
  ':ph'=>trim($_POST['phone']),
  ':em'=>$email,
  ':pc'=>$colorList,
]);

//--- grab names for email
$firstName = trim($_POST['first_name']);
$lastName  = trim($_POST['last_name']);

//--- HTML email
$tmplPath = __DIR__.'/phpmailtemplate.html';
if (file_exists($tmplPath)) {
  $tpl = file_get_contents($tmplPath);
  $htmlBody = strtr($tpl, [
    '{{name}}'        => "$firstName $lastName",
    '{{url}}'         => rtrim($_ENV['APP_URL'],'/').'/',
    '{{supportMail}}' => getenv('SUPPORT_MAIL'),
  ]);
} else {
  $htmlBody = "<p>Hi $firstName,</p><p>Thanks for joining our list!</p>";
}

//--- Send via PHPMailer
try {
  $mail = new PHPMailer(true);
  $mail->CharSet = "UTF-8";
  $mail->isSMTP();
  $mail->Host       = $_SERVER['SMTP_HOST'];
  $mail->SMTPAuth   = true;
  $mail->Username   = $_SERVER['SMTP_USER'];
  $mail->Password   = $_SERVER['SMTP_PASS'];
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  $mail->Port       = $_SERVER['SMTP_PORT'] ?: 587;

  $mail->setFrom($_SERVER['SMTP_FROM'], $_SERVER['SMTP_FROM_NAME']);
  $mail->addAddress($email, "$firstName $lastName");
  $mail->isHTML(true);
  $mail->Subject = 'You\'re on the list for the Paddington bag!';
  $mail->Body    = $htmlBody;
  $mail->AltBody = "Hi $firstName,\n\nThanks for joining!";

  $mail->send();
} catch (Exception $e) {
  error_log('Mail error: '.$mail->ErrorInfo);
}

//--- Success!
echo json_encode(['success'=>true]);
exit;
