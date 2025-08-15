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
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
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
    crossorigin="anonymous">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="./assets/css/style.css">
</head>

<body>
  <div class="container py-5">
    <h1 class="mb-4">Waiting List Submissions</h1>
    <div class="d-flex justify-content-between align-items-center pt-4 pb-3">
      <p class="mb-0">
        <a href="export_csv.php" class="btn btn-secondary">
          Download as CSV
        </a>
      </p>
      <!-- Send CSV to Email -->
      <form id="emailCsvForm" action="export_email.php" method="post"
        class="d-flex align-items-center gap-2 mt-3">
        <label for="csvEmail" class="mb-0">Send CSV to Email:</label>
        <input type="email" id="csvEmail" name="email"
          class="form-control form-control-sm w-auto"
          placeholder="name@example.com" required>
        <button type="submit" id="emailCsvBtn" class="btn btn-primary btn-sm">
          <span class="label">Send CSV</span>
          <span class="spinner-border spinner-border-sm d-none ms-2" role="status" aria-hidden="true"></span>
        </button>
      </form>
      <!-- Toast -->
      <div class="email-toast position-fixed bottom-0 p-3" style="z-index:1080">
        <div id="emailToast" class="toast align-items-center text-bg-success border-0" role="status" aria-live="polite" aria-atomic="true">
          <div class="d-flex">
            <div class="toast-body" id="emailToastBody">Email sent!</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
          </div>
        </div>
      </div>
    </div>

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

  <!-- jQuery (load first) -->
  <script src="https://code.jquery.com/jquery-3.7.1.slim.min.js" integrity="sha256-kmHvs0B+OpCW5GVHUNjv9rOmY0IvSIRcf7zGUDTDQM8=" crossorigin="anonymous"></script>
  <!-- Bootstrap JS Bundle -->
  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-…"
    crossorigin="anonymous"></script>
  <!-- Custom JS -->
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const form = document.getElementById('emailCsvForm');
      const btn = document.getElementById('emailCsvBtn');
      const spinner = btn.querySelector('.spinner-border');
      const label = btn.querySelector('.label');
      const toastEl = document.getElementById('emailToast');
      const toastBody = document.getElementById('emailToastBody');
      const toast = new bootstrap.Toast(toastEl, {
        delay: 4000
      });

      form.addEventListener('submit', (e) => {
        e.preventDefault();

        // UI: disable + show spinner
        btn.disabled = true;
        spinner.classList.remove('d-none');
        label.textContent = 'Sending…';

        fetch(form.action, {
            method: 'POST',
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: new FormData(form)
          })
          .then(async (res) => {
            const text = (await res.text()).trim();
            if (!res.ok) throw new Error(text || 'Request failed');

            // success toast
            toastEl.classList.remove('text-bg-danger');
            toastEl.classList.add('text-bg-success');
            toastBody.textContent = text || 'Email sent!';
            toast.show();
          })
          .catch(err => {
            // error toast
            toastEl.classList.remove('text-bg-success');
            toastEl.classList.add('text-bg-danger');
            toastBody.textContent = 'Failed to send email. ' + (err.message || '');
            toast.show();
          })
          .finally(() => {
            // reset UI
            btn.disabled = false;
            spinner.classList.add('d-none');
            label.textContent = 'Send CSV';
          });
      });
    });
  </script>
</body>

</html>