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
$where_conditions = ["sale_date BETWEEN '$from_date' AND '$to_date'"];
if($customer_filter > 0) {
    $where_conditions[] = "customer_id = $customer_filter";
}
if(!empty($payment_type)) {
    $where_conditions[] = "payment_type = '$payment_type'";
}
$where_clause = implode(" AND ", $where_conditions);

$query = "SELECT sm.*, c.customer_name, c.customer_code 
          FROM sale_master sm 
          LEFT JOIN customers c ON sm.customer_id = c.id 
          WHERE $where_clause 
          ORDER BY sm.sale_date DESC, sm.id DESC";
$result = mysqli_query($conn, $query);

// Get customers for filter
$customers_query = "SELECT id, customer_name, customer_code FROM customers WHERE status = 1 ORDER BY customer_name";
$customers_result = mysqli_query($conn, $customers_query);

// Calculate summaries
$total_sales = 0;
$cash_sales = 0;
$credit_sales = 0;
$partial_sales = 0;
$total_received = 0;
$total_outstanding = 0;
$sales_data = [];

while($row = mysqli_fetch_assoc($result)) {
    $row['grand_total'] = floatval($row['grand_total']);
    $row['received_amount'] = floatval($row['received_amount']);
    $row['remaining_amount'] = floatval($row['remaining_amount']);
    
    $sales_data[] = $row;
    $total_sales += $row['grand_total'];
    $total_received += $row['received_amount'];
    $total_outstanding += $row['remaining_amount'];
    
    if($row['payment_type'] == 'cash') {
        $cash_sales += $row['grand_total'];
    } elseif($row['payment_type'] == 'credit') {
        $credit_sales += $row['grand_total'];
    } elseif($row['payment_type'] == 'partial') {
        $partial_sales += $row['grand_total'];
    }
}

// Monthly data for chart
$monthly_query = "SELECT 
                    DATE_FORMAT(sale_date, '%Y-%m') as month,
                    SUM(grand_total) as total,
                    COUNT(*) as count
                  FROM sale_master 
                  WHERE sale_date BETWEEN '$from_date' AND '$to_date'
                  GROUP BY DATE_FORMAT(sale_date, '%Y-%m')
                  ORDER BY month ASC";
$monthly_result = mysqli_query($conn, $monthly_query);
$monthly_data = [];
while($row = mysqli_fetch_assoc($monthly_result)) {
    $monthly_data[] = $row;
}

