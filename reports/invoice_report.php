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
$invoice_type = isset($_GET['invoice_type']) ? $_GET['invoice_type'] : 'all';
$invoice_search = isset($_GET['invoice_search']) ? $_GET['invoice_search'] : '';

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

// Get Sales Invoices
$sales_query = "SELECT 
                    'SALE' as invoice_type,
                    id,
                    invoice_no,
                    sale_date as invoice_date,
                    customer_id as party_id,
                    COALESCE((SELECT customer_name FROM customers WHERE id = sale_master.customer_id), 'Walk-In Customer') as party_name,
                    COALESCE((SELECT customer_code FROM customers WHERE id = sale_master.customer_id), 'CUS-0000') as party_code,
                    grand_total as total_amount,
                    payment_type
                FROM sale_master 
                WHERE sale_date BETWEEN '$from_date' AND '$to_date'";

// Get Purchase Invoices
$purchase_query = "SELECT 
                    'PURCHASE' as invoice_type,
                    id,
                    invoice_no,
                    purchase_date as invoice_date,
                    supplier_id as party_id,
                    COALESCE((SELECT supplier_name FROM suppliers WHERE id = purchase_master.supplier_id), 'N/A') as party_name,
                    COALESCE((SELECT supplier_code FROM suppliers WHERE id = purchase_master.supplier_id), 'SUP-0000') as party_code,
                    grand_total as total_amount,
                    payment_type
                FROM purchase_master 
                WHERE purchase_date BETWEEN '$from_date' AND '$to_date'";

// Combine queries based on filter
$final_query = "";
if($invoice_type == 'sale') {
    $final_query = $sales_query;
} elseif($invoice_type == 'purchase') {
    $final_query = $purchase_query;
} else {
    $final_query = $sales_query . " UNION ALL " . $purchase_query;
}

// Add invoice search if provided
if(!empty($invoice_search)) {
    $final_query .= " AND invoice_no LIKE '%$invoice_search%'";
}

$final_query .= " ORDER BY invoice_date DESC, id DESC";
$result = mysqli_query($conn, $final_query);

// Calculate summaries
$total_sales_amount = 0;
$total_purchase_amount = 0;
$total_invoices = 0;
$invoice_data = [];
$sales_count = 0;
$purchase_count = 0;

while($row = mysqli_fetch_assoc($result)) {
    $row['total_amount'] = floatval($row['total_amount']);
    $invoice_data[] = $row;
    $total_invoices++;
    
    if($row['invoice_type'] == 'SALE') {
        $total_sales_amount += $row['total_amount'];
        $sales_count++;
    } else {
        $total_purchase_amount += $row['total_amount'];
        $purchase_count++;
    }
}

