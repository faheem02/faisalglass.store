<?php
/**
 * Dashboard Page
 * Faysal Glass And Aluminium Centre
 * 
 * Displays key business metrics and statistics
 */

// Start session and check login
session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

// Include required files
include('../includes/database.php');
include('../includes/txt.php');

// Get user info from session
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'] ?? $username;
$user_role = $_SESSION['user_role'];

$page_title = "Dashboard";

// ============================================
// Initialize variables with default values
// ============================================
$total_products = 0;
$total_customers = 0;
$total_suppliers = 0;
$today_sales = 0;
$today_purchases = 0;
$cash_in_hand = 0;
$total_receivable = 0;
$total_payable = 0;

// ============================================
// Query for Total Products
// ============================================
$query_products = "SELECT COUNT(*) as total FROM products WHERE status = 1";
$result_products = mysqli_query($conn, $query_products);
if ($result_products && mysqli_num_rows($result_products) > 0) {
    $row = mysqli_fetch_assoc($result_products);
    $total_products = $row['total'];
}

// ============================================
// Query for Total Customers
// ============================================
$query_customers = "SELECT COUNT(*) as total FROM customers WHERE status = 1";
$result_customers = mysqli_query($conn, $query_customers);
if ($result_customers && mysqli_num_rows($result_customers) > 0) {
    $row = mysqli_fetch_assoc($result_customers);
    $total_customers = $row['total'];
}

// ============================================
// Query for Total Suppliers
// ============================================
$query_suppliers = "SELECT COUNT(*) as total FROM suppliers WHERE status = 1";
$result_suppliers = mysqli_query($conn, $query_suppliers);
if ($result_suppliers && mysqli_num_rows($result_suppliers) > 0) {
    $row = mysqli_fetch_assoc($result_suppliers);
    $total_suppliers = $row['total'];
}

// ============================================
// Query for Today's Sales (from sale_master)
// ============================================
$today_date = date('Y-m-d');
$query_today_sales = "SELECT COALESCE(SUM(grand_total), 0) as total 
                       FROM sale_master 
                       WHERE DATE(sale_date) = '$today_date' AND status = 1";
$result_today_sales = mysqli_query($conn, $query_today_sales);
if ($result_today_sales && mysqli_num_rows($result_today_sales) > 0) {
    $row = mysqli_fetch_assoc($result_today_sales);
    $today_sales = floatval($row['total']);
}

// ============================================
// Query for Today's Purchases (from purchase_master)
// ============================================
$query_today_purchases = "SELECT COALESCE(SUM(grand_total), 0) as total 
                          FROM purchase_master 
                          WHERE DATE(purchase_date) = '$today_date' AND status = 1";
$result_today_purchases = mysqli_query($conn, $query_today_purchases);
if ($result_today_purchases && mysqli_num_rows($result_today_purchases) > 0) {
    $row = mysqli_fetch_assoc($result_today_purchases);
    $today_purchases = floatval($row['total']);
}

// ============================================
// Query for Cash in Hand (from cash_book)
// ============================================
$query_cash = "SELECT balance FROM cash_book ORDER BY id DESC LIMIT 1";
$result_cash = mysqli_query($conn, $query_cash);
if ($result_cash && mysqli_num_rows($result_cash) > 0) {
    $row = mysqli_fetch_assoc($result_cash);
    $cash_in_hand = floatval($row['balance']);
}

// ============================================
// Query for Total Receivable (customers who owe us)
// ============================================
$query_receivable = "SELECT COALESCE(SUM(current_balance), 0) as total 
                     FROM customers 
                     WHERE current_balance > 0 AND status = 1";
$result_receivable = mysqli_query($conn, $query_receivable);
if ($result_receivable && mysqli_num_rows($result_receivable) > 0) {
    $row = mysqli_fetch_assoc($result_receivable);
    $total_receivable = floatval($row['total']);
}

// ============================================
// Query for Total Payable (we owe suppliers)
// ============================================
$query_payable = "SELECT COALESCE(SUM(current_balance), 0) as total 
                  FROM suppliers 
                  WHERE current_balance > 0 AND status = 1";
$result_payable = mysqli_query($conn, $query_payable);
if ($result_payable && mysqli_num_rows($result_payable) > 0) {
    $row = mysqli_fetch_assoc($result_payable);
    $total_payable = floatval($row['total']);
}

// ============================================
// Query for Monthly Sales Chart
// ============================================
$monthly_sales_query = "SELECT 
                            DATE_FORMAT(sale_date, '%b') as month,
                            COALESCE(SUM(grand_total), 0) as total
                        FROM sale_master 
                        WHERE YEAR(sale_date) = YEAR(CURDATE())
                        AND status = 1
                        GROUP BY MONTH(sale_date)
                        ORDER BY MONTH(sale_date) ASC";
