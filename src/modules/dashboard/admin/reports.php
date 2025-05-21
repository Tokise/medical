<?php
session_start();
require_once '../../../../config/config.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /medical/src/auth/login.php');
    exit();
}
// Fetch data for reports
$staff = [];
$sql = "SELECT u.*, r.role_name, d.specialization FROM users u JOIN roles r ON u.role_id = r.role_id LEFT JOIN doctors d ON u.user_id = d.user_id WHERE r.role_name IN ('doctor', 'nurse') ORDER BY u.first_name, u.last_name";
$result = mysqli_query($conn, $sql);
if ($result) while ($row = mysqli_fetch_assoc($result)) $staff[] = $row;

$schedules = [];
$sql = "SELECT s.*, u.first_name, u.last_name, r.role_name FROM staff_schedules s JOIN users u ON s.staff_id = u.user_id JOIN roles r ON u.role_id = r.role_id ORDER BY s.date DESC, s.start_time";
$result = mysqli_query($conn, $sql);
if ($result) while ($row = mysqli_fetch_assoc($result)) $schedules[] = $row;

$inventory = [];
$sql = "SELECT * FROM medical_supplies ORDER BY item_name";
$result = mysqli_query($conn, $sql);
if ($result) while ($row = mysqli_fetch_assoc($result)) $inventory[] = $row;