$page_title = "Sale Report";
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
        
        /* Improved Table Styles */
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
        
        .table-custom tbody tr:last-child td {
            border-bottom: none;
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
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        .payment-cash { background: #28a745; color: white; }
        .payment-bank { background: #17a2b8; color: white; }
        .payment-credit { background: #dc3545; color: white; }
        .payment-partial { background: #ffc107; color: #2c3e50; }
        
        /* Status Badges */
        .status-badge {
            padding: 5px 10px;
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
        
        /* Customer Cell */
        .customer-name {
            font-weight: 600;
            color: #1e7e34;
        }
        .customer-code {
            font-size: 11px;
            color: #6c757d;
            font-family: monospace;
        }
        
        /* Action Button */
        .btn-view {
            background: #36b9cc;
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            transition: all 0.2s;
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
                            <i class="fas fa-chart-line"></i> Sale Report
                        </h1>
                        <div class="no-print">
                            <button onclick="window.open('print_sale_report.php?from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>&customer_id=<?php echo $customer_filter; ?>&payment_type=<?php echo urlencode($payment_type); ?>', '_blank', 'width=1000,height=750')" class="btn btn-secondary btn-sm">
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
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Sales</div>
                                <div class="stat-number" style="color: #1e7e34;">₨ <?php echo number_format($total_sales, 2); ?></div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stat-card border-left-primary">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Cash Sales</div>
                                <div class="stat-number" style="color: #4e73df;">₨ <?php echo number_format($cash_sales, 2); ?></div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stat-card border-left-warning">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Credit Sales</div>
                                <div class="stat-number" style="color: #f6c23e;">₨ <?php echo number_format($credit_sales, 2); ?></div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stat-card border-left-danger">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Outstanding Receivables</div>
                                <div class="stat-number" style="color: #e74a3b;">₨ <?php echo number_format($total_outstanding, 2); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Monthly Chart -->
                    <!--<div class="card shadow mb-4 no-print">-->
                    <!--    <div class="card-header py-3">-->
                    <!--        <h6 class="m-0 font-weight-bold" style="color: #1e7e34;">-->
                    <!--            <i class="fas fa-chart-bar"></i> Monthly Sales Trend-->
                    <!--        </h6>-->
                    <!--    </div>-->
                    <!--    <div class="card-body">-->
                    <!--        <canvas id="salesChart" style="height: 300px;"></canvas>-->
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
                            <div class="form-group mb-2 mb-md-0">
                                <select name="payment_type" class="form-control form-control-sm">
                                    <option value="">-- All Payment Types --</option>
                                    <option value="cash" <?php echo $payment_type == 'cash' ? 'selected' : ''; ?>>Cash</option>
                                    <option value="bank" <?php echo $payment_type == 'bank' ? 'selected' : ''; ?>>Bank</option>
                                    <option value="credit" <?php echo $payment_type == 'credit' ? 'selected' : ''; ?>>Credit</option>
                                    <option value="partial" <?php echo $payment_type == 'partial' ? 'selected' : ''; ?>>Partial</option>
                                </select>
                            </div>
                            <div>
                                <input type="hidden" name="filter_type" value="custom">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-search"></i> Apply
                                </button>
                                <a href="sale_report.php" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-sync-alt"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Data Table -->
                    <div class="data-table-wrapper">
                        <div class="card-header py-3" style="background: linear-gradient(135deg, #e8f5e9, #e3f2fd); border-bottom: 2px solid #1e7e34;">
                            <h6 class="m-0 font-weight-bold" style="color: #1e7e34;">
                                <i class="fas fa-list"></i> Sale Transactions 
                                (<?php echo date('d-m-Y', strtotime($from_date)); ?> to <?php echo date('d-m-Y', strtotime($to_date)); ?>)
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-custom" id="saleTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th width="12%">Invoice #</th>
                                            <th width="10%">Date</th>
                                            <th width="20%">Customer</th>
                                            <th width="12%">Payment Type</th>
                                            <th width="12%" class="text-right">Total Amount</th>
                                            <th width="12%" class="text-right">Received</th>
                                            <th width="12%" class="text-right">Remaining</th>
                                            <th width="10%">Status</th>
                                            <th width="10%" class="no-print">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(!empty($sales_data)): ?>
                                            <?php foreach($sales_data as $sale): ?>
                                            <tr>
                                                <td>
                                                    <span class="invoice-badge"><?php echo htmlspecialchars($sale['invoice_no']); ?></span>
                                                </td>
                                                <td><?php echo date('d-m-Y', strtotime($sale['sale_date'])); ?></td>
                                                <td>
                                                    <div class="customer-name"><?php echo htmlspecialchars($sale['customer_name'] ?? 'Walk-In Customer'); ?></div>
                                                    <div class="customer-code"><?php echo htmlspecialchars($sale['customer_code'] ?? ''); ?></div>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $payment_class = '';
                                                    if($sale['payment_type'] == 'cash') $payment_class = 'payment-cash';
                                                    elseif($sale['payment_type'] == 'bank') $payment_class = 'payment-bank';
                                                    elseif($sale['payment_type'] == 'credit') $payment_class = 'payment-credit';
                                                    else $payment_class = 'payment-partial';
                                                    ?>
                                                    <span class="payment-badge <?php echo $payment_class; ?>">
                                                        <?php echo ucfirst($sale['payment_type']); ?>
                                                    </span>
                                                </td>
                                                <td class="text-right amount-positive">₨ <?php echo number_format($sale['grand_total'], 2); ?></td>
                                                <td class="text-right amount-positive">₨ <?php echo number_format($sale['received_amount'], 2); ?></td>
                                                <td class="text-right <?php echo $sale['remaining_amount'] > 0 ? 'amount-negative' : 'amount-neutral'; ?>">
                                                    ₨ <?php echo number_format($sale['remaining_amount'], 2); ?>
                                                </td>
                                                <td>
                                                    <?php if($sale['remaining_amount'] == 0): ?>
                                                        <span class="status-badge status-paid"><i class="fas fa-check-circle"></i> Paid</span>
                                                    <?php elseif($sale['received_amount'] > 0): ?>
                                                        <span class="status-badge status-partial"><i class="fas fa-clock"></i> Partial</span>
                                                    <?php else: ?>
                                                        <span class="status-badge status-unpaid"><i class="fas fa-times-circle"></i> Unpaid</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="no-print">
                                                    <a href="../sales/view_invoice.php?id=<?php echo $sale['id']; ?>" 
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
                                                    <h5>No sales found</h5>
                                                    <p class="text-muted">No transactions in selected period</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr style="background: linear-gradient(135deg, #e8f5e9, #e3f2fd); font-weight: bold;">
                                            <td colspan="4" class="text-right">Totals:</td>
                                            <td class="text-right amount-positive">₨ <?php echo number_format($total_sales, 2); ?></td>
                                            <td class="text-right amount-positive">₨ <?php echo number_format($total_received, 2); ?></td>
                                            <td class="text-right amount-negative">₨ <?php echo number_format($total_outstanding, 2); ?></td>
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
            // Initialize DataTable
            $('#saleTable').DataTable({
                "pageLength": 25,
                "order": [[1, 'desc']],
                "language": {
                    "search": "🔍 Search:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "emptyTable": "No sales data available",
                    "zeroRecords": "No matching sales found"
                },
                "columnDefs": [
                    { "orderable": false, "targets": [8] }
                ]
            });
            
            // Sales Chart
            var ctx = document.getElementById('salesChart').getContext('2d');
            var chartData = <?php echo json_encode($monthly_data); ?>;
            
            var labels = chartData.map(item => {
                let date = item.month.split('-');
                let months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                return months[parseInt(date[1]) - 1] + ' ' + date[0];
            });
            var values = chartData.map(item => item.total);
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Sales Amount (₨)',
                        data: values,
                        backgroundColor: 'rgba(30, 126, 52, 0.1)',
                        borderColor: '#1e7e34',
                        borderWidth: 3,
                        pointBackgroundColor: '#4e73df',
                        pointBorderColor: '#fff',
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        fill: true,
                        tension: 0.3
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
                var table = document.getElementById('saleTable');
                var html = table.cloneNode(true);
                // Remove action column from export
                $(html).find('th:last-child, td:last-child').remove();
                var url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html.outerHTML);
                var link = document.createElement('a');
                link.download = 'sale_report_' + new Date().toISOString().slice(0,19) + '.xls';
                link.href = url;
                link.click();
                Swal.fire('Success!', 'Export completed!', 'success');
            });
            
            // Export to CSV
            $('#exportCSVBtn').click(function() {
                var csv = [];
                var rows = document.querySelectorAll('#saleTable tr');
                for (var i = 0; i < rows.length; i++) {
                    var row = [], cols = rows[i].querySelectorAll('td, th');
                    // Skip action column (last column)
                    var colCount = (i === 0) ? cols.length - 1 : cols.length - 1;
                    for (var j = 0; j < colCount; j++) {
                        var text = cols[j].innerText.replace(/₨/g, '').trim();
                        row.push('"' + text + '"');
                    }
                    csv.push(row.join(','));
                }
                var blob = new Blob([csv.join('\n')], {type: 'text/csv'});
                var link = document.createElement('a');
                link.download = 'sale_report_' + new Date().toISOString().slice(0,19) + '.csv';
                link.href = URL.createObjectURL(blob);
                link.click();
                URL.revokeObjectURL(link.href);
                Swal.fire('Success!', 'Export completed!', 'success');
            });
        });
    </script>
</body>
</html>