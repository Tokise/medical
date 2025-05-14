<?php
session_start();
require_once '../../../config/config.php';

// Check if user is logged in and has teacher role
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'teacher') {
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

// Get user's health record
$healthRecordQuery = "SELECT * FROM teachers WHERE user_id = ?";
$healthRecordStmt = $conn->prepare($healthRecordQuery);
$healthRecordStmt->bind_param("i", $user_id);
$healthRecordStmt->execute();
$healthRecord = $healthRecordStmt->get_result()->fetch_assoc();

// Get user's medical conditions
$conditionsQuery = "SELECT * FROM medical_history WHERE user_id = ?";
$conditionsStmt = $conn->prepare($conditionsQuery);
$conditionsStmt->bind_param("i", $user_id);
$conditionsStmt->execute();
$conditions = $conditionsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get user's allergies
$allergiesQuery = "SELECT * FROM allergies WHERE user_id = ?";
$allergiesStmt = $conn->prepare($allergiesQuery);
$allergiesStmt->bind_param("i", $user_id);
$allergiesStmt->execute();
$allergies = $allergiesStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = "";
    $messageType = "";
    
    // Process basic health info
    if (isset($_POST['update_basic'])) {
        $blood_type = $_POST['blood_type'];
        $gender = $_POST['gender'];
        $emergency_contact = $_POST['emergency_contact'];
        $emergency_phone = $_POST['emergency_phone'];
        
        // Update teacher health record
        $updateBasicQuery = "UPDATE teachers SET 
                            blood_type = ?, 
                            gender = ?, 
                            emergency_contact = ?,
                            emergency_phone = ?,
                            updated_at = NOW()
                            WHERE user_id = ?";
        $updateBasicStmt = $conn->prepare($updateBasicQuery);
        $updateBasicStmt->bind_param("ssssi", $blood_type, $gender, $emergency_contact, $emergency_phone, $user_id);
        
        if ($updateBasicStmt->execute()) {
            $message = "Basic health information updated successfully!";
            $messageType = "success";
        } else {
            $message = "Error updating basic health information: " . $conn->error;
            $messageType = "error";
        }
    }
    
    // Process medical conditions
    if (isset($_POST['add_condition'])) {
        $condition_name = $_POST['condition_name'];
        $diagnosis_date = $_POST['diagnosis_date'];
        $treatment = $_POST['treatment'];
        $notes = $_POST['condition_notes'];
        
        // Get a default doctor ID or set to NULL if no constraint is necessary
        $doctor_id = null;
        
        // Let's just select a user ID directly without relying on role columns
        $doctor_query = "SELECT user_id FROM users LIMIT 1";
        $doctor_result = $conn->query($doctor_query);
        if ($doctor_result && $doctor_result->num_rows > 0) {
            $doctor_data = $doctor_result->fetch_assoc();
            $doctor_id = $doctor_data['user_id'];
        }
        
        // Check table columns
        $table_columns = [];
        $columnsQuery = "SHOW COLUMNS FROM medical_history";
        $columnsResult = $conn->query($columnsQuery);
        
        if ($columnsResult) {
            while ($columnRow = $columnsResult->fetch_assoc()) {
                $table_columns[] = $columnRow['Field'];
            }
        }
        
        // Build the query dynamically based on existing columns
        $fields = ["user_id", "condition_name", "diagnosis_date", "treatment", "notes"];
        $values = ["?", "?", "?", "?", "?"];
        $types = "issss";
        $bind_params = [$user_id, $condition_name, $diagnosis_date, $treatment, $notes];
        
        // Add doctor_id if needed (based on foreign key constraint)
        if (in_array('doctor_id', $table_columns)) {
            $fields[] = "doctor_id";
            $values[] = "?";
            $types .= "i";
            $bind_params[] = $doctor_id;
        }
        
        // Add timestamp columns if they exist
        if (in_array('created_at', $table_columns)) {
            $fields[] = "created_at";
            $values[] = "NOW()";
        }
        
        if (in_array('updated_at', $table_columns)) {
            $fields[] = "updated_at";
            $values[] = "NOW()";
        }
        
        $fieldList = implode(", ", $fields);
        $valueList = implode(", ", $values);
        
        $addConditionQuery = "INSERT INTO medical_history ($fieldList) VALUES ($valueList)";
        $addConditionStmt = $conn->prepare($addConditionQuery);
        
        // Bind parameters
        if (!empty($bind_params)) {
            $addConditionStmt->bind_param($types, ...$bind_params);
        }
        
        if ($addConditionStmt->execute()) {
            $message = "Medical condition added successfully!";
            $messageType = "success";
            
            // Refresh conditions list
            $conditionsStmt->execute();
            $conditions = $conditionsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $message = "Error adding medical condition: " . $conn->error;
            $messageType = "error";
        }
    }
    
    // Process allergies
    if (isset($_POST['add_allergy'])) {
        $allergy_name = $_POST['allergy_name'];
        $severity = $_POST['severity'];
        $notes = $_POST['allergy_notes'];
        
        // Check if the allergies table has various columns
        $table_columns = [];
        $columnsQuery = "SHOW COLUMNS FROM allergies";
        $columnsResult = $conn->query($columnsQuery);
        
        if ($columnsResult) {
            while ($columnRow = $columnsResult->fetch_assoc()) {
                $table_columns[] = $columnRow['Field'];
            }
        }
        
        // Build the query dynamically based on existing columns
        $fields = ["user_id", "allergy_name", "severity", "notes"];
        $values = ["?", "?", "?", "?"];
        $types = "isss";
        $bind_params = [$user_id, $allergy_name, $severity, $notes];
        
        // Add reaction if column exists
        if (in_array('reaction', $table_columns)) {
            $reaction = $_POST['reaction'] ?? '';
            $fields[] = "reaction";
            $values[] = "?";
            $types .= "s";
            $bind_params[] = $reaction;
        }
        
        // Add timestamp columns if they exist
        if (in_array('created_at', $table_columns)) {
            $fields[] = "created_at";
            $values[] = "NOW()";
        }
        
        if (in_array('updated_at', $table_columns)) {
            $fields[] = "updated_at";
            $values[] = "NOW()";
        }
        
        $fieldList = implode(", ", $fields);
        $valueList = implode(", ", $values);
        
        $addAllergyQuery = "INSERT INTO allergies ($fieldList) VALUES ($valueList)";
        $addAllergyStmt = $conn->prepare($addAllergyQuery);
        
        // Only bind parameters if there are any (NOW() doesn't need binding)
        if (!empty($bind_params)) {
            $addAllergyStmt->bind_param($types, ...$bind_params);
        }
        
        if ($addAllergyStmt->execute()) {
            $message = "Allergy information added successfully!";
            $messageType = "success";
            
            // Refresh allergies list
            $allergiesStmt->execute();
            $allergies = $allergiesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $message = "Error adding allergy information: " . $conn->error;
            $messageType = "error";
        }
    }
    
    // Delete medical condition
    if (isset($_POST['delete_condition'])) {
        $condition_id = $_POST['condition_id'];
        
        // Check if the primary key column is named 'id' or something else
        $checkColumnQuery = "SHOW KEYS FROM medical_history WHERE Key_name = 'PRIMARY'";
        $columnResult = $conn->query($checkColumnQuery);
        $pk_column = 'id'; // Default assumption
        
        if ($columnResult && $columnResult->num_rows > 0) {
            $columnData = $columnResult->fetch_assoc();
            $pk_column = $columnData['Column_name'];
        }
        
        $deleteConditionQuery = "DELETE FROM medical_history WHERE $pk_column = ? AND user_id = ?";
        $deleteConditionStmt = $conn->prepare($deleteConditionQuery);
        $deleteConditionStmt->bind_param("ii", $condition_id, $user_id);
        
        if ($deleteConditionStmt->execute()) {
            $message = "Medical condition deleted successfully!";
            $messageType = "success";
            
            // Refresh conditions list
            $conditionsStmt->execute();
            $conditions = $conditionsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $message = "Error deleting medical condition: " . $conn->error;
            $messageType = "error";
        }
    }
    
    // Delete allergy
    if (isset($_POST['delete_allergy'])) {
        $allergy_id = $_POST['allergy_id'];
        
        // Check if the primary key column is named 'id' or something else
        $checkColumnQuery = "SHOW KEYS FROM allergies WHERE Key_name = 'PRIMARY'";
        $columnResult = $conn->query($checkColumnQuery);
        $pk_column = 'id'; // Default assumption
        
        if ($columnResult && $columnResult->num_rows > 0) {
            $columnData = $columnResult->fetch_assoc();
            $pk_column = $columnData['Column_name'];
        }
        
        $deleteAllergyQuery = "DELETE FROM allergies WHERE $pk_column = ? AND user_id = ?";
        $deleteAllergyStmt = $conn->prepare($deleteAllergyQuery);
        $deleteAllergyStmt->bind_param("ii", $allergy_id, $user_id);
        
        if ($deleteAllergyStmt->execute()) {
            $message = "Allergy deleted successfully!";
            $messageType = "success";
            
            // Refresh allergies list
            $allergiesStmt->execute();
            $allergies = $allergiesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $message = "Error deleting allergy: " . $conn->error;
            $messageType = "error";
        }
    }
    
    // Reload health record data after all operations
    $healthRecordStmt->execute();
    $healthRecord = $healthRecordStmt->get_result()->fetch_assoc();
}
$has_reaction = isset($allergy['reaction']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Record Management - Teacher Dashboard</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Flatpickr for date selection -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/medical/src/styles/components.css"> 
    <link rel="stylesheet" href="/medical/src/styles/variables.css">
    <link rel="stylesheet" href="/medical/src/styles/global.css">
    <link rel="stylesheet" href="styles/teacher.css">
    <style>
        .health-record-form {
            background-color: var(--surface-color);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--card-shadow);
        }
        
        .form-section {
            margin-bottom: 2rem;
        }
        
        .form-section-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .form-section-header i {
            margin-right: 0.5rem;
            color: var(--primary-color);
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .record-list {
            margin: 1rem 0;
        }
        
        .record-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background-color: var(--light-background);
            border-radius: var(--border-radius);
            margin-bottom: 0.75rem;
            transition: all 0.3s ease;
        }
        
        .record-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
        }
        
        .record-details {
            flex: 1;
        }
        
        .record-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .record-subtitle {
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        
        .record-notes {
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }
        
        .record-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .severity-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 1rem;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-left: 0.5rem;
        }
        
        .severity-mild {
            background-color: var(--success-light);
            color: var(--success-dark);
        }
        
        .severity-moderate {
            background-color: var(--warning-light);
            color: var(--warning-dark);
        }
        
        .severity-severe {
            background-color: var(--danger-light);
            color: var(--danger-dark);
        }
        
        .message-container {
            margin-bottom: 1.5rem;
            padding: 1rem;
            border-radius: var(--border-radius);
            animation: fadeIn 0.5s ease;
        }
        
        .message-success {
            background-color: var(--success-light);
            color: var(--success-dark);
            border-left: 4px solid var(--success-color);
        }
        
        .message-error {
            background-color: var(--danger-light);
            color: var(--danger-dark);
            border-left: 4px solid var(--danger-color);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-in {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeIn 0.5s ease forwards;
        }
    </style>
</head>
<body>
    <?php include_once '../../../includes/header.php'; ?>
    <br><br><br>
    <div class="container">
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-notes-medical"></i> Health Record Management</h1>
                <p>Manage your health information to ensure the school clinic can provide appropriate care.</p>
            </div>
            <div class="page-actions">
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
        
        <?php if(isset($message) && !empty($message)): ?>
            <div class="message-container message-<?= $messageType ?>">
                <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <div class="health-record-form">
            <!-- Basic Health Information -->
            <div class="form-section animate-in" style="animation-delay: 0.1s;">
                <div class="form-section-header">
                    <i class="fas fa-heartbeat"></i>
                    <h2>Basic Health Information</h2>
                </div>
                
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="blood_type">Blood Type</label>
                            <select name="blood_type" id="blood_type" class="form-control" required>
                                <option value="">Select Blood Type</option>
                                <option value="A+" <?= isset($healthRecord['blood_type']) && $healthRecord['blood_type'] === 'A+' ? 'selected' : '' ?>>A+</option>
                                <option value="A-" <?= isset($healthRecord['blood_type']) && $healthRecord['blood_type'] === 'A-' ? 'selected' : '' ?>>A-</option>
                                <option value="B+" <?= isset($healthRecord['blood_type']) && $healthRecord['blood_type'] === 'B+' ? 'selected' : '' ?>>B+</option>
                                <option value="B-" <?= isset($healthRecord['blood_type']) && $healthRecord['blood_type'] === 'B-' ? 'selected' : '' ?>>B-</option>
                                <option value="AB+" <?= isset($healthRecord['blood_type']) && $healthRecord['blood_type'] === 'AB+' ? 'selected' : '' ?>>AB+</option>
                                <option value="AB-" <?= isset($healthRecord['blood_type']) && $healthRecord['blood_type'] === 'AB-' ? 'selected' : '' ?>>AB-</option>
                                <option value="O+" <?= isset($healthRecord['blood_type']) && $healthRecord['blood_type'] === 'O+' ? 'selected' : '' ?>>O+</option>
                                <option value="O-" <?= isset($healthRecord['blood_type']) && $healthRecord['blood_type'] === 'O-' ? 'selected' : '' ?>>O-</option>
                                <option value="Unknown" <?= isset($healthRecord['blood_type']) && $healthRecord['blood_type'] === 'Unknown' ? 'selected' : '' ?>>Unknown</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <select name="gender" id="gender" class="form-control" required>
                                <option value="">Select Gender</option>
                                <option value="Male" <?= isset($healthRecord['gender']) && $healthRecord['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= isset($healthRecord['gender']) && $healthRecord['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                                <option value="Other" <?= isset($healthRecord['gender']) && $healthRecord['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
                                <option value="Prefer not to say" <?= isset($healthRecord['gender']) && $healthRecord['gender'] === 'Prefer not to say' ? 'selected' : '' ?>>Prefer not to say</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="emergency_contact">Emergency Contact Name</label>
                            <input type="text" name="emergency_contact" id="emergency_contact" class="form-control" value="<?= isset($healthRecord['emergency_contact']) ? htmlspecialchars($healthRecord['emergency_contact']) : '' ?>" placeholder="Emergency Contact Name">
                        </div>
                        
                        <div class="form-group">
                            <label for="emergency_phone">Emergency Contact Phone</label>
                            <input type="text" name="emergency_phone" id="emergency_phone" class="form-control" value="<?= isset($healthRecord['emergency_phone']) ? htmlspecialchars($healthRecord['emergency_phone']) : '' ?>" placeholder="Emergency Contact Phone">
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="update_basic" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Basic Information
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Medical Conditions -->
            <div class="form-section animate-in" style="animation-delay: 0.2s;">
                <div class="form-section-header">
                    <i class="fas fa-stethoscope"></i>
                    <h2>Medical Conditions</h2>
                </div>
                
                <div class="record-list">
                    <?php if (count($conditions) > 0): ?>
                        <?php foreach ($conditions as $index => $condition): ?>
                            <div class="record-item animate-in" style="animation-delay: <?= 0.1 * ($index + 1) ?>s;">
                                <div class="record-details">
                                    <div class="record-title"><?= htmlspecialchars($condition['condition_name']) ?></div>
                                    <div class="record-subtitle">
                                        <span>Diagnosed: <?= date('M d, Y', strtotime($condition['diagnosis_date'])) ?></span> | 
                                        <span>Treatment: <?= htmlspecialchars($condition['treatment']) ?></span>
                                    </div>
                                    <?php if (!empty($condition['notes'])): ?>
                                        <div class="record-notes">
                                            <strong>Notes:</strong> <?= htmlspecialchars($condition['notes']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
<div class="record-actions">
    <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this medical condition?');">
        <input type="hidden" name="condition_id" value="<?php echo isset($condition['medical_history_id']) ? $condition['medical_history_id'] : (isset($condition['condition_id']) ? $condition['condition_id'] : ''); ?>">
        <button type="submit" name="delete_condition" class="btn btn-icon btn-danger">
            <i class="fas fa-trash-alt"></i>
        </button>
    </form>
</div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-clipboard-list"></i>
                            <h4 class="empty-title">No Medical Conditions</h4>
                            <p class="empty-description">You haven't added any medical conditions yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <form method="POST" action="" class="add-record-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="condition_name">Condition Name</label>
                            <input type="text" name="condition_name" id="condition_name" class="form-control" required placeholder="Enter medical condition">
                        </div>
                        
                        <div class="form-group">
                            <label for="diagnosis_date">Diagnosis Date</label>
                            <input type="date" name="diagnosis_date" id="diagnosis_date" class="form-control date-picker" required placeholder="Select date">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="treatment">Treatment</label>
                        <input type="text" name="treatment" id="treatment" class="form-control" placeholder="Current treatment or medication">
                    </div>
                    
                    <div class="form-group">
                        <label for="condition_notes">Additional Notes</label>
                        <textarea name="condition_notes" id="condition_notes" class="form-control" rows="3" placeholder="Any additional information about this condition"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="add_condition" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Medical Condition
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Allergies -->
            <div class="form-section animate-in" style="animation-delay: 0.3s;">
                <div class="form-section-header">
                    <i class="fas fa-allergies"></i>
                    <h2>Allergies</h2>
                </div>
                
                <div class="record-list">
                    <?php if (count($allergies) > 0): ?>
                        <?php foreach ($allergies as $index => $allergy): ?>
                            <div class="record-item animate-in" style="animation-delay: <?= 0.1 * ($index + 1) ?>s;">
                                <div class="record-details">
                                    <div class="record-title">
                                        <?= htmlspecialchars($allergy['allergy_name']) ?>
                                        <span class="severity-badge severity-<?= strtolower($allergy['severity']) ?>">
                                            <?= htmlspecialchars($allergy['severity']) ?>
                                        </span>
                                    </div>
                                    <div class="record-subtitle">
    <?php if ($has_reaction): ?>
        <span>Reaction: <?php echo htmlspecialchars($allergy['reaction'] ?? ''); ?></span>
    <?php endif; ?>
</div>
<?php if (!empty($allergy['notes'])): ?>
    <div class="record-notes">
        <strong>Notes:</strong> <?php echo htmlspecialchars($allergy['notes']); ?>
    </div>
<?php endif; ?>
</div>
<div class="record-actions">
    <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this allergy?');">
        <input type="hidden" name="allergy_id" value="<?php echo isset($allergy['allergy_id']) ? $allergy['allergy_id'] : (isset($allergy['id']) ? $allergy['id'] : ''); ?>">
        <button type="submit" name="delete_allergy" class="btn btn-icon btn-danger">
            <i class="fas fa-trash-alt"></i>
        </button>
    </form>
</div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-clipboard-list"></i>
                            <h4 class="empty-title">No Allergies</h4>
                            <p class="empty-description">You haven't added any allergies yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <form method="POST" action="" class="add-record-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="allergy_name">Allergy Name</label>
                            <input type="text" name="allergy_name" id="allergy_name" class="form-control" required placeholder="Enter allergy (food, medication, etc.)">
                        </div>
                        
                        <div class="form-group">
                            <label for="severity">Severity</label>
                            <select name="severity" id="severity" class="form-control" required>
                                <option value="">Select Severity</option>
                                <option value="Mild">Mild</option>
                                <option value="Moderate">Moderate</option>
                                <option value="Severe">Severe</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="reaction">Reaction</label>
                        <input type="text" name="reaction" id="reaction" class="form-control" required placeholder="Describe your allergic reaction">
                    </div>
                    
                    <div class="form-group">
                        <label for="allergy_notes">Additional Notes</label>
                        <textarea name="allergy_notes" id="allergy_notes" class="form-control" rows="3" placeholder="Any additional information about this allergy"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="add_allergy" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Allergy
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize date pickers
        flatpickr(".date-picker", {
            dateFormat: "Y-m-d",
            maxDate: "today"
        });
        
        // Automatically fade message after 5 seconds
        const messageContainer = document.querySelector('.message-container');
        if (messageContainer) {
            setTimeout(function() {
                messageContainer.style.opacity = '0';
                messageContainer.style.transform = 'translateY(-10px)';
                setTimeout(function() {
                    messageContainer.style.display = 'none';
                }, 500);
            }, 5000);
        }
    });
    </script>
</body>
</html>