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

// Apply quick filters
if($filter_type == 'today') {
    $from_date = date('Y-m-d');
    $to_date = date('Y-m-d');
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

// ============================================
// PROFIT CALCULATION (Sale Price - Purchase Price)
// ============================================

// Get all sold products with their sale price and purchase price
$profit_query = "SELECT 
                    sd.product_id,
                    p.product_name,
                    p.product_code,
                    p.purchase_price,
                    SUM(sd.quantity) as total_quantity_sold,
                    AVG(sd.rate) as avg_sale_price,
                    SUM(sd.amount) as total_sale_amount
                 FROM sale_details sd
                 LEFT JOIN sale_master sm ON sd.sale_id = sm.id
                 LEFT JOIN products p ON sd.product_id = p.id
                 WHERE sm.sale_date BETWEEN '$from_date' AND '$to_date'
                 AND sm.status = 1
                 GROUP BY sd.product_id, p.product_name, p.product_code, p.purchase_price";

$profit_result = mysqli_query($conn, $profit_query);

$total_sales_revenue = 0;
$total_cost_of_goods = 0;
$total_profit = 0;
$profit_details = [];

if($profit_result && mysqli_num_rows($profit_result) > 0) {
    while($row = mysqli_fetch_assoc($profit_result)) {
        $quantity = floatval($row['total_quantity_sold']);
        $sale_price = floatval($row['avg_sale_price']);
        $purchase_price = floatval($row['purchase_price']);
        $sale_amount = floatval($row['total_sale_amount']);
        
        // Calculate cost of goods sold for this product
        $cost_of_goods = $quantity * $purchase_price;
        $profit_per_product = $sale_amount - $cost_of_goods;
        
        $profit_details[] = [
            'product_id' => $row['product_id'],
            'product_code' => $row['product_code'],
            'product_name' => $row['product_name'],
            'quantity' => $quantity,
            'sale_price' => $sale_price,
            'purchase_price' => $purchase_price,
            'sale_amount' => $sale_amount,
            'cost_of_goods' => $cost_of_goods,
            'profit' => $profit_per_product
        ];
        
        $total_sales_revenue += $sale_amount;
        $total_cost_of_goods += $cost_of_goods;
        $total_profit += $profit_per_product;
    }
}

// ============================================
// OTHER INCOME
// ============================================
$other_income_query = "SELECT COALESCE(SUM(debit), 0) as other_income 
                       FROM cash_book 
                       WHERE reference_type = 'ADJUSTMENT' 
                       AND debit > 0
                       AND date BETWEEN '$from_date' AND '$to_date'";
$other_income_result = mysqli_query($conn, $other_income_query);
if($other_income_result && mysqli_num_rows($other_income_result) > 0) {
    $other_income_row = mysqli_fetch_assoc($other_income_result);
    $other_income = floatval($other_income_row['other_income']);
} else {
    $other_income = 0;
}

$total_income = $total_sales_revenue + $other_income;

// ============================================
// EXPENSES SECTION
// ============================================

// 1. Salary Expense
$salary_expense_query = "SELECT COALESCE(SUM(credit), 0) as salary_expense 
                         FROM cash_book 
                         WHERE reference_type = 'SALARY' 
                         AND credit > 0
                         AND date BETWEEN '$from_date' AND '$to_date'";
$salary_expense_result = mysqli_query($conn, $salary_expense_query);
if($salary_expense_result && mysqli_num_rows($salary_expense_result) > 0) {
    $salary_row = mysqli_fetch_assoc($salary_expense_result);
    $salary_expense = floatval($salary_row['salary_expense']);
} else {
    $salary_expense = 0;
}

// Employee salary payments
$employee_salary_query = "SELECT COALESCE(SUM(amount), 0) as employee_salary 
                          FROM employee_payments 
                          WHERE payment_date BETWEEN '$from_date' AND '$to_date'";
$employee_salary_result = mysqli_query($conn, $employee_salary_query);
if($employee_salary_result && mysqli_num_rows($employee_salary_result) > 0) {
    $emp_salary_row = mysqli_fetch_assoc($employee_salary_result);
    $employee_salary = floatval($emp_salary_row['employee_salary']);
} else {
    $employee_salary = 0;
}

$total_salary_expense = $salary_expense + $employee_salary;

// 2. General Expenses
$expenses_query = "SELECT COALESCE(SUM(amount), 0) as total_expenses 
                   FROM expenses 
                   WHERE expense_date BETWEEN '$from_date' AND '$to_date'";
$expenses_result = mysqli_query($conn, $expenses_query);
if($expenses_result && mysqli_num_rows($expenses_result) > 0) {
    $expenses_row = mysqli_fetch_assoc($expenses_result);
    $total_expenses = floatval($expenses_row['total_expenses']);
} else {
    $total_expenses = 0;
}

// 3. Other Expenses
$other_expenses_query = "SELECT COALESCE(SUM(credit), 0) as other_expenses 
                         FROM cash_book 
                         WHERE reference_type NOT IN ('SALARY', 'EXPENSE', 'PURCHASE', 'SALE', 'CUSTOMER_RECEIPT', 'OPENING')
                         AND credit > 0
                         AND date BETWEEN '$from_date' AND '$to_date'";
$other_expenses_result = mysqli_query($conn, $other_expenses_query);
if($other_expenses_result && mysqli_num_rows($other_expenses_result) > 0) {
    $other_row = mysqli_fetch_assoc($other_expenses_result);
    $other_expenses = floatval($other_row['other_expenses']);
} else {
    $other_expenses = 0;
}

$total_expenses_amount = $total_salary_expense + $total_expenses + $other_expenses;

// ============================================
// NET PROFIT
// ============================================
$gross_profit = $total_profit;
$net_profit = $gross_profit + $other_income - $total_expenses_amount;

// ============================================
// MONTHLY DATA FOR CHART
// ============================================
$monthly_profit_query = "SELECT 
                            DATE_FORMAT(sm.sale_date, '%Y-%m') as month,
                            COALESCE(SUM(sd.amount), 0) as revenue,
                            COALESCE(SUM(sd.quantity * p.purchase_price), 0) as cost
                         FROM sale_details sd
                         LEFT JOIN sale_master sm ON sd.sale_id = sm.id
                         LEFT JOIN products p ON sd.product_id = p.id
                         WHERE sm.sale_date BETWEEN '$from_date' AND '$to_date'
                         AND sm.status = 1
                         GROUP BY DATE_FORMAT(sm.sale_date, '%Y-%m')
                         ORDER BY month ASC";
$monthly_profit_result = mysqli_query($conn, $monthly_profit_query);
$monthly_data = [];
if($monthly_profit_result && mysqli_num_rows($monthly_profit_result) > 0) {
    while($row = mysqli_fetch_assoc($monthly_profit_result)) {
        $monthly_data[] = $row;
    }
}

$page_title = "Profit & Loss Statement";
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
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .filter-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .pl-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        
        .pl-header {
            background: linear-gradient(135deg, #1e7e34 0%, #4e73df 100%);
            color: white;
            padding: 15px 20px;
            font-weight: 700;
        }
        
        .pl-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 20px;
            border-bottom: 1px solid #e3e6f0;
        }
        
        .pl-row:hover {
            background-color: #f8f9fc;
        }
        
        .pl-label {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .pl-amount {
            font-weight: 700;
            color: #1e7e34;
        }
        
        .pl-amount-negative {
            color: #dc3545;
        }
        
        .pl-total {
            background: linear-gradient(135deg, #e8f5e9, #e3f2fd);
            font-weight: 800;
            padding: 15px 20px;
            border-top: 2px solid #1e7e34;
        }
        
        .profit-positive {
            color: #28a745;
        }
        
        .profit-negative {
            color: #dc3545;
        }
        
        .chart-container {
            position: relative;
            height: 350px;
        }
        
        .product-table {
            width: 100%;
            margin-bottom: 0;
            border-collapse: collapse;
        }
        
        .product-table thead tr {
            background: linear-gradient(135deg, #1e7e34 0%, #4e73df 100%);
        }
        
        .product-table thead th {
            color: white !important;
            font-weight: 700;
            padding: 12px 10px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
        }
        
        .product-table tbody td {
            padding: 10px;
            vertical-align: middle;
            border-bottom: 1px solid #e3e6f0;
        }
        
        .product-code {
            background: #e8f5e9;
            color: #1e7e34;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-family: monospace;
            font-weight: 600;
            display: inline-block;
        }
        
        @media print {
            .no-print { display: none !important; }
        }
        
        @media (max-width: 768px) {
            .pl-row {
                flex-direction: column;
                text-align: center;
            }
            .pl-amount {
                margin-top: 5px;
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
                            <i class="fas fa-chart-line"></i> Profit & Loss Statement
                        </h1>
                        <div class="no-print">
                            <button onclick="window.print()" class="btn btn-secondary btn-sm">
                                <i class="fas fa-print"></i> Print
                            </button>
                            <button id="exportExcelBtn" class="btn btn-success btn-sm">
                                <i class="fas fa-file-excel"></i> Export
                            </button>
                        </div>
                    </div>
                    
                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stat-card border-left-success">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Revenue</div>
                                <div class="stat-number" style="color: #1e7e34;">₨ <?php echo number_format($total_sales_revenue, 2); ?></div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stat-card border-left-primary">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Cost of Goods Sold</div>
                                <div class="stat-number" style="color: #4e73df;">₨ <?php echo number_format($total_cost_of_goods, 2); ?></div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stat-card border-left-warning">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Expenses</div>
                                <div class="stat-number" style="color: #f6c23e;">₨ <?php echo number_format($total_expenses_amount, 2); ?></div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stat-card border-left-info">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Net Profit/Loss</div>
                                <div class="stat-number <?php echo $net_profit >= 0 ? 'profit-positive' : 'profit-negative'; ?>">
                                    ₨ <?php echo number_format(abs($net_profit), 2); ?>
                                    <?php echo $net_profit >= 0 ? 'Profit' : 'Loss'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Profit Formula Explanation -->
                    <div class="alert alert-info no-print">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Profit Calculation Formula:</strong> 
                        Profit = (Sale Price - Purchase Price) × Quantity Sold
                        <br>
                        <small>Total Profit: ₨ <?php echo number_format($total_profit, 2); ?> from <?php echo count($profit_details); ?> product(s)</small>
                    </div>
                    
                    <!-- Monthly Profit Chart -->
                    <div class="card shadow mb-4 no-print">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold" style="color: #1e7e34;">
                                <i class="fas fa-chart-line"></i> Monthly Revenue vs Cost
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="profitChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filters -->
                    <div class="filter-card no-print">
                        <form method="GET" action="" class="form-inline justify-content-between flex-wrap">
                            <div class="btn-group mb-2 mb-md-0">
                                <a href="?filter_type=today" class="btn btn-sm <?php echo $filter_type == 'today' ? 'btn-success' : 'btn-outline-success'; ?>">Today</a>
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
                            <div>
                                <input type="hidden" name="filter_type" value="custom">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-search"></i> Apply
                                </button>
                                <a href="profit_loss.php" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-sync-alt"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Product-wise Profit Table -->
                    <div class="pl-card">
                        <div class="pl-header">
                            <i class="fas fa-box"></i> Product-wise Profit Analysis
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="product-table" id="productTable">
                                    <thead>
                                        <tr>
                                            <th>Product Code</th>
                                            <th>Product Name</th>
                                            <th class="text-right">Qty Sold</th>
                                            <th class="text-right">Sale Price (₨)</th>
                                            <th class="text-right">Purchase Price (₨)</th>
                                            <th class="text-right">Total Sale (₨)</th>
                                            <th class="text-right">COGS (₨)</th>
                                            <th class="text-right">Profit (₨)</th>
                                            <th class="text-center">Margin</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(!empty($profit_details)): ?>
                                            <?php foreach($profit_details as $item): ?>
                                                <tr>
                                                    <td><span class="product-code"><?php echo htmlspecialchars($item['product_code']); ?></span></td>
                                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                                    <td class="text-right"><?php echo number_format($item['quantity'], 2); ?></td>
                                                    <td class="text-right">₨ <?php echo number_format($item['sale_price'], 2); ?></td>
                                                    <td class="text-right">₨ <?php echo number_format($item['purchase_price'], 2); ?></td>
                                                    <td class="text-right">₨ <?php echo number_format($item['sale_amount'], 2); ?></td>
                                                    <td class="text-right">₨ <?php echo number_format($item['cost_of_goods'], 2); ?></td>
                                                    <td class="text-right <?php echo $item['profit'] >= 0 ? 'profit-positive' : 'profit-negative'; ?>">
                                                        ₨ <?php echo number_format($item['profit'], 2); ?>
                                                    </td
                                                    <td class="text-center">
                                                        <?php 
                                                        $margin = $item['sale_amount'] > 0 ? ($item['profit'] / $item['sale_amount']) * 100 : 0;
                                                        $margin_class = $margin >= 0 ? 'profit-positive' : 'profit-negative';
                                                        ?>
                                                        <span class="<?php echo $margin_class; ?>">
                                                            <?php echo number_format($margin, 2); ?>%
                                                        </span>
                                                    </td
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="9" class="text-center py-5">
                                                    <i class="fas fa-box-open fa-3x text-muted mb-3 d-block"></i>
                                                    <h5>No sales found</h5>
                                                    <p class="text-muted">No products sold in selected period</p>
                                                </td
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr style="background: linear-gradient(135deg, #e8f5e9, #e3f2fd); font-weight: bold;">
                                            <td colspan="3" class="text-right">TOTALS:</td
                                            <td class="text-right">-</td
                                            <td class="text-right">-</td
                                            <td class="text-right">₨ <?php echo number_format($total_sales_revenue, 2); ?></td
                                            <td class="text-right">₨ <?php echo number_format($total_cost_of_goods, 2); ?></td
                                            <td class="text-right profit-positive">₨ <?php echo number_format($total_profit, 2); ?></td
                                            <td class="text-center profit-positive"><?php echo $total_sales_revenue > 0 ? number_format(($total_profit / $total_sales_revenue) * 100, 2) : 0; ?>%</td
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Profit & Loss Statement -->
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="pl-card">
                                <div class="pl-header">
                                    <i class="fas fa-chart-line"></i> INCOME
                                </div>
                                <div class="pl-body">
                                    <div class="pl-row">
                                        <span class="pl-label">Sales Revenue</span>
                                        <span class="pl-amount">₨ <?php echo number_format($total_sales_revenue, 2); ?></span>
                                    </div>
                                    <div class="pl-row">
                                        <span class="pl-label">Other Income</span>
                                        <span class="pl-amount">₨ <?php echo number_format($other_income, 2); ?></span>
                                    </div>
                                    <div class="pl-total">
                                        <span class="pl-label">TOTAL INCOME</span>
                                        <span class="pl-amount">₨ <?php echo number_format($total_income, 2); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="pl-card">
                                <div class="pl-header">
                                    <i class="fas fa-boxes"></i> COST OF GOODS SOLD
                                </div>
                                <div class="pl-body">
                                    <div class="pl-row">
                                        <span class="pl-label">Opening Inventory</span>
                                        <span class="pl-amount">—</span>
                                    </div>
                                    <div class="pl-row">
                                        <span class="pl-label">+ Purchases</span>
                                        <span class="pl-amount">—</span>
                                    </div>
                                    <div class="pl-row">
                                        <span class="pl-label">- Closing Inventory</span>
                                        <span class="pl-amount">—</span>
                                    </div>
                                    <div class="pl-total">
                                        <span class="pl-label">COST OF GOODS SOLD</span>
                                        <span class="pl-amount">₨ <?php echo number_format($total_cost_of_goods, 2); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="pl-card">
                                <div class="pl-header">
                                    <i class="fas fa-money-bill-wave"></i> GROSS PROFIT
                                </div>
                                <div class="pl-body">
                                    <div class="pl-row">
                                        <span class="pl-label">Sales Revenue</span>
                                        <span class="pl-amount">₨ <?php echo number_format($total_sales_revenue, 2); ?></span>
                                    </div>
                                    <div class="pl-row">
                                        <span class="pl-label">Less: COGS</span>
                                        <span class="pl-amount">₨ <?php echo number_format($total_cost_of_goods, 2); ?></span>
                                    </div>
                                    <div class="pl-total profit-positive">
                                        <span class="pl-label">GROSS PROFIT</span>
                                        <span class="pl-amount">₨ <?php echo number_format($total_profit, 2); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="pl-card">
                                <div class="pl-header">
                                    <i class="fas fa-receipt"></i> EXPENSES
                                </div>
                                <div class="pl-body">
                                    <div class="pl-row">
                                        <span class="pl-label">Salary Expense</span>
                                        <span class="pl-amount">₨ <?php echo number_format($total_salary_expense, 2); ?></span>
                                    </div>
                                    <div class="pl-row">
                                        <span class="pl-label">General Expenses</span>
                                        <span class="pl-amount">₨ <?php echo number_format($total_expenses, 2); ?></span>
                                    </div>
                                    <div class="pl-row">
                                        <span class="pl-label">Other Expenses</span>
                                        <span class="pl-amount">₨ <?php echo number_format($other_expenses, 2); ?></span>
                                    </div>
                                    <div class="pl-total">
                                        <span class="pl-label">TOTAL EXPENSES</span>
                                        <span class="pl-amount pl-amount-negative">₨ <?php echo number_format($total_expenses_amount, 2); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Net Profit/Loss Summary -->
                    <div class="pl-card">
                        <div class="pl-header" style="background: linear-gradient(135deg, <?php echo $net_profit >= 0 ? '#28a745' : '#dc3545'; ?>, <?php echo $net_profit >= 0 ? '#1e7e34' : '#c82333'; ?>);">
                            <i class="fas fa-chart-line"></i> NET PROFIT / LOSS
                        </div>
                        <div class="pl-body">
                            <div class="pl-row">
                                <span class="pl-label">Gross Profit</span>
                                <span class="pl-amount">₨ <?php echo number_format($total_profit, 2); ?></span>
                            </div>
                            <div class="pl-row">
                                <span class="pl-label">+ Other Income</span>
                                <span class="pl-amount">₨ <?php echo number_format($other_income, 2); ?></span>
                            </div>
                            <div class="pl-row">
                                <span class="pl-label">- Total Expenses</span>
                                <span class="pl-amount pl-amount-negative">₨ <?php echo number_format($total_expenses_amount, 2); ?></span>
                            </div>
                            <div class="pl-total <?php echo $net_profit >= 0 ? 'profit-positive' : 'profit-negative'; ?>" style="font-size: 20px;">
                                <span class="pl-label" style="font-size: 18px;">NET <?php echo $net_profit >= 0 ? 'PROFIT' : 'LOSS'; ?></span>
                                <span class="pl-amount" style="font-size: 20px;">
                                    ₨ <?php echo number_format(abs($net_profit), 2); ?>
                                </span>
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
            $('#productTable').DataTable({
                "pageLength": 25,
                "order": [[7, 'desc']],
                "language": {
                    "search": "🔍 Search:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries"
                }
            });
            
            // Monthly Profit Chart
            var chartData = <?php echo json_encode($monthly_data); ?>;
            
            var labels = chartData.map(function(item) {
                var date = item.month.split('-');
                var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                return months[parseInt(date[1]) - 1] + ' ' + date[0];
            });
            
            var revenueData = chartData.map(function(item) {
                return parseFloat(item.revenue);
            });
            
            var costData = chartData.map(function(item) {
                return parseFloat(item.cost);
            });
            
            if(labels.length > 0) {
                var ctx = document.getElementById('profitChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Revenue (₨)',
                                data: revenueData,
                                backgroundColor: 'rgba(30, 126, 52, 0.1)',
                                borderColor: '#1e7e34',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.3,
                                pointBackgroundColor: '#1e7e34',
                                pointBorderColor: '#fff',
                                pointRadius: 5,
                                pointHoverRadius: 7
                            },
                            {
                                label: 'Cost (₨)',
                                data: costData,
                                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                                borderColor: '#dc3545',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.3,
                                pointBackgroundColor: '#dc3545',
                                pointBorderColor: '#fff',
                                pointRadius: 5,
                                pointHoverRadius: 7
                            }
                        ]
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
                                        return context.dataset.label + ': ₨ ' + context.raw.toLocaleString();
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
            }
            
            // Export to Excel
            $('#exportExcelBtn').click(function() {
                var html = document.querySelector('.data-table-wrapper').outerHTML;
                var url = 'data:application/vnd.ms-excel,' + encodeURIComponent('<html><head><meta charset="UTF-8"></head><body>' + html + '</body></html>');
                var link = document.createElement('a');
                link.download = 'profit_loss_statement_' + new Date().toISOString().slice(0,19) + '.xls';
                link.href = url;
                link.click();
                Swal.fire('Success!', 'Export completed!', 'success');
            });
        });
    </script>
</body>
</html>