$appointments = [];
$sql = "SELECT a.*, CONCAT(u_patient.first_name, ' ', u_patient.last_name) as patient_name, CONCAT(u_staff.first_name, ' ', u_staff.last_name) as staff_name FROM appointments a JOIN users u_patient ON a.patient_id = u_patient.user_id JOIN users u_staff ON a.doctor_id = u_staff.user_id ORDER BY a.appointment_date DESC";
$result = mysqli_query($conn, $sql);
if ($result) while ($row = mysqli_fetch_assoc($result)) $appointments[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Medical Management System</title>
    <link rel="stylesheet" href="../../../../src/styles/variables.css">
    <link rel="stylesheet" href="../../../../src/styles/global.css">
    <link rel="stylesheet" href="../../../../src/styles/components.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .reports-container { padding: 2rem; margin-top: 5rem; }
        .tabs { display: flex; gap: 1rem; margin-bottom: 2rem; }
        .tab-btn { background: #f4f7fa; border: none; border-radius: 8px 8px 0 0; padding: 0.7rem 1.5rem; font-size: 1rem; font-weight: 600; color: #333; cursor: pointer; transition: background 0.2s; }
        .tab-btn.active, .tab-btn:hover { background: #007bff; color: #fff; }
        .report-section { display: none; background: #fff; border-radius: 0 0 12px 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); padding: 2rem; }
        .report-section.active { display: block; }
        .export-btns { margin-bottom: 1.2rem; display: flex; gap: 0.7rem; }
        .export-btns button { border: none; border-radius: 6px; padding: 0.5rem 1.2rem; font-size: 1rem; font-weight: 500; cursor: pointer; background: #f4f7fa; color: #333; transition: background 0.2s; }
        .export-btns button:hover { background: #007bff; color: #fff; }
        .report-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .report-table th, .report-table td { padding: 0.7rem 1rem; border-bottom: 1px solid #eee; text-align: left; }
        .report-table th { background: #f8f9fa; color: #333; }
        .report-table tr:hover { background: #f4f7fa; }
        @media (max-width: 700px) { .report-section { padding: 1rem; } .tabs { flex-direction: column; gap: 0.5rem; } }
    </style>
</head>
<body>
<?php include '../../../../includes/header.php'; ?>
<div class="reports-container">
    <h1>Reports</h1>
    <div class="tabs">
        <button class="tab-btn active" data-tab="staff">Staff</button>
        <button class="tab-btn" data-tab="schedule">Schedule</button>
        <button class="tab-btn" data-tab="inventory">Inventory</button>
        <button class="tab-btn" data-tab="appointments">Appointments</button>
    </div>
    <div class="report-section active" id="tab-staff">
        <div class="export-btns">
            <button onclick="exportTable('staff','pdf')"><i class="fas fa-file-pdf"></i> PDF</button>
            <button onclick="exportTable('staff','excel')"><i class="fas fa-file-excel"></i> Excel</button>
            <button onclick="printTable('staff')"><i class="fas fa-print"></i> Print</button>
        </div>
        <table class="report-table" id="table-staff">
            <thead><tr><th>Name</th><th>Role</th><th>Specialization</th><th>Email</th></tr></thead>
            <tbody>
            <?php foreach ($staff as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['first_name'].' '.$s['last_name']) ?></td>
                    <td><?= htmlspecialchars(ucfirst($s['role_name'])) ?></td>
                    <td><?= htmlspecialchars($s['specialization'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($s['email']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="report-section" id="tab-schedule">
        <div class="export-btns">
            <button onclick="exportTable('schedule','pdf')"><i class="fas fa-file-pdf"></i> PDF</button>
            <button onclick="exportTable('schedule','excel')"><i class="fas fa-file-excel"></i> Excel</button>
            <button onclick="printTable('schedule')"><i class="fas fa-print"></i> Print</button>
        </div>
        <table class="report-table" id="table-schedule">
            <thead><tr><th>Staff Name</th><th>Role</th><th>Date</th><th>Start Time</th><th>End Time</th><th>Type</th></tr></thead>
            <tbody>
            <?php foreach ($schedules as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['first_name'].' '.$s['last_name']) ?></td>
                    <td><?= htmlspecialchars(ucfirst($s['role_name'])) ?></td>
                    <td><?= htmlspecialchars($s['date']) ?></td>
                    <td><?= htmlspecialchars($s['start_time']) ?></td>
                    <td><?= htmlspecialchars($s['end_time']) ?></td>
                    <td><?= htmlspecialchars(ucfirst($s['type'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="report-section" id="tab-inventory">
        <div class="export-btns">
            <button onclick="exportTable('inventory','pdf')"><i class="fas fa-file-pdf"></i> PDF</button>
            <button onclick="exportTable('inventory','excel')"><i class="fas fa-file-excel"></i> Excel</button>
            <button onclick="printTable('inventory')"><i class="fas fa-print"></i> Print</button>
        </div>
        <table class="report-table" id="table-inventory">
            <thead><tr><th>Item Name</th><th>Category</th><th>Quantity</th><th>Min Quantity</th></tr></thead>
            <tbody>
            <?php foreach ($inventory as $i): ?>
                <tr>
                    <td><?= htmlspecialchars($i['item_name']) ?></td>
                    <td><?= htmlspecialchars($i['description']) ?></td>
                    <td><?= htmlspecialchars($i['current_quantity']) ?></td>
                    <td><?= htmlspecialchars($i['reorder_level']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="report-section" id="tab-appointments">
        <div class="export-btns">
            <button onclick="exportTable('appointments','pdf')"><i class="fas fa-file-pdf"></i> PDF</button>
            <button onclick="exportTable('appointments','excel')"><i class="fas fa-file-excel"></i> Excel</button>
            <button onclick="printTable('appointments')"><i class="fas fa-print"></i> Print</button>
        </div>
        <table class="report-table" id="table-appointments">
            <thead><tr><th>Date</th><th>Patient</th><th>Staff</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($appointments as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['appointment_date']) ?></td>
                    <td><?= htmlspecialchars($a['patient_name']) ?></td>
                    <td><?= htmlspecialchars($a['staff_name']) ?></td>
                    <td><?= htmlspecialchars($a['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
// Tab logic
const tabBtns = document.querySelectorAll('.tab-btn');
const sections = document.querySelectorAll('.report-section');
tabBtns.forEach(btn => btn.addEventListener('click', function() {
    tabBtns.forEach(b => b.classList.remove('active'));
    sections.forEach(s => s.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
}));
// Export functions
function exportTable(type, format) {
    const table = document.getElementById('table-' + type);
    if (format === 'pdf') {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        doc.autoTable({ html: table });
        doc.save(type + '_report.pdf');
    } else if (format === 'excel') {
        const wb = XLSX.utils.table_to_book(table, {sheet: type + ' Report'});
        XLSX.writeFile(wb, type + '_report.xlsx');
    }
}
function printTable(type) {
    const table = document.getElementById('table-' + type).outerHTML;
    const win = window.open('', '', 'width=900,height=700');
    win.document.write('<html><head><title>Print Report</title>');
    win.document.write('<link rel="stylesheet" href="/medical/assets/css/style.css">');
    win.document.write('</head><body>');
    win.document.write(table);
    win.document.write('</body></html>');
    win.document.close();
    win.print();
}
</script>
</body>
</html> 