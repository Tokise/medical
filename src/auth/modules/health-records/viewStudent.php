<?php
session_start();
require_once '../../../config/config.php';

// Check if user is logged in and has student role
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'student') {
    header("Location: /medical/auth/login.php");
    exit;
}

// Get current user data
$user_id = $_SESSION['id'];
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    // User not found in database
    session_destroy();
    header("Location: /medical/auth/login.php?error=user_not_found");
    exit;
}

// Get student data
try {
    $studentQuery = "SELECT * FROM students WHERE user_id = ?";
    $studentStmt = $conn->prepare($studentQuery);
    $studentStmt->bind_param("i", $user_id);
    $studentStmt->execute();
    $student = $studentStmt->get_result()->fetch_assoc();
} catch (Exception $e) {
    error_log("Error fetching student data: " . $e->getMessage());
    $student = null;
}

// Get medical conditions
try {
    $conditionsQuery = "SELECT * FROM medical_history WHERE user_id = ? ORDER BY diagnosis_date DESC";
    $conditionsStmt = $conn->prepare($conditionsQuery);
    $conditionsStmt->bind_param("i", $user_id);
    $conditionsStmt->execute();
    $medicalConditions = $conditionsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching medical conditions: " . $e->getMessage());
    $medicalConditions = [];
}

// Get allergies
try {
    $allergiesQuery = "SELECT * FROM allergies WHERE user_id = ? ORDER BY severity DESC";
    $allergiesStmt = $conn->prepare($allergiesQuery);
    $allergiesStmt->bind_param("i", $user_id);
    $allergiesStmt->execute();
    $allergies = $allergiesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching allergies: " . $e->getMessage());
    $allergies = [];
}

// Process form submissions
$successMessage = '';
$errorMessage = '';

// Handle blood type update
if (isset($_POST['update_blood_type'])) {
    $blood_type = $_POST['blood_type'];
    
    try {
        // Check if student record exists
        if ($student) {
            $updateQuery = "UPDATE students SET blood_type = ? WHERE user_id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("si", $blood_type, $user_id);
            
            if ($updateStmt->execute()) {
                $successMessage = "Blood type updated successfully!";
                // Refresh student data
                $studentStmt->execute();
                $student = $studentStmt->get_result()->fetch_assoc();
            } else {
                $errorMessage = "Failed to update blood type.";
            }
        } else {
            // Create new student record if it doesn't exist
            $insertQuery = "INSERT INTO students (user_id, blood_type) VALUES (?, ?)";
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bind_param("is", $user_id, $blood_type);
            
            if ($insertStmt->execute()) {
                $successMessage = "Blood type added successfully!";
                // Refresh student data
                $studentStmt->execute();
                $student = $studentStmt->get_result()->fetch_assoc();
            } else {
                $errorMessage = "Failed to add blood type.";
            }
        }
    } catch (Exception $e) {
        $errorMessage = "An error occurred: " . $e->getMessage();
        error_log("Error updating blood type: " . $e->getMessage());
    }
}

// Handle emergency contact update
if (isset($_POST['update_emergency_contact'])) {
    $emergency_contact_name = $_POST['emergency_contact_name'];
    $emergency_contact_relationship = $_POST['emergency_contact_relationship'];
    $emergency_contact_phone = $_POST['emergency_contact_phone'];
    
    try {
        // Check if student record exists
        if ($student) {
            $updateQuery = "UPDATE students SET 
                emergency_contact_name = ?, 
                emergency_contact_relationship = ?, 
                emergency_contact_phone = ? 
                WHERE user_id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("sssi", $emergency_contact_name, $emergency_contact_relationship, $emergency_contact_phone, $user_id);
            
            if ($updateStmt->execute()) {
                $successMessage = "Emergency contact updated successfully!";
                // Refresh student data
                $studentStmt->execute();
                $student = $studentStmt->get_result()->fetch_assoc();
            } else {
                $errorMessage = "Failed to update emergency contact.";
            }
        } else {
            // Create new student record if it doesn't exist
            $insertQuery = "INSERT INTO students (user_id, emergency_contact_name, emergency_contact_relationship, emergency_contact_phone) 
                           VALUES (?, ?, ?, ?)";
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bind_param("isss", $user_id, $emergency_contact_name, $emergency_contact_relationship, $emergency_contact_phone);
            
            if ($insertStmt->execute()) {
                $successMessage = "Emergency contact added successfully!";
                // Refresh student data
                $studentStmt->execute();
                $student = $studentStmt->get_result()->fetch_assoc();
            } else {
                $errorMessage = "Failed to add emergency contact.";
            }
        }
    } catch (Exception $e) {
        $errorMessage = "An error occurred: " . $e->getMessage();
        error_log("Error updating emergency contact: " . $e->getMessage());
    }
}

