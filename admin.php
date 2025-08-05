<?php
// admin.php

// Load Composer & .env
require __DIR__ . '/vendor/autoload.php';
//--- load config file to load the .env variables
require_once __DIR__ . '/config.php';

$host = $_ENV['DB_HOST'];

// Fetch admin creds from env
$adminUser = $_ENV['ADMIN_USER'];
$adminPass = $_ENV['ADMIN_PASS'];

// HTTP-Basic auth handshake
if (
    !isset($_SERVER['PHP_AUTH_USER']) ||
    $_SERVER['PHP_AUTH_USER'] !== $adminUser ||
    $_SERVER['PHP_AUTH_PW']   !== $adminPass
) {
    header('WWW-Authenticate: Basic realm="Admin Area"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Authentication required.';
    exit;
}

// Grab DB creds from SERVER
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

// Fetch all entries, most recent first
$stmt = $pdo->query("SELECT id, title, first_name, last_name, phone, email, preferred_colors, created_at
                     FROM chloe_waitlist
                     ORDER BY created_at DESC");
$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Waiting List Submissions</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <!-- Bootstrap CSS -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
    rel="stylesheet"
    integrity="sha384-…"
    crossorigin="anonymous"
  >
</head>
<body>
  <div class="container py-5">
    <h1 class="mb-4">Waiting List Submissions</h1>

    <p>
      <a href="export_csv.php" class="btn btn-secondary">
        Download as CSV
      </a>
    </p>

    <table class="table table-striped table-bordered">
      <thead class="table-dark">
        <tr>
          <th>ID</th>
          <th>Title</th>
          <th>Name</th>
          <th>Phone</th>
          <th>Email</th>
          <th>Colors</th>
          <th>Submitted At</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($entries as $e): ?>
          <tr>
            <td><?= htmlspecialchars($e['id']) ?></td>
            <td><?= htmlspecialchars($e['title']) ?: '—' ?></td>
            <td>
              <?= htmlspecialchars($e['first_name']) ?>
              <?= htmlspecialchars($e['last_name']) ?>
            </td>
            <td><?= htmlspecialchars($e['phone']) ?></td>
            <td><?= htmlspecialchars($e['email']) ?></td>
            <td><?= htmlspecialchars($e['preferred_colors']) ?></td>
            <td><?= htmlspecialchars($e['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Bootstrap JS Bundle -->
  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-…"
    crossorigin="anonymous"
  ></script>
</body>
</html>
