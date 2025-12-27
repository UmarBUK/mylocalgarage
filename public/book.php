<?php 
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php'; ?>



    <title>Book a Job – My Local Garage</title>

    <h1>Book a Job</h1>

    <form method="post" action="submit.php">
        <label>Name:</label><br>
        <input type="text" name="name" required><br><br>

        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>

        <label>Phone:</label><br>
        <input type="text" name="phone"><br><br>

        <label>Vehicle:</label><br>
        <input type="text" name="vehicle"><br><br>

        <label>Message:</label><br>
        <textarea name="message"></textarea><br><br>

        <button type="submit">Submit Booking</button>
    </form>

<?php  require_once __DIR__ . '/../includes/footer.php';?>
