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
            case 'add_inventory':
                $item_name = mysqli_real_escape_string($conn, $_POST['item_name']);
                $category = mysqli_real_escape_string($conn, $_POST['category']);
                $quantity = (int)$_POST['quantity'];
                $min_quantity = (int)$_POST['min_quantity'];
                $sql = "INSERT INTO medical_supplies (item_name, description, current_quantity, reorder_level) VALUES (?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ssii", $item_name, $category, $quantity, $min_quantity);
                if (mysqli_stmt_execute($stmt)) {
                    header("Location: inventory_management.php?success=1");
                    exit();
                } else {
                    header("Location: inventory_management.php?error=1");
                    exit();
                }
                break;
            case 'delete_inventory':
                $item_id = (int)$_POST['item_id'];
                $sql = "DELETE FROM medical_supplies WHERE item_id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $item_id);
                if (mysqli_stmt_execute($stmt)) {
                    header("Location: inventory_management.php?success=1");
                    exit();
                } else {
                    header("Location: inventory_management.php?error=1");
                    exit();
                }
                break;
            case 'update_inventory':
                $item_id = (int)$_POST['item_id'];
                $item_name = mysqli_real_escape_string($conn, $_POST['item_name']);
                $category = mysqli_real_escape_string($conn, $_POST['category']);
                $quantity = (int)$_POST['quantity'];
                $min_quantity = (int)$_POST['min_quantity'];
                $sql = "UPDATE medical_supplies SET item_name=?, description=?, current_quantity=?, reorder_level=? WHERE item_id=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ssiii", $item_name, $category, $quantity, $min_quantity, $item_id);
                if (mysqli_stmt_execute($stmt)) {
                    header("Location: inventory_management.php?success=1");
                    exit();
                } else {
                    header("Location: inventory_management.php?error=1");
                    exit();
                }
                break;
        }
    }
}

