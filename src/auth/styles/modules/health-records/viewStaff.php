<?php
session_start();
require_once '../../../config/config.php';

// Check if user is logged in and has staff role
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'staff') {
    header("Location: /medical/auth/login.php");
    exit;
}

// Get current user data
$user_id = $_SESSION['id'];
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Check if staff table exists and create if necessary
$tableCheck = $conn->query("SHOW TABLES LIKE 'staff'");
if($tableCheck->num_rows == 0) {
    // Create staff table if it doesn't exist
    $createTableQuery = "CREATE TABLE IF NOT EXISTS `staff` (
    `staff_id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `blood_type` varchar(5) DEFAULT NULL,
    `date_of_birth` date DEFAULT NULL,
    `gender` varchar(20) DEFAULT NULL,
    `height` decimal(5,2) DEFAULT NULL,
    `weight` decimal(5,2) DEFAULT NULL,
    `department` varchar(100) DEFAULT NULL,
    `emergency_contact` varchar(100) DEFAULT NULL,
    `emergency_phone` varchar(20) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`staff_id`),
    KEY `user_id` (`user_id`)
    )";

    $conn->query($createTableQuery);
}

// Check if emergency_contact and emergency_phone columns exist
$checkEmergencyContactCol = $conn->query("SHOW COLUMNS FROM `staff` LIKE 'emergency_contact'");
if($checkEmergencyContactCol->num_rows == 0) {
    $conn->query("ALTER TABLE `staff` ADD COLUMN `emergency_contact` varchar(100) DEFAULT NULL");
}

$checkEmergencyPhoneCol = $conn->query("SHOW COLUMNS FROM `staff` LIKE 'emergency_phone'");
if($checkEmergencyPhoneCol->num_rows == 0) {
    $conn->query("ALTER TABLE `staff` ADD COLUMN `emergency_phone` varchar(20) DEFAULT NULL");
}

// Check if updated_at column exists
$checkUpdatedAtCol = $conn->query("SHOW COLUMNS FROM `staff` LIKE 'updated_at'");
if($checkUpdatedAtCol->num_rows == 0) {
    $conn->query("ALTER TABLE `staff` ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
}
 
// Check if medical_history table exists
$medHistoryCheck = $conn->query("SHOW TABLES LIKE 'medical_history'");
if($medHistoryCheck->num_rows == 0) {
    $createMedHistoryQuery = "CREATE TABLE IF NOT EXISTS `medical_history` (
        `condition_id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `condition_name` varchar(255) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`condition_id`),
        KEY `user_id` (`user_id`)
    )";
    $conn->query($createMedHistoryQuery);
} 

