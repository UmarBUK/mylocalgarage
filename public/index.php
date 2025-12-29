<?php
// /public/index.php
require_once __DIR__ . '/../includes/header.php';
?>

<main class="container">
  <h1>Find a garage near you</h1>

  <form method="POST" action="/search.php" autocomplete="off">
    <label for="postcode">Enter your postcode</label><br>
    <input
      type="text"
      id="postcode"
      name="postcode"
      placeholder="e.g. SW1A 1AA"
      required
      maxlength="10"
    >
    <button type="submit">Find garages</button>
  </form>

  <p style="margin-top: 1rem; opacity: 0.8;">
    Enter a UK postcode to search for nearby garages.
  </p>
</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
