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
$customer_filter = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;

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

// Get customers for filter
$customers_query = "SELECT id, customer_name, customer_code FROM customers WHERE status = 1 ORDER BY customer_name";
$customers_result = mysqli_query($conn, $customers_query);

// Get customer data with ledger calculations
$customer_where = "";
if($customer_filter > 0) {
    $customer_where = " AND id = $customer_filter";
}

$customers_data_query = "SELECT id, customer_code, customer_name, company_name, mobile, email, 
                         opening_balance, balance_type, current_balance, status, created_at
                         FROM customers 
                         WHERE status = 1 $customer_where
                         ORDER BY customer_name ASC";
$customers_result_data = mysqli_query($conn, $customers_data_query);

// Calculate summaries
$total_customers = 0;
$total_receivable = 0;
$total_payable = 0;
$customer_data = [];

while($customer = mysqli_fetch_assoc($customers_result_data)) {
    $customer_id = $customer['id'];
    $current_balance = floatval($customer['current_balance']);
    
    // Get sales amount for the period
    $sales_query = "SELECT COALESCE(SUM(grand_total), 0) as sales_amount 
                    FROM sale_master 
                    WHERE customer_id = $customer_id 
                    AND sale_date BETWEEN '$from_date' AND '$to_date'";
    $sales_result = mysqli_query($conn, $sales_query);
    $sales_amount = floatval(mysqli_fetch_assoc($sales_result)['sales_amount']);
    
    // Get received amount for the period
    $received_query = "SELECT COALESCE(SUM(amount), 0) as received_amount 
                       FROM customer_receipts 
                       WHERE customer_id = $customer_id 
                       AND receipt_date BETWEEN '$from_date' AND '$to_date'";
    $received_result = mysqli_query($conn, $received_query);
    $received_amount = floatval(mysqli_fetch_assoc($received_result)['received_amount']);
    
    // Get opening balance (before from_date)
    $opening_balance = floatval($customer['opening_balance']);
    if($customer['balance_type'] == 'payable') {
        $opening_balance = -$opening_balance;
    }
    
    $customer_data[] = [
        'id' => $customer_id,
        'customer_code' => $customer['customer_code'],
        'customer_name' => $customer['customer_name'],
        'company_name' => $customer['company_name'],
        'mobile' => $customer['mobile'],
        'email' => $customer['email'],
        'opening_balance' => $opening_balance,
        'sales_amount' => $sales_amount,
        'received_amount' => $received_amount,
        'current_balance' => $current_balance,
        'status' => $customer['status'],
        'created_at' => $customer['created_at']
    ];
    
    $total_customers++;
    if($current_balance > 0) {
        $total_receivable += $current_balance;
    } elseif($current_balance < 0) {
        $total_payable += abs($current_balance);
    }
}

