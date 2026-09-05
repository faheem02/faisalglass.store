<?php
session_start();
if(!isset($_SESSION['user_id'])) exit;
include('../includes/database.php');

$hold_no = isset($_GET['hold_no']) ? mysqli_real_escape_string($conn, $_GET['hold_no']) : '';
$customer = isset($_GET['customer']) ? mysqli_real_escape_string($conn, $_GET['customer']) : '';
$status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : 'hold';

$sql = "SELECT h.id, h.hold_no, h.hold_date, COALESCE(NULLIF(h.walk_in_customer_name, ''), NULLIF(c.customer_name, ''), 'Walk-In') as customer_name, h.walk_in_customer_name, h.grand_total, h.status, u.username as created_by_name 
        FROM hold_sales_master h 
        LEFT JOIN customers c ON h.customer_id = c.id 
        LEFT JOIN users u ON h.created_by = u.id 
        WHERE h.status = '$status'";
if($hold_no) $sql .= " AND h.hold_no LIKE '%$hold_no%'";
if($customer) $sql .= " AND (c.customer_name LIKE '%$customer%' OR h.walk_in_customer_name LIKE '%$customer%')";
$sql .= " ORDER BY h.hold_date DESC";

$result = mysqli_query($conn, $sql);
$data = [];
while($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}
echo json_encode(['data' => $data]);
?>