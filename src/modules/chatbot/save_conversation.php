<?php
session_start();
require_once '../../../config/config.php';

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Respond with an error or redirect
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

// Check if it's a POST request and contains necessary data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['query']) && isset($_POST['response'])) {
    $user_id = $_SESSION['user_id'];
    $query = $_POST['query'];
    $response = $_POST['response'];
    
    // Prepare an insert statement
    $sql = "INSERT INTO ai_consultations (user_id, query, response) VALUES (?, ?, ?)";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, "iss", $user_id, $query, $response);
        
        // Attempt to execute the prepared statement
        if (mysqli_stmt_execute($stmt)) {
            // Success
            http_response_code(200); // OK
            echo json_encode(['success' => true, 'message' => 'Conversation saved']);
        } else {
            // Error
            http_response_code(500); // Internal Server Error
            echo json_encode(['success' => false, 'message' => 'Error saving conversation', 'error' => mysqli_stmt_error($stmt)]);
        }

        // Close statement
        mysqli_stmt_close($stmt);
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'message' => 'Error preparing statement', 'error' => mysqli_error($conn)]);
    }
    
    // Close connection
    mysqli_close($conn);

} else {
    // Invalid request
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Invalid request']);
}
?> 