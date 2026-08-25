<?php
/**
 * Supplier Ledger Page
 * Faysal Glass And Aluminium Centre
 * 
 * Display complete supplier ledger statement with running balance
 * Page: Supplier Ledger
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$page_title = "Supplier Ledger";

// Check if supplier ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: supplier_view.php");
    exit();
}

$supplier_id = intval($_GET['id']);

// Fetch supplier details
$query = "SELECT * FROM suppliers WHERE id = $supplier_id";
$result = mysqli_query($conn, $query);

if(!$result || mysqli_num_rows($result) == 0) {
    header("Location: supplier_view.php");
    exit();
}

$supplier = mysqli_fetch_assoc($result);

// Get date range filter
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');

// Fetch ledger entries with date range (JOIN purchase_master for invoice_no)
$ledger_query = "SELECT sl.*, pm.invoice_no
                 FROM supplier_ledger sl
                 LEFT JOIN purchase_master pm ON sl.reference_type = 'PURCHASE' AND sl.reference_id = pm.id
                 WHERE sl.supplier_id = $supplier_id 
                 AND sl.date BETWEEN '$from_date' AND '$to_date'
                 ORDER BY sl.date ASC, sl.id ASC";
$ledger_result = mysqli_query($conn, $ledger_query);

// Calculate summary
$summary_query = "SELECT 
                    SUM(debit) as total_debit,
                    SUM(credit) as total_credit
                  FROM supplier_ledger 
                  WHERE supplier_id = $supplier_id 
                  AND date BETWEEN '$from_date' AND '$to_date'";
$summary_result = mysqli_query($conn, $summary_query);
$summary = mysqli_fetch_assoc($summary_result);

$total_debit = floatval($summary['total_debit']);
$total_credit = floatval($summary['total_credit']);

// Get opening balance (before from_date)
$opening_query = "SELECT balance FROM supplier_ledger 
                  WHERE supplier_id = $supplier_id 
                  AND date < '$from_date' 
                  ORDER BY date DESC, id DESC LIMIT 1";
$opening_result = mysqli_query($conn, $opening_query);
$opening_balance = 0;
if($opening_result && mysqli_num_rows($opening_result) > 0) {
    $opening_data = mysqli_fetch_assoc($opening_result);
    $opening_balance = floatval($opening_data['balance']);
}

// If no previous balance, get opening from opening entry
if($opening_balance == 0) {
    $opening_entry_query = "SELECT credit, debit FROM supplier_ledger 
                            WHERE supplier_id = $supplier_id 
                            AND reference_type = 'OPENING'
                            ORDER BY id ASC LIMIT 1";
    $opening_entry_result = mysqli_query($conn, $opening_entry_query);
    if($opening_entry_result && mysqli_num_rows($opening_entry_result) > 0) {
        $opening_entry = mysqli_fetch_assoc($opening_entry_result);
        $opening_balance = floatval($opening_entry['credit']) - floatval($opening_entry['debit']);
    }
}

// Calculate closing balance
$closing_balance = $opening_balance + $total_credit - $total_debit;
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
        .supplier-info {
            background: #f8f9fc;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #1e7e34;
        }
        .info-label {
            font-size: 12px;
            text-transform: uppercase;
            color: #6c757d;
            font-weight: 600;
        }
        .info-value {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
        }
        .summary-card {
            text-align: center;
            padding: 15px;
            border-radius: 10px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            height: 100%;
        }
        .summary-number {
            font-size: 24px;
            font-weight: bold;
        }
        .balance-positive {
            color: #dc3545;
        }
        .balance-negative {
            color: #28a745;
        }
        .table thead th {
            background-color: #1e7e34;
            color: white;
            font-weight: 600;
        }
        .filter-section {
            background: #f8f9fc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
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
            .card-header-custom, .table thead th, .supplier-info {
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
                <i class="fas fa-book text-success mr-2"></i> Supplier Ledger
            </h1>
            <div class="no-print">
                <a href="supplier_detail.php?id=<?php echo $supplier_id; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Detail
                </a>
                <button type="button" class="btn btn-info ml-2 print-btn" onclick="window.open('print_supplier_ledger.php?id=<?php echo $supplier_id; ?>&from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>', '_blank', 'width=1000,height=750')">
                    <i class="fas fa-print mr-1"></i> Print
                </button>
                <button type="button" class="btn btn-green ml-2 export-btn" id="exportBtn">
                    <i class="fas fa-file-excel mr-1"></i> Export
                </button>
            </div>
        </div>
        
        <!-- Supplier Information -->
        <div class="supplier-info">
            <div class="row">
                <div class="col-md-3">
                    <div class="info-label"><i class="fas fa-barcode"></i> Supplier Code</div>
                    <div class="info-value"><?php echo $supplier['supplier_code']; ?></div>
                </div>
                <div class="col-md-3">
                    <div class="info-label"><i class="fas fa-truck"></i> Supplier Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($supplier['supplier_name']); ?></div>
                </div>
                <div class="col-md-3">
                    <div class="info-label"><i class="fas fa-phone"></i> Mobile</div>
                    <div class="info-value"><?php echo htmlspecialchars($supplier['mobile']); ?></div>
                </div>
                <div class="col-md-3">
                    <div class="info-label"><i class="fas fa-chart-line"></i> Current Balance</div>
                    <div class="info-value <?php echo $closing_balance > 0 ? 'balance-positive' : ($closing_balance < 0 ? 'balance-negative' : ''); ?>">
                        <?php 
                        if($closing_balance > 0) {
                            echo formatCurrency($closing_balance) . ' (Payable)';
                        } elseif($closing_balance < 0) {
                            echo formatCurrency(abs($closing_balance)) . ' (Receivable)';
                        } else {
                            echo formatCurrency(0);
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="card form-card no-print">
            <div class="card-header-custom">
                <i class="fas fa-filter mr-2"></i> Filter Ledger
            </div>
            <div class="card-body">
                <form method="GET" action="" id="filterForm">
                    <input type="hidden" name="id" value="<?php echo $supplier_id; ?>">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-calendar-alt text-success mr-1"></i> From Date</label>
                                <input type="date" name="from_date" class="form-control" value="<?php echo $from_date; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-calendar-alt text-success mr-1"></i> To Date</label>
                                <input type="date" name="to_date" class="form-control" value="<?php echo $to_date; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
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
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Opening Balance</div>
                    <div class="summary-number <?php echo $opening_balance > 0 ? 'balance-positive' : ($opening_balance < 0 ? 'balance-negative' : ''); ?>">
                        <?php echo formatCurrency(abs($opening_balance)); ?>
                        <br><small><?php echo $opening_balance > 0 ? '(Payable)' : ($opening_balance < 0 ? '(Receivable)' : '(Zero)'); ?></small>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="summary-card">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Credits (Payable)</div>
                    <div class="summary-number text-danger"><?php echo formatCurrency($total_credit); ?></div>
                    <small>Purchase & Opening</small>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="summary-card">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Debits (Payments)</div>
                    <div class="summary-number text-info"><?php echo formatCurrency($total_debit); ?></div>
                    <small>Payments Made</small>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="summary-card">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Closing Balance</div>
                    <div class="summary-number <?php echo $closing_balance > 0 ? 'balance-positive' : ($closing_balance < 0 ? 'balance-negative' : ''); ?>">
                        <?php echo formatCurrency(abs($closing_balance)); ?>
                        <br><small><?php echo $closing_balance > 0 ? '(Payable)' : ($closing_balance < 0 ? '(Receivable)' : '(Zero)'); ?></small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Ledger Table -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-list mr-2"></i> Ledger Statement
                <span class="float-right">
                    Period: <?php echo date('d-m-Y', strtotime($from_date)); ?> to <?php echo date('d-m-Y', strtotime($to_date)); ?>
                </span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="ledgerTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Reference Type</th>
                                <th>Invoice #</th>
                                <th>Description</th>
                                <th class="text-right">Debit (Payment)</th>
                                <th class="text-right">Credit (Purchase)</th>
                                <th class="text-right">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $running_balance = $opening_balance;
                            if(mysqli_num_rows($ledger_result) > 0): 
                                while($entry = mysqli_fetch_assoc($ledger_result)):
                                    $running_balance = $entry['balance'];
                            ?>
                            <tr>
                                <td><?php echo date('d-m-Y', strtotime($entry['date'])); ?></td>
                                <td>
                                    <?php 
                                    $badge_class = '';
                                    $type_label = '';
                                    switch($entry['reference_type']) {
                                        case 'OPENING':
                                            $badge_class = 'badge-info';
                                            $type_label = 'Opening';
                                            break;
                                        case 'PURCHASE':
                                            $badge_class = 'badge-primary';
                                            $type_label = 'Purchase';
                                            break;
                                        case 'PAYMENT':
                                            $badge_class = 'badge-success';
                                            $type_label = 'Payment';
                                            break;
                                        case 'ADJUSTMENT':
                                            $badge_class = 'badge-warning';
                                            $type_label = 'Adjustment';
                                            break;
                                        default:
                                            $badge_class = 'badge-secondary';
                                            $type_label = $entry['reference_type'];
                                    }
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo $type_label; ?></span>
                                </td>
                                <td>
                                    <?php if($entry['reference_type'] == 'PURCHASE' && !empty($entry['invoice_no'])): ?>
                                        <a href="../purchases/print_invoice.php?invoice_no=<?php echo urlencode($entry['invoice_no']); ?>" target="_blank" class="font-weight-bold">
                                            <?php echo htmlspecialchars($entry['invoice_no']); ?>
                                        </a>
                                    <?php elseif($entry['reference_type'] == 'PAYMENT'): ?>
                                        PAY-<?php echo str_pad($entry['reference_id'], 4, '0', STR_PAD_LEFT); ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($entry['description']); ?></td>
                                <td class="text-right text-success"><?php echo $entry['debit'] > 0 ? formatCurrency($entry['debit']) : '-'; ?></td>
                                <td class="text-right text-danger"><?php echo $entry['credit'] > 0 ? formatCurrency($entry['credit']) : '-'; ?></td>
                                <td class="text-right">
                                    <strong>
                                        <?php 
                                        if($running_balance > 0) {
                                            echo '<span class="text-danger">' . formatCurrency($running_balance) . ' (Payable)</span>';
                                        } elseif($running_balance < 0) {
                                            echo '<span class="text-success">' . formatCurrency(abs($running_balance)) . ' (Receivable)</span>';
                                        } else {
                                            echo formatCurrency(0);
                                        }
                                        ?>
                                    </strong>
                                </td>
                            </tr>
                            <?php 
                                endwhile;
                            else: 
                            ?>
                            <tr>
                                <td colspan="7" class="text-center">No ledger entries found for the selected period</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background: #f8f9fc; font-weight: bold;">
                                <td colspan="4" class="text-right"><strong>Totals:</strong></td>
                                <td class="text-right text-success"><strong><?php echo formatCurrency($total_debit); ?></strong></td>
                                <td class="text-right text-danger"><strong><?php echo formatCurrency($total_credit); ?></strong></td>
                                <td class="text-right">
                                    <strong>
                                        <?php 
                                        if($closing_balance > 0) {
                                            echo '<span class="text-danger">' . formatCurrency($closing_balance) . ' (Payable)</span>';
                                        } elseif($closing_balance < 0) {
                                            echo '<span class="text-success">' . formatCurrency(abs($closing_balance)) . ' (Receivable)</span>';
                                        } else {
                                            echo formatCurrency(0);
                                        }
                                        ?>
                                    </strong>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Balance Formula Explanation -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-calculator mr-2"></i> Balance Formula
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-4">
                        <h6>Opening Balance</h6>
                        <p class="small text-muted">Balance at start of period</p>
                    </div>
                    <div class="col-md-4">
                        <h6>+ Purchases (Credits)</h6>
                        <p class="small text-muted">Company owes supplier</p>
                    </div>
                    <div class="col-md-4">
                        <h6>- Payments (Debits)</h6>
                        <p class="small text-muted">Payments made to supplier</p>
                    </div>
                </div>
                <div class="row text-center mt-3">
                    <div class="col-md-12">
                        <h5 class="text-success">= Closing Balance</h5>
                        <p class="small">
                            <?php if($closing_balance > 0): ?>
                                <span class="text-danger">Positive Balance: Company owes supplier <?php echo formatCurrency($closing_balance); ?></span>
                            <?php elseif($closing_balance < 0): ?>
                                <span class="text-success">Negative Balance: Supplier owes company <?php echo formatCurrency(abs($closing_balance)); ?></span>
                            <?php else: ?>
                                <span>Zero Balance: No outstanding amount</span>
                            <?php endif; ?>
                        </p>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/js/sb-admin-2.min.js"></script>

<script>
$(document).ready(function() {
    $('#ledgerTable').DataTable({
        "order": [[0, "asc"]],
        "pageLength": 25,
        "language": {
            "search": "Search:",
            "lengthMenu": "Show _MENU_ entries",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries",
            "infoEmpty": "Showing 0 to 0 of 0 entries",
            "infoFiltered": "(filtered from _MAX_ total entries)",
            "zeroRecords": "No entries found"
        }
    });
});

// Export to CSV
$('#exportBtn').on('click', function() {
    var tableData = [];
    var headers = ['Date', 'Reference Type', 'Invoice #', 'Description', 'Debit (Payment)', 'Credit (Purchase)', 'Balance'];
    tableData.push(headers);
    
    $('#ledgerTable tbody tr').each(function() {
        var row = [];
        $(this).find('td').each(function() {
            row.push($(this).text().trim());
        });
        tableData.push(row);
    });
    
    // Create CSV
    var csv = tableData.map(row => row.join(',')).join('\n');
    var blob = new Blob([csv], { type: 'text/csv' });
    var url = window.URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = 'supplier_ledger_<?php echo $supplier['supplier_code']; ?>.csv';
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