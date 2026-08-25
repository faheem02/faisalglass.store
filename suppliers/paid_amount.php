<?php
/**
 * Paid Amount Page
 * Faysal Glass And Aluminium Centre
 * 
 * Display all supplier payment history with summary
 * Page: Paid Amount (Payment History)
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$page_title = "Payment History";
$success_msg = '';
$error_msg = '';

// Get date range filter
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
$supplier_filter = isset($_GET['supplier_id']) ? intval($_GET['supplier_id']) : 0;

// Fetch suppliers for dropdown
$suppliers_query = "SELECT id, supplier_name, supplier_code FROM suppliers WHERE status = 1 ORDER BY supplier_name";
$suppliers_result = mysqli_query($conn, $suppliers_query);

// Build payment query with filters
$payment_query = "SELECT p.*, s.supplier_name, s.supplier_code, s.mobile,
                  ba.bank_name, ba.account_title
                  FROM supplier_payments p
                  LEFT JOIN suppliers s ON p.supplier_id = s.id
                  LEFT JOIN bank_accounts ba ON p.bank_account_id = ba.id
                  WHERE p.payment_date BETWEEN '$from_date' AND '$to_date'";

if($supplier_filter > 0) {
    $payment_query .= " AND p.supplier_id = $supplier_filter";
}

$payment_query .= " ORDER BY p.payment_date DESC, p.id DESC";
$payments_result = mysqli_query($conn, $payment_query);

// Calculate summary totals
$summary_query = "SELECT 
                    SUM(CASE WHEN payment_method = 'cash' THEN amount ELSE 0 END) as total_cash,
                    SUM(CASE WHEN payment_method = 'bank' THEN amount ELSE 0 END) as total_bank,
                    SUM(amount) as total_payments
                  FROM supplier_payments p
                  WHERE p.payment_date BETWEEN '$from_date' AND '$to_date'";

if($supplier_filter > 0) {
    $summary_query .= " AND p.supplier_id = $supplier_filter";
}

$summary_result = mysqli_query($conn, $summary_query);
$summary = mysqli_fetch_assoc($summary_result);

$total_cash = floatval($summary['total_cash']);
$total_bank = floatval($summary['total_bank']);
$total_payments = floatval($summary['total_payments']);

// Get payment method distribution
$method_query = "SELECT payment_method, COUNT(*) as count, SUM(amount) as total
                 FROM supplier_payments p
                 WHERE p.payment_date BETWEEN '$from_date' AND '$to_date'";

if($supplier_filter > 0) {
    $method_query .= " AND p.supplier_id = $supplier_filter";
}

$method_query .= " GROUP BY payment_method";
$method_result = mysqli_query($conn, $method_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | <?php echo $software_name; ?></title>
    
    <!-- Bootstrap 4 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    
    <!-- SB Admin 2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        .btn-green {
            background-color: #1e7e34;
            border-color: #1e7e34;
            color: white;
        }
        .btn-green:hover {
            background-color: #155724;
            border-color: #155724;
            color: white;
        }
        .card-header-custom {
            background: linear-gradient(135deg, #1e7e34, #0066cc);
            color: white;
            border-radius: 10px 10px 0 0;
            padding: 15px 20px;
        }
        .form-card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        .summary-card {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            height: 100%;
            transition: transform 0.2s;
        }
        .summary-card:hover {
            transform: translateY(-3px);
        }
        .summary-number {
            font-size: 28px;
            font-weight: bold;
        }
        .filter-section {
            background: #f8f9fc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .table thead th {
            background-color: #1e7e34;
            color: white;
            font-weight: 600;
        }
        .badge-cash {
            background-color: #28a745;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
        }
        .badge-bank {
            background-color: #0066cc;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
        }
        .payment-code {
            font-family: monospace;
            font-size: 12px;
            color: #0066cc;
        }
        .print-btn, .export-btn {
            cursor: pointer;
        }
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
            .card-header-custom, .table thead th, .badge-cash, .badge-bank {
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
        
        <!-- Page Header -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-money-bill-wave text-success mr-2"></i> Supplier Payment History
            </h1>
            <div class="no-print">
                <a href="supplier_view.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Suppliers
                </a>
                <button type="button" class="btn btn-info ml-2 print-btn" onclick="window.open('print_payment_history.php?from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>&supplier_id=<?php echo $supplier_filter; ?>', '_blank', 'width=1000,height=750')">
                    <i class="fas fa-print mr-1"></i> Print
                </button>
                <button type="button" class="btn btn-green ml-2 export-btn" id="exportBtn">
                    <i class="fas fa-file-excel mr-1"></i> Export
                </button>
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="card form-card no-print">
            <div class="card-header-custom">
                <i class="fas fa-filter mr-2"></i> Filter Payments
            </div>
            <div class="card-body">
                <form method="GET" action="" id="filterForm">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><i class="fas fa-calendar-alt text-success mr-1"></i> From Date</label>
                                <input type="date" name="from_date" class="form-control" value="<?php echo $from_date; ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><i class="fas fa-calendar-alt text-success mr-1"></i> To Date</label>
                                <input type="date" name="to_date" class="form-control" value="<?php echo $to_date; ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><i class="fas fa-truck text-success mr-1"></i> Supplier</label>
                                <select name="supplier_id" class="form-control">
                                    <option value="0">All Suppliers</option>
                                    <?php while($sup = mysqli_fetch_assoc($suppliers_result)): ?>
                                        <option value="<?php echo $sup['id']; ?>" <?php echo ($supplier_filter == $sup['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($sup['supplier_name'] . ' (' . $sup['supplier_code'] . ')'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-green form-control">
                                    <i class="fas fa-search mr-1"></i> Filter
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Summary Cards -->
        <div class="row">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="summary-card">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Payments</div>
                    <div class="summary-number text-primary"><?php echo formatCurrency($total_payments); ?></div>
                    <small class="text-muted">Period: <?php echo date('d-m-Y', strtotime($from_date)); ?> to <?php echo date('d-m-Y', strtotime($to_date)); ?></small>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="summary-card">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Cash Payments</div>
                    <div class="summary-number text-success"><?php echo formatCurrency($total_cash); ?></div>
                    <small><i class="fas fa-money-bill-wave"></i> Cash Transactions</small>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="summary-card">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Bank Payments</div>
                    <div class="summary-number text-info"><?php echo formatCurrency($total_bank); ?></div>
                    <small><i class="fas fa-university"></i> Bank Transactions</small>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="summary-card">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Payment Methods</div>
                    <div class="summary-number">
                        <?php 
                        $cash_count = 0;
                        $bank_count = 0;
                        mysqli_data_seek($method_result, 0);
                        while($method = mysqli_fetch_assoc($method_result)) {
                            if($method['payment_method'] == 'cash') $cash_count = $method['count'];
                            if($method['payment_method'] == 'bank') $bank_count = $method['count'];
                        }
                        ?>
                        <?php echo $cash_count + $bank_count; ?>
                    </div>
                    <small><?php echo $cash_count; ?> Cash | <?php echo $bank_count; ?> Bank</small>
                </div>
            </div>
        </div>
        
        <!-- Payment Methods Distribution -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-chart-pie mr-2"></i> Payment Methods Distribution
            </div>
            <div class="card-body">
                <div class="row">
                    <?php 
                    mysqli_data_seek($method_result, 0);
                    while($method = mysqli_fetch_assoc($method_result)):
                        $percentage = ($total_payments > 0) ? ($method['total'] / $total_payments) * 100 : 0;
                    ?>
                    <div class="col-md-6">
                        <div class="text-center">
                            <h5>
                                <?php if($method['payment_method'] == 'cash'): ?>
                                    <i class="fas fa-money-bill-wave text-success"></i> Cash
                                <?php else: ?>
                                    <i class="fas fa-university text-info"></i> Bank
                                <?php endif; ?>
                            </h5>
                            <h4><?php echo formatCurrency($method['total']); ?></h4>
                            <div class="progress mb-3">
                                <div class="progress-bar <?php echo $method['payment_method'] == 'cash' ? 'bg-success' : 'bg-info'; ?>" 
                                     style="width: <?php echo $percentage; ?>%" 
                                     role="progressbar">
                                    <?php echo round($percentage, 1); ?>%
                                </div>
                            </div>
                            <small><?php echo $method['count']; ?> Transactions</small>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <?php if(mysqli_num_rows($method_result) == 0): ?>
                    <div class="col-md-12 text-center">
                        <p class="text-muted">No payment data available</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Payments List Table -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-list mr-2"></i> Payment Transactions
                <span class="float-right">
                    Total Records: <strong id="totalCount">0</strong>
                </span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="paymentsTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Supplier Code</th>
                                <th>Supplier Name</th>
                                <th>Payment Method</th>
                                <th>Reference No</th>
                                <th class="text-right">Amount</th>
                                <th>Remarks</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($payment = mysqli_fetch_assoc($payments_result)): ?>
                            <tr>
                                <td><?php echo $payment['id']; ?></td>
                                <td><?php echo date('d-m-Y', strtotime($payment['payment_date'])); ?></td>
                                <td class="payment-code"><?php echo $payment['supplier_code']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($payment['supplier_name']); ?></strong>
                                    <br><small class="text-muted"><i class="fas fa-phone"></i> <?php echo $payment['mobile']; ?></small>
                                </td>
                                <td>
                                    <?php if($payment['payment_method'] == 'cash'): ?>
                                        <span class="badge-cash"><i class="fas fa-money-bill-wave"></i> Cash</span>
                                    <?php else: ?>
                                        <span class="badge-bank"><i class="fas fa-university"></i> Bank</span>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($payment['bank_name']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($payment['reference_no']) ?: '-'; ?></td>
                                <td class="text-right text-danger font-weight-bold"><?php echo formatCurrency($payment['amount']); ?></td>
                                <td><?php echo htmlspecialchars($payment['remarks']) ?: '-'; ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info" onclick="viewPayment(<?php echo $payment['id']; ?>)" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="deletePayment(<?php echo $payment['id']; ?>, <?php echo $payment['supplier_id']; ?>, <?php echo $payment['amount']; ?>)" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($payments_result) == 0): ?>
                            <tr>
                                <td colspan="9" class="text-center">No payment records found for the selected period</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background: #f8f9fc; font-weight: bold;">
                                <td colspan="6" class="text-right"><strong>Total:</strong></td>
                                <td class="text-right text-danger"><strong><?php echo formatCurrency($total_payments); ?></strong></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
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

<!-- View Payment Modal -->
<div class="modal fade" id="viewPaymentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #1e7e34, #0066cc); color: white;">
                <h5 class="modal-title"><i class="fas fa-receipt"></i> Payment Details</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="paymentDetails">
                <!-- Payment details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printPaymentReceipt()">Print</button>
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
var paymentsTable;
var currentPaymentId = 0;

$(document).ready(function() {
    // Initialize DataTable
    paymentsTable = $('#paymentsTable').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 25,
        "language": {
            "search": "Search:",
            "lengthMenu": "Show _MENU_ entries",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries",
            "infoEmpty": "Showing 0 to 0 of 0 entries",
            "infoFiltered": "(filtered from _MAX_ total entries)",
            "zeroRecords": "No payment records found"
        },
        "drawCallback": function() {
            $('#totalCount').text(paymentsTable.rows().count());
        }
    });
    
    // Update total count
    $('#totalCount').text(paymentsTable.rows().count());
});

// View Payment Details
function viewPayment(id) {
    currentPaymentId = id;
    $.ajax({
        url: 'get_payment_details.php',
        type: 'GET',
        data: { id: id },
        success: function(response) {
            $('#paymentDetails').html(response);
            $('#viewPaymentModal').modal('show');
        },
        error: function() {
            Swal.fire({ title: 'Error!', text: 'Failed to load payment details!', icon: 'error', confirmButtonColor: '#1e7e34' });
        }
    });
}

function printPaymentReceipt() {
    if(currentPaymentId === 0) {
        Swal.fire({ title: 'Error!', text: 'No payment selected!', icon: 'error', confirmButtonColor: '#1e7e34' });
        return;
    }
    window.open('print_payment_receipt.php?id=' + currentPaymentId, '_blank', 'width=900,height=700');
}

// Delete Payment
function deletePayment(id, supplierId, amount) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This payment record will be permanently deleted and supplier balance will be updated!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'delete_payment.php?id=' + id + '&supplier_id=' + supplierId + '&amount=' + amount;
        }
    });
}

// Export to CSV
$('#exportBtn').on('click', function() {
    var tableData = [];
    var headers = ['ID', 'Date', 'Supplier Code', 'Supplier Name', 'Payment Method', 'Reference No', 'Amount', 'Remarks'];
    tableData.push(headers);
    
    $('#paymentsTable tbody tr').each(function() {
        var row = [];
        $(this).find('td').each(function(index) {
            if(index < 8) { // Only first 8 columns
                row.push($(this).text().trim());
            }
        });
        tableData.push(row);
    });
    
    // Create CSV
    var csv = tableData.map(row => row.join(',')).join('\n');
    var blob = new Blob([csv], { type: 'text/csv' });
    var url = window.URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = 'supplier_payments_<?php echo date('Y-m-d'); ?>.csv';
    a.click();
    window.URL.revokeObjectURL(url);
    
    Swal.fire({
        title: 'Success!',
        text: 'Export completed successfully!',
        icon: 'success',
        confirmButtonColor: '#1e7e34',
        timer: 2000
    });
});
</script>

</body>
</html>

<?php mysqli_close($conn); ?>