$page_title = "Customer Report";
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
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', 'Segoe UI', sans-serif;
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
        .customer-table {
            width: 100%;
            margin-bottom: 0;
            border-collapse: collapse;
        }
        
        .customer-table thead tr {
            background: linear-gradient(135deg, #1e7e34 0%, #4e73df 100%);
        }
        
        .customer-table thead th {
            color: white !important;
            font-weight: 700;
            padding: 14px 10px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
            text-align: left;
        }
        
        .customer-table thead th.text-right {
            text-align: right;
        }
        
        .customer-table thead th.text-center {
            text-align: center;
        }
        
        .customer-table tbody tr {
            border-bottom: 1px solid #e3e6f0;
            transition: all 0.2s ease;
        }
        
        .customer-table tbody tr:hover {
            background-color: #f8f9fc;
        }
        
        .customer-table tbody td {
            padding: 12px 10px;
            vertical-align: middle;
            color: #2c3e50;
            font-size: 12px;
        }
        
        .customer-table tbody td.text-right {
            text-align: right;
        }
        
        .customer-table tbody td.text-center {
            text-align: center;
        }
        
        /* Customer Code Badge */
        .customer-code {
            background: #e8f5e9;
            color: #1e7e34;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-family: monospace;
            font-weight: 600;
            display: inline-block;
        }
        
        .customer-name {
            font-weight: 600;
            color: #2c3e50;
            font-size: 13px;
            margin-bottom: 3px;
        }
        
        /* Balance Badges */
        .balance-receivable {
            background: #28a745;
            color: white;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        .balance-payable {
            background: #dc3545;
            color: white;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        .balance-zero {
            background: #6c757d;
            color: white;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        /* Amount */
        .amount-positive {
            color: #28a745;
            font-weight: 700;
        }
        
        .amount-negative {
            color: #dc3545;
            font-weight: 700;
        }
        
        /* Table Footer */
        .table-footer {
            background: linear-gradient(135deg, #f8f9fc, #eef2f7);
            font-weight: 700;
        }
        
        .total-value {
            background: linear-gradient(135deg, #1e7e34, #4e73df);
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            display: inline-block;
            font-weight: 700;
            font-size: 13px;
        }
        
        /* Chart Container */
        .chart-container {
            position: relative;
            height: 280px;
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
        
        @media print {
            .no-print { display: none !important; }
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
        
        @media (max-width: 768px) {
            .customer-table thead th { font-size: 9px; padding: 10px 5px; }
            .customer-table tbody td { padding: 10px 5px; font-size: 10px; }
        }
        
        /* Customer Table Specific Styles */
.customer-table td {
    vertical-align: middle;
    padding: 14px 10px;
}

.customer-code {
    background: #e8f5e9;
    color: #1e7e34;
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 11px;
    font-family: monospace;
    font-weight: 600;
    display: inline-block;
}

.customer-name {
    font-weight: 600;
    color: #2c3e50;
    font-size: 13px;
    margin-bottom: 4px;
}

/* Balance Badges */
.balance-receivable {
    background: linear-gradient(135deg, #28a745, #1e7e34);
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
}

.balance-payable {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
}

.balance-zero {
    background: #6c757d;
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
}

/* Amount Styles */
.amount-positive {
    color: #28a745;
    font-weight: 700;
}

/* Total Value Badge */
.total-value {
    background: linear-gradient(135deg, #1e7e34, #4e73df);
    color: white;
    padding: 6px 15px;
    border-radius: 25px;
    display: inline-block;
    font-weight: 700;
    font-size: 13px;
}

/* Action Buttons */
.action-btn {
    width: 32px;
    height: 32px;
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

.table-footer td {
    padding: 15px 10px !important;
    border-top: 2px solid #1e7e34;
}

/* Header Stats */
.header-stats .stat-badge {
    background: #e8f5e9;
    color: #1e7e34;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
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
                            <i class="fas fa-users"></i> Customer Report
                        </h1>
                        <div class="no-print">
                            <button onclick="window.print()" class="btn btn-secondary btn-sm">
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
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Customers</div>
                                <div class="stat-number" style="color: #1e7e34;"><?php echo $total_customers; ?></div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6 mb-3">
                            <div class="stat-card border-left-primary">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Receivable</div>
                                <div class="stat-number" style="color: #4e73df;">₨ <?php echo number_format($total_receivable, 2); ?></div>
                                <small class="text-muted">Customer owes company</small>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6 mb-3">
                            <div class="stat-card border-left-warning">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Payable</div>
                                <div class="stat-number" style="color: #f6c23e;">₨ <?php echo number_format($total_payable, 2); ?></div>
                                <small class="text-muted">Company owes customer</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Top Customers Chart -->
                    <!--<div class="row mb-4 no-print">-->
                    <!--    <div class="col-md-6">-->
                    <!--        <div class="card shadow mb-4">-->
                    <!--            <div class="card-header py-3">-->
                    <!--                <h6 class="m-0 font-weight-bold" style="color: #1e7e34;">-->
                    <!--                    <i class="fas fa-chart-bar"></i> Top 5 Customers by Balance-->
                    <!--                </h6>-->
                    <!--            </div>-->
                    <!--            <div class="card-body">-->
                    <!--                <div class="chart-container">-->
                    <!--                    <canvas id="topCustomersChart"></canvas>-->
                    <!--                </div>-->
                    <!--            </div>-->
                    <!--        </div>-->
                    <!--    </div>-->
                    <!--    <div class="col-md-6">-->
                    <!--        <div class="card shadow mb-4">-->
                    <!--            <div class="card-header py-3">-->
                    <!--                <h6 class="m-0 font-weight-bold" style="color: #1e7e34;">-->
                    <!--                    <i class="fas fa-chart-pie"></i> Balance Distribution-->
                    <!--                </h6>-->
                    <!--            </div>-->
                    <!--            <div class="card-body">-->
                    <!--                <div class="chart-container">-->
                    <!--                    <canvas id="balanceChart"></canvas>-->
                    <!--                </div>-->
                    <!--            </div>-->
                    <!--        </div>-->
                    <!--    </div>-->
                    <!--</div>-->
                    
                    <!-- Filters -->
                    <div class="filter-card no-print">
                        <form method="GET" action="" class="form-inline justify-content-between flex-wrap">
                            <div class="btn-group mb-2 mb-md-0">
                                <a href="?filter_type=today&customer_id=<?php echo $customer_filter; ?>" class="btn btn-sm <?php echo $filter_type == 'today' ? 'btn-success' : 'btn-outline-success'; ?>">Today</a>
                                <a href="?filter_type=yesterday&customer_id=<?php echo $customer_filter; ?>" class="btn btn-sm <?php echo $filter_type == 'yesterday' ? 'btn-success' : 'btn-outline-success'; ?>">Yesterday</a>
                                <a href="?filter_type=week&customer_id=<?php echo $customer_filter; ?>" class="btn btn-sm <?php echo $filter_type == 'week' ? 'btn-success' : 'btn-outline-success'; ?>">This Week</a>
                                <a href="?filter_type=month&customer_id=<?php echo $customer_filter; ?>" class="btn btn-sm <?php echo $filter_type == 'month' ? 'btn-success' : 'btn-outline-success'; ?>">This Month</a>
                                <a href="?filter_type=year&customer_id=<?php echo $customer_filter; ?>" class="btn btn-sm <?php echo $filter_type == 'year' ? 'btn-success' : 'btn-outline-success'; ?>">This Year</a>
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
                                <select name="customer_id" class="form-control form-control-sm">
                                    <option value="0">-- All Customers --</option>
                                    <?php 
                                    mysqli_data_seek($customers_result, 0);
                                    while($c = mysqli_fetch_assoc($customers_result)): 
                                    ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo $customer_filter == $c['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['customer_name']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div>
                                <input type="hidden" name="filter_type" value="custom">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-search"></i> Apply
                                </button>
                                <a href="customer_report.php" class="btn btn-secondary btn-sm">
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
                <i class="fas fa-list"></i> Customer Ledger Summary
                (<?php echo date('d-m-Y', strtotime($from_date)); ?> to <?php echo date('d-m-Y', strtotime($to_date)); ?>)
            </h6>
            <div class="header-stats">
                <span class="stat-badge">
                    <i class="fas fa-users"></i> Total: <?php echo $total_customers; ?>
                </span>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="customer-table" id="customerTable">
                <thead>
                    <tr>
                        <th width="10%">CUSTOMER CODE</th>
                        <th width="18%">CUSTOMER NAME</th>
                        <th width="12%">COMPANY</th>
                        <th width="10%">MOBILE</th>
                        <th width="10%" class="text-right">OPENING</th>
                        <th width="10%" class="text-right">SALES</th>
                        <th width="10%" class="text-right">RECEIVED</th>
                        <th width="12%" class="text-right">CURRENT BALANCE</th>
                        <th width="8%" class="text-center">ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($customer_data)): ?>
                        <?php foreach($customer_data as $customer): ?>
                            <tr>
                                <td>
                                    <span class="customer-code"><?php echo htmlspecialchars($customer['customer_code']); ?></span>
                                </td>
                                <td>
                                    <div class="customer-name"><?php echo htmlspecialchars($customer['customer_name']); ?></div>
                                    <?php if(!empty($customer['email'])): ?>
                                        <small class="text-muted"><?php echo htmlspecialchars($customer['email']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($customer['company_name'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars($customer['mobile']); ?></td>
                                <td class="text-right">₨ <?php echo number_format($customer['opening_balance'], 2); ?></td>
                                <td class="text-right amount-positive">₨ <?php echo number_format($customer['sales_amount'], 2); ?></td>
                                <td class="text-right">₨ <?php echo number_format($customer['received_amount'], 2); ?></td>
                                <td class="text-right">
                                    <?php if($customer['current_balance'] > 0): ?>
                                        <span class="balance-receivable">
                                            ₨ <?php echo number_format($customer['current_balance'], 2); ?> DR
                                        </span>
                                    <?php elseif($customer['current_balance'] < 0): ?>
                                        <span class="balance-payable">
                                            ₨ <?php echo number_format(abs($customer['current_balance']), 2); ?> CR
                                        </span>
                                    <?php else: ?>
                                        <span class="balance-zero">
                                            ₨ 0.00
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="../customers/customer_detail.php?id=<?php echo $customer['id']; ?>" 
                                       class="action-btn" target="_blank" title="View Customer">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="../customers/customer_ledger.php?id=<?php echo $customer['id']; ?>" 
                                       class="action-btn ml-1" target="_blank" title="View Ledger">
                                        <i class="fas fa-book"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <i class="fas fa-users fa-3x text-muted mb-3 d-block"></i>
                                <h5>No customers found</h5>
                                <p class="text-muted">No customer data available</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="table-footer">
                        <td colspan="4" class="text-right"><strong>TOTALS:</strong></td>
                        <td class="text-right">—</td>
                        <td class="text-right amount-positive">
                            <strong>₨ <?php echo number_format(array_sum(array_column($customer_data, 'sales_amount')), 2); ?></strong>
                        </td>
                        <td class="text-right">
                            <strong>₨ <?php echo number_format(array_sum(array_column($customer_data, 'received_amount')), 2); ?></strong>
                        </td>
                        <td class="text-right">
                            <span class="total-value">
                                ₨ <?php echo number_format($total_receivable - $total_payable, 2); ?>
                            </span>
                        </td>
                        <td class="text-center"></td>
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
            $('#customerTable').DataTable({
                "pageLength": 25,
                "order": [[7, 'desc']],
                "language": {
                    "search": "🔍 Search:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "emptyTable": "No customer data available",
                    "zeroRecords": "No matching customers found"
                },
                "columnDefs": [
                    { "orderable": false, "targets": [8] }
                ]
            });
            
            // Top Customers Chart
            var topCustomers = <?php 
                $top_customers = array_slice($customer_data, 0, 5);
                $customer_names = array_map(function($item) { 
                    return strlen($item['customer_name']) > 20 ? substr($item['customer_name'], 0, 17) . '...' : $item['customer_name']; 
                }, $top_customers);
                $customer_balances = array_map(function($item) { return abs($item['current_balance']); }, $top_customers);
                echo json_encode(['names' => $customer_names, 'balances' => $customer_balances]);
            ?>;
            
            if(topCustomers.names.length > 0 && topCustomers.balances.length > 0 && Math.max(...topCustomers.balances) > 0) {
                var ctx1 = document.getElementById('topCustomersChart').getContext('2d');
                new Chart(ctx1, {
                    type: 'bar',
                    data: {
                        labels: topCustomers.names,
                        datasets: [{
                            label: 'Balance (₨)',
                            data: topCustomers.balances,
                            backgroundColor: 'rgba(30, 126, 52, 0.7)',
                            borderColor: '#1e7e34',
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
            
            // Balance Distribution Chart
            var receivableCount = <?php echo count(array_filter($customer_data, function($item) { return $item['current_balance'] > 0; })); ?>;
            var payableCount = <?php echo count(array_filter($customer_data, function($item) { return $item['current_balance'] < 0; })); ?>;
            var zeroCount = <?php echo count(array_filter($customer_data, function($item) { return $item['current_balance'] == 0; })); ?>;
            
            if(receivableCount > 0 || payableCount > 0 || zeroCount > 0) {
                var ctx2 = document.getElementById('balanceChart').getContext('2d');
                new Chart(ctx2, {
                    type: 'doughnut',
                    data: {
                        labels: ['Receivable (Customer Owes)', 'Payable (We Owe Customer)', 'Settled'],
                        datasets: [{
                            data: [receivableCount, payableCount, zeroCount],
                            backgroundColor: ['#28a745', '#dc3545', '#6c757d'],
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
            
            // Export to Excel
            $('#exportExcelBtn').click(function() {
                var table = document.getElementById('customerTable');
                var html = table.cloneNode(true);
                var url = 'data:application/vnd.ms-excel,' + encodeURIComponent('<html><head><meta charset="UTF-8"></head><body>' + html.outerHTML + '</body></html>');
                var link = document.createElement('a');
                link.download = 'customer_report_' + new Date().toISOString().slice(0,19) + '.xls';
                link.href = url;
                link.click();
                Swal.fire('Success!', 'Export completed!', 'success');
            });
            
            // Export to CSV
            $('#exportCSVBtn').click(function() {
                var csv = [];
                var rows = document.querySelectorAll('#customerTable tr');
                for (var i = 0; i < rows.length; i++) {
                    var row = [], cols = rows[i].querySelectorAll('td, th');
                    for (var j = 0; j < cols.length; j++) {
                        var text = cols[j].innerText.replace(/₨/g, '').trim();
                        row.push('"' + text + '"');
                    }
                    csv.push(row.join(','));
                }
                var blob = new Blob([csv.join('\n')], {type: 'text/csv;charset=utf-8;'});
                var link = document.createElement('a');
                link.download = 'customer_report_' + new Date().toISOString().slice(0,19) + '.csv';
                link.href = URL.createObjectURL(blob);
                link.click();
                URL.revokeObjectURL(link.href);
                Swal.fire('Success!', 'Export completed!', 'success');
            });
        });
    </script>
</body>
</html>