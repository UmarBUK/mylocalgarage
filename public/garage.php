<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.local.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    http_response_code(400);
    echo "<main class=\"container\"><h1>Bad request</h1><p>Missing garage ID.</p></main>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$stmt = $pdo->prepare("SELECT id, name, address_line1, address_line2, town, county, postcode, contact_email, phone
                       FROM garages
                       WHERE id = :id AND is_active = 1");
$stmt->execute([':id' => $id]);
$garage = $stmt->fetch();

if (!$garage) {
    http_response_code(404);
    echo "<main class=\"container\"><h1>Not found</h1><p>Garage not found.</p></main>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

echo "<main class=\"container\">";
echo "<h1>" . h($garage['name']) . "</h1>";
echo "<p>" . h($garage['address_line1']);
if (!empty($garage['address_line2'])) echo ", " . h($garage['address_line2']);
echo ", " . h($garage['town']);
if (!empty($garage['county'])) echo ", " . h($garage['county']);
echo ", " . h($garage['postcode']) . "</p>";

echo "<h2>Send an enquiry</h2>";

echo "<form method=\"POST\" action=\"/enquire.php\" autocomplete=\"off\">
  <input type=\"hidden\" name=\"garage_id\" value=\"" . (int)$garage['id'] . "\">

  <label for=\"name\">Your name</label><br>
  <input id=\"name\" name=\"name\" type=\"text\" required maxlength=\"80\"><br><br>

  <label for=\"email\">Your email</label><br>
  <input id=\"email\" name=\"email\" type=\"email\" required maxlength=\"160\"><br><br>

  <label for=\"phone\">Phone (optional)</label><br>
  <input id=\"phone\" name=\"phone\" type=\"text\" maxlength=\"30\"><br><br>

  <label for=\"message\">What do you need help with?</label><br>
  <textarea id=\"message\" name=\"message\" rows=\"6\" required maxlength=\"2000\"></textarea><br><br>

  <button type=\"submit\">Send to garage</button>
</form>";

echo "<p style=\"margin-top:1rem;\"><a href=\"/\">Search again</a></p>";
echo "</main>";

require_once __DIR__ . '/../includes/footer.php';
