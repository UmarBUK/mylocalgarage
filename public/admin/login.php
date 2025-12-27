<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<?php

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = :u");
    $stmt->execute(['u' => $_POST['username']]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($_POST['password'], $admin['password_hash'])) {
        session_regenerate_id(true);
	$_SESSION['admin'] = true;
        header('Location: jobs.php');
        exit;
    }

    $error = "Invalid login";
}
?>
<h1>Admin Login</h1>

<?php
if (!empty($error)) {
    echo "<p class='error'>$error</p>";
}
?>

<form method="post">
    <input name="username" placeholder="Username" required><br><br>
    <input name="password" type="password" placeholder="Password" required><br><br>
    <button type="submit">Login</button>
</form>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>

