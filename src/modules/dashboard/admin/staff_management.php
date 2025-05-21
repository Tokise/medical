<?php
session_start();
require_once '../../../../config/config.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /medical/src/auth/login.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_staff':
                $username = mysqli_real_escape_string($conn, $_POST['username']);
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $email = mysqli_real_escape_string($conn, $_POST['email']);
                $firstName = mysqli_real_escape_string($conn, $_POST['first_name']);
                $lastName = mysqli_real_escape_string($conn, $_POST['last_name']);
                $role = mysqli_real_escape_string($conn, $_POST['role']);
                $specialization = isset($_POST['specialization']) ? mysqli_real_escape_string($conn, $_POST['specialization']) : null;
                
                // Start transaction
                mysqli_begin_transaction($conn);
                
                try {
                    // Insert into users table
                    $sql = "INSERT INTO users (username, password, email, first_name, last_name, role_id) 
                           SELECT ?, ?, ?, ?, ?, role_id FROM roles WHERE role_name = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "ssssss", $username, $password, $email, $firstName, $lastName, $role);
                    mysqli_stmt_execute($stmt);
                    
                    $userId = mysqli_insert_id($conn);
                    
                    // Insert into specific role table
                    if ($role === 'doctor') {
                        $sql = "INSERT INTO doctors (user_id, specialization) VALUES (?, ?)";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "is", $userId, $specialization);
                    } else if ($role === 'nurse') {
                        $sql = "INSERT INTO nurses (user_id) VALUES (?)";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "i", $userId);
                    }
                    
                    mysqli_stmt_execute($stmt);
                    mysqli_commit($conn);
                    header("Location: staff_management.php?success=1");
                    exit();
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    header("Location: staff_management.php?error=1");
                    exit();
                }
                break;
                
            case 'delete_staff':
                $userId = (int)$_POST['user_id'];
                $role = mysqli_real_escape_string($conn, $_POST['role']);
                
                mysqli_begin_transaction($conn);
                
                try {
                    // Delete from specific role table first
                    if ($role === 'doctor') {
                        mysqli_query($conn, "DELETE FROM doctors WHERE user_id = $userId");
                    } else if ($role === 'nurse') {
                        mysqli_query($conn, "DELETE FROM nurses WHERE user_id = $userId");
                    }
                    
                    // Then delete from users table
                    mysqli_query($conn, "DELETE FROM users WHERE user_id = $userId");
                    
                    mysqli_commit($conn);
                    header("Location: staff_management.php?success=1");
                    exit();
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    header("Location: staff_management.php?error=1");
                    exit();
                }
                break;
                
            case 'update_staff':
                $user_id = (int)$_POST['user_id'];
                $email = mysqli_real_escape_string($conn, $_POST['email']);
                $firstName = mysqli_real_escape_string($conn, $_POST['first_name']);
                $lastName = mysqli_real_escape_string($conn, $_POST['last_name']);
                $role = mysqli_real_escape_string($conn, $_POST['role']);
                $specialization = isset($_POST['specialization']) ? mysqli_real_escape_string($conn, $_POST['specialization']) : null;
                // Update users table
                $sql = "UPDATE users SET email=?, first_name=?, last_name=?, role_id=(SELECT role_id FROM roles WHERE role_name=?) WHERE user_id=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ssssi", $email, $firstName, $lastName, $role, $user_id);
                mysqli_stmt_execute($stmt);
                // Update specialization if doctor
                if ($role === 'doctor') {
                    $sql = "UPDATE doctors SET specialization=? WHERE user_id=?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "si", $specialization, $user_id);
                    mysqli_stmt_execute($stmt);
                }
                header("Location: staff_management.php?success=1");
                exit();
        }
    }
}

// Get all staff members
$staff = [];
$sql = "SELECT u.*, r.role_name, 
        CASE 
            WHEN d.doctor_id IS NOT NULL THEN d.specialization 
            ELSE NULL 
        END as specialization
        FROM users u 
        JOIN roles r ON u.role_id = r.role_id 
        LEFT JOIN doctors d ON u.user_id = d.user_id 
        LEFT JOIN nurses n ON u.user_id = n.user_id 
        WHERE r.role_name IN ('doctor', 'nurse')
        ORDER BY u.first_name, u.last_name";