// Handle adding new medical condition
if (isset($_POST['add_condition'])) {
    $condition = $_POST['condition'];
    $diagnosis_date = $_POST['diagnosis_date'];
    $notes = $_POST['condition_notes'];
    
    try {
        $insertQuery = "INSERT INTO medical_history (user_id, condition_name, diagnosis_date, notes) 
                       VALUES (?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param("isss", $user_id, $condition, $diagnosis_date, $notes);
        
        if ($insertStmt->execute()) {
            $successMessage = "Medical condition added successfully!";
            // Refresh medical conditions
            $conditionsStmt->execute();
            $medicalConditions = $conditionsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $errorMessage = "Failed to add medical condition.";
        }
    } catch (Exception $e) {
        $errorMessage = "An error occurred: " . $e->getMessage();
        error_log("Error adding medical condition: " . $e->getMessage());
    }
}

// Handle deleting medical condition
if (isset($_POST['delete_condition'])) {
    $condition_id = $_POST['condition_id'];
    
    try {
        $deleteQuery = "DELETE FROM medical_history WHERE id = ? AND user_id = ?";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bind_param("ii", $condition_id, $user_id);
        
        if ($deleteStmt->execute()) {
            $successMessage = "Medical condition deleted successfully!";
            // Refresh medical conditions
            $conditionsStmt->execute();
            $medicalConditions = $conditionsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $errorMessage = "Failed to delete medical condition.";
        }
    } catch (Exception $e) {
        $errorMessage = "An error occurred: " . $e->getMessage();
        error_log("Error deleting medical condition: " . $e->getMessage());
    }
}

// Handle adding new allergy
if (isset($_POST['add_allergy'])) {
    $allergen = $_POST['allergen'];
    $severity = $_POST['severity'];
    $reaction = $_POST['reaction'];
    $notes = $_POST['allergy_notes'];
    
    try {
        $insertQuery = "INSERT INTO allergies (user_id, allergen, severity, reaction, notes) 
                       VALUES (?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param("issss", $user_id, $allergen, $severity, $reaction, $notes);
        
        if ($insertStmt->execute()) {
            $successMessage = "Allergy added successfully!";
            // Refresh allergies
            $allergiesStmt->execute();
            $allergies = $allergiesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $errorMessage = "Failed to add allergy.";
        }
    } catch (Exception $e) {
        $errorMessage = "An error occurred: " . $e->getMessage();
        error_log("Error adding allergy: " . $e->getMessage());
    }
}

// Handle deleting allergy
if (isset($_POST['delete_allergy'])) {
    $allergy_id = $_POST['allergy_id'];
    
    try {
        $deleteQuery = "DELETE FROM allergies WHERE id = ? AND user_id = ?";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bind_param("ii", $allergy_id, $user_id);
        
        if ($deleteStmt->execute()) {
            $successMessage = "Allergy deleted successfully!";
            // Refresh allergies
            $allergiesStmt->execute();
            $allergies = $allergiesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $errorMessage = "Failed to delete allergy.";
        }
    } catch (Exception $e) {
        $errorMessage = "An error occurred: " . $e->getMessage();
        error_log("Error deleting allergy: " . $e->getMessage());
    }
}

// Pass the role to be used in the sidebar
$role = 'Student';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Records - Medical Management System</title>
    <link rel="stylesheet" href="/medical/src/styles/variables.css">
    <link rel="stylesheet" href="/medical/src/styles/components.css">
    <link rel="stylesheet" href="/medical/src/styles/global.css">
    <link rel="stylesheet" href="/medical/src/modules/dashboard/patient/student/styles/student.css">
    <link rel="stylesheet" href="/medical/src/modules/dashboard/patient/health-records/styles/health-records.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .health-records-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .section-title {
            margin-bottom: 20px;
            color: var(--primary-color);
            font-size: 1.8rem;
        }
        
        .health-record-card {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 24px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .health-record-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        
        .health-record-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .health-record-header h3 {
            font-size: 1.4rem;
            color: var(--text-dark);
            display: flex;
            align-items: center;
        }
        
        .health-record-header h3 i {
            margin-right: 12px;
            color: var(--primary-color);
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin: -10px;
        }
        
        .form-group {
            flex: 1 1 300px;
            padding: 10px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(var(--primary-rgb), 0.2);
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-dark);
        }
        
        .btn-group {
            display: flex;
            justify-content: flex-end;
            margin-top: 15px;
        }
        
        .record-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 20px;
        }
        
        .record-item {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background-color 0.3s ease;
        }
        
        .record-item:hover {
            background-color: #f0f0f0;
        }
        
        .record-item-content {
            flex: 1;
        }
        
        .record-item-content h4 {
            font-size: 1.1rem;
            margin-bottom: 5px;
            color: var(--text-dark);
        }
        
        .record-item-content p {
            color: var(--text-gray);
            margin: 5px 0;
            font-size: 0.9rem;
        }
        
        .record-actions {
            display: flex;
            gap: 10px;
        }
        
        .record-actions button {
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        
        .record-actions button:hover {
            background-color: rgba(0,0,0,0.05);
        }
        
        .delete-btn {
            color: var(--danger-color);
        }
        
        .edit-btn {
            color: var(--primary-color);
        }
        
        .badge {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 15px;
            margin-left: 10px;
        }
        
        .badge-mild {
            background-color: #e3f2fd;
            color: #1565c0;
        }
        
        .badge-moderate {
            background-color: #fff8e1;
            color: #f57f17;
        }
        
        .badge-severe {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
        }
        
        .alert i {
            font-size: 1.4rem;
            margin-right: 15px;
        }
        
        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .alert-error {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            text-align: center;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 20px;
        }
        
        .empty-title {
            font-size: 1.4rem;
            color: var(--text-dark);
            margin-bottom: 10px;
        }
        
        .empty-description {
            color: var(--text-gray);
            margin-bottom: 20px;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-in {
            animation: fadeIn 0.5s ease forwards;
        }
        
        .health-record-card,
        .record-item {
            opacity: 0;
        }
    </style>
</head>
<body>
    <?php include_once '../../../includes/header.php'; ?>
    <br><br><br>
    <div class="health-records-container">
        <!-- Page Header -->
        <div class="welcome-banner">
            <div class="welcome-content">
                <h1>Health Records</h1>
                <p>Manage your health information to ensure you receive the best care.</p>
            </div>
            <div class="welcome-image">
                <img src="/medical/assets/img/health-records.svg" alt="Health Records" />
            </div>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($successMessage) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>
        
        <!-- Blood Type Section -->
        <div class="health-record-card" id="blood-type-card">
            <div class="health-record-header">
                <h3><i class="fas fa-tint"></i> Blood Type</h3>
            </div>
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="blood_type">Your Blood Type</label>
                        <select name="blood_type" id="blood_type" class="form-control" required>
                            <option value="">-- Select Blood Type --</option>
                            <option value="A+" <?= (isset($student['blood_type']) && $student['blood_type'] === 'A+') ? 'selected' : '' ?>>A+</option>
                            <option value="A-" <?= (isset($student['blood_type']) && $student['blood_type'] === 'A-') ? 'selected' : '' ?>>A-</option>
                            <option value="B+" <?= (isset($student['blood_type']) && $student['blood_type'] === 'B+') ? 'selected' : '' ?>>B+</option>
                            <option value="B-" <?= (isset($student['blood_type']) && $student['blood_type'] === 'B-') ? 'selected' : '' ?>>B-</option>
                            <option value="AB+" <?= (isset($student['blood_type']) && $student['blood_type'] === 'AB+') ? 'selected' : '' ?>>AB+</option>
                            <option value="AB-" <?= (isset($student['blood_type']) && $student['blood_type'] === 'AB-') ? 'selected' : '' ?>>AB-</option>
                            <option value="O+" <?= (isset($student['blood_type']) && $student['blood_type'] === 'O+') ? 'selected' : '' ?>>O+</option>
                            <option value="O-" <?= (isset($student['blood_type']) && $student['blood_type'] === 'O-') ? 'selected' : '' ?>>O-</option>
                            <option value="Unknown" <?= (isset($student['blood_type']) && $student['blood_type'] === 'Unknown') ? 'selected' : '' ?>>Unknown</option>
                        </select>
                    </div>
                </div>
                <div class="btn-group">
                    <button type="submit" name="update_blood_type" class="btn btn-primary">Update Blood Type</button>
                </div>
            </form>
        </div>
        
        <!-- Emergency Contact Section -->
        <div class="health-record-card" id="emergency-contact-card">
            <div class="health-record-header">
                <h3><i class="fas fa-phone-alt"></i> Emergency Contact</h3>
            </div>
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="emergency_contact_name">Contact Name</label>
                        <input type="text" name="emergency_contact_name" id="emergency_contact_name" class="form-control" 
                               value="<?= isset($student['emergency_contact_name']) ? htmlspecialchars($student['emergency_contact_name']) : '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="emergency_contact_relationship">Relationship</label>
                        <input type="text" name="emergency_contact_relationship" id="emergency_contact_relationship" class="form-control" 
                               value="<?= isset($student['emergency_contact_relationship']) ? htmlspecialchars($student['emergency_contact_relationship']) : '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="emergency_contact_phone">Phone Number</label>
                        <input type="tel" name="emergency_contact_phone" id="emergency_contact_phone" class="form-control" 
                               value="<?= isset($student['emergency_contact_phone']) ? htmlspecialchars($student['emergency_contact_phone']) : '' ?>" required>
                    </div>
                </div>
                <div class="btn-group">
                    <button type="submit" name="update_emergency_contact" class="btn btn-primary">Update Emergency Contact</button>
                </div>
            </form>
        </div>
        
        <!-- Medical Conditions Section -->
        <div class="health-record-card" id="medical-conditions-card">
            <div class="health-record-header">
                <h3><i class="fas fa-heartbeat"></i> Medical Conditions</h3>
            </div>
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="condition">Condition Name</label>
                        <input type="text" name="condition" id="condition" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="diagnosis_date">Diagnosis Date</label>
                        <input type="date" name="diagnosis_date" id="diagnosis_date" class="form-control" required>
                    </div>
                    <div class="form-group" style="flex: 100%;">
                        <label for="condition_notes">Notes</label>
                        <textarea name="condition_notes" id="condition_notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="btn-group">
                    <button type="submit" name="add_condition" class="btn btn-primary">Add Condition</button>
                </div>
            </form>
            
            <div class="record-list">
                <h4>Your Medical Conditions</h4>
                
                <?php if (empty($medicalConditions)): ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <div class="empty-title">No medical conditions recorded</div>
                        <div class="empty-description">Add your medical conditions to keep your health record up to date</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($medicalConditions as $condition): ?>
                        <div class="record-item">
                            <div class="record-item-content">
                                <h4><?= htmlspecialchars($condition['condition_name']) ?></h4>
                                <p><strong>Diagnosed:</strong> <?= date('F j, Y', strtotime($condition['diagnosis_date'])) ?></p>
                                <?php if (!empty($condition['notes'])): ?>
                                    <p><strong>Notes:</strong> <?= htmlspecialchars($condition['notes']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="record-actions">
                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this condition?');">
                                    <input type="hidden" name="condition_id" value="<?= $condition['id'] ?>">
                                    <button type="submit" name="delete_condition" class="delete-btn">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Allergies Section -->
        <div class="health-record-card" id="allergies-card">
            <div class="health-record-header">
                <h3><i class="fas fa-allergies"></i> Allergies</h3>
            </div>
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="allergen">Allergen</label>
                        <input type="text" name="allergen" id="allergen" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="severity">Severity</label>
                        <select name="severity" id="severity" class="form-control" required>
                            <option value="">-- Select Severity --</option>
                            <option value="Mild">Mild</option>
                            <option value="Moderate">Moderate</option>
                            <option value="Severe">Severe</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="reaction">Reaction</label>
                        <input type="text" name="reaction" id="reaction" class="form-control" required>
                    </div>
                    <div class="form-group" style="flex: 100%;">
                        <label for="allergy_notes">Notes</label>
                        <textarea name="allergy_notes" id="allergy_notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="btn-group">
                    <button type="submit" name="add_allergy" class="btn btn-primary">Add Allergy</button>
                </div>
            </form>
            
            <div class="record-list">
                <h4>Your Allergies</h4>
                
                <?php if (empty($allergies)): ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <div class="empty-title">No allergies recorded</div>
                        <div class="empty-description">Add your allergies to ensure safe medical care</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($allergies as $allergy): ?>
                        <div class="record-item">
                            <div class="record-item-content">
                                <h4>
                                    <?= htmlspecialchars($allergy['allergen']) ?>
                                    <span class="badge badge-<?= strtolower($allergy['severity']) ?>"><?= $allergy['severity'] ?></span>
                                </h4>
                                <p><strong>Reaction:</strong> <?= htmlspecialchars($allergy['reaction']) ?></p>
                                <?php if (!empty($allergy['notes'])): ?>
                                    <p><strong>Notes:</strong> <?= htmlspecialchars($allergy['notes']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="record-actions">
                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this allergy?');">
                                    <input type="hidden" name="allergy_id" value="<?= $allergy['id'] ?>">
                                    <button type="submit" name="delete_allergy" class="delete-btn">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Back to Dashboard Button -->
        <div class="btn-group" style="justify-content: center; margin-top: 30px; margin-bottom: 30px;">
            <a href="/medical/src/modules/dashboard/patient/student/index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add animation classes after DOM is loaded
        setTimeout(() => {
            let cards = document.querySelectorAll('.health-record-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.classList.add('animate-in');
                }, index * 150);
            });
            
            // Animate list items
            let recordItems = document.querySelectorAll('.record-item');
            recordItems.forEach((item, index) => {
                setTimeout(() => {
                    item.classList.add('animate-in');
                }, index * 100 + 600);
            });
            
            // Flash success message and fade out
            const alertSuccess = document.querySelector('.alert-success');
            if (alertSuccess) {
                setTimeout(() => {
                    alertSuccess.style.transition = 'opacity 1s ease';
                    alertSuccess.style.opacity = '0';
                }, 5000);
            }
        }, 300);
        
        // Form validation
        const addConditionForm = document.querySelector('form[name="add_condition"]');
        if (addConditionForm) {
            addConditionForm.addEventListener('submit', function(e) {
                const conditionField = document.getElementById('condition');
                if (conditionField.value.trim() === '') {
                    e.preventDefault();
                    alert('Please enter a condition name');
                    conditionField.focus();
                }
            });
        }
        
        const addAllergyForm = document.querySelector('form[name="add_allergy"]');
        if (addAllergyForm) {
            addAllergyForm.addEventListener('submit', function(e) {
                const allergenField = document.getElementById('allergen');
                if (allergenField.value.trim() === '') {
                    e.preventDefault();
                    alert('Please enter an allergen');
                    allergenField.focus();
                }
                
                const severityField = document.getElementById('severity');
                if (severityField.value === '') {
                    e.preventDefault();
                    alert('Please select a severity level');
                    severityField.focus();
                }
            });
        }
        
        // Enable confirmation for deletion actions
        const deleteForms = document.querySelectorAll('form[onsubmit]');
        deleteForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!confirm('Are you sure you want to delete this item?')) {
                    e.preventDefault();
                }
            });
        });
        
        // Auto-scroll to section if hash in URL
        if (window.location.hash) {
            const targetElement = document.querySelector(window.location.hash);
            if (targetElement) {
                targetElement.scrollIntoView({ behavior: 'smooth' });
                targetElement.classList.add('highlight');
                setTimeout(() => {
                    targetElement.classList.remove('highlight');
                }, 2000);
            }
        }
    });
</script>
</body>
</html>