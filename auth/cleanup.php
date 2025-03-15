<?php
// Connect without database selected
$conn = new mysqli('localhost', 'root', '');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Drop and recreate database
$conn->query("DROP DATABASE IF EXISTS medical_management");

if ($conn->error) {
    echo "Error: " . $conn->error;
} else {
    echo "Database reset complete with fresh tables and default data.";
}

$conn->close();
