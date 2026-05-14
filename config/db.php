<?php
$host = "localhost";
$dbname = "inventory_db";
$username = "root"; 
$password = ""; 

try {
    // Added charset for better symbol support (like ₱)
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
// No closing tag needed herey