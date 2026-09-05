<?php
/**
 * View Quotations Page
 * Faysal Glass And Aluminium Centre
 * 
 * Display all quotations with status badges including Hold & Payment Details
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
            
            // Only reverse stock if this quotation actually deducted it
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
        
        // 3. Remove cash/bank book entries
        mysqli_query($conn, "DELETE FROM cash_book WHERE reference_type = 'QUOTATION' AND reference_id = $delete_id");
        mysqli_query($conn, "DELETE FROM bank_book WHERE reference_type = 'QUOTATION' AND reference_id = $delete_id");
        
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
        
        .modal-header-custom {
            background: linear-gradient(135deg, #1e7e34, #0066cc);
            color: white;
            border-radius: 0.3rem 0.3rem 0 0;
        }
        .view-info-card {
            background: #f8faf9;
            border: 1px solid #e5e7eb;
            border-left: 3px solid #1e7e34;
            border-radius: 4px;
            padding: 8px 12px;
        }
        .view-info-label {
            font-size: 10px;
            font-weight: 700;
            color: #6b7280;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .view-info-value { font-weight: 600; color: #111827; word-break: break-word; }
        .view-total-label { font-size: 12px; font-weight: 600; color: #374151; }
        .view-total-value { font-weight: 700; color: #111827; text-align: right; }
        .view-grand-total { background: #1e7e34; color: #fff; border-radius: 6px; }
        .view-grand-total .view-total-label { color: #fff; }
        .view-grand-total .view-total-value { color: #fff; font-size: 16px; }
        .view-modal-table thead th {
            background-color: #1e7e34;
            color: white;
            font-weight: 600;
            font-size: 12px;
            text-align: center;
            border: none;
        }
        .view-modal-table td { vertical-align: middle; font-size: 13px; }
        .view-modal-table .size-subheader th {
            background: #0f6bb5;
            font-size: 10px;
            padding: 5px;
            font-weight: 500;
        }
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
                                        <th>Received</th>
                                        <th>Remaining</th>
                                        <th>Payment Type</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['quotation_no']); ?></strong></td>
                                        <td><?php echo date('d-m-Y', strtotime($row['quotation_date'])); ?></td>
                                        <td>
                                            <?php if(!empty($row['walk_in_customer_name'])): ?><strong><?php echo htmlspecialchars($row['walk_in_customer_name']); ?></strong><br><small class="text-info"><i class="fas fa-walking mr-1"></i>Walk-In<?php echo !empty($row['walk_in_customer_phone']) ? ' - ' . htmlspecialchars($row['walk_in_customer_phone']) : ''; ?></small><?php else: ?><?php echo htmlspecialchars($row['customer_name'] ?? 'Walk-In'); ?><?php if(!empty($row['customer_code'])): ?><br><small class="text-muted"><?php echo $row['customer_code']; ?></small><?php endif; ?><?php endif; ?>
                                        </td>
                                        <?php
                                    $q_grand = floatval($row['grand_total'] ?? 0);
                                    $q_received = floatval($row['received_amount'] ?? 0);
                                    // Compute remaining when the stored value is empty/zero
                                    $q_remaining = (isset($row['remaining_amount']) && $row['remaining_amount'] !== null && floatval($row['remaining_amount']) > 0)
                                        ? floatval($row['remaining_amount'])
                                        : max(0, $q_grand - $q_received);
                                    ?>
                                    <td class="text-right"><?php echo formatCurrency($q_grand); ?></td>
                                        <td class="text-right text-success"><?php echo formatCurrency($q_received); ?></td>
                                        <td class="text-right text-danger"><?php echo formatCurrency($q_remaining); ?></td>
                                        <td>
                                            <span class="badge badge-secondary text-uppercase"><?php echo htmlspecialchars($row['payment_type'] ?? 'credit'); ?></span>
                                        </td>
                                        <td class="action-buttons">
                                            <button class="btn btn-sm btn-info" onclick="openViewModal(<?php echo $row['id']; ?>)" title="View Quotation">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <a href="add_quotation.php?edit_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Edit Quotation">
                                                <i class="fas fa-edit"></i>
                                            </a>
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

<!-- View Quotation Modal -->
<div class="modal fade" id="viewQuotationModal" tabindex="-1" role="dialog" aria-labelledby="viewQuotationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header modal-header-custom py-3">
                <h5 class="modal-title" id="viewQuotationModalLabel"><i class="fas fa-file-alt"></i> Quotation Details</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="viewQuotationBody">
                <div class="text-center p-5">
                    <div class="spinner-border text-success" role="status"></div>
                    <p class="mt-2 mb-0">Loading quotation details...</p>
                </div>
            </div>
            <div class="modal-footer" id="viewQuotationFooter">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
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
        text: "This quotation will be permanently deleted! Stock and customer ledger will be restored.",
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

function formatNumber(val) {
    return parseFloat(val || 0).toLocaleString('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function statusBadgeHtml(status) {
    var cls = 'status-draft';
    if(status == 'hold') cls = 'status-hold';
    else if(status == 'pending') cls = 'status-pending';
    else if(status == 'approved') cls = 'status-approved';
    else if(status == 'rejected') cls = 'status-rejected';
    else if(status == 'converted') cls = 'status-converted';
    return '<span class="status-badge ' + cls + '">' + (status.charAt(0).toUpperCase() + status.slice(1)) + '</span>';
}

function openViewModal(id) {
    $('#viewQuotationModal').modal('show');
    $('#viewQuotationBody').html('<div class="text-center p-5"><div class="spinner-border text-success" role="status"></div><p class="mt-2 mb-0">Loading quotation details...</p></div>');
    
    $.ajax({
        url: 'get_quotation_details.php',
        type: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if(!response.success) {
                $('#viewQuotationBody').html('<div class="alert alert-danger m-3"><i class="fas fa-exclamation-circle"></i> ' + (response.message || 'Failed to load quotation') + '</div>');
                return;
            }
            
            var q = response.quotation;
            var c = response.customer;
            
            var html = '';
            html += '<div class="d-flex justify-content-between align-items-center mb-3">';
            html += '<h5 class="mb-0"><strong>' + q.quotation_no + '</strong></h5>';
            html += statusBadgeHtml(q.status);
            html += '</div>';
            
            var qCustName = q.walk_in_customer_name ? (q.walk_in_customer_name + ' <small class="text-muted">(Walk-In)</small>') : (c.customer_name + '<small class="text-muted">' + (c.customer_code || '') + '</small>');
            var qMobile = q.walk_in_customer_phone ? q.walk_in_customer_phone : (c.mobile || '-');
            
            html += '<div class="row mb-3">';
            html += '<div class="col-md-3 mb-2"><div class="view-info-card"><div class="view-info-label">Customer</div><div class="view-info-value">' + qCustName + '</div></div></div>';
            html += '<div class="col-md-3 mb-2"><div class="view-info-card"><div class="view-info-label">Mobile</div><div class="view-info-value">' + qMobile + '</div></div></div>';
            html += '<div class="col-md-3 mb-2"><div class="view-info-card"><div class="view-info-label">Quotation Date</div><div class="view-info-value">' + q.quotation_date + '</div></div></div>';
            html += '<div class="col-md-3 mb-2"><div class="view-info-card"><div class="view-info-label">Valid Until</div><div class="view-info-value">' + (q.valid_until || '-') + '</div></div></div>';
            html += '<div class="col-md-4 mb-2"><div class="view-info-card"><div class="view-info-label">Payment Method</div><div class="view-info-value text-uppercase">' + q.payment_type + (q.bank_name ? ' (' + q.bank_name + ')' : '') + '</div></div></div>';
            html += '<div class="col-md-4 mb-2"><div class="view-info-card"><div class="view-info-label">Reference No</div><div class="view-info-value">' + (q.reference_no || '-') + '</div></div></div>';
            html += '<div class="col-md-4 mb-2"><div class="view-info-card"><div class="view-info-label">Customer Balance</div><div class="view-info-value">₨ ' + formatNumber(c.current_balance) + '</div></div></div>';
            html += '<div class="col-md-12 mb-2"><div class="view-info-card"><div class="view-info-label">Address</div><div class="view-info-value">' + (c.address || '-') + '</div></div></div>';
            html += '</div>';
            
            if(response.items.length > 0) {
                html += '<div class="table-responsive"><table class="table table-bordered view-modal-table">';
                html += '<thead><tr>';
                html += '<th rowspan="2" width="5%">SR #</th><th colspan="2">ACTUAL SIZE</th>';
                html += '<th rowspan="2" width="7%">QTY</th><th rowspan="2" width="11%">Total Area (sq ft)</th>';
                html += '<th rowspan="2" width="18%">GLASS TYPE</th><th rowspan="2" width="8%">PRICE</th>';
                html += '<th rowspan="2" width="10%">DISC %</th><th rowspan="2" width="12%">TOTAL PRICE</th>';
                html += '</tr><tr class="size-subheader"><th width="9%">HEIGHT</th><th width="9%">WIDTH</th></tr></thead><tbody>';
                
                var totalArea = 0;
                var totalPrice = 0;
                for(var i = 0; i < response.items.length; i++) {
                    var item = response.items[i];
                    var unitArea = parseFloat(item.area) || 0;
                    var qty = parseFloat(item.quantity) || 0;
                    var lineArea = unitArea * qty;
                    var rate = parseFloat(item.rate > 0 ? item.rate : item.unit_price) || 0;
                    var amount = parseFloat(item.net_amount > 0 ? item.net_amount : item.amount) || 0;
                    totalArea += lineArea;
                    totalPrice += amount;
                    html += '<tr>';
                    html += '<td class="text-center">' + (i + 1) + '</td>';
                    html += '<td class="text-center">' + (parseFloat(item.client_height) || 0) + '</td>';
                    html += '<td class="text-center">' + (parseFloat(item.client_width) || 0) + '</td>';
                    html += '<td class="text-center">' + qty + '</td>';
                    html += '<td class="text-right">' + formatNumber(lineArea) + '</td>';
                    html += '<td>' + (item.product_name || '-') + '</td>';
                    html += '<td class="text-right">' + formatNumber(rate) + '</td>';
                    html += '<td class="text-center">' + (parseFloat(item.discount_percentage) || 0) + '%</td>';
                    html += '<td class="text-right"><strong>' + formatNumber(amount) + '</strong></td>';
                    html += '</tr>';
                }
                html += '</tbody></table></div>';
                
                html += '<div class="row justify-content-end">';
                html += '<div class="col-md-5">';
                html += '<div class="view-info-card mb-2 d-flex justify-content-between"><span class="view-total-label">Subtotal</span><span class="view-total-value">' + formatNumber(q.subtotal) + '</span></div>';
                if(q.discount_amount > 0) {
                    html += '<div class="view-info-card mb-2 d-flex justify-content-between"><span class="view-total-label">Discount (' + q.discount_percentage + '%)</span><span class="view-total-value text-danger">- ' + formatNumber(q.discount_amount) + '</span></div>';
                }
                if(q.other_charges > 0) {
                    html += '<div class="view-info-card mb-2 d-flex justify-content-between"><span class="view-total-label">Other Charges</span><span class="view-total-value">+ ' + formatNumber(q.other_charges) + '</span></div>';
                }
                html += '<div class="view-info-card view-grand-total mb-2 d-flex justify-content-between p-3"><span class="view-total-label">Grand Total</span><span class="view-total-value">₨ ' + formatNumber(q.grand_total) + '</span></div>';
                html += '<div class="view-info-card mb-2 d-flex justify-content-between"><span class="view-total-label">Advance / Paid</span><span class="view-total-value text-success">₨ ' + formatNumber(q.received_amount || 0) + '</span></div>';
                var remainingCalc = (q.remaining_amount != null && parseFloat(q.remaining_amount) > 0) ? parseFloat(q.remaining_amount) : Math.max(0, parseFloat(q.grand_total || 0) - parseFloat(q.received_amount || 0));
                html += '<div class="view-info-card mb-2 d-flex justify-content-between"><span class="view-total-label">Remaining Balance</span><span class="view-total-value text-danger">₨ ' + formatNumber(remainingCalc) + '</span></div>';
                html += '</div></div>';
            } else {
                html += '<div class="alert alert-info">No products found for this quotation.</div>';
            }
            
            if(q.remarks) {
                html += '<div class="alert alert-warning mb-0"><strong>Remarks:</strong> ' + q.remarks + '</div>';
            }
            
            $('#viewQuotationBody').html(html);
            $('#viewQuotationFooter').html(
                '<a href="add_quotation.php?edit_id=' + q.id + '" class="btn btn-warning"><i class="fas fa-edit"></i> Edit</a>' +
                '<a href="print_quotation.php?id=' + q.id + '" class="btn btn-primary" target="_blank"><i class="fas fa-print"></i> Print</a>' +
                '<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>'
            );
        },
        error: function() {
            $('#viewQuotationBody').html('<div class="alert alert-danger m-3"><i class="fas fa-exclamation-circle"></i> Failed to load quotation details</div>');
        }
    });
}
</script>

</body>
</html>
<?php mysqli_close($conn); ?>