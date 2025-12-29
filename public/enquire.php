<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.local.php';

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo "<main class=\"container\"><h1>Method Not Allowed</h1></main>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$garageId = (int)($_POST['garage_id'] ?? 0);
$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

$errors = [];
if ($garageId <= 0) $errors[] = "Invalid garage.";
if ($name === '' || mb_strlen($name) < 2) $errors[] = "Please enter your name.";
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Please enter a valid email.";
if ($message === '' || mb_strlen($message) < 10) $errors[] = "Please enter a bit more detail (min 10 chars).";
if (mb_strlen($message) > 2000) $errors[] = "Message is too long.";

$stmt = $pdo->prepare("SELECT id, name, contact_email FROM garages WHERE id = :id AND is_active = 1");
$stmt->execute([':id' => $garageId]);
$garage = $stmt->fetch();

if (!$garage) $errors[] = "Garage not found.";

echo "<main class=\"container\">";
echo "<h1>Enquiry</h1>";

if (!empty($errors)) {
    echo "<p><strong>Couldn’t send:</strong></p><ul>";
    foreach ($errors as $e) echo "<li>" . h($e) . "</li>";
    echo "</ul>";
    echo "<p><a href=\"/garage.php?id=" . (int)$garageId . "\">Go back</a></p>";
    echo "</main>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// ---- DEV mail: log to file (no SMTP yet) ----
$to = (string)$garage['contact_email'];
$subject = "New enquiry for " . (string)$garage['name'];

$body = "Garage: {$garage['name']} (ID {$garage['id']})\n"
      . "From: {$name}\n"
      . "Email: {$email}\n"
      . ($phone !== '' ? "Phone: {$phone}\n" : "")
      . "\nMessage:\n{$message}\n"
      . "\n---\nSent: " . date('c') . "\n";

$logDir = __DIR__ . '/../storage';
$logFile = $logDir . '/mail.log';

if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

file_put_contents($logFile, "TO: {$to}\nSUBJECT: {$subject}\n{$body}\n\n", FILE_APPEND);

echo "<p><strong>Sent!</strong> Your enquiry has been sent to the garage.</p>";
echo "<p><a href=\"/\">Search again</a></p>";
echo "</main>";

require_once __DIR__ . '/../includes/footer.php';
