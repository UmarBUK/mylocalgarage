<?php
require_once __DIR__ . '/../config/database.local.php';
require_once __DIR__ . '/../includes/header.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare(
        "INSERT INTO jobs (customer_name, email, phone, vehicle, message)
         VALUES (:name, :email, :phone, :vehicle, :message)"
    );

    $stmt->execute([
        'name'    => $_POST['name'] ?? '',
        'email'   => $_POST['email'] ?? '',
        'phone'   => $_POST['phone'] ?? '',
        'vehicle' => $_POST['vehicle'] ?? '',
        'message' => $_POST['message'] ?? ''
    ]);

    $message = 'Booking received! Thank you — we’ll be in touch shortly.';
}
?>

<?php if ($message): ?>
    <div class="alert success">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

