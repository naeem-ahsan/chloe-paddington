<?php
// submit.php

// Load Composer & .env
require __DIR__ . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__)->load();

// JSON response header
header('Content-Type: application/json');

// Grab DB creds from env
$host = getenv('DB_HOST') ?: '127.0.0.1';
$db   = getenv('DB_NAME') ?: 'paddington';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

// Connect via PDO
try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$db};charset=utf8mb4",
        $user,
        $pass,
        [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION ]
    );
} catch (PDOException $e) {
    error_log('[DB ERROR] ' . $e->getMessage());
    echo json_encode([
      'success' => false,
      'message' => 'Server error. Please try again later.'
    ]);
    exit;
}

// Required-fields check
$required = ['title','first_name','last_name','phone','email'];
foreach ($required as $f) {
    if (empty($_POST[$f])) {
        echo json_encode(['success'=>false,'message'=> ucfirst($f) . " is required."]);
        exit;
    }
}

// title validation
$allowedTitles = ['Mrs.','Mr.','Miss'];
$title = trim($_POST['title']);
if (! in_array($title, $allowedTitles, true)) {
    echo json_encode([
      'success' => false,
      'message' => 'Invalid title selection.'
    ]);
    exit;
}

// Email format
$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
if (! $email) {
    echo json_encode(['success'=>false,'message'=>'Invalid email address.']);
    exit;
}

// Preferred colors
$allowedColors = ['Black','Cream','Brown','Beige'];
$colors = array_filter(
    (array)($_POST['preferred_colors'] ?? []),
    fn($c)=> in_array($c, $allowedColors, true)
);
$colorList = implode(',', $colors);

// Insert into database
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
