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
$employee_filter = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;

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

// Get employees for filter
$employees_query = "SELECT id, employee_name, employee_code FROM employees WHERE status = 1 ORDER BY employee_name";
$employees_result = mysqli_query($conn, $employees_query);

// Get employee salary data
$employee_where = "";
if($employee_filter > 0) {
    $employee_where = " AND id = $employee_filter";
}

$employees_data_query = "SELECT id, employee_code, employee_name, designation, department, 
                         basic_salary, allowances, deductions, net_salary, 
                         opening_balance, balance_type, current_balance, status, joining_date
                         FROM employees 
                         WHERE status = 1 $employee_where
                         ORDER BY employee_name ASC";
$employees_result_data = mysqli_query($conn, $employees_data_query);

// Calculate summaries
$total_employees = 0;
$total_salary_expense = 0;
$total_paid_salary = 0;
$total_outstanding = 0;
$employee_data = [];

while($employee = mysqli_fetch_assoc($employees_result_data)) {
    $employee_id = $employee['id'];
    $net_salary = floatval($employee['net_salary']);
    $current_balance = floatval($employee['current_balance']);
    
    // Get paid salary for the period
    $paid_query = "SELECT COALESCE(SUM(amount), 0) as paid_amount 
                   FROM employee_payments 
                   WHERE employee_id = $employee_id 
                   AND payment_date BETWEEN '$from_date' AND '$to_date'";
    $paid_result = mysqli_query($conn, $paid_query);
    $paid_amount = floatval(mysqli_fetch_assoc($paid_result)['paid_amount']);
    
    // Get salary entries for the period
    $salary_query = "SELECT COALESCE(SUM(net_salary), 0) as salary_amount 
                     FROM employee_salary 
                     WHERE employee_id = $employee_id 
                     AND month_year BETWEEN DATE_FORMAT('$from_date', '%Y-%m') AND DATE_FORMAT('$to_date', '%Y-%m')";
    $salary_result = mysqli_query($conn, $salary_query);
    $salary_amount = floatval(mysqli_fetch_assoc($salary_result)['salary_amount']);
    
    // Calculate payable salary (net salary per month)
    $payable_salary = $net_salary;
    
    $employee_data[] = [
        'id' => $employee_id,
        'employee_code' => $employee['employee_code'],
        'employee_name' => $employee['employee_name'],
        'designation' => $employee['designation'],
        'department' => $employee['department'],
        'basic_salary' => floatval($employee['basic_salary']),
        'allowances' => floatval($employee['allowances']),
        'deductions' => floatval($employee['deductions']),
        'net_salary' => $net_salary,
        'payable_salary' => $payable_salary,
        'paid_salary' => $paid_amount,
        'current_balance' => $current_balance,
        'status' => $employee['status'],
        'joining_date' => $employee['joining_date']
    ];
    
    $total_employees++;
    $total_salary_expense += $payable_salary;
    $total_paid_salary += $paid_amount;
    $total_outstanding += $current_balance;
}