$monthly_sales_result = mysqli_query($conn, $monthly_sales_query);
$monthly_sales_data = [];
while($row = mysqli_fetch_assoc($monthly_sales_result)) {
    $monthly_sales_data[] = $row;
}
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
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --primary-green: #1e7e34;
            --primary-blue: #0066cc;
        }
        
        body {
            background-color: #f0f2f5;
            color: #1a1a1a;
        }
        
        .card-stats {
            border-radius: 12px;
            border: none;
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .card-stats:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .card-stats-green {
            background: linear-gradient(135deg, #1e7e34 0%, #28a745 100%);
        }
        
        .card-stats-blue {
            background: linear-gradient(135deg, #0066cc 0%, #007bff 100%);
        }
        
        .card-stats-teal {
            background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%);
        }
        
        .card-stats-orange {
            background: linear-gradient(135deg, #ea580c 0%, #f97316 100%);
        }
        
        .card-stats-purple {
            background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 100%);
        }
        
        .card-stats-pink {
            background: linear-gradient(135deg, #db2777 0%, #ec4899 100%);
        }
        
        .card-stats-red {
            background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
        }
        
        .card-stats-amber {
            background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);
        }
        
        .stats-icon {
            font-size: 3rem;
            opacity: 0.8;
            color: white;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: white;
            margin: 0;
        }
        
        .stats-label {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.9);
            margin: 0;
            font-weight: 500;
        }
        
        .page-header-custom {
            background: linear-gradient(135deg, #1e7e34, #0066cc);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            color: white;
        }
        
        .page-header-custom h1 {
            color: white;
            margin: 0;
            font-size: 1.8rem;
        }
        
        .page-header-custom p {
            margin: 0;
            opacity: 0.9;
        }
        
        .chart-card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .chart-card .card-header {
            background: white;
            border-bottom: 2px solid #1e7e34;
            padding: 1rem;
            font-weight: 600;
            color: #1a1a1a;
        }
        
        .welcome-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .stats-number {
                font-size: 1.5rem;
            }
            
            .stats-icon {
                font-size: 2rem;
            }
            
            .page-header-custom h1 {
                font-size: 1.3rem;
            }
        }
        
        .date-badge {
            background: rgba(255,255,255,0.2);
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.9rem;
        }
        
        /* Button Styles */
        .btn-green {
            background-color: #1e7e34;
            border-color: #1e7e34;
            color: #ffffff;
            font-weight: 500;
        }
        
        .btn-green:hover {
            background-color: #155724;
            border-color: #155724;
            color: #ffffff;
        }
        
        .btn-blue {
            background-color: #0066cc;
            border-color: #0066cc;
            color: #ffffff;
            font-weight: 500;
        }
        
        .btn-blue:hover {
            background-color: #004085;
            border-color: #004085;
            color: #ffffff;
        }
        
        .btn-outline-success {
            color: #1e7e34;
            border-color: #1e7e34;
            font-weight: 500;
        }
        
        .btn-outline-success:hover {
            background-color: #1e7e34;
            border-color: #1e7e34;
            color: #ffffff;
        }
        
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
    </style>
</head>
<body id="page-top">

<div id="wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <!-- Topbar -->
            <div class="topbar no-print">
                <div class="welcome-text" style="color: #1e7e34;">
                    <i class="fas fa-store"></i> <?php echo $software_name; ?>
                </div>
                <div class="user-info">
                    <span style="color: #4e73df;">
                        <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($full_name); ?> (<?php echo ucfirst($user_role); ?>)
                    </span>
                    <a href="../logout.php" style="color: #dc3545; text-decoration: none; margin-left: 15px;">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
            
            <!-- Begin Page Content -->
            <div class="container-fluid">
                
                <!-- Page Header -->
                <div class="page-header-custom mt-3">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1><i class="fas fa-chalkboard-user mr-2"></i> Dashboard</h1>
                            <p>Welcome back, <?php echo htmlspecialchars($full_name); ?>! Here's what's happening with your business today.</p>
                        </div>
                        <div class="col-md-4 text-md-right">
                            <div class="date-badge d-inline-block">
                                <i class="fas fa-clock mr-1"></i> <?php echo date('h:i A'); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Cards Row 1 -->
                <div class="row">
                    <!-- Total Products -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card card-stats card-stats-green shadow">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="stats-number"><?php echo number_format($total_products); ?></div>
                                        <div class="stats-label">Total Products</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-boxes stats-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Customers -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card card-stats card-stats-blue shadow">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="stats-number"><?php echo number_format($total_customers); ?></div>
                                        <div class="stats-label">Total Customers</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users stats-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Suppliers -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card card-stats card-stats-teal shadow">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="stats-number"><?php echo number_format($total_suppliers); ?></div>
                                        <div class="stats-label">Total Suppliers</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-truck stats-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Cash In Hand -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card card-stats card-stats-purple shadow">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="stats-number">₨ <?php echo number_format($cash_in_hand, 2); ?></div>
                                        <div class="stats-label">Cash In Hand</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-money-bill-wave stats-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Cards Row 2 -->
                <div class="row">
                    <!-- Today's Sales -->
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card card-stats card-stats-orange shadow">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="stats-number">₨ <?php echo number_format($today_sales, 2); ?></div>
                                        <div class="stats-label">
                                            <i class="fas fa-calendar-day mr-1"></i> 
                                            Today's Sales
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-shopping-cart stats-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Today's Purchases -->
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card card-stats card-stats-pink shadow">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="stats-number">₨ <?php echo number_format($today_purchases, 2); ?></div>
                                        <div class="stats-label">
                                            <i class="fas fa-calendar-day mr-1"></i> 
                                            Today's Purchases
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-boxes stats-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Net Profit Today -->
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card card-stats card-stats-amber shadow">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="stats-number">₨ <?php echo number_format($today_sales - $today_purchases, 2); ?></div>
                                        <div class="stats-label">
                                            <i class="fas fa-chart-line mr-1"></i> 
                                            Net Profit Today
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-chart-line stats-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Cards Row 3 - Receivable/Payable -->
                <div class="row">
                    <!-- Total Receivable -->
                    <div class="col-xl-6 col-md-6 mb-4">
                        <div class="card card-stats card-stats-blue shadow">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="stats-number">₨ <?php echo number_format($total_receivable, 2); ?></div>
                                        <div class="stats-label">
                                            <i class="fas fa-hand-holding-usd mr-1"></i> 
                                            Total Receivable (Customers owe)
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users stats-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Payable -->
                    <div class="col-xl-6 col-md-6 mb-4">
                        <div class="card card-stats card-stats-red shadow">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="stats-number">₨ <?php echo number_format($total_payable, 2); ?></div>
                                        <div class="stats-label">
                                            <i class="fas fa-hand-holding-heart mr-1"></i> 
                                            Total Payable (We owe suppliers)
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-truck stats-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Monthly Sales Chart -->
                <div class="row mt-2">
                    <div class="col-md-12">
                        <div class="card chart-card shadow">
                            <div class="card-header">
                                <i class="fas fa-chart-line text-success mr-2"></i> Monthly Sales Trend (<?php echo date('Y'); ?>)
                            </div>
                            <div class="card-body">
                                <canvas id="salesChart" style="height: 300px; width: 100%;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions Row -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="welcome-section">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h5 class="mb-2"><i class="fas fa-bolt text-success mr-2"></i> Quick Actions</h5>
                                    <p class="text-muted mb-0">Access frequently used features quickly</p>
                                </div>
                                <div class="col-md-6 text-md-right mt-3 mt-md-0">
                                    <a href="../sales/add_sale.php" class="btn btn-green mr-2">
                                        <i class="fas fa-plus-circle"></i> New Sale
                                    </a>
                                    <a href="../purchases/add_purchase.php" class="btn btn-blue mr-2">
                                        <i class="fas fa-plus-circle"></i> New Purchase
                                    </a>
                                    <a href="../products/addproducts.php" class="btn btn-outline-success">
                                        <i class="fas fa-box"></i> Add Stock
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
            <!-- End Page Content -->
        </div>
        
        <!-- Footer -->
        <footer class="sticky-footer bg-white no-print">
            <div class="container my-auto">
                <div class="copyright text-center my-auto">
                    <span>&copy; <?php echo date('Y'); ?> <?php echo $software_name; ?> - All Rights Reserved</span>
                </div>
            </div>
        </footer>
    </div>
</div>
<!-- End of Wrapper -->

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/js/sb-admin-2.min.js"></script>

<script>
    // Monthly Sales Chart
    var ctx = document.getElementById('salesChart').getContext('2d');
    var monthlyData = <?php echo json_encode($monthly_sales_data); ?>;
    
    var months = monthlyData.map(function(item) { return item.month; });
    var sales = monthlyData.map(function(item) { return parseFloat(item.total); });
    
    // Ensure all months are displayed
    var allMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    var fullSalesData = [];
    
    for(var i = 0; i < allMonths.length; i++) {
        var found = false;
        for(var j = 0; j < months.length; j++) {
            if(months[j] === allMonths[i]) {
                fullSalesData.push(sales[j]);
                found = true;
                break;
            }
        }
        if(!found) {
            fullSalesData.push(0);
        }
    }
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: allMonths,
            datasets: [{
                label: 'Sales Amount (₨)',
                data: fullSalesData,
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
</script>

</body>
</html>

<?php
// Close database connection
mysqli_close($conn);
?>