<?php
// submit.php
header('Content-Type: application/json');
$config = require 'config.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
        $config['db_user'],
        $config['db_pass'],
        [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION ]
    );
} catch (PDOException $e) {
    error_log('[DB ERROR] '.$e->getMessage());
    echo json_encode([
      'success' => false,
      'message' => 'Server error. Please try again later.'
    ]);
    exit;
}

// Required fields validation
$required = ['first_name','last_name','phone','email'];
foreach ($required as $f) {
    if (empty($_POST[$f])) {
        echo json_encode(['success'=>false,'message'=>"“{$f}” is required."]);
        exit;
    }
}

// Title (optional) validation
$allowedTitles = ['Mrs.','Mr.','Miss'];
$title = trim($_POST['title'] ?? '');
if ($title !== '' && ! in_array($title, $allowedTitles, true)) {
    echo json_encode(['success'=>false,'message'=>'Invalid title selection.']);
    exit;
}

// Email validation
$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
if (! $email) {
    echo json_encode(['success'=>false,'message'=>'Invalid email address.']);
    exit;
}

// Preferred colors
$allowedColors = ['Black','Cream','Brown','Beige'];
$colors = array_filter(
  (array)($_POST['preferred_colors'] ?? []),
  fn($c)=> in_array($c,$allowedColors, true)
);
$colorList = implode(',', $colors);

// Insert
$stmt = $pdo->prepare("
  INSERT INTO waitlist
    (title, first_name, last_name, phone, email, preferred_colors)
  VALUES
    (:title, :fn, :ln, :ph, :em, :pc)
");
$stmt->execute([
  ':title' => $title,
  ':fn'    => trim($_POST['first_name']),
  ':ln'    => trim($_POST['last_name']),
  ':ph'    => trim($_POST['phone']),
  ':em'    => $email,
  ':pc'    => $colorList,
]);

// Success!
echo json_encode(['success'=>true]);
exit;