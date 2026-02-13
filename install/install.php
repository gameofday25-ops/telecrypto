<?php

declare(strict_types=1);

$errors = [];
$checks = [
    'pdo' => extension_loaded('pdo'),
    'pdo_mysql' => extension_loaded('pdo_mysql'),
    'json' => extension_loaded('json'),
    'openssl' => extension_loaded('openssl'),
    'mbstring' => extension_loaded('mbstring'),
    'bcmath' => extension_loaded('bcmath'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $env = sprintf(
"APP_ENV=production
APP_DEBUG=0
APP_TIMEZONE=UTC
BOT_TOKEN=%s
WEBAPP_URL=%s
ADMIN_IDS=%s
DB_HOST=%s
DB_PORT=%s
DB_NAME=%s
DB_USER=%s
DB_PASS=%s
",
        trim($_POST['BOT_TOKEN'] ?? ''),
        trim($_POST['WEBAPP_URL'] ?? ''),
        trim($_POST['ADMIN_IDS'] ?? ''),
        trim($_POST['DB_HOST'] ?? 'localhost'),
        trim($_POST['DB_PORT'] ?? '3306'),
        trim($_POST['DB_NAME'] ?? ''),
        trim($_POST['DB_USER'] ?? ''),
        trim($_POST['DB_PASS'] ?? '')
    );

    $root = dirname(__DIR__);
    file_put_contents($root . '/.env', $env);

    try {
        $pdo = new PDO('mysql:host=' . $_POST['DB_HOST'] . ';port=' . $_POST['DB_PORT'] . ';dbname=' . $_POST['DB_NAME'] . ';charset=utf8mb4', $_POST['DB_USER'], $_POST['DB_PASS']);
        $ok = (bool)$pdo;
    } catch (Throwable $e) {
        $ok = false;
        $errors[] = $e->getMessage();
    }

    if ($ok) {
        $success = 'Готово: .env записан, БД подключается.';
    }
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Install</title></head><body>
<h1>TeleCrypto install</h1>
<ul>
<?php foreach ($checks as $name => $status): ?>
  <li><?= htmlspecialchars($name) ?>: <?= $status ? 'OK' : 'MISSING' ?></li>
<?php endforeach; ?>
</ul>
<?php if (!empty($errors)): ?><pre><?= htmlspecialchars(implode("\n", $errors)) ?></pre><?php endif; ?>
<?php if (!empty($success)): ?><p><?= htmlspecialchars($success) ?></p><?php endif; ?>
<form method="post">
  <input name="BOT_TOKEN" placeholder="BOT_TOKEN" required><br>
  <input name="WEBAPP_URL" placeholder="https://domain.com/app/" required><br>
  <input name="ADMIN_IDS" placeholder="12345,6789" required><br>
  <input name="DB_HOST" value="localhost"><br>
  <input name="DB_PORT" value="3306"><br>
  <input name="DB_NAME" placeholder="db" required><br>
  <input name="DB_USER" placeholder="user" required><br>
  <input name="DB_PASS" placeholder="pass" required><br>
  <button>Сохранить</button>
</form>
</body></html>
