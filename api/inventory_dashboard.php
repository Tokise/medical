<?php
require_once '../config/config.php';
header('Content-Type: application/json');

// Inventory trends for the last 7 days
$labels = [];
$totals = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $labels[] = $date;
    $sql = "SELECT SUM(current_quantity) as total FROM medical_supplies WHERE DATE(updated_at) <= '$date'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $totals[] = (int)($row['total'] ?? 0);
}

// Inventory items for the table
$items = [];
$sql = "SELECT item_name, description, current_quantity, reorder_level FROM medical_supplies ORDER BY item_name";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $status = ($row['current_quantity'] <= 0) ? 'Out of Stock' : (($row['current_quantity'] <= $row['reorder_level']) ? 'Low' : 'In Stock');
        $items[] = [
            'name' => $row['item_name'],
            'category' => $row['description'],
            'quantity' => $row['current_quantity'],
            'min_quantity' => $row['reorder_level'],
            'status' => $status
        ];
    }
}

echo json_encode([
    'labels' => $labels,
    'totals' => $totals,
    'items' => $items
]); 