$page_title = "Invoice Report";
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background-color: #f8f9fc;
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
        }
        
        /* Summary Cards */
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
        
        /* Topbar */
        .topbar {
            height: 60px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        /* Filter Card */
        .filter-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        /* Data Table Wrapper */
        .data-table-wrapper {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        /* Table Styles */
        .invoice-table {
            width: 100%;
            margin-bottom: 0;
            border-collapse: collapse;
        }
        
        .invoice-table thead tr {
            background: linear-gradient(135deg, #1e7e34 0%, #4e73df 100%);
        }
        
        .invoice-table thead th {
            color: white !important;
            font-weight: 700;
            padding: 14px 12px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
            text-align: left;
        }
        
        .invoice-table thead th.text-right {
            text-align: right;
        }
        
        .invoice-table thead th.text-center {
            text-align: center;
        }
        
        .invoice-table tbody tr {
            border-bottom: 1px solid #e3e6f0;
            transition: all 0.2s ease;
        }
        
        .invoice-table tbody tr:hover {
            background-color: #f8f9fc;
        }
        
        .invoice-table tbody td {
            padding: 14px 12px;
            vertical-align: middle;
            color: #2c3e50;
            font-size: 13px;
        }
        
        .invoice-table tbody td.text-right {
            text-align: right;
        }
        
        .invoice-table tbody td.text-center {
            text-align: center;
        }
        
        /* Invoice Badge */
        .invoice-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            font-family: monospace;
        }
        
        .invoice-badge.sale {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: white;
        }
        
        .invoice-badge.purchase {
            background: linear-gradient(135deg, #4e73df, #224abe);
            color: white;
        }
        
        /* Type Badge */
        .type-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .type-badge.sale {
            background: #e8f5e9;
            color: #1e7e34;
        }
        
        .type-badge.purchase {
            background: #e3f2fd;
            color: #4e73df;
        }
        
        /* Party Info */
        .party-name {
            font-weight: 600;
            color: #2c3e50;
            font-size: 13px;
            margin-bottom: 3px;
        }
        
        .party-code {
            font-size: 11px;
            color: #6c757d;
            font-family: monospace;
        }
        
        /* Payment Badge */
        .payment-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .payment-cash { background: #28a745; color: white; }
        .payment-bank { background: #17a2b8; color: white; }
        .payment-credit { background: #dc3545; color: white; }
        .payment-partial { background: #ffc107; color: #2c3e50; }
        
        /* Amount */
        .amount-text {
            font-weight: 700;
            color: #1e7e34;
            font-size: 13px;
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .status-completed {
            background: #e8f5e9;
            color: #1e7e34;
        }
        
        .status-recorded {
            background: #e3f2fd;
            color: #4e73df;
        }
        
        /* Action Button */
        .action-btn {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #36b9cc;
            color: white;
            border-radius: 8px;
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .action-btn:hover {
            background: #2c9faf;
            color: white;
            transform: translateY(-2px);
            text-decoration: none;
        }
        
        /* Table Footer */
        .table-footer {
            background: linear-gradient(135deg, #f8f9fc, #eef2f7);
            font-weight: 700;
        }
        
        .grand-total {
            background: linear-gradient(135deg, #1e7e34, #4e73df);
            color: white;
            padding: 6px 15px;
            border-radius: 20px;
            display: inline-block;
            font-weight: 700;
        }
        
        /* Chart Container */
        .chart-container {
            position: relative;
            height: 250px;
        }
        
        /* Header Stats */
        .stat-badge {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .stat-badge.sales {
            background: #e8f5e9;
            color: #1e7e34;
        }
        
        .stat-badge.purchases {
            background: #e3f2fd;
            color: #4e73df;
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
            .card-header, .table thead th {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        
        /* DataTables Custom */
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 20px;
            padding: 5px 15px;
        }
        .dataTables_wrapper .dataTables_length select {
            border-radius: 20px;
            padding: 5px 10px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: linear-gradient(135deg, #1e7e34, #4e73df) !important;
            color: white !important;
            border: none;
            border-radius: 20px;
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
                            <i class="fas fa-file-invoice"></i> Invoice Report
                        </h1>
                        <div class="no-print">
                            <button onclick="window.open('print_invoice_report.php?from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>&filter_type=<?php echo $filter_type; ?>&invoice_type=<?php echo $invoice_type; ?>&invoice_search=<?php echo urlencode($invoice_search); ?>', '_blank', 'width=1000,height=750')" class="btn btn-secondary btn-sm">
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
                        <div class="col-xl-4 col-md-6 mb-3">
                            <div class="stat-card border-left-success">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Invoices</div>
                                <div class="stat-number" style="color: #1e7e34;"><?php echo $total_invoices; ?></div>
                                <div class="mt-2">
                                    <span class="stat-badge sales"><i class="fas fa-shopping-cart"></i> Sales: <?php echo $sales_count; ?></span>
                                    <span class="stat-badge purchases ml-2"><i class="fas fa-truck"></i> Purchases: <?php echo $purchase_count; ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6 mb-3">
                            <div class="stat-card border-left-primary">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Sales Amount</div>
                                <div class="stat-number" style="color: #4e73df;">₨ <?php echo number_format($total_sales_amount, 2); ?></div>
                                <small class="text-muted">Total Sales Revenue</small>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6 mb-3">
                            <div class="stat-card border-left-warning">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Purchase Amount</div>
                                <div class="stat-number" style="color: #f6c23e;">₨ <?php echo number_format($total_purchase_amount, 2); ?></div>
                                <small class="text-muted">Total Purchase Cost</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts -->
                    <!--<div class="row mb-4 no-print">-->
                    <!--    <div class="col-md-6">-->
                    <!--        <div class="card shadow mb-4">-->
                    <!--            <div class="card-header py-3">-->
                    <!--                <h6 class="m-0 font-weight-bold" style="color: #1e7e34;">-->
                    <!--                    <i class="fas fa-chart-pie"></i> Invoice Distribution-->
                    <!--                </h6>-->
                    <!--            </div>-->
                    <!--            <div class="card-body">-->
                    <!--                <div class="chart-container">-->
                    <!--                    <canvas id="invoiceChart"></canvas>-->
                    <!--                </div>-->
                    <!--            </div>-->
                    <!--        </div>-->
                    <!--    </div>-->
                    <!--    <div class="col-md-6">-->
                    <!--        <div class="card shadow mb-4">-->
                    <!--            <div class="card-header py-3">-->
                    <!--                <h6 class="m-0 font-weight-bold" style="color: #1e7e34;">-->
                    <!--                    <i class="fas fa-chart-bar"></i> Amount Comparison-->
                    <!--                </h6>-->
                    <!--            </div>-->
                    <!--            <div class="card-body">-->
                    <!--                <div class="chart-container">-->
                    <!--                    <canvas id="amountChart"></canvas>-->
                    <!--                </div>-->
                    <!--            </div>-->
                    <!--        </div>-->
                    <!--    </div>-->
                    <!--</div>-->
                    
                    <!-- Filters -->
                    <div class="filter-card no-print">
                        <form method="GET" action="" class="form-inline justify-content-between flex-wrap">
                            <div class="btn-group mb-2 mb-md-0">
                                <a href="?filter_type=today&invoice_type=<?php echo $invoice_type; ?>" class="btn btn-sm <?php echo $filter_type == 'today' ? 'btn-success' : 'btn-outline-success'; ?>">Today</a>
                                <a href="?filter_type=yesterday&invoice_type=<?php echo $invoice_type; ?>" class="btn btn-sm <?php echo $filter_type == 'yesterday' ? 'btn-success' : 'btn-outline-success'; ?>">Yesterday</a>
                                <a href="?filter_type=week&invoice_type=<?php echo $invoice_type; ?>" class="btn btn-sm <?php echo $filter_type == 'week' ? 'btn-success' : 'btn-outline-success'; ?>">This Week</a>
                                <a href="?filter_type=month&invoice_type=<?php echo $invoice_type; ?>" class="btn btn-sm <?php echo $filter_type == 'month' ? 'btn-success' : 'btn-outline-success'; ?>">This Month</a>
                                <a href="?filter_type=year&invoice_type=<?php echo $invoice_type; ?>" class="btn btn-sm <?php echo $filter_type == 'year' ? 'btn-success' : 'btn-outline-success'; ?>">This Year</a>
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
                                <select name="invoice_type" class="form-control form-control-sm">
                                    <option value="all" <?php echo $invoice_type == 'all' ? 'selected' : ''; ?>>All Invoices</option>
                                    <option value="sale" <?php echo $invoice_type == 'sale' ? 'selected' : ''; ?>>Sales Invoices</option>
                                    <option value="purchase" <?php echo $invoice_type == 'purchase' ? 'selected' : ''; ?>>Purchase Invoices</option>
                                </select>
                            </div>
                            <div class="form-group mb-2 mb-md-0">
                                <input type="text" name="invoice_search" class="form-control form-control-sm" 
                                       placeholder="Search Invoice #" value="<?php echo htmlspecialchars($invoice_search); ?>">
                            </div>
                            <div>
                                <input type="hidden" name="filter_type" value="custom">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-search"></i> Apply
                                </button>
                                <a href="invoice_report.php" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-sync-alt"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Data Table -->
                    <div class="data-table-wrapper">
                        <div class="card-header py-3" style="background: linear-gradient(135deg, #e8f5e9, #e3f2fd); border-bottom: 2px solid #1e7e34;">
                            <div class="d-flex justify-content-between align-items-center flex-wrap">
                                <h6 class="m-0 font-weight-bold" style="color: #1e7e34;">
                                    <i class="fas fa-list"></i> Invoice Transactions 
                                    (<?php echo date('d-m-Y', strtotime($from_date)); ?> to <?php echo date('d-m-Y', strtotime($to_date)); ?>)
                                </h6>
                                <div class="header-stats">
                                    <span class="stat-badge sales">
                                        <i class="fas fa-shopping-cart"></i> Sales: <?php echo $sales_count; ?>
                                    </span>
                                    <span class="stat-badge purchases">
                                        <i class="fas fa-truck"></i> Purchases: <?php echo $purchase_count; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="invoice-table" id="invoiceTable">
                                    <thead>
                                        <tr>
                                            <th width="15%">INVOICE #</th>
                                            <th width="12%">DATE</th>
                                            <th width="10%">TYPE</th>
                                            <th width="22%">PARTY NAME</th>
                                            <th width="13%">PAYMENT TYPE</th>
                                            <th width="13%" class="text-right">TOTAL AMOUNT</th>
                                            <th width="10%">STATUS</th>
                                            <th width="5%" class="text-center no-print">ACTION</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(!empty($invoice_data)): ?>
                                            <?php foreach($invoice_data as $invoice): ?>
                                            <tr>
                                                <td>
                                                    <?php if($invoice['invoice_type'] == 'SALE'): ?>
                                                        <span class="invoice-badge sale">
                                                            <i class="fas fa-shopping-cart"></i> <?php echo htmlspecialchars($invoice['invoice_no']); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="invoice-badge purchase">
                                                            <i class="fas fa-truck"></i> <?php echo htmlspecialchars($invoice['invoice_no']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <i class="fas fa-calendar-alt text-muted mr-1"></i>
                                                    <?php echo date('d-m-Y', strtotime($invoice['invoice_date'])); ?>
                                                </td>
                                                <td>
                                                    <?php if($invoice['invoice_type'] == 'SALE'): ?>
                                                        <span class="type-badge sale">
                                                            <i class="fas fa-shopping-cart"></i> Sale
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="type-badge purchase">
                                                            <i class="fas fa-truck"></i> Purchase
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="party-name">
                                                        <i class="fas <?php echo $invoice['invoice_type'] == 'SALE' ? 'fa-user' : 'fa-building'; ?> text-success mr-1"></i>
                                                        <?php echo htmlspecialchars($invoice['party_name']); ?>
                                                    </div>
                                                    <div class="party-code">
                                                        <i class="fas fa-barcode text-muted mr-1"></i>
                                                        <?php echo htmlspecialchars($invoice['party_code']); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $payment_class = '';
                                                    $payment_icon = '';
                                                    $payment_text = '';
                                                    if($invoice['payment_type'] == 'cash') {
                                                        $payment_class = 'payment-cash';
                                                        $payment_icon = 'fa-money-bill-wave';
                                                        $payment_text = 'Cash';
                                                    } elseif($invoice['payment_type'] == 'bank') {
                                                        $payment_class = 'payment-bank';
                                                        $payment_icon = 'fa-university';
                                                        $payment_text = 'Bank';
                                                    } elseif($invoice['payment_type'] == 'credit') {
                                                        $payment_class = 'payment-credit';
                                                        $payment_icon = 'fa-credit-card';
                                                        $payment_text = 'Credit';
                                                    } else {
                                                        $payment_class = 'payment-partial';
                                                        $payment_icon = 'fa-chart-line';
                                                        $payment_text = 'Partial';
                                                    }
                                                    ?>
                                                    <span class="payment-badge <?php echo $payment_class; ?>">
                                                        <i class="fas <?php echo $payment_icon; ?>"></i>
                                                        <?php echo $payment_text; ?>
                                                    </span>
                                                </td>
                                                <td class="text-right">
                                                    <span class="amount-text">
                                                        <i class="fas fa-rupee-sign text-success mr-1"></i>
                                                        <?php echo number_format($invoice['total_amount'], 2); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if($invoice['invoice_type'] == 'SALE'): ?>
                                                        <span class="status-badge status-completed">
                                                            <i class="fas fa-check-circle"></i> Completed
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="status-badge status-recorded">
                                                            <i class="fas fa-check-circle"></i> Recorded
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center no-print">
                                                    <?php if($invoice['invoice_type'] == 'SALE'): ?>
                                                        <a href="../sales/view_invoice.php?id=<?php echo $invoice['id']; ?>" 
                                                           class="action-btn" target="_blank" title="View Invoice">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="../purchases/view_purchase.php?id=<?php echo $invoice['id']; ?>" 
                                                           class="action-btn" target="_blank" title="View Purchase">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center py-5">
                                                    <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                                    <h5>No invoices found</h5>
                                                    <p class="text-muted">No invoice transactions in selected period</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <?php if(!empty($invoice_data)): ?>
                                    <tfoot>
                                        <tr class="table-footer">
                                            <td colspan="5" class="text-right"><strong>GRAND TOTAL:</strong></td>
                                            <td class="text-right">
                                                <span class="grand-total">
                                                    ₨ <?php echo number_format($total_sales_amount + $total_purchase_amount, 2); ?>
                                                </span>
                                            </td>
                                            <td colspan="2"></td>
                                        </tr>
                                    </tfoot>
                                    <?php endif; ?>
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
            $('#invoiceTable').DataTable({
                "pageLength": 25,
                "order": [[1, 'desc']],
                "language": {
                    "search": "🔍 Search:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "emptyTable": "No invoice data available",
                    "zeroRecords": "No matching invoices found"
                },
                "columnDefs": [
                    { "orderable": false, "targets": [7] }
                ]
            });
            
            // Invoice Distribution Chart
            var salesCount = <?php echo $sales_count; ?>;
            var purchaseCount = <?php echo $purchase_count; ?>;
            
            if(salesCount > 0 || purchaseCount > 0) {
                var ctx1 = document.getElementById('invoiceChart').getContext('2d');
                new Chart(ctx1, {
                    type: 'doughnut',
                    data: {
                        labels: ['Sales Invoices', 'Purchase Invoices'],
                        datasets: [{
                            data: [salesCount, purchaseCount],
                            backgroundColor: ['#28a745', '#4e73df'],
                            borderWidth: 0,
                            hoverOffset: 10
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                            }
                        }
                    }
                });
            }
            
            // Amount Comparison Chart
            var salesAmount = <?php echo $total_sales_amount; ?>;
            var purchaseAmount = <?php echo $total_purchase_amount; ?>;
            
            if(salesAmount > 0 || purchaseAmount > 0) {
                var ctx2 = document.getElementById('amountChart').getContext('2d');
                new Chart(ctx2, {
                    type: 'bar',
                    data: {
                        labels: ['Sales', 'Purchase'],
                        datasets: [{
                            label: 'Amount (₨)',
                            data: [salesAmount, purchaseAmount],
                            backgroundColor: ['rgba(40, 167, 69, 0.7)', 'rgba(78, 115, 223, 0.7)'],
                            borderColor: ['#28a745', '#4e73df'],
                            borderWidth: 2,
                            borderRadius: 8,
                            barPercentage: 0.6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'top',
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
            }
            
            // Export to Excel
            $('#exportExcelBtn').click(function() {
                var table = document.getElementById('invoiceTable');
                var html = table.cloneNode(true);
                $(html).find('th:last-child, td:last-child').remove();
                var url = 'data:application/vnd.ms-excel,' + encodeURIComponent('<html><head><meta charset="UTF-8"></head><body>' + html.outerHTML + '</body></html>');
                var link = document.createElement('a');
                link.download = 'invoice_report_' + new Date().toISOString().slice(0,19) + '.xls';
                link.href = url;
                link.click();
                Swal.fire('Success!', 'Export completed!', 'success');
            });
            
            // Export to CSV
            $('#exportCSVBtn').click(function() {
                var csv = [];
                var rows = document.querySelectorAll('#invoiceTable tr');
                for (var i = 0; i < rows.length; i++) {
                    var row = [], cols = rows[i].querySelectorAll('td, th');
                    var colCount = (i === 0) ? cols.length - 1 : cols.length - 1;
                    for (var j = 0; j < colCount; j++) {
                        var text = cols[j].innerText.replace(/₨/g, '').trim();
                        row.push('"' + text + '"');
                    }
                    csv.push(row.join(','));
                }
                var blob = new Blob([csv.join('\n')], {type: 'text/csv;charset=utf-8;'});
                var link = document.createElement('a');
                link.download = 'invoice_report_' + new Date().toISOString().slice(0,19) + '.csv';
                link.href = URL.createObjectURL(blob);
                link.click();
                URL.revokeObjectURL(link.href);
                Swal.fire('Success!', 'Export completed!', 'success');
            });
        });
    </script>
</body>
</html>