// Get all inventory items
$inventory = [];
$sql = "SELECT * FROM medical_supplies ORDER BY item_name";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $row['status'] = ($row['current_quantity'] <= 0) ? 'Out of Stock' : (($row['current_quantity'] <= $row['reorder_level']) ? 'Low' : 'In Stock');
        $inventory[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Medical Management System</title>
    <link rel="stylesheet" href="../../../../src/styles/variables.css">
    <link rel="stylesheet" href="../../../../src/styles/global.css">
    <link rel="stylesheet" href="../../../../src/styles/components.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .inventory-management {
            padding: 2rem;
            margin-top: 5rem;
        }
        .add-inventory-btn {
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
        .inventory-table-container {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-top: 2rem;
        }
        table.inventory-table {
            width: 100%;
            border-collapse: collapse;
        }
        table.inventory-table th, table.inventory-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #eee;
            text-align: left;
        }
        table.inventory-table th {
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
        .badge-status {
            display: inline-block;
            padding: 0.3em 0.8em;
            border-radius: 12px;
            font-size: 0.95em;
            font-weight: 600;
            color: #fff;
        }
        .badge-instock { background: #28a745; }
        .badge-low { background: #ffc107; color: #222; }
        .badge-out { background: #dc3545; }
    </style>
</head>
<body>
    <?php include '../../../../includes/header.php'; ?>
    <div class="inventory-management">
        <h1>Inventory Management</h1>
        <button class="add-inventory-btn" id="openAddModal" title="Add Inventory"><i class="fas fa-plus"></i></button>
        <div class="inventory-table-container">
            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Min Quantity</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inventory as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['description']); ?></td>
                            <td><?php echo htmlspecialchars($item['current_quantity']); ?></td>
                            <td><?php echo htmlspecialchars($item['reorder_level']); ?></td>
                            <td>
                                <span class="badge-status badge-<?php echo $item['status'] === 'In Stock' ? 'instock' : ($item['status'] === 'Low' ? 'low' : 'out'); ?>">
                                    <?php echo $item['status']; ?>
                                </span>
                            </td>
                            <td class="action-btns">
                                <button type="button" class="btn btn-primary edit-inventory-btn" data-id="<?php echo $item['item_id']; ?>" data-name="<?php echo htmlspecialchars($item['item_name']); ?>" data-category="<?php echo htmlspecialchars($item['description']); ?>" data-quantity="<?php echo $item['current_quantity']; ?>" data-min_quantity="<?php echo $item['reorder_level']; ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_inventory">
                                    <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                    <button type="submit" class="btn btn-danger delete-inventory-btn"><i class="fas fa-trash"></i> Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <!-- Modal for Add Inventory -->
        <div class="modal" id="addInventoryModal">
            <div class="modal-content">
                <button class="close-modal" id="closeAddModal" aria-label="Close">&times;</button>
                <h2>Add Inventory Item</h2>
                <hr class="modal-divider" />
                <form method="POST" action="" id="addInventoryForm">
                    <input type="hidden" name="action" value="add_inventory">
                    <div class="form-group">
                        <label for="item_name">Item Name</label>
                        <input type="text" id="item_name" name="item_name" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category" required>
                            <option value="Medicine">Medicine</option>
                            <option value="Pills">Pills</option>
                            <option value="First Aid">First Aid</option>
                            <option value="PPE">PPE</option>
                            <option value="Equipment">Equipment</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="quantity">Quantity</label>
                        <input type="number" id="quantity" name="quantity" min="0" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="min_quantity">Min Quantity</label>
                        <input type="number" id="min_quantity" name="min_quantity" min="0" required autocomplete="off">
                    </div>
                    <button type="submit" class="btn btn-primary">Add Inventory</button>
                </form>
            </div>
        </div>
        <!-- Edit Inventory Modal -->
        <div class="modal" id="editInventoryModal">
            <div class="modal-content" id="editInventoryContent" style="animation: fadeInModal 0.3s cubic-bezier(.4,2,.6,1);">
                <button class="close-modal" id="closeEditModal" aria-label="Close">&times;</button>
                <h2>Edit Inventory Item</h2>
                <hr class="modal-divider" />
                <form method="POST" action="" id="editInventoryForm">
                    <input type="hidden" name="action" value="update_inventory">
                    <input type="hidden" name="item_id" id="edit_item_id">
                    <div class="form-group">
                        <label for="edit_item_name">Item Name</label>
                        <input type="text" id="edit_item_name" name="item_name" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="edit_category">Category</label>
                        <select id="edit_category" name="category" required>
                            <option value="Medicine">Medicine</option>
                            <option value="Pills">Pills</option>
                            <option value="First Aid">First Aid</option>
                            <option value="PPE">PPE</option>
                            <option value="Equipment">Equipment</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_quantity">Quantity</label>
                        <input type="number" id="edit_quantity" name="quantity" min="0" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="edit_min_quantity">Min Quantity</label>
                        <input type="number" id="edit_min_quantity" name="min_quantity" min="0" required autocomplete="off">
                    </div>
                    <button type="submit" class="btn btn-primary">Update Inventory</button>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Modal logic
        const addModal = document.getElementById('addInventoryModal');
        const openAddModal = document.getElementById('openAddModal');
        const closeAddModal = document.getElementById('closeAddModal');
        openAddModal.onclick = () => addModal.style.display = 'flex';
        closeAddModal.onclick = () => addModal.style.display = 'none';
        window.onclick = function(event) {
            if (event.target === addModal) addModal.style.display = 'none';
        }
        // Edit modal logic
        const editModal = document.getElementById('editInventoryModal');
        const closeEditModal = document.getElementById('closeEditModal');
        document.querySelectorAll('.edit-inventory-btn').forEach(btn => {
            btn.onclick = function() {
                document.getElementById('edit_item_id').value = btn.dataset.id;
                document.getElementById('edit_item_name').value = btn.dataset.name;
                document.getElementById('edit_category').value = btn.dataset.category;
                document.getElementById('edit_quantity').value = btn.dataset.quantity;
                document.getElementById('edit_min_quantity').value = btn.dataset.min_quantity;
                editModal.style.display = 'flex';
            };
        });
        closeEditModal.onclick = function() { editModal.style.display = 'none'; };
        window.onclick = function(event) {
            if (event.target === editModal) editModal.style.display = 'none';
        };
        // SweetAlert2 notifications from PHP
        <?php if (isset($_GET['success'])): ?>
            Swal.fire({icon:'success',title:'Success',text:'Inventory action completed successfully!'});
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            Swal.fire({icon:'error',title:'Error',text:'There was an error processing your request.'});
        <?php endif; ?>
        document.querySelectorAll('.delete-inventory-btn').forEach(btn => {
            btn.onclick = function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'This will delete the inventory item.',
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