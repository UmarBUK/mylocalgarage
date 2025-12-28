<?php
require_once __DIR__ . '/../../config/database.local.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<?php
ini_set('session.use_strict_mode', 1);
session_set_cookie_params([
    'httponly' => true,
    'secure' => true,
    'samesite' => 'Strict'
]);

$timeout = 900; // 15 minutes

if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > $timeout) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$_SESSION['last_activity'] = time();


if (empty($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$jobs = $pdo->query("SELECT * FROM jobs ORDER BY created_at DESC")->fetchAll();
?>

<a href="logout.php">Logout</a>
<hr>
<h1>Job Requests</h1>

<?php foreach ($jobs as $job): ?>
<hr>
<b><?= htmlspecialchars($job['customer_name']) ?></b><br>
<?= htmlspecialchars($job['email']) ?><br>
<?= htmlspecialchars($job['vehicle']) ?><br>
<?= nl2br(htmlspecialchars($job['message'])) ?><br>
<small><?= $job['created_at'] ?></small>
<?php endforeach; ?>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>

