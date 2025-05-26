<?php
session_start();
require_once '../../../../../../config/config.php';

// Check if user is logged in and has a valid patient role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['student', 'teacher', 'staff'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Check if it's a POST request and contains necessary data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prescription_medication_id']) && isset($_POST['patient_status'])) {
    $user_id = $_SESSION['user_id'];
    $prescription_medication_id = (int)$_POST['prescription_medication_id'];
    $patient_status = mysqli_real_escape_string($conn, $_POST['patient_status']);
    $patient_notes = mysqli_real_escape_string($conn, $_POST['patient_notes'] ?? '');

    // Ensure the medication belongs to a prescription owned by the current user
    $sql_check = "SELECT pm.id FROM prescription_medications pm JOIN prescriptions p ON pm.prescription_id = p.prescription_id WHERE pm.id = ? AND p.user_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $prescription_medication_id, $user_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows === 0) {
        http_response_code(403); // Forbidden
        echo json_encode(['success' => false, 'error' => 'Medication does not belong to your prescriptions']);
        $stmt_check->close();
        $conn->close();
        exit;
    }
    $stmt_check->close();

    // Prepare an update statement
    $sql_update = "UPDATE prescription_medications SET patient_status = ?, patient_notes = ?, patient_updated_at = CURRENT_TIMESTAMP WHERE id = ?";

    if ($stmt_update = mysqli_prepare($conn, $sql_update)) {
        // Bind variables to the prepared statement
        mysqli_stmt_bind_param($stmt_update, "ssi", $patient_status, $patient_notes, $prescription_medication_id);

        // Attempt to execute the prepared statement
        if (mysqli_stmt_execute($stmt_update)) {
            // Success
            http_response_code(200); // OK
            echo json_encode(['success' => true, 'message' => 'Adherence status updated successfully']);
        } else {
            // Error
            http_response_code(500); // Internal Server Error
            echo json_encode(['success' => false, 'message' => 'Error updating adherence status', 'error' => mysqli_stmt_error($stmt_update)]);
        }

        // Close statement
        mysqli_stmt_close($stmt_update);
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