// Check if allergies table exists
$allergiesCheck = $conn->query("SHOW TABLES LIKE 'allergies'");
if($allergiesCheck->num_rows == 0) {
    $createAllergiesQuery = "CREATE TABLE IF NOT EXISTS `allergies` (
        `allergy_id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `allergy_name` varchar(255) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`allergy_id`),
        KEY `user_id` (`user_id`)
    )";
    $conn->query($createAllergiesQuery);
}

// Get user's health record (basic info)
$healthRecordQuery = "SELECT * FROM staff WHERE user_id = ?";
$healthRecordStmt = $conn->prepare($healthRecordQuery);
$healthRecordStmt->bind_param("i", $user_id);
$healthRecordStmt->execute();
$healthRecord = $healthRecordStmt->get_result()->fetch_assoc();

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update staff health information
    $blood_type = $_POST['blood_type'] ?? '';
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $height = !empty($_POST['height']) ? $_POST['height'] : null;
    $weight = !empty($_POST['weight']) ? $_POST['weight'] : null;
    $emergency_contact = $_POST['emergency_contact'] ?? '';
    $emergency_phone = $_POST['emergency_phone'] ?? '';
    
    // Check if staff record exists
    $checkStaffQuery = "SELECT user_id FROM staff WHERE user_id = ?";
    $checkStmt = $conn->prepare($checkStaffQuery);
    $checkStmt->bind_param("i", $user_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    try {
        if ($result->num_rows > 0) {
            // Update existing record
            $updateQuery = "UPDATE staff SET blood_type = ?, date_of_birth = ?, gender = ?, 
                          height = ?, weight = ?, emergency_contact = ?, emergency_phone = ? 
                          WHERE user_id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("sssddssi", $blood_type, $date_of_birth, $gender, $height, $weight, 
                                  $emergency_contact, $emergency_phone, $user_id);
            
            if (!$updateStmt->execute()) {
                throw new Exception("Error updating record: " . $conn->error);
            }
        } else {
            // Insert new record
            $insertQuery = "INSERT INTO staff (user_id, blood_type, date_of_birth, gender, height, weight, 
                          emergency_contact, emergency_phone) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bind_param("issddsss", $user_id, $blood_type, $date_of_birth, $gender, $height, 
                                  $weight, $emergency_contact, $emergency_phone);
            
            if (!$insertStmt->execute()) {
                throw new Exception("Error creating record: " . $conn->error);
            }
        }
        
        // Handle medical conditions
        if (isset($_POST['medical_conditions'])) {
            // First delete existing conditions
            $deleteMedicalQuery = "DELETE FROM medical_history WHERE user_id = ?";
            $deleteMedicalStmt = $conn->prepare($deleteMedicalQuery);
            $deleteMedicalStmt->bind_param("i", $user_id);
            
            if (!$deleteMedicalStmt->execute()) {
                throw new Exception("Error removing old medical conditions: " . $conn->error);
            }
            
            // Add new conditions (only if not empty)
            if (!empty($_POST['medical_conditions'])) {
                $conditions = explode(',', $_POST['medical_conditions']);
                foreach ($conditions as $condition) {
                    $condition = trim($condition);
                    if (!empty($condition)) {
                        $insertConditionQuery = "INSERT INTO medical_history (user_id, condition_name, doctor_id) 
                                              VALUES (?, ?, ?)";
                        $insertConditionStmt = $conn->prepare($insertConditionQuery);
                        $doctor_id = $user_id; // Using the current user as doctor_id to satisfy the constraint
                        $insertConditionStmt->bind_param("isi", $user_id, $condition, $doctor_id);
                        
                        if (!$insertConditionStmt->execute()) {
                            throw new Exception("Error adding medical condition: " . $conn->error);
                        }
                    }
                }
            }
        }
        
        // Handle allergies
        if (isset($_POST['allergies'])) {
            // First delete existing allergies
            $deleteAllergiesQuery = "DELETE FROM allergies WHERE user_id = ?";
            $deleteAllergiesStmt = $conn->prepare($deleteAllergiesQuery);
            $deleteAllergiesStmt->bind_param("i", $user_id);
            
            if (!$deleteAllergiesStmt->execute()) {
                throw new Exception("Error removing old allergies: " . $conn->error);
            }
            
            // Add new allergies (only if not empty)
            if (!empty($_POST['allergies'])) {
                $allergies = explode(',', $_POST['allergies']);
                foreach ($allergies as $allergy) {
                    $allergy = trim($allergy);
                    if (!empty($allergy)) {
                        $insertAllergyQuery = "INSERT INTO allergies (user_id, allergy_name) 
                                            VALUES (?, ?)";
                        $insertAllergyStmt = $conn->prepare($insertAllergyQuery);
                        $insertAllergyStmt->bind_param("is", $user_id, $allergy);
                        
                        if (!$insertAllergyStmt->execute()) {
                            throw new Exception("Error adding allergy: " . $conn->error);
                        }
                    }
                }
            }
        }
        
        // Reload health record after update
        $healthRecordStmt->execute();
        $healthRecord = $healthRecordStmt->get_result()->fetch_assoc();
        
        $success_message = "Health record updated successfully!";
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get all allergies for this user
$allergiesQuery = "SELECT allergy_name FROM allergies WHERE user_id = ?";
$allergiesStmt = $conn->prepare($allergiesQuery);
$allergiesStmt->bind_param("i", $user_id);
$allergiesStmt->execute();
$allergiesResult = $allergiesStmt->get_result();
$allergiesList = [];
while ($row = $allergiesResult->fetch_assoc()) {
    $allergiesList[] = $row['allergy_name'];
}
$allergiesString = implode(', ', $allergiesList);

// Get all medical conditions for this user
$conditionsQuery = "SELECT condition_name FROM medical_history WHERE user_id = ?";
$conditionsStmt = $conn->prepare($conditionsQuery);
$conditionsStmt->bind_param("i", $user_id);
$conditionsStmt->execute();
$conditionsResult = $conditionsStmt->get_result();
$conditionsList = [];
while ($row = $conditionsResult->fetch_assoc()) {
    $conditionsList[] = $row['condition_name'];
}
$conditionsString = implode(', ', $conditionsList);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Health Record - MedMS</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Flatpickr for date selection -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- jQuery Tags Input CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-tagsinput/1.3.6/jquery.tagsinput.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/medical/src/styles/components.css"> 
    <link rel="stylesheet" href="/medical/src/styles/variables.css">
    <link rel="stylesheet" href="/medical/src/styles/global.css">
    <link rel="stylesheet" href="../staff/styles/staff.css">
    <link rel="stylesheet" href="../health-records/styles/viewStaff.css">
</head>

<body>
    <?php include_once '../../../includes/header.php'; ?>
    <br><br><br>
    <div class="staff-dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <h1>
                <i class="fas fa-file-medical"></i>
                Health Record
            </h1>
            <p>View and update your health information</p>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>
        
        <div class="content-grid">
            <!-- Left Column: Health Record Form -->
            <div class="content-column">
                <div class="health-record-form animate-in">
                    <h3>Update Health Information</h3>
                    <p>Keep your health information up to date for better care</p>
                    
                    <form method="POST" action="">
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label class="form-label" for="blood_type">Blood Type</label>
                                    <select class="form-control" id="blood_type" name="blood_type">
                                        <option value="">Select Blood Type</option>
                                        <option value="A+" <?= ($healthRecord['blood_type'] ?? '') === 'A+' ? 'selected' : '' ?>>A+</option>
                                        <option value="A-" <?= ($healthRecord['blood_type'] ?? '') === 'A-' ? 'selected' : '' ?>>A-</option>
                                        <option value="B+" <?= ($healthRecord['blood_type'] ?? '') === 'B+' ? 'selected' : '' ?>>B+</option>
                                        <option value="B-" <?= ($healthRecord['blood_type'] ?? '') === 'B-' ? 'selected' : '' ?>>B-</option>
                                        <option value="AB+" <?= ($healthRecord['blood_type'] ?? '') === 'AB+' ? 'selected' : '' ?>>AB+</option>
                                        <option value="AB-" <?= ($healthRecord['blood_type'] ?? '') === 'AB-' ? 'selected' : '' ?>>AB-</option>
                                        <option value="O+" <?= ($healthRecord['blood_type'] ?? '') === 'O+' ? 'selected' : '' ?>>O+</option>
                                        <option value="O-" <?= ($healthRecord['blood_type'] ?? '') === 'O-' ? 'selected' : '' ?>>O-</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-col">
                                <div class="form-group">
                                    <label class="form-label" for="date_of_birth">Date of Birth</label>
                                    <input type="text" class="form-control datepicker" id="date_of_birth" name="date_of_birth" 
                                           value="<?= htmlspecialchars($healthRecord['date_of_birth'] ?? '') ?>" placeholder="YYYY-MM-DD">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label class="form-label" for="gender">Gender</label>
                                    <select class="form-control" id="gender" name="gender">
                                        <option value="">Select Gender</option>
                                        <option value="Male" <?= ($healthRecord['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                                        <option value="Female" <?= ($healthRecord['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                                        <option value="Other" <?= ($healthRecord['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                                        <option value="Prefer not to say" <?= ($healthRecord['gender'] ?? '') === 'Prefer not to say' ? 'selected' : '' ?>>Prefer not to say</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-col">
                                <div class="form-group">
                                    <label class="form-label" for="height">Height (cm)</label>
                                    <input type="number" step="0.01" class="form-control" id="height" name="height" 
                                           value="<?= htmlspecialchars($healthRecord['height'] ?? '') ?>" placeholder="e.g., 175">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label class="form-label" for="weight">Weight (kg)</label>
                                    <input type="number" step="0.01" class="form-control" id="weight" name="weight" 
                                           value="<?= htmlspecialchars($healthRecord['weight'] ?? '') ?>" placeholder="e.g., 68.5">
                                </div>
                            </div>
                            
                            <div class="form-col">
                                <div class="form-group">
                                    <label class="form-label" for="allergies">Allergies</label>
                                    <input type="text" class="form-control tagsinput" id="allergies" name="allergies" 
                                           value="<?= htmlspecialchars($allergiesString) ?>" placeholder="Enter allergies, separated by commas">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="medical_conditions">Medical Conditions</label>
                            <input type="text" class="form-control tagsinput" id="medical_conditions" name="medical_conditions" 
                                   value="<?= htmlspecialchars($conditionsString) ?>" placeholder="Enter medical conditions, separated by commas">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label class="form-label" for="emergency_contact">Emergency Contact Name</label>
                                    <input type="text" class="form-control" id="emergency_contact" name="emergency_contact" 
                                           value="<?= htmlspecialchars($healthRecord['emergency_contact'] ?? '') ?>" placeholder="e.g., John Doe">
                                </div>
                            </div>
                            
                            <div class="form-col">
                                <div class="form-group">
                                    <label class="form-label" for="emergency_phone">Emergency Contact Phone</label>
                                    <input type="text" class="form-control" id="emergency_phone" name="emergency_phone" 
                                           value="<?= htmlspecialchars($healthRecord['emergency_phone'] ?? '') ?>" placeholder="e.g., +1234567890">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Save Health Record
                            </button>
                            <a href="/medical/src/modules/dashboard/staff.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i>
                                Back to Dashboard
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Right Column: Health Summary Cards -->
            <div class="content-column">
                <div class="health-info-card animate-in" style="animation-delay: 0.1s;">
                    <div class="health-info-header">
                        <div class="health-info-icon">
                            <i class="fas fa-id-card"></i>
                        </div>
                        <h3 class="health-info-title">Basic Information</h3>
                    </div>
                    <div class="health-info-content">
                        <div class="health-info-item">
                            <div class="health-info-label">Name</div>
                            <div class="health-info-value"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
                        </div>
                        <div class="health-info-item">
                            <div class="health-info-label">Employee ID</div>
                            <div class="health-info-value"><?= htmlspecialchars($user['username'] ?? 'Not available') ?></div>
                        </div>
                        <div class="health-info-item">
                            <div class="health-info-label">Department</div>
                            <div class="health-info-value"><?= htmlspecialchars($healthRecord['department'] ?? 'Not specified') ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="health-info-card animate-in" style="animation-delay: 0.2s;">
                    <div class="health-info-header">
                        <div class="health-info-icon">
                            <i class="fas fa-heartbeat"></i>
                        </div>
                        <h3 class="health-info-title">Health Snapshot</h3>
                    </div>
                    <div class="health-info-content">
                        <div class="health-info-item">
                            <div class="health-info-label">Blood Type</div>
                            <div class="health-info-value"><?= htmlspecialchars($healthRecord['blood_type'] ?? 'Not specified') ?></div>
                        </div>
                        <div class="health-info-item">
                            <div class="health-info-label">Height</div>
                            <div class="health-info-value"><?= $healthRecord['height'] ? htmlspecialchars($healthRecord['height']) . ' cm' : 'Not specified' ?></div>
                        </div>
                        <div class="health-info-item">
                            <div class="health-info-label">Weight</div>
                            <div class="health-info-value"><?= $healthRecord['weight'] ? htmlspecialchars($healthRecord['weight']) . ' kg' : 'Not specified' ?></div>
                        </div>
                        <?php if (!empty($healthRecord['height']) && !empty($healthRecord['weight'])): ?>
                            <?php $bmi = $healthRecord['weight'] / (($healthRecord['height']/100) * ($healthRecord['height']/100)); ?>
                            <div class="health-info-item">
                                <div class="health-info-label">BMI</div>
                                <div class="health-info-value"><?= number_format($bmi, 2) ?> (<?= getBMICategory($bmi) ?>)</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="health-info-card animate-in" style="animation-delay: 0.3s;">
                    <div class="health-info-header">
                        <div class="health-info-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h3 class="health-info-title">Health Alerts</h3>
                    </div>
                    <div class="health-info-content">
                        <div class="health-info-item">
                            <div class="health-info-label">Allergies</div>
                            <div class="health-info-value">
                                <?php if (!empty($allergiesList)): ?>
                                    <?php foreach ($allergiesList as $allergy): ?>
                                        <span class="tag"><?= htmlspecialchars($allergy) ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted">No allergies recorded</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="health-info-item">
                            <div class="health-info-label">Medical Conditions</div>
                            <div class="health-info-value">
                                <?php if (!empty($conditionsList)): ?>
                                    <?php foreach ($conditionsList as $condition): ?>
                                        <span class="tag"><?= htmlspecialchars($condition) ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted">No medical conditions recorded</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="health-info-card animate-in" style="animation-delay: 0.4s;">
                    <div class="health-info-header">
                        <div class="health-info-icon">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <h3 class="health-info-title">Emergency Information</h3>
                    </div>
                    <div class="health-info-content">
                        <div class="health-info-item">
                            <div class="health-info-label">Emergency Contact</div>
                            <div class="health-info-value"><?= htmlspecialchars($healthRecord['emergency_contact'] ?? 'Not specified') ?></div>
                        </div>
                        <div class="health-info-item">
                            <div class="health-info-label">Emergency Phone</div>
                            <div class="health-info-value"><?= htmlspecialchars($healthRecord['emergency_phone'] ?? 'Not specified') ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Flatpickr JS for date picking -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <!-- jQuery for tag input -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-tagsinput/1.3.6/jquery.tagsinput.min.js"></script>
    
    <script>
        // Initialize datepicker
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr(".datepicker", {
                dateFormat: "Y-m-d",
                maxDate: "today"
            });
            
            // Initialize tag inputs
            $('.tagsinput').tagsInput({
                'defaultText': 'Add item',
                'width': '100%',
                'height': 'auto',
                'removeWithBackspace': true,
                'delimiter': [',']
            });
            
            // Add animation classes
            const animateItems = document.querySelectorAll('.animate-in');
            animateItems.forEach(item => {
                item.classList.add('show');
            });
        });
        
        // Basic form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            let dateOfBirth = document.getElementById('date_of_birth').value;
            
            // Check date of birth format
            if (dateOfBirth && !/^\d{4}-\d{2}-\d{2}$/.test(dateOfBirth)) {
                alert('Please enter date of birth in YYYY-MM-DD format');
                e.preventDefault();
                return;
            }
        });
    </script>
    
    <?php
    // Function to determine BMI category
    function getBMICategory($bmi) {
        if ($bmi < 18.5) {
            return 'Underweight';
        } else if ($bmi < 25) {
            return 'Normal weight';
        } else if ($bmi < 30) {
            return 'Overweight';
        } else {
            return 'Obese';
        }
    }
    ?>
</body>
</html>