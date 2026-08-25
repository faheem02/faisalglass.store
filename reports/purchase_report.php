<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

// Get current user info
$user_id = $_SESSION['user_id'] ?? 0;
$user_name = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'staff';

// Date filters
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'month';
$supplier_filter = isset($_GET['supplier_id']) ? intval($_GET['supplier_id']) : 0;
$payment_type = isset($_GET['payment_type']) ? $_GET['payment_type'] : '';

// Apply quick filters
if($filter_type == 'today') {
    $from_date = date('Y-m-d');
    $to_date = date('Y-m-d');
} elseif($filter_type == 'yesterday') {
    $from_date = date('Y-m-d', strtotime('-1 day'));
    $to_date = date('Y-m-d', strtotime('-1 day'));
} elseif($filter_type == 'week') {
    $from_date = date('Y-m-d', strtotime('monday this week'));
    $to_date = date('Y-m-d');
} elseif($filter_type == 'month') {
    $from_date = date('Y-m-01');
    $to_date = date('Y-m-d');
} elseif($filter_type == 'year') {
    $from_date = date('Y-01-01');
    $to_date = date('Y-m-d');
}

// Build query
$where_conditions = ["purchase_date BETWEEN '$from_date' AND '$to_date'"];
if($supplier_filter > 0) {
    $where_conditions[] = "supplier_id = $supplier_filter";
}
if(!empty($payment_type)) {
    $where_conditions[] = "payment_type = '$payment_type'";
}
$where_clause = implode(" AND ", $where_conditions);

$query = "SELECT pm.*, s.supplier_name, s.supplier_code 
          FROM purchase_master pm 
          LEFT JOIN suppliers s ON pm.supplier_id = s.id 
          WHERE $where_clause 
          ORDER BY pm.purchase_date DESC, pm.id DESC";
$result = mysqli_query($conn, $query);

// Get suppliers for filter
$suppliers_query = "SELECT id, supplier_name, supplier_code FROM suppliers WHERE status = 1 ORDER BY supplier_name";
$suppliers_result = mysqli_query($conn, $suppliers_query);

// Calculate summaries
$total_purchases = 0;
$cash_purchases = 0;
$credit_purchases = 0;
$bank_purchases = 0;
$total_paid = 0;
$total_outstanding = 0;
$purchase_data = [];

while($row = mysqli_fetch_assoc($result)) {
    $row['grand_total'] = floatval($row['grand_total']);
    $row['paid_amount'] = floatval($row['paid_amount']);
    $row['remaining_amount'] = floatval($row['remaining_amount']);
    
    $purchase_data[] = $row;
    $total_purchases += $row['grand_total'];
    $total_paid += $row['paid_amount'];
    $total_outstanding += $row['remaining_amount'];
    
    if($row['payment_type'] == 'cash') {
        $cash_purchases += $row['grand_total'];
    } elseif($row['payment_type'] == 'bank') {
        $bank_purchases += $row['grand_total'];
    } elseif($row['payment_type'] == 'credit') {
        $credit_purchases += $row['grand_total'];
    }
}

// Monthly data for chart
$monthly_query = "SELECT 
                    DATE_FORMAT(purchase_date, '%Y-%m') as month,
                    SUM(grand_total) as total,
                    COUNT(*) as count
                  FROM purchase_master 
                  WHERE purchase_date BETWEEN '$from_date' AND '$to_date'
                  GROUP BY DATE_FORMAT(purchase_date, '%Y-%m')
                  ORDER BY month ASC";
$monthly_result = mysqli_query($conn, $monthly_query);
$monthly_data = [];
while($row = mysqli_fetch_assoc($monthly_result)) {
    $monthly_data[] = $row;
}

