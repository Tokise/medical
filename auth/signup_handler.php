<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    // Check if email exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Email already exists'
        ]);
        exit();
    }

    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $email, $password);

    if ($stmt->execute()) {
        $_SESSION['user_id'] = $conn->insert_id;
        echo json_encode([
            'success' => true,
            'redirect' => '../../login.php'
        ]);
        exit();
    }

    echo json_encode([
        'success' => false,
        'message' => 'Failed to create account'
    ]);
    exit();
}