$page_title = "Salary Report";
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
        .salary-table {
            width: 100%;
            margin-bottom: 0;
            border-collapse: collapse;
        }
        
        .salary-table thead tr {
            background: linear-gradient(135deg, #1e7e34 0%, #4e73df 100%);
        }
        
        .salary-table thead th {
            color: white !important;
            font-weight: 700;
            padding: 14px 10px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
            text-align: left;
        }
        
        .salary-table thead th.text-right {
            text-align: right;
        }
        
        .salary-table thead th.text-center {
            text-align: center;
        }
        
        .salary-table tbody tr {
            border-bottom: 1px solid #e3e6f0;
            transition: all 0.2s ease;
        }
        
        .salary-table tbody tr:hover {
            background-color: #f8f9fc;
        }
        
        .salary-table tbody td {
            padding: 12px 10px;
            vertical-align: middle;
            color: #2c3e50;
            font-size: 12px;
        }
        
        .salary-table tbody td.text-right {
            text-align: right;
        }
        
        .salary-table tbody td.text-center {
            text-align: center;
        }
        
        /* Employee Code Badge */
        .employee-code {
            background: #e8f5e9;
            color: #1e7e34;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-family: monospace;
            font-weight: 600;
            display: inline-block;
        }
        
        .employee-name {
            font-weight: 600;
            color: #2c3e50;
            font-size: 13px;
            margin-bottom: 3px;
        }
        
        /* Balance Badges */
        .balance-payable {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        .balance-advance {
            background: linear-gradient(135deg, #28a745, #1e7e34);
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
        
        .amount-negative {
            color: #dc3545;
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
        
        /* Chart Container */
        .chart-container {
            position: relative;
            height: 280px;
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
            .salary-table thead th { font-size: 9px; padding: 10px 5px; }
            .salary-table tbody td { padding: 10px 5px; font-size: 10px; }
        }
        /* Salary Table Specific Styles */
.salary-table td {
    vertical-align: middle;
    padding: 14px 10px;
}

.employee-code {
    background: #e8f5e9;
    color: #1e7e34;
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 11px;
    font-family: monospace;
    font-weight: 600;
    display: inline-block;
}

.employee-name {
    font-weight: 600;
    color: #2c3e50;
    font-size: 13px;
    margin-bottom: 0;
}

/* Balance Badges */
.balance-payable {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
}

.balance-advance {
    background: linear-gradient(135deg, #28a745, #1e7e34);
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
                            <i class="fas fa-money-bill-wave"></i> Salary Report
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
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Employees</div>
                                <div class="stat-number" style="color: #1e7e34;"><?php echo $total_employees; ?></div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6 mb-3">
                            <div class="stat-card border-left-primary">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Salary Expense</div>
                                <div class="stat-number" style="color: #4e73df;">₨ <?php echo number_format($total_salary_expense, 2); ?></div>
                                <small class="text-muted">Monthly Payable</small>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6 mb-3">
                            <div class="stat-card border-left-warning">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Paid This Period</div>
                                <div class="stat-number" style="color: #f6c23e;">₨ <?php echo number_format($total_paid_salary, 2); ?></div>
                                <small class="text-muted"><?php echo date('M Y', strtotime($from_date)); ?> - <?php echo date('M Y', strtotime($to_date)); ?></small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Department Chart -->
                    <!--<div class="row mb-4 no-print">-->
                    <!--    <div class="col-md-6">-->
                    <!--        <div class="card shadow mb-4">-->
                    <!--            <div class="card-header py-3">-->
                    <!--                <h6 class="m-0 font-weight-bold" style="color: #1e7e34;">-->
                    <!--                    <i class="fas fa-chart-bar"></i> Salary by Department-->
                    <!--                </h6>-->
                    <!--            </div>-->
                    <!--            <div class="card-body">-->
                    <!--                <div class="chart-container">-->
                    <!--                    <canvas id="departmentChart"></canvas>-->
                    <!--                </div>-->
                    <!--            </div>-->
                    <!--        </div>-->
                    <!--    </div>-->
                    <!--    <div class="col-md-6">-->
                    <!--        <div class="card shadow mb-4">-->
                    <!--            <div class="card-header py-3">-->
                    <!--                <h6 class="m-0 font-weight-bold" style="color: #1e7e34;">-->
                    <!--                    <i class="fas fa-chart-pie"></i> Salary Distribution-->
                    <!--                </h6>-->
                    <!--            </div>-->
                    <!--            <div class="card-body">-->
                    <!--                <div class="chart-container">-->
                    <!--                    <canvas id="salaryChart"></canvas>-->
                    <!--                </div>-->
                    <!--            </div>-->
                    <!--        </div>-->
                    <!--    </div>-->
                    <!--</div>-->
                    
                    <!-- Filters -->
                    <div class="filter-card no-print">
                        <form method="GET" action="" class="form-inline justify-content-between flex-wrap">
                            <div class="btn-group mb-2 mb-md-0">
                                <a href="?filter_type=today&employee_id=<?php echo $employee_filter; ?>" class="btn btn-sm <?php echo $filter_type == 'today' ? 'btn-success' : 'btn-outline-success'; ?>">Today</a>
                                <a href="?filter_type=yesterday&employee_id=<?php echo $employee_filter; ?>" class="btn btn-sm <?php echo $filter_type == 'yesterday' ? 'btn-success' : 'btn-outline-success'; ?>">Yesterday</a>
                                <a href="?filter_type=week&employee_id=<?php echo $employee_filter; ?>" class="btn btn-sm <?php echo $filter_type == 'week' ? 'btn-success' : 'btn-outline-success'; ?>">This Week</a>
                                <a href="?filter_type=month&employee_id=<?php echo $employee_filter; ?>" class="btn btn-sm <?php echo $filter_type == 'month' ? 'btn-success' : 'btn-outline-success'; ?>">This Month</a>
                                <a href="?filter_type=year&employee_id=<?php echo $employee_filter; ?>" class="btn btn-sm <?php echo $filter_type == 'year' ? 'btn-success' : 'btn-outline-success'; ?>">This Year</a>
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
                                <select name="employee_id" class="form-control form-control-sm">
                                    <option value="0">-- All Employees --</option>
                                    <?php 
                                    mysqli_data_seek($employees_result, 0);
                                    while($e = mysqli_fetch_assoc($employees_result)): 
                                    ?>
                                    <option value="<?php echo $e['id']; ?>" <?php echo $employee_filter == $e['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($e['employee_name']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div>
                                <input type="hidden" name="filter_type" value="custom">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-search"></i> Apply
                                </button>
                                <a href="salary_report.php" class="btn btn-secondary btn-sm">
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
                <i class="fas fa-list"></i> Employee Salary Summary
                (<?php echo date('d-m-Y', strtotime($from_date)); ?> to <?php echo date('d-m-Y', strtotime($to_date)); ?>)
            </h6>
            <div class="header-stats">
                <span class="stat-badge">
                    <i class="fas fa-users"></i> Total: <?php echo $total_employees; ?>
                </span>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="salary-table" id="salaryTable">
                <thead>
                    <tr>
                        <th width="8%">EMP CODE</th>
                        <th width="15%">EMPLOYEE NAME</th>
                        <th width="10%">DESIGNATION</th>
                        <th width="10%">DEPARTMENT</th>
                        <th width="10%" class="text-right">BASIC</th>
                        <th width="8%" class="text-right">ALLOW.</th>
                        <th width="8%" class="text-right">DEDUCT.</th>
                        <th width="10%" class="text-right">NET SALARY</th>
                        <th width="10%" class="text-right">PAID</th>
                        <th width="11%" class="text-right">BALANCE</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($employee_data)): ?>
                        <?php foreach($employee_data as $employee): ?>
                            <tr>
                                <td>
                                    <span class="employee-code"><?php echo htmlspecialchars($employee['employee_code']); ?></span>
                                </td>
                                <td>
                                    <div class="employee-name"><?php echo htmlspecialchars($employee['employee_name']); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($employee['designation'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars($employee['department'] ?? '—'); ?></td>
                                <td class="text-right">₨ <?php echo number_format($employee['basic_salary'], 2); ?></td>
                                <td class="text-right">₨ <?php echo number_format($employee['allowances'], 2); ?></td>
                                <td class="text-right">₨ <?php echo number_format($employee['deductions'], 2); ?></td>
                                <td class="text-right amount-positive">₨ <?php echo number_format($employee['net_salary'], 2); ?></td>
                                <td class="text-right">₨ <?php echo number_format($employee['paid_salary'], 2); ?></td>
                                <td class="text-right">
                                    <?php if($employee['current_balance'] > 0): ?>
                                        <span class="balance-payable">
                                            ₨ <?php echo number_format($employee['current_balance'], 2); ?>
                                        </span>
                                    <?php elseif($employee['current_balance'] < 0): ?>
                                        <span class="balance-advance">
                                            ₨ <?php echo number_format(abs($employee['current_balance']), 2); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="balance-zero">
                                            ₨ 0.00
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <i class="fas fa-users fa-3x text-muted mb-3 d-block"></i>
                                <h5>No employees found</h5>
                                <p class="text-muted">No employee salary data available</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="table-footer">
                        <td colspan="4" class="text-right"><strong>TOTALS:</strong></td>
                        <td class="text-right"><strong>₨ <?php echo number_format(array_sum(array_column($employee_data, 'basic_salary')), 2); ?></strong></td>
                        <td class="text-right"><strong>₨ <?php echo number_format(array_sum(array_column($employee_data, 'allowances')), 2); ?></strong></td>
                        <td class="text-right"><strong>₨ <?php echo number_format(array_sum(array_column($employee_data, 'deductions')), 2); ?></strong></td>
                        <td class="text-right amount-positive"><strong>₨ <?php echo number_format($total_salary_expense, 2); ?></strong></td>
                        <td class="text-right"><strong>₨ <?php echo number_format($total_paid_salary, 2); ?></strong></td>
                        <td class="text-right">
                            <span class="total-value">
                                ₨ <?php echo number_format($total_outstanding, 2); ?>
                            </span>
                        </td>
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
            $('#salaryTable').DataTable({
                "pageLength": 25,
                "order": [[7, 'desc']],
                "language": {
                    "search": "🔍 Search:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "emptyTable": "No salary data available",
                    "zeroRecords": "No matching employees found"
                },
                "columnDefs": [
                    { "orderable": false, "targets": [9] }
                ]
            });
            
            // Department Chart
            var departments = {};
            <?php foreach($employee_data as $emp): ?>
                <?php 
                $dept = !empty($emp['department']) ? $emp['department'] : 'Other';
                ?>
                departments['<?php echo addslashes($dept); ?>'] = (departments['<?php echo addslashes($dept); ?>'] || 0) + <?php echo $emp['net_salary']; ?>;
            <?php endforeach; ?>
            
            var deptNames = Object.keys(departments);
            var deptValues = Object.values(departments);
            
            if(deptNames.length > 0 && deptValues.length > 0 && Math.max(...deptValues) > 0) {
                var ctx1 = document.getElementById('departmentChart').getContext('2d');
                new Chart(ctx1, {
                    type: 'bar',
                    data: {
                        labels: deptNames,
                        datasets: [{
                            label: 'Salary Amount (₨)',
                            data: deptValues,
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
            
            // Salary Distribution Chart
            var basicTotal = <?php echo array_sum(array_column($employee_data, 'basic_salary')); ?>;
            var allowancesTotal = <?php echo array_sum(array_column($employee_data, 'allowances')); ?>;
            var deductionsTotal = <?php echo array_sum(array_column($employee_data, 'deductions')); ?>;
            
            if(basicTotal > 0 || allowancesTotal > 0 || deductionsTotal > 0) {
                var ctx2 = document.getElementById('salaryChart').getContext('2d');
                new Chart(ctx2, {
                    type: 'doughnut',
                    data: {
                        labels: ['Basic Salary', 'Allowances', 'Deductions'],
                        datasets: [{
                            data: [basicTotal, allowancesTotal, deductionsTotal],
                            backgroundColor: ['#28a745', '#4e73df', '#dc3545'],
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
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.label + ': ₨ ' + context.raw.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
            }
            
            // Export to Excel
            $('#exportExcelBtn').click(function() {
                var table = document.getElementById('salaryTable');
                var html = table.cloneNode(true);
                var url = 'data:application/vnd.ms-excel,' + encodeURIComponent('<html><head><meta charset="UTF-8"></head><body>' + html.outerHTML + '</body></html>');
                var link = document.createElement('a');
                link.download = 'salary_report_' + new Date().toISOString().slice(0,19) + '.xls';
                link.href = url;
                link.click();
                Swal.fire('Success!', 'Export completed!', 'success');
            });
            
            // Export to CSV
            $('#exportCSVBtn').click(function() {
                var csv = [];
                var rows = document.querySelectorAll('#salaryTable tr');
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
                link.download = 'salary_report_' + new Date().toISOString().slice(0,19) + '.csv';
                link.href = URL.createObjectURL(blob);
                link.click();
                URL.revokeObjectURL(link.href);
                Swal.fire('Success!', 'Export completed!', 'success');
            });
        });
    </script>
</body>
</html>