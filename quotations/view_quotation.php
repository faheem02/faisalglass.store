<?php
/**
 * View Quotations Page
 * Faysal Glass And Aluminium Centre
 * 
 * Display all quotations with status badges including Hold
 * Page: View Quotations
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$page_title = "View Quotations";
$success_msg = '';
$error_msg = '';

// Delete quotation if requested (also reverses stock + customer ledger, like sale deletion)
if(isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    mysqli_begin_transaction($conn);
    try {
        // 1. Restore stock: add back the area consumed by this quotation
        $det_q = mysqli_query($conn, "SELECT product_id, area, quantity FROM quotation_details WHERE quotation_id = $delete_id");
        if($det_q === false) {
            throw new Exception("Failed to read quotation details: " . mysqli_error($conn));
        }
        while($det = mysqli_fetch_assoc($det_q)) {
            $pid = intval($det['product_id']);
            $total_area = floatval($det['area']) * floatval($det['quantity']);
            
            // Only reverse stock if this quotation actually deducted it (skips pre-posting legacy quotations)
            $posted_q = "SELECT id FROM inventory_ledger WHERE reference_type = 'QUOTATION' AND reference_id = $delete_id AND product_id = $pid LIMIT 1";
            $posted_r = mysqli_query($conn, $posted_q);
            $posted = ($posted_r && mysqli_num_rows($posted_r) > 0);
            
            if($posted) {
                $stock_q = "SELECT balance_qty FROM inventory_ledger WHERE product_id = $pid ORDER BY id DESC LIMIT 1";
                $stock_r = mysqli_query($conn, $stock_q);
                $cur = 0;
                if($stock_r && mysqli_num_rows($stock_r) > 0) {
                    $cur = floatval(mysqli_fetch_assoc($stock_r)['balance_qty']);
                }
                $new_stock = $cur + $total_area;
                if(!mysqli_query($conn, "INSERT INTO inventory_ledger (date, product_id, reference_type, reference_id, qty_in, qty_out, balance_qty, unit_price, total_amount, remarks) VALUES (CURDATE(), $pid, 'ADJUSTMENT', $delete_id, $total_area, 0, $new_stock, 0, 0, 'Quotation Deletion Restore')")) {
                    throw new Exception("Failed to restore stock: " . mysqli_error($conn));
                }
                // Remove the original QUOTATION stock entries
                if(!mysqli_query($conn, "DELETE FROM inventory_ledger WHERE reference_type = 'QUOTATION' AND reference_id = $delete_id AND product_id = $pid")) {
                    throw new Exception("Failed to clean quotation stock entries: " . mysqli_error($conn));
                }
            }
        }
        
        // 2. Remove customer ledger entry and recompute customer balance
        $qm = mysqli_query($conn, "SELECT customer_id FROM quotation_master WHERE id = $delete_id");
        $quotation = ($qm && mysqli_num_rows($qm) > 0) ? mysqli_fetch_assoc($qm) : null;
        if($quotation) {
            if(!mysqli_query($conn, "DELETE FROM customer_ledger WHERE reference_type = 'QUOTATION' AND reference_id = $delete_id")) {
                throw new Exception("Failed to remove ledger entry: " . mysqli_error($conn));
            }
            $bal_q = mysqli_query($conn, "SELECT COALESCE(SUM(debit) - SUM(credit), 0) as balance FROM customer_ledger WHERE customer_id = {$quotation['customer_id']}");
            $new_bal = 0;
            if($bal_q && mysqli_num_rows($bal_q) > 0) {
                $new_bal = floatval(mysqli_fetch_assoc($bal_q)['balance']);
            }
            if(!mysqli_query($conn, "UPDATE customers SET current_balance = $new_bal WHERE id = {$quotation['customer_id']}")) {
                throw new Exception("Failed to update customer balance: " . mysqli_error($conn));
            }
        }
        
        mysqli_query($conn, "DELETE FROM quotation_details WHERE quotation_id = $delete_id");
        mysqli_query($conn, "DELETE FROM quotation_master WHERE id = $delete_id");
        mysqli_commit($conn);
        $success_msg = "Quotation deleted successfully! Stock and customer ledger restored.";
    } catch(Exception $e) {
        mysqli_rollback($conn);
        $error_msg = "Failed to delete quotation: " . $e->getMessage();
    }
}

// Fetch all quotations
$query = "SELECT q.*, c.customer_name, c.customer_code 
          FROM quotation_master q
          LEFT JOIN customers c ON q.customer_id = c.id
          ORDER BY q.id DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | <?php echo $software_name; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        .btn-green { background-color: #1e7e34; border-color: #1e7e34; color: white; }
        .btn-green:hover { background-color: #155724; border-color: #155724; color: white; }
        .card-header-custom { background: linear-gradient(135deg, #1e7e34, #0066cc); color: white; border-radius: 10px 10px 0 0; padding: 15px 20px; }
        .table thead th { background-color: #1e7e34; color: white; font-weight: 600; }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            min-width: 80px;
            text-align: center;
        }
        .status-draft { background-color: #6c757d; color: white; }
        .status-hold { background-color: #ffc107; color: #212529; }
        .status-pending { background-color: #17a2b8; color: white; }
        .status-approved { background-color: #28a745; color: white; }
        .status-rejected { background-color: #dc3545; color: white; }
        .status-converted { background-color: #007bff; color: white; }
        
        .action-buttons .btn { margin: 2px; }
    </style>
</head>
<body id="page-top">
<div id="wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <div class="container-fluid">
                
                <div class="d-sm-flex align-items-center justify-content-between mb-4 mt-3">
                    <h1 class="h3 mb-0" style="color: #1e7e34;">
                        <i class="fas fa-file-alt text-success mr-2"></i> View Quotations
                    </h1>
                    <a href="add_quotation.php" class="btn btn-green">
                        <i class="fas fa-plus-circle mr-1"></i> Add Quotation
                    </a>
                </div>
                
                <?php if($success_msg): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle mr-2"></i> <?php echo $success_msg; ?>
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                <?php endif; ?>
                <?php if($error_msg): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error_msg; ?>
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                <?php endif; ?>
                
                <div class="card shadow mb-4">
                    <div class="card-header-custom">
                        <i class="fas fa-list mr-2"></i> Quotations List
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Quotation No</th>
                                        <th>Date</th>
                                        <th>Customer</th>
                                        <th>Grand Total</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = mysqli_fetch_assoc($result)): 
                                        $status = $row['status'];
                                        $status_class = 'status-draft';
                                        if($status == 'hold') $status_class = 'status-hold';
                                        elseif($status == 'pending') $status_class = 'status-pending';
                                        elseif($status == 'approved') $status_class = 'status-approved';
                                        elseif($status == 'rejected') $status_class = 'status-rejected';
                                        elseif($status == 'converted') $status_class = 'status-converted';
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['quotation_no']); ?></strong></td>
                                        <td><?php echo date('d-m-Y', strtotime($row['quotation_date'])); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($row['customer_name']); ?>
                                            <br><small class="text-muted"><?php echo $row['customer_code']; ?></small>
                                        </td>
                                        <td class="text-right"><?php echo formatCurrency($row['grand_total']); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <?php echo ucfirst($status); ?>
                                            </span>
                                        </td>
                                        <td class="action-buttons">
                                            <a href="print_quotation.php?id=<?php echo $row['id']; ?>" 
                                               class="btn btn-sm btn-primary" target="_blank" title="Print">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $row['id']; ?>)" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        
        <footer class="sticky-footer bg-white">
            <div class="container my-auto">
                <div class="copyright text-center my-auto">
                    <span>&copy; <?php echo date('Y'); ?> <?php echo $software_name; ?> - All Rights Reserved</span>
                </div>
            </div>
        </footer>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/js/sb-admin-2.min.js"></script>

<script>
$(document).ready(function() {
    $('#dataTable').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 25,
        "language": {
            "search": "Search:",
            "lengthMenu": "Show _MENU_ entries",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries",
            "infoEmpty": "Showing 0 to 0 of 0 entries",
            "infoFiltered": "(filtered from _MAX_ total entries)",
            "zeroRecords": "No quotations found"
        }
    });
});

function confirmDelete(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This quotation will be permanently deleted!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if(result.isConfirmed) {
            window.location.href = 'view_quotation.php?delete_id=' + id;
        }
    });
}
</script>

</body>
</html>
<?php mysqli_close($conn); ?>