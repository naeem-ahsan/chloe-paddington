<?php
// export_csv.php

// Load Composer & .env
require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$host = getenv('DB_HOST');

// Grab DB creds from SERVER variables
$host = $_SERVER['DB_HOST'];
$db   = $_SERVER['DB_NAME'];
$user = $_SERVER['DB_USER'];
$pass = $_SERVER['DB_PASS'];

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


// Fetch all entries
$stmt = $pdo->query("
    SELECT id, title, first_name, last_name, phone, email, preferred_colors, created_at
    FROM chloe_waitlist
    ORDER BY created_at DESC
");

// Send CSV headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="chloe_waitlist.csv"');

// Open output stream & write rows
$output = fopen('php://output', 'w');

// Column headers
fputcsv(
  $output,
  ['ID','Title','First Name','Last Name','Phone','Email','Preferred Colors','Submitted At'],
  ',',    // delimiter
  '"',    // enclosure
  '\\'    // escape character
);

// Data rows
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv(
      $output,
      [
        $row['id'],
        $row['title'],
        $row['first_name'],
        $row['last_name'],
        $row['phone'],
        $row['email'],
        $row['preferred_colors'],
        $row['created_at'],
      ],
      ',',    // delimiter
      '"',    // enclosure
      '\\'    // escape character
    );
}

fclose($output);
exit;