$page_title = "Purchase Report";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | <?php echo $software_name; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: transform 0.2s;
            height: 100%;
        }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-number { font-size: 28px; font-weight: bold; }
        .topbar {
            height: 60px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .filter-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        /* Table Styles */
        .data-table-wrapper {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .table-custom {
            margin-bottom: 0;
            width: 100%;
        }
        
        .table-custom thead th {
            background: linear-gradient(135deg, #1e7e34 0%, #4e73df 100%);
            color: white !important;
            font-weight: 600;
            padding: 14px 12px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
            white-space: nowrap;
        }
        
        .table-custom tbody td {
            padding: 12px;
            vertical-align: middle;
            color: #2c3e50;
            font-size: 13px;
            border-bottom: 1px solid #e3e6f0;
        }
        
        .table-custom tbody tr:hover {
            background-color: #f8f9fc;
            transition: all 0.2s ease;
        }
        
        /* Invoice Badge */
        .invoice-badge {
            background: linear-gradient(135deg, #4e73df, #224abe);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            font-family: monospace;
            display: inline-block;
        }
        
        /* Payment Type Badges */
        .payment-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        .payment-cash { background: #28a745; color: white; }
        .payment-bank { background: #17a2b8; color: white; }
        .payment-credit { background: #dc3545; color: white; }
        
        /* Status Badges */
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        .status-paid { background: #28a745; color: white; }
        .status-partial { background: #ffc107; color: #2c3e50; }
        .status-unpaid { background: #dc3545; color: white; }
        
        /* Amount Styles */
        .amount-positive { color: #28a745; font-weight: 600; }
        .amount-negative { color: #dc3545; font-weight: 600; }
        .amount-neutral { color: #6c757d; }
        
        /* Supplier Cell */
        .supplier-name {
            font-weight: 600;
            color: #1e7e34;
            font-size: 14px;
        }
        .supplier-code {
            font-size: 11px;
            color: #6c757d;
            font-family: monospace;
        }
        
        /* Amount Group */
        .amount-group {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 4px;
        }
        .amount-main {
            font-weight: 700;
            font-size: 14px;
        }
        .amount-sub {
            font-size: 11px;
            color: #6c757d;
        }
        
        /* Action Button */
        .btn-view {
            background: #36b9cc;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            transition: all 0.2s;
            border: none;
        }
        .btn-view:hover {
            background: #2c9faf;
            color: white;
            transform: translateY(-1px);
        }
        
        /* DataTable Custom */
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 20px;
            padding: 5px 10px;
        }
        .dataTables_wrapper .dataTables_filter input {
            width: 250px;
            padding: 6px 15px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: linear-gradient(135deg, #1e7e34, #4e73df) !important;
            color: white !important;
            border: none;
            border-radius: 20px;
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
            .stat-card { border: 1px solid #ddd; }
            .card-header, .table thead th {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <div class="topbar no-print">
                    <div class="welcome-text" style="color: #1e7e34;">
                        <i class="fas fa-store"></i> <?php echo $software_name; ?>
                    </div>
                    <div class="user-info">
                        <span style="color: #4e73df;">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user_name); ?> (<?php echo ucfirst($user_role); ?>)
                        </span>
                        <a href="../logout.php" style="color: #dc3545; text-decoration: none;">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
                
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4 mt-3">
                        <h1 class="h3 mb-0" style="color: #1e7e34;">
                            <i class="fas fa-shopping-cart"></i> Purchase Report
                        </h1>
                        <div class="no-print">
                            <button onclick="window.open('print_purchase_report.php?from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&filter_type=<?php echo urlencode($filter_type); ?>&supplier_id=<?php echo $supplier_filter; ?>&payment_type=<?php echo urlencode($payment_type); ?>', '_blank', 'width=1000,height=750')" class="btn btn-secondary btn-sm">
                                <i class="fas fa-print"></i> Print
                            </button>
                            <button id="exportExcelBtn" class="btn btn-success btn-sm">
                                <i class="fas fa-file-excel"></i> Excel
                            </button>
                            <button id="exportCSVBtn" class="btn btn-info btn-sm">
                                <i class="fas fa-file-csv"></i> CSV
                            </button>
                        </div>
                    </div>
                    
                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stat-card border-left-success">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Purchases</div>
                                <div class="stat-number" style="color: #1e7e34;">₨ <?php echo number_format($total_purchases, 2); ?></div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stat-card border-left-primary">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Cash Purchases</div>
                                <div class="stat-number" style="color: #4e73df;">₨ <?php echo number_format($cash_purchases, 2); ?></div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stat-card border-left-warning">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Credit Purchases</div>
                                <div class="stat-number" style="color: #f6c23e;">₨ <?php echo number_format($credit_purchases, 2); ?></div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stat-card border-left-danger">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Outstanding Payables</div>
                                <div class="stat-number" style="color: #e74a3b;">₨ <?php echo number_format($total_outstanding, 2); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Monthly Chart -->
                        <!--<div class="card shadow mb-4 no-print">-->
                        <!--    <div class="card-header py-3">-->
                        <!--        <h6 class="m-0 font-weight-bold" style="color: #1e7e34;">-->
                        <!--            <i class="fas fa-chart-bar"></i> Monthly Purchase Trend-->
                        <!--        </h6>-->
                        <!--    </div>-->
                        <!--    <div class="card-body">-->
                        <!--        <canvas id="purchaseChart" style="height: 300px;"></canvas>-->
                        <!--    </div>-->
                        <!--</div>-->
                    
                    <!-- Filters -->
                    <div class="filter-card no-print">
                        <form method="GET" action="" class="form-inline justify-content-between flex-wrap">
                            <div class="btn-group mb-2 mb-md-0">
                                <a href="?filter_type=today" class="btn btn-sm <?php echo $filter_type == 'today' ? 'btn-success' : 'btn-outline-success'; ?>">Today</a>
                                <a href="?filter_type=yesterday" class="btn btn-sm <?php echo $filter_type == 'yesterday' ? 'btn-success' : 'btn-outline-success'; ?>">Yesterday</a>
                                <a href="?filter_type=week" class="btn btn-sm <?php echo $filter_type == 'week' ? 'btn-success' : 'btn-outline-success'; ?>">This Week</a>
                                <a href="?filter_type=month" class="btn btn-sm <?php echo $filter_type == 'month' ? 'btn-success' : 'btn-outline-success'; ?>">This Month</a>
                                <a href="?filter_type=year" class="btn btn-sm <?php echo $filter_type == 'year' ? 'btn-success' : 'btn-outline-success'; ?>">This Year</a>
                            </div>
                            <div class="form-group mb-2 mb-md-0">
                                <label class="mr-2">From:</label>
                                <input type="date" name="from_date" class="form-control form-control-sm" value="<?php echo $from_date; ?>">
                            </div>
                            <div class="form-group mb-2 mb-md-0">
                                <label class="mr-2 ml-md-3">To:</label>
                                <input type="date" name="to_date" class="form-control form-control-sm" value="<?php echo $to_date; ?>">
                            </div>
                            <div class="form-group mb-2 mb-md-0">
                                <select name="supplier_id" class="form-control form-control-sm">
                                    <option value="0">-- All Suppliers --</option>
                                    <?php 
                                    mysqli_data_seek($suppliers_result, 0);
                                    while($s = mysqli_fetch_assoc($suppliers_result)): 
                                    ?>
                                    <option value="<?php echo $s['id']; ?>" <?php echo $supplier_filter == $s['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($s['supplier_name']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group mb-2 mb-md-0">
                                <select name="payment_type" class="form-control form-control-sm">
                                    <option value="">-- All Payment Types --</option>
                                    <option value="cash" <?php echo $payment_type == 'cash' ? 'selected' : ''; ?>>Cash</option>
                                    <option value="bank" <?php echo $payment_type == 'bank' ? 'selected' : ''; ?>>Bank</option>
                                    <option value="credit" <?php echo $payment_type == 'credit' ? 'selected' : ''; ?>>Credit</option>
                                </select>
                            </div>
                            <div>
                                <input type="hidden" name="filter_type" value="custom">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-search"></i> Apply
                                </button>
                                <a href="purchase_report.php" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-sync-alt"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Data Table -->
                    <div class="data-table-wrapper">
                        <div class="card-header py-3" style="background: linear-gradient(135deg, #e8f5e9, #e3f2fd); border-bottom: 2px solid #1e7e34;">
                            <h6 class="m-0 font-weight-bold" style="color: #1e7e34;">
                                <i class="fas fa-list"></i> Purchase Transactions 
                                (<?php echo date('d-m-Y', strtotime($from_date)); ?> to <?php echo date('d-m-Y', strtotime($to_date)); ?>)
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-custom" id="purchaseTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th width="12%">INVOICE #</th>
                                            <th width="10%">DATE</th>
                                            <th width="20%">SUPPLIER</th>
                                            <th width="12%">PAYMENT TYPE</th>
                                            <th width="12%" class="text-right">GRAND TOTAL</th>
                                            <th width="12%" class="text-right">PAID AMOUNT</th>
                                            <th width="12%" class="text-right">REMAINING</th>
                                            <th width="10%">STATUS</th>
                                            <th width="10%" class="no-print">ACTION</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(!empty($purchase_data)): ?>
                                            <?php foreach($purchase_data as $purchase): ?>
                                            <tr>
                                                <td>
                                                    <span class="invoice-badge"><?php echo htmlspecialchars($purchase['invoice_no']); ?></span>
                                                </td>
                                                <td>
                                                    <i class="fas fa-calendar-alt text-muted mr-1"></i>
                                                    <?php echo date('d-m-Y', strtotime($purchase['purchase_date'])); ?>
                                                </td>
                                                <td>
                                                    <div class="supplier-name">
                                                        <i class="fas fa-building text-success mr-1"></i>
                                                        <?php echo htmlspecialchars($purchase['supplier_name'] ?? 'N/A'); ?>
                                                    </div>
                                                    <div class="supplier-code">
                                                        <i class="fas fa-barcode text-muted mr-1"></i>
                                                        <?php echo htmlspecialchars($purchase['supplier_code'] ?? ''); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $payment_class = '';
                                                    $payment_icon = '';
                                                    if($purchase['payment_type'] == 'cash') {
                                                        $payment_class = 'payment-cash';
                                                        $payment_icon = 'fa-money-bill-wave';
                                                    } elseif($purchase['payment_type'] == 'bank') {
                                                        $payment_class = 'payment-bank';
                                                        $payment_icon = 'fa-university';
                                                    } else {
                                                        $payment_class = 'payment-credit';
                                                        $payment_icon = 'fa-credit-card';
                                                    }
                                                    ?>
                                                    <span class="payment-badge <?php echo $payment_class; ?>">
                                                        <i class="fas <?php echo $payment_icon; ?>"></i>
                                                        <?php echo ucfirst($purchase['payment_type']); ?>
                                                    </span>
                                                </td>
                                                <td class="text-right">
                                                    <div class="amount-group">
                                                        <span class="amount-main amount-positive">
                                                            <i class="fas fa-rupee-sign text-success mr-1"></i>
                                                            <?php echo number_format($purchase['grand_total'], 2); ?>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="text-right">
                                                    <div class="amount-group">
                                                        <span class="amount-main amount-positive">
                                                            <i class="fas fa-check-circle text-success mr-1"></i>
                                                            <?php echo number_format($purchase['paid_amount'], 2); ?>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="text-right">
                                                    <div class="amount-group">
                                                        <span class="amount-main <?php echo $purchase['remaining_amount'] > 0 ? 'amount-negative' : 'amount-neutral'; ?>">
                                                            <i class="fas fa-clock mr-1"></i>
                                                            <?php echo number_format($purchase['remaining_amount'], 2); ?>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if($purchase['remaining_amount'] == 0): ?>
                                                        <span class="status-badge status-paid">
                                                            <i class="fas fa-check-circle"></i> Paid
                                                        </span>
                                                    <?php elseif($purchase['paid_amount'] > 0): ?>
                                                        <span class="status-badge status-partial">
                                                            <i class="fas fa-chart-line"></i> Partial
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="status-badge status-unpaid">
                                                            <i class="fas fa-times-circle"></i> Unpaid
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="no-print">
                                                    <a href="../purchases/view_purchase.php?id=<?php echo $purchase['id']; ?>" 
                                                       class="btn btn-view" target="_blank">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="9" class="text-center py-5">
                                                    <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                                    <h5>No purchases found</h5>
                                                    <p class="text-muted">No purchase transactions in selected period</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr style="background: linear-gradient(135deg, #e8f5e9, #e3f2fd); font-weight: bold;">
                                            <td colspan="4" class="text-right"><strong>TOTAL:</strong></td>
                                            <td class="text-right amount-positive">
                                                <strong>₨ <?php echo number_format($total_purchases, 2); ?></strong>
                                            </td>
                                            <td class="text-right amount-positive">
                                                <strong>₨ <?php echo number_format($total_paid, 2); ?></strong>
                                            </td>
                                            <td class="text-right amount-negative">
                                                <strong>₨ <?php echo number_format($total_outstanding, 2); ?></strong>
                                            </td>
                                            <td colspan="2"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include('../includes/footer.php'); ?>
        </div>
    </div>
    
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/js/sb-admin-2.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#purchaseTable').DataTable({
                "pageLength": 25,
                "order": [[1, 'desc']],
                "language": {
                    "search": "🔍 Search:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "emptyTable": "No purchase data available",
                    "zeroRecords": "No matching purchases found"
                },
                "columnDefs": [
                    { "orderable": false, "targets": [8] }
                ]
            });
            
            // Purchase Chart
            var ctx = document.getElementById('purchaseChart').getContext('2d');
            var chartData = <?php echo json_encode($monthly_data); ?>;
            
            var labels = chartData.map(item => {
                let date = item.month.split('-');
                let months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                return months[parseInt(date[1]) - 1] + ' ' + date[0];
            });
            var values = chartData.map(item => item.total);
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Purchase Amount (₨)',
                        data: values,
                        backgroundColor: 'rgba(78, 115, 223, 0.5)',
                        borderColor: '#4e73df',
                        borderWidth: 2,
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return '₨ ' + context.raw.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₨ ' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
            
            // Export to Excel
            $('#exportExcelBtn').click(function() {
                var table = document.getElementById('purchaseTable');
                var html = table.cloneNode(true);
                $(html).find('th:last-child, td:last-child').remove();
                var url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html.outerHTML);
                var link = document.createElement('a');
                link.download = 'purchase_report_' + new Date().toISOString().slice(0,19) + '.xls';
                link.href = url;
                link.click();
                Swal.fire('Success!', 'Export completed!', 'success');
            });
            
            // Export to CSV
            $('#exportCSVBtn').click(function() {
                var csv = [];
                var rows = document.querySelectorAll('#purchaseTable tr');
                for (var i = 0; i < rows.length; i++) {
                    var row = [], cols = rows[i].querySelectorAll('td, th');
                    var colCount = (i === 0) ? cols.length - 1 : cols.length - 1;
                    for (var j = 0; j < colCount; j++) {
                        var text = cols[j].innerText.replace(/₨/g, '').trim();
                        row.push('"' + text + '"');
                    }
                    csv.push(row.join(','));
                }
                var blob = new Blob([csv.join('\n')], {type: 'text/csv'});
                var link = document.createElement('a');
                link.download = 'purchase_report_' + new Date().toISOString().slice(0,19) + '.csv';
                link.href = URL.createObjectURL(blob);
                link.click();
                URL.revokeObjectURL(link.href);
                Swal.fire('Success!', 'Export completed!', 'success');
            });
        });
    </script>
</body>
</html>