$result = mysqli_query($conn, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $staff[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management - Medical Management System</title>
    <link rel="stylesheet" href="../../../../src/styles/variables.css">
    <link rel="stylesheet" href="../../../../src/styles/global.css">
    <link rel="stylesheet" href="../../../../src/styles/components.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .staff-management {
            padding: 2rem;
            margin-top: 5rem;
        }
        .add-staff-btn {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            font-size: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            cursor: pointer;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .staff-table-container {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-top: 2rem;
        }
        table.staff-table {
            width: 100%;
            border-collapse: collapse;
        }
        table.staff-table th, table.staff-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #eee;
            text-align: left;
        }
        table.staff-table th {
            background: #f8f9fa;
            color: #333;
        }
        .action-btns button {
            margin-right: 0.5rem;
        }
        /* Modern Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100vw;
            height: 100vh;
            overflow: auto;
            background: rgba(0,0,0,0.25);
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: #fff;
            margin: auto;
            padding: 2.5rem 2rem 2rem 2rem;
            border-radius: 18px;
            width: 100%;
            max-width: 420px;
            position: relative;
            box-shadow: 0 8px 32px rgba(0,0,0,0.18);
            display: flex;
            flex-direction: column;
            align-items: stretch;
            animation: modalIn 0.2s cubic-bezier(.4,2,.6,1) 1;
        }
        @keyframes modalIn {
            from { transform: translateY(40px) scale(0.98); opacity: 0; }
            to { transform: none; opacity: 1; }
        }
        .close-modal {
            position: absolute;
            top: 1.2rem;
            right: 1.2rem;
            width: 36px;
            height: 36px;
            background: #f2f2f2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: #888;
            cursor: pointer;
            border: none;
            transition: background 0.2s;
        }
        .close-modal:hover {
            background: #e0e0e0;
            color: #333;
        }
        .modal-content h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 0 0.5rem 0;
            text-align: left;
            color: #222;
        }
        .modal-divider {
            height: 2px;
            background: #f0f0f0;
            margin: 0.5rem 0 1.5rem 0;
            border: none;
        }
        .modal-content .form-group {
            margin-bottom: 1.1rem;
        }
        .modal-content label {
            font-weight: 500;
            color: #333;
            margin-bottom: 0.3rem;
            display: block;
        }
        .modal-content input,
        .modal-content select {
            width: 100%;
            padding: 0.6rem 0.9rem;
            border: 1.5px solid #e0e0e0;
            border-radius: 7px;
            font-size: 1rem;
            background: #fafbfc;
            transition: border 0.2s, box-shadow 0.2s;
        }
        .modal-content input:focus,
        .modal-content select:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 2px #007bff22;
        }
        .modal-content button[type="submit"] {
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 7px;
            padding: 0.8rem 0;
            font-size: 1.1rem;
            font-weight: 600;
            margin-top: 0.7rem;
            cursor: pointer;
            transition: background 0.18s, box-shadow 0.18s;
            box-shadow: 0 2px 8px rgba(0,123,255,0.08);
        }
        .modal-content button[type="submit"]:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <?php include '../../../../includes/header.php'; ?>
    <div class="staff-management">
        <h1>Staff Management</h1>
        <button class="add-staff-btn" id="openAddModal" title="Add Staff"><i class="fas fa-plus"></i></button>
        <div class="staff-table-container">
            <table class="staff-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Specialization</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($staff as $member): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($member['role_name'])); ?></td>
                            <td><?php echo htmlspecialchars($member['specialization'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($member['email']); ?></td>
                            <td class="action-btns">
                                <button type="button" class="btn btn-primary edit-staff-btn" data-id="<?php echo $member['user_id']; ?>" data-email="<?php echo htmlspecialchars($member['email']); ?>" data-first_name="<?php echo htmlspecialchars($member['first_name']); ?>" data-last_name="<?php echo htmlspecialchars($member['last_name']); ?>" data-role="<?php echo htmlspecialchars($member['role_name']); ?>" data-specialization="<?php echo htmlspecialchars($member['specialization']); ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_staff">
                                    <input type="hidden" name="user_id" value="<?php echo $member['user_id']; ?>">
                                    <input type="hidden" name="role" value="<?php echo $member['role_name']; ?>">
                                    <button type="submit" class="btn btn-danger delete-staff-btn"><i class="fas fa-trash"></i> Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <!-- Modal for Add Staff -->
        <div class="modal" id="addStaffModal">
            <div class="modal-content">
                <button class="close-modal" id="closeAddModal" aria-label="Close">&times;</button>
                <h2>Add New Staff Member</h2>
                <hr class="modal-divider" />
                <form method="POST" action="" id="addStaffForm">
                    <input type="hidden" name="action" value="add_staff">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="role">Role</label>
                        <select id="role" name="role" required onchange="toggleSpecialization()">
                            <option value="doctor">Doctor</option>
                            <option value="nurse">Nurse</option>
                        </select>
                    </div>
                    <div class="form-group" id="specializationGroup" style="display: none;">
                        <label for="specialization">Specialization</label>
                        <input type="text" id="specialization" name="specialization" autocomplete="off">
                    </div>
                    <button type="submit" class="btn btn-primary">Add Staff Member</button>
                </form>
            </div>
        </div>
        <!-- Edit Staff Modal -->
        <div class="modal" id="editStaffModal">
            <div class="modal-content" id="editStaffContent" style="animation: fadeInModal 0.3s cubic-bezier(.4,2,.6,1);">
                <button class="close-modal" id="closeEditStaffModal" aria-label="Close">&times;</button>
                <h2>Edit Staff Member</h2>
                <hr class="modal-divider" />
                <form method="POST" action="" id="editStaffForm">
                    <input type="hidden" name="action" value="update_staff">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="form-group">
                        <label for="edit_email">Email</label>
                        <input type="email" id="edit_email" name="email" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="edit_first_name">First Name</label>
                        <input type="text" id="edit_first_name" name="first_name" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="edit_last_name">Last Name</label>
                        <input type="text" id="edit_last_name" name="last_name" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="edit_role">Role</label>
                        <select id="edit_role" name="role" required onchange="toggleEditSpecialization()">
                            <option value="doctor">Doctor</option>
                            <option value="nurse">Nurse</option>
                        </select>
                    </div>
                    <div class="form-group" id="edit_specializationGroup" style="display: none;">
                        <label for="edit_specialization">Specialization</label>
                        <input type="text" id="edit_specialization" name="specialization" autocomplete="off">
                    </div>
                    <button type="submit" class="btn btn-primary">Update Staff</button>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Modal logic
        const addModal = document.getElementById('addStaffModal');
        const openAddModal = document.getElementById('openAddModal');
        const closeAddModal = document.getElementById('closeAddModal');
        openAddModal.onclick = () => addModal.style.display = 'flex';
        closeAddModal.onclick = () => addModal.style.display = 'none';
        window.onclick = function(event) {
            if (event.target === addModal) addModal.style.display = 'none';
        }
        // Specialization toggle
        function toggleSpecialization() {
            const role = document.getElementById('role').value;
            const specializationGroup = document.getElementById('specializationGroup');
            const specializationInput = document.getElementById('specialization');
            if (role === 'doctor') {
                specializationGroup.style.display = 'block';
                specializationInput.required = true;
            } else {
                specializationGroup.style.display = 'none';
                specializationInput.required = false;
            }
        }
        // Edit modal logic
        const editStaffModal = document.getElementById('editStaffModal');
        const closeEditStaffModal = document.getElementById('closeEditStaffModal');
        document.querySelectorAll('.edit-staff-btn').forEach(btn => {
            btn.onclick = function() {
                document.getElementById('edit_user_id').value = btn.dataset.id;
                document.getElementById('edit_email').value = btn.dataset.email;
                document.getElementById('edit_first_name').value = btn.dataset.first_name;
                document.getElementById('edit_last_name').value = btn.dataset.last_name;
                document.getElementById('edit_role').value = btn.dataset.role;
                document.getElementById('edit_specialization').value = btn.dataset.specialization;
                if (btn.dataset.role === 'doctor') {
                    document.getElementById('edit_specializationGroup').style.display = 'block';
                } else {
                    document.getElementById('edit_specializationGroup').style.display = 'none';
                }
                editStaffModal.style.display = 'flex';
            };
        });
        closeEditStaffModal.onclick = function() { editStaffModal.style.display = 'none'; };
        window.onclick = function(event) {
            if (event.target === editStaffModal) editStaffModal.style.display = 'none';
        };
        function toggleEditSpecialization() {
            const role = document.getElementById('edit_role').value;
            const specializationGroup = document.getElementById('edit_specializationGroup');
            const specializationInput = document.getElementById('edit_specialization');
            if (role === 'doctor') {
                specializationGroup.style.display = 'block';
                specializationInput.required = true;
            } else {
                specializationGroup.style.display = 'none';
                specializationInput.required = false;
            }
        }
        // SweetAlert2 notifications from PHP
        <?php if (isset($_GET['success'])): ?>
            Swal.fire({icon:'success',title:'Success',text:'Staff member action completed successfully!'});
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            Swal.fire({icon:'error',title:'Error',text:'There was an error processing your request.'});
        <?php endif; ?>
        document.querySelectorAll('.delete-staff-btn').forEach(btn => {
            btn.onclick = function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'This will delete the staff member.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        btn.closest('form').submit();
                    }
                });
                return false;
            };
        });
    </script>
</body>
</html> 