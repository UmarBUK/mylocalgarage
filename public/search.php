<?php
// /public/search.php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.local.php'; // provides $pdo

/**
 * Normalize a UK postcode: trim, uppercase, collapse whitespace,
 * and ensure a single space before the last 3 characters where possible.
 */
function normalize_uk_postcode(string $raw): string
{
    $p = strtoupper(trim($raw));
    $p = preg_replace('/\s+/', '', $p) ?? ''; // remove all spaces

    // If too short, just return cleaned version
    if (strlen($p) < 5) return $p;

    // Insert a space before the last 3 chars (UK postcodes have 3-char inward code)
    return substr($p, 0, -3) . ' ' . substr($p, -3);
}

/**
 * Basic UK postcode format check (not perfect, but strong enough for MVP).
 * We still rely on the API response as the final authority.
 */
function is_valid_uk_postcode_format(string $postcode): bool
{
    // Accepts standard UK formats like "SW1A 1AA", "M1 1AE", "B33 8TH", etc.
    // Also supports BFPO with digits (simple allowance).
    $pattern = '/^(GIR 0AA|BFPO ?\d{1,4}|[A-Z]{1,2}\d[A-Z\d]? \d[A-Z]{2})$/';
    return (bool)preg_match($pattern, $postcode);
}

/**
 * Call postcodes.io to resolve postcode to lat/lng.
 * Returns array: ['ok' => bool, 'data' => mixed, 'error' => string|null]
 */
function lookup_postcode_latlng(string $postcode): array
{
    $url = 'https://api.postcodes.io/postcodes/' . rawurlencode($postcode);

    $ch = curl_init($url);
    if ($ch === false) {
        return ['ok' => false, 'data' => null, 'error' => 'Could not initialize HTTP client.'];
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_FAILONERROR => false, // we handle non-200 ourselves
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'User-Agent: MyLocalGarage/1.0',
        ],
    ]);

    $body = curl_exec($ch);
    $errNo = curl_errno($ch);
    $errStr = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errNo !== 0) {
        return ['ok' => false, 'data' => null, 'error' => 'Network error: ' . $errStr];
    }

    if ($body === false || $body === '') {
        return ['ok' => false, 'data' => null, 'error' => 'Empty response from postcode service.'];
    }

    $json = json_decode($body, true);
    if (!is_array($json)) {
        return ['ok' => false, 'data' => null, 'error' => 'Unexpected response format from postcode service.'];
    }

    // postcodes.io typically returns { status: 200/404, result: {...}|null }
    if ($httpCode !== 200 || ($json['result'] ?? null) === null) {
        $msg = $json['error'] ?? 'Postcode not found.';
        return ['ok' => false, 'data' => $json, 'error' => $msg];
    }

    $result = $json['result'];
    $lat = $result['latitude'] ?? null;
    $lng = $result['longitude'] ?? null;

    if (!is_numeric($lat) || !is_numeric($lng)) {
        return ['ok' => false, 'data' => $json, 'error' => 'Postcode found, but coordinates were missing.'];
    }

    return [
        'ok' => true,
        'data' => [
            'postcode' => $result['postcode'] ?? $postcode,
            'latitude' => (float)$lat,
            'longitude' => (float)$lng,
            'admin_district' => $result['admin_district'] ?? null,
            'region' => $result['region'] ?? null,
            'country' => $result['country'] ?? null,
        ],
        'error' => null
    ];
}

function find_garages_nearby(PDO $pdo, float $lat, float $lng, float $radiusMiles = 10.0, int $limit = 25): array
{
    // Haversine distance in miles
    $sql = "
      SELECT
        id, name, address_line1, address_line2, town, county, postcode,
        contact_email, phone, latitude, longitude,
        (
          3959 * 2 * ASIN(
            SQRT(
              POWER(SIN(RADIANS(latitude - :lat) / 2), 2) +
              COS(RADIANS(:lat)) * COS(RADIANS(latitude)) *
              POWER(SIN(RADIANS(longitude - :lng) / 2), 2)
            )
          )
        ) AS distance_miles
      FROM garages
      WHERE is_active = 1
      HAVING distance_miles <= :radius
      ORDER BY distance_miles ASC
      LIMIT :limit
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':lat', $lat);
    $stmt->bindValue(':lng', $lng);
    $stmt->bindValue(':radius', $radiusMiles);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

