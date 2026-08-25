<?php
/**
 * View Purchase Invoice Page
 * Faysal Glass And Aluminium Centre
 * 
 * Display all purchase invoices with list, view, print, delete, and refund functionality
 * Page: View Purchase Invoice
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$page_title = "View Purchase Invoice";
$success_msg = '';
$error_msg = '';

// Handle Delete Purchase
if(isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    $purchase_query = "SELECT * FROM purchase_master WHERE id = $delete_id";
    $purchase_result = mysqli_query($conn, $purchase_query);
    
    if($purchase_result && mysqli_num_rows($purchase_result) > 0) {
        $purchase = mysqli_fetch_assoc($purchase_result);
        
        mysqli_begin_transaction($conn);
        
        try {
            $details_query = "SELECT * FROM purchase_details WHERE purchase_id = $delete_id";
            $details_result = mysqli_query($conn, $details_query);
            
            while($detail = mysqli_fetch_assoc($details_result)) {
                $stock_query = "SELECT balance_qty FROM inventory_ledger WHERE product_id = {$detail['product_id']} ORDER BY id DESC LIMIT 1";
                $stock_result = mysqli_query($conn, $stock_query);
                $current_stock = 0;
                if($stock_result && mysqli_num_rows($stock_result) > 0) {
                    $stock_data = mysqli_fetch_assoc($stock_result);
                    $current_stock = floatval($stock_data['balance_qty']);
                }
                
                $new_stock = $current_stock - $detail['quantity'];
                
                $inventory_query = "INSERT INTO inventory_ledger (date, product_id, reference_type, reference_id, 
                                    qty_in, qty_out, balance_qty, unit_price, total_amount, remarks) 
                                    VALUES (CURDATE(), {$detail['product_id']}, 'ADJUSTMENT', $delete_id, 
                                    0, {$detail['quantity']}, $new_stock, {$detail['unit_price']}, 0, 
                                    'Purchase Deleted: {$purchase['invoice_no']}')";
                mysqli_query($conn, $inventory_query);
            }
            
            mysqli_query($conn, "DELETE FROM purchase_details WHERE purchase_id = $delete_id");
            mysqli_query($conn, "DELETE FROM purchase_master WHERE id = $delete_id");
            mysqli_query($conn, "DELETE FROM supplier_ledger WHERE reference_type = 'PURCHASE' AND reference_id = $delete_id");
            
            $supplier_balance_query = "SELECT SUM(credit) - SUM(debit) as balance FROM supplier_ledger WHERE supplier_id = {$purchase['supplier_id']}";
            $supplier_balance_result = mysqli_query($conn, $supplier_balance_query);
            $new_supplier_balance = 0;
            if($supplier_balance_result && mysqli_num_rows($supplier_balance_result) > 0) {
                $bal_data = mysqli_fetch_assoc($supplier_balance_result);
                $new_supplier_balance = floatval($bal_data['balance']);
            }
            
            mysqli_query($conn, "UPDATE suppliers SET current_balance = $new_supplier_balance WHERE id = {$purchase['supplier_id']}");
            mysqli_query($conn, "DELETE FROM cash_book WHERE reference_type = 'PURCHASE' AND reference_id = $delete_id");
            mysqli_query($conn, "DELETE FROM bank_book WHERE reference_type = 'PURCHASE' AND reference_id = $delete_id");
            
            mysqli_commit($conn);
            $success_msg = "Purchase invoice deleted successfully!";
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error_msg = $e->getMessage();
        }
    }
}

// Get filter parameters
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
$filter_supplier = isset($_GET['supplier_id']) ? intval($_GET['supplier_id']) : 0;
$filter_invoice = isset($_GET['invoice_no']) ? mysqli_real_escape_string($conn, $_GET['invoice_no']) : '';
$filter_payment_type = isset($_GET['payment_type']) ? mysqli_real_escape_string($conn, $_GET['payment_type']) : '';

$suppliers_query = "SELECT id, supplier_name FROM suppliers WHERE status = 1 ORDER BY supplier_name";
$suppliers_result = mysqli_query($conn, $suppliers_query);

$purchases_query = "SELECT p.*, s.supplier_name, s.supplier_code, s.mobile
                    FROM purchase_master p
                    LEFT JOIN suppliers s ON p.supplier_id = s.id
                    WHERE p.purchase_date BETWEEN '$from_date' AND '$to_date'";

if($filter_supplier > 0) $purchases_query .= " AND p.supplier_id = $filter_supplier";
if(!empty($filter_invoice)) $purchases_query .= " AND p.invoice_no LIKE '%$filter_invoice%'";
if(!empty($filter_payment_type)) $purchases_query .= " AND p.payment_type = '$filter_payment_type'";

$purchases_query .= " ORDER BY p.id DESC";
$purchases_result = mysqli_query($conn, $purchases_query);

$summary_query = "SELECT 
                    SUM(grand_total) as total_purchase,
                    SUM(paid_amount) as total_paid,
                    SUM(remaining_amount) as total_remaining,
                    COUNT(*) as total_count
                  FROM purchase_master p
                  WHERE p.purchase_date BETWEEN '$from_date' AND '$to_date'";

if($filter_supplier > 0) $summary_query .= " AND p.supplier_id = $filter_supplier";
if(!empty($filter_payment_type)) $summary_query .= " AND p.payment_type = '$filter_payment_type'";

$summary_result = mysqli_query($conn, $summary_query);
$summary = mysqli_fetch_assoc($summary_result);

$total_purchase = floatval($summary['total_purchase']);
$total_paid = floatval($summary['total_paid']);
$total_remaining = floatval($summary['total_remaining']);
$total_count = intval($summary['total_count']);

$today_summary = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(grand_total) as today_total FROM purchase_master WHERE purchase_date = CURDATE()"));
$today_purchase = floatval($today_summary['today_total']);
$month_summary = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(grand_total) as month_total FROM purchase_master WHERE MONTH(purchase_date) = MONTH(CURDATE()) AND YEAR(purchase_date) = YEAR(CURDATE())"));
$month_purchase = floatval($month_summary['month_total']);
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
        .form-card { border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 30px; }
        .summary-card { text-align: center; padding: 20px; border-radius: 10px; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.08); height: 100%; transition: transform 0.2s; }
        .summary-card:hover { transform: translateY(-3px); }
        .summary-number { font-size: 28px; font-weight: bold; }
        .table thead th { background-color: #1e7e34; color: white; font-weight: 600; }
        .badge-paid { background-color: #28a745; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .badge-partial { background-color: #ffc107; color: #1a1a1a; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .badge-pending { background-color: #dc3545; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .badge-refund { background-color: #17a2b8; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .invoice-no { font-family: monospace; font-weight: bold; color: #0066cc; }
        .action-btns{display:flex;align-items:center;flex-wrap:nowrap;gap:4px}
        .action-btns .btn{display:inline-flex;align-items:center;white-space:nowrap}
        @media print {
            body { background: #fff !important; }
            #wrapper { margin: 0 !important; }
            #accordionSidebar, .topbar, .sticky-footer, .scroll-to-top,
            .no-print, .modal, .modal-backdrop, .dataTables_length,
            .dataTables_filter, .dataTables_info, .dataTables_paginate,
            .dataTables_wrapper > .row:first-child, .dataTables_wrapper > .row:last-child {
                display: none !important;
            }
            .container-fluid { padding: 0 !important; }
            .card { border: none !important; box-shadow: none !important; margin-bottom: 8px !important; }
            .card-header-custom, .table thead th {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body id="page-top">

<div id="wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <div class="container-fluid">
        
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-file-invoice text-success mr-2"></i> View Purchase Invoice
            </h1>
            <div class="no-print">
                <a href="add_purchase.php" class="btn btn-green">
                    <i class="fas fa-plus-circle mr-1"></i> Add Purchase
                </a>
                <button type="button" class="btn btn-outline-success ml-2" onclick="window.open('print_purchase_list.php?from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&supplier_id=<?php echo $filter_supplier; ?>&invoice_no=<?php echo urlencode($filter_invoice); ?>&payment_type=<?php echo urlencode($filter_payment_type); ?>', '_blank', 'width=1100,height=700')">
                    <i class="fas fa-print mr-1"></i> Print
                </button>
                <button type="button" class="btn btn-outline-info ml-2" id="exportBtn">
                    <i class="fas fa-file-excel mr-1"></i> Export
                </button>
            </div>
        </div>
        
        <?php if($success_msg): ?>
        <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle mr-2"></i> <?php echo $success_msg; ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
        <?php endif; ?>
        <?php if($error_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error_msg; ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-xl-3 col-md-6 mb-4"><div class="summary-card"><div class="text-primary text-uppercase mb-1">Today's Purchase</div><div class="summary-number text-primary"><?php echo formatCurrency($today_purchase); ?></div><small><?php echo date('d-m-Y'); ?></small></div></div>
            <div class="col-xl-3 col-md-6 mb-4"><div class="summary-card"><div class="text-success text-uppercase mb-1">Monthly Purchase</div><div class="summary-number text-success"><?php echo formatCurrency($month_purchase); ?></div><small><?php echo date('F Y'); ?></small></div></div>
            <div class="col-xl-3 col-md-6 mb-4"><div class="summary-card"><div class="text-info text-uppercase mb-1">Selected Period</div><div class="summary-number text-info"><?php echo formatCurrency($total_purchase); ?></div><small><?php echo date('d-m-Y', strtotime($from_date)); ?> to <?php echo date('d-m-Y', strtotime($to_date)); ?></small></div></div>
            <div class="col-xl-3 col-md-6 mb-4"><div class="summary-card"><div class="text-warning text-uppercase mb-1">Outstanding Payables</div><div class="summary-number text-warning"><?php echo formatCurrency($total_remaining); ?></div><small><?php echo $total_count; ?> Invoices</small></div></div>
        </div>
        
        <div class="card form-card no-print">
            <div class="card-header-custom"><i class="fas fa-filter mr-2"></i> Filter Purchases</div>
            <div class="card-body">
                <form method="GET" action="" class="form-inline">
                    <div class="row w-100">
                        <div class="col-md-2"><input type="date" name="from_date" class="form-control w-100" value="<?php echo $from_date; ?>"></div>
                        <div class="col-md-2"><input type="date" name="to_date" class="form-control w-100" value="<?php echo $to_date; ?>"></div>
                        <div class="col-md-3"><select name="supplier_id" class="form-control w-100"><option value="0">All Suppliers</option><?php while($sup = mysqli_fetch_assoc($suppliers_result)): ?><option value="<?php echo $sup['id']; ?>" <?php echo ($filter_supplier == $sup['id']) ? 'selected' : ''; ?>><?php echo $sup['supplier_name']; ?></option><?php endwhile; ?></select></div>
                        <div class="col-md-2"><input type="text" name="invoice_no" class="form-control w-100" placeholder="Invoice No" value="<?php echo $filter_invoice; ?>"></div>
                        <div class="col-md-2"><select name="payment_type" class="form-control w-100"><option value="">All</option><option value="cash" <?php echo $filter_payment_type == 'cash' ? 'selected' : ''; ?>>Cash</option><option value="bank" <?php echo $filter_payment_type == 'bank' ? 'selected' : ''; ?>>Bank</option><option value="credit" <?php echo $filter_payment_type == 'credit' ? 'selected' : ''; ?>>Credit</option></select></div>
                        <div class="col-md-1"><button type="submit" class="btn btn-green w-100"><i class="fas fa-search"></i></button></div>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card form-card">
            <div class="card-header-custom"><i class="fas fa-list mr-2"></i> Purchase Invoices <span class="float-right">Total: <strong><?php echo formatCurrency($total_purchase); ?></strong> | Records: <strong id="totalCount">0</strong></span></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="purchasesTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Invoice No</th>
                                <th>Date</th>
                                <th>Supplier</th>
                                <th>Grand Total</th>
                                <th>Paid</th>
                                <th>Remaining</th>
                                <th>Payment Type</th>
                                <th>Reference No</th>
                                <th>Remarks</th>
                                <th>Status</th>
                                <th class="no-print">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($purchase = mysqli_fetch_assoc($purchases_result)): 
                                // Safely get values with defaults
                                $refund_status = isset($purchase['refund_status']) ? $purchase['refund_status'] : 'none';
                                $remaining_amount = isset($purchase['remaining_amount']) ? floatval($purchase['remaining_amount']) : 0;
                                $paid_amount = isset($purchase['paid_amount']) ? floatval($purchase['paid_amount']) : 0;
                                $grand_total = isset($purchase['grand_total']) ? floatval($purchase['grand_total']) : 0;
                                $payment_type = isset($purchase['payment_type']) ? $purchase['payment_type'] : 'credit';
                                
                                // Determine status
                                $status = '';
                                $status_class = '';
                                if($refund_status != 'none' && $refund_status !== null && $refund_status != '') {
                                    $status = 'Refunded (' . ucfirst($refund_status) . ')';
                                    $status_class = 'badge-refund';
                                } elseif($remaining_amount <= 0) {
                                    $status = 'Paid';
                                    $status_class = 'badge-paid';
                                } elseif($paid_amount > 0) {
                                    $status = 'Partial';
                                    $status_class = 'badge-partial';
                                } else {
                                    $status = 'Pending';
                                    $status_class = 'badge-pending';
                                }
                                
                                // Payment type badge
                                $payment_badge = '';
                                if($payment_type == 'cash') {
                                    $payment_badge = '<span class="badge badge-success">Cash</span>';
                                } elseif($payment_type == 'bank') {
                                    $payment_badge = '<span class="badge badge-info">Bank</span>';
                                } else {
                                    $payment_badge = '<span class="badge badge-secondary">Credit</span>';
                                }
                            ?>
                            <tr>
                                <td class="invoice-no"><?php echo isset($purchase['invoice_no']) ? $purchase['invoice_no'] : 'N/A'; ?></td>
                                <td class="text-nowrap"><?php echo isset($purchase['purchase_date']) ? date('d-m-Y', strtotime($purchase['purchase_date'])) : '-'; ?></td>
                                <td>
                                    <strong><?php echo isset($purchase['supplier_name']) ? htmlspecialchars($purchase['supplier_name']) : 'N/A'; ?></strong>
                                    <br><small class="text-muted"><?php echo isset($purchase['supplier_code']) ? $purchase['supplier_code'] : ''; ?></small>
                                </td>
                                <td class="text-right"><?php echo formatCurrency($grand_total); ?></td>
                                <td class="text-right text-success"><?php echo formatCurrency($paid_amount); ?></td>
                                <td class="text-right text-danger"><?php echo formatCurrency($remaining_amount); ?></td>
                                <td class="text-center"><?php echo $payment_badge; ?></td>
                                <td><?php echo !empty($purchase['reference_no']) ? htmlspecialchars($purchase['reference_no']) : '-'; ?></td>
                                <td><?php echo !empty($purchase['remarks']) ? htmlspecialchars($purchase['remarks']) : '-'; ?></td>
                                <td class="text-center"><span class="<?php echo $status_class; ?>"><?php echo $status; ?></span></td>
                                <td class="text-nowrap no-print">
                                    <div class="action-btns">
                                        <button class="btn btn-sm btn-info" onclick="viewInvoice(<?php echo $purchase['id']; ?>, '<?php echo isset($purchase['invoice_no']) ? htmlspecialchars($purchase['invoice_no'], ENT_QUOTES) : ''; ?>')" title="View Details"><i class="fas fa-eye"></i></button>
                                        <a href="add_purchase.php?edit_id=<?php echo $purchase['id']; ?>" class="btn btn-sm btn-warning" title="Edit"><i class="fas fa-edit"></i></a>
                                        <button class="btn btn-sm btn-primary" onclick="printInvoice('<?php echo isset($purchase['invoice_no']) ? htmlspecialchars($purchase['invoice_no'], ENT_QUOTES) : ''; ?>')" title="Print"><i class="fas fa-print"></i></button>
                                        <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $purchase['id']; ?>)" title="Delete"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($purchases_result) == 0): ?>
                            <td><td colspan="11" class="text-center">No purchase records found for the selected period</td></tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background: #f8f9fc; font-weight: bold;"><td colspan="3" class="text-right"><strong>Totals:</strong></td><td class="text-right"><strong><?php echo formatCurrency($total_purchase); ?></strong></td><td class="text-right"><strong><?php echo formatCurrency($total_paid); ?></strong></td><td class="text-right"><strong><?php echo formatCurrency($total_remaining); ?></strong></td><td colspan="5"></td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        
    </div>
    
    <footer class="sticky-footer bg-white">
        <div class="container my-auto"><div class="copyright text-center my-auto"><span>&copy; <?php echo date('Y'); ?> <?php echo $software_name; ?> - All Rights Reserved</span></div></div>
    </footer>
</div>

<!-- View Invoice Modal -->
<div class="modal fade" id="viewInvoiceModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #1e7e34, #0066cc); color: white;">
                <h5 class="modal-title"><i class="fas fa-receipt"></i> Purchase Invoice Details</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="invoiceDetails"><div class="text-center p-5"><div class="spinner-border text-success" role="status"></div><p class="mt-2">Loading invoice details...</p></div></div>
            <div class="modal-footer" id="invoiceFooter"><button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/js/sb-admin-2.min.js"></script>

<script>
var purchasesTable;

$(document).ready(function() {
    if($('#purchasesTable tbody tr').length > 0) {
        purchasesTable = $('#purchasesTable').DataTable({
            "order": [[0, "desc"]],
            "pageLength": 25,
            "language": {
                "search": "Search:",
                "lengthMenu": "Show _MENU_ entries",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                "infoEmpty": "Showing 0 to 0 of 0 entries",
                "infoFiltered": "(filtered from _MAX_ total entries)",
                "zeroRecords": "No purchases found"
            },
            "drawCallback": function() { $('#totalCount').text(purchasesTable.rows().count()); }
        });
        $('#totalCount').text(purchasesTable.rows().count());
    } else {
        $('#totalCount').text('0');
    }
});

function viewInvoice(id, invoiceNo) {
    $('#invoiceDetails').html('<div class="text-center p-5"><div class="spinner-border text-success" role="status"></div><p class="mt-2">Loading invoice details...</p></div>');
    $('#viewInvoiceModal').modal('show');
    $('#invoiceFooter').html(
        '<a href="add_purchase.php?edit_id=' + id + '" class="btn btn-warning"><i class="fas fa-edit"></i> Edit</a>' +
        '<button type="button" class="btn btn-primary" onclick="printInvoice(\'' + invoiceNo + '\')"><i class="fas fa-print"></i> Print</button>' +
        '<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>'
    );
    $.ajax({
        url: 'get_invoice_details.php',
        type: 'GET',
        data: { id: id },
        success: function(response) { $('#invoiceDetails').html(response); },
        error: function() { $('#invoiceDetails').html('<div class="alert alert-danger">Failed to load invoice details!</div>'); }
    });
}

function printInvoice(invoiceNo) {
    window.open('print_invoice.php?invoice_no=' + encodeURIComponent(invoiceNo), '_blank', 'width=900,height=600');
}

function confirmDelete(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This purchase invoice will be permanently deleted and all accounting entries will be reversed!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if(result.isConfirmed) window.location.href = 'view_invoice.php?delete_id=' + id;
    });
}

function formatCurrency(amount) {
    return '₨ ' + parseFloat(amount).toFixed(2);
}

$('#exportBtn').on('click', function() {
    var tableData = [];
    var headers = ['Invoice No', 'Date', 'Supplier', 'Grand Total', 'Paid', 'Remaining', 'Payment Type', 'Status'];
    tableData.push(headers);
    $('#purchasesTable tbody tr').each(function() {
        var row = [];
        $(this).find('td').each(function(index) { if(index < 8) row.push($(this).text().trim()); });
        tableData.push(row);
    });
    var csv = tableData.map(row => row.join(',')).join('\n');
    var blob = new Blob([csv], { type: 'text/csv' });
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = 'purchase_invoices_<?php echo date('Y-m-d'); ?>.csv';
    a.click();
    URL.revokeObjectURL(url);
    Swal.fire({ title: 'Success!', text: 'Export completed successfully!', icon: 'success', confirmButtonColor: '#1e7e34', timer: 2000 });
});
</script>

</body>
</html>

<?php mysqli_close($conn);
?>