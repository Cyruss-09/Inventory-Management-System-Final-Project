<?php
$host = "localhost";
$dbname = "inventory_db"; // <-- Replace this with your actual database name (e.g., eventpro_db or inventory_db)
$username = "root";             // Default XAMPP username
$password = "";                 // Default XAMPP password is empty

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Force PDO to throw exceptions on error so you can catch them cleanly
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Kill execution if the database won't connect so settings.php doesn't try to run queries on a broken connection
    die("Database Connection failed: " . $e->getMessage());
}
?>