// ---- Controller logic ----

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo "<main class=\"container\"><h1>Method Not Allowed</h1><p>Please use the search form.</p></main>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$raw = (string)($_POST['postcode'] ?? '');
$normalized = normalize_uk_postcode($raw);

$errors = [];

if ($normalized === '' || strlen($normalized) < 5) {
    $errors[] = 'Please enter a postcode.';
} elseif (strlen($normalized) > 10) {
    $errors[] = 'Postcode looks too long.';
} elseif (!is_valid_uk_postcode_format($normalized)) {
    $errors[] = 'That postcode format doesn’t look valid. Please check and try again.';
}

echo "<main class=\"container\">";
echo "<h1>Search results</h1>";

if (!empty($errors)) {
    echo "<p><strong>We couldn’t search yet:</strong></p><ul>";
    foreach ($errors as $e) {
        echo "<li>" . htmlspecialchars($e, ENT_QUOTES, 'UTF-8') . "</li>";
    }
    echo "</ul>";
    echo "<p><a href=\"/\">Go back</a></p>";
    echo "</main>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$lookup = lookup_postcode_latlng($normalized);

if (!$lookup['ok']) {
    echo "<p><strong>Postcode lookup failed:</strong> " . htmlspecialchars($lookup['error'] ?? 'Unknown error', ENT_QUOTES, 'UTF-8') . "</p>";
    echo "<p>Postcode: <code>" . htmlspecialchars($normalized, ENT_QUOTES, 'UTF-8') . "</code></p>";
    echo "<p><a href=\"/\">Try again</a></p>";
    echo "</main>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$data = $lookup['data'];

$radiusMiles = 10.0;
$garages = find_garages_nearby($pdo, $data['latitude'], $data['longitude'], $radiusMiles);


echo "<p><strong>Postcode:</strong> <code>" . htmlspecialchars($data['postcode'], ENT_QUOTES, 'UTF-8') . "</code></p>";
echo "<p><strong>Coordinates:</strong> " . htmlspecialchars((string)$data['latitude'], ENT_QUOTES, 'UTF-8') . ", " . htmlspecialchars((string)$data['longitude'], ENT_QUOTES, 'UTF-8') . "</p>";
echo "<h2>Garages within " . htmlspecialchars((string)$radiusMiles, ENT_QUOTES, 'UTF-8') . " miles</h2>";

if (empty($garages)) {
    echo "<p>No garages found nearby yet. (Add sample garages to test.)</p>";
} else {
    echo "<ul>";
    foreach ($garages as $g) {
        $name = htmlspecialchars($g['name'], ENT_QUOTES, 'UTF-8');
        $town = htmlspecialchars($g['town'], ENT_QUOTES, 'UTF-8');
        $pc   = htmlspecialchars($g['postcode'], ENT_QUOTES, 'UTF-8');
        $dist = htmlspecialchars(number_format((float)$g['distance_miles'], 1), ENT_QUOTES, 'UTF-8');
        $id = (int)$g['id'];
        
        echo "<li><a href=\"/garage.php?id={$id}\"><strong>{$name}</strong></a> — {$town} ({$pc}) — {$dist} miles</li>";

        
    }
    echo "</ul>";
}



if (!empty($data['admin_district']) || !empty($data['region']) || !empty($data['country'])) {
    echo "<p style=\"opacity:0.85;\">";
    $parts = array_filter([$data['admin_district'], $data['region'], $data['country']]);
    echo htmlspecialchars(implode(', ', $parts), ENT_QUOTES, 'UTF-8');
    echo "</p>";
}



// Next phase placeholder:
echo "<hr>";
echo "<p><em>Next step:</em> use these coordinates to find garages within a radius and display them.</p>";
echo "<p><a href=\"/\">Search another postcode</a></p>";

echo "</main>";

require_once __DIR__ . '/../includes/footer.php';
