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

// Note: Since there is no dedicated refund table, refunds are tracked through:
// 1. inventory_ledger with reference_type = 'ADJUSTMENT' (negative qty_in / qty_out)
// 2. Or negative sale entries

// For now, we'll show adjustments that represent refunds
$where_conditions = ["date BETWEEN '$from_date' AND '$to_date'", "reference_type = 'ADJUSTMENT'"];
if($customer_filter > 0) {
    $where_conditions[] = "customer_id = $customer_filter";
}
$where_clause = implode(" AND ", $where_conditions);

$query = "SELECT il.*, p.product_name, p.product_code 
          FROM inventory_ledger il 
          LEFT JOIN products p ON il.product_id = p.id 
          WHERE reference_type = 'ADJUSTMENT'
          AND date BETWEEN '$from_date' AND '$to_date'
          ORDER BY il.date DESC, il.id DESC";
$result = mysqli_query($conn, $query);

// Get customers for filter
$customers_query = "SELECT id, customer_name, customer_code FROM customers WHERE status = 1 ORDER BY customer_name";
$customers_result = mysqli_query($conn, $customers_query);

// Calculate summaries
$total_refunds = 0;
$refund_count = 0;
$refund_data = [];

while($row = mysqli_fetch_assoc($result)) {
    $amount = abs(floatval($row['total_amount']));
    $row['refund_amount'] = $amount;
    $refund_data[] = $row;
    $total_refunds += $amount;
    $refund_count++;
}

$page_title = "Refund Report";
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
        }
        
        .refund-badge {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .product-name {
            font-weight: 600;
            color: #1e7e34;
        }
        
        .refund-amount {
            color: #dc3545;
            font-weight: 700;
            font-size: 14px;
        }
        
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
        }
        
        @media print {
            .no-print { display: none !important; }
            .stat-card { border: 1px solid #ddd; }
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
                            <i class="fas fa-undo-alt"></i> Refund Report
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
                        <div class="col-xl-6 col-md-6 mb-3">
                            <div class="stat-card border-left-danger">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Refunds</div>
                                <div class="stat-number" style="color: #dc3545;">₨ <?php echo number_format($total_refunds, 2); ?></div>
                            </div>
                        </div>
                        <div class="col-xl-6 col-md-6 mb-3">
                            <div class="stat-card border-left-warning">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Refund Count</div>
                                <div class="stat-number" style="color: #f6c23e;"><?php echo $refund_count; ?></div>
                            </div>
                        </div>
                    </div>
                    
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
                            <div>
                                <input type="hidden" name="filter_type" value="custom">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-search"></i> Apply
                                </button>
                                <a href="refund_report.php" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-sync-alt"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Data Table -->
                    <div class="data-table-wrapper">
                        <div class="card-header py-3" style="background: linear-gradient(135deg, #e8f5e9, #e3f2fd); border-bottom: 2px solid #dc3545;">
                            <h6 class="m-0 font-weight-bold" style="color: #dc3545;">
                                <i class="fas fa-list"></i> Refund Transactions 
                                (<?php echo date('d-m-Y', strtotime($from_date)); ?> to <?php echo date('d-m-Y', strtotime($to_date)); ?>)
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-custom" id="refundTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th width="10%">Date</th>
                                            <th width="15%">Original Invoice</th>
                                            <th width="20%">Product</th>
                                            <th width="10%">Quantity</th>
                                            <th width="15%" class="text-right">Refund Amount</th>
                                            <th width="30%">Reason / Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(!empty($refund_data)): ?>
                                            <?php foreach($refund_data as $refund): ?>
                                            <tr>
                                                <td>
                                                    <?php echo date('d-m-Y', strtotime($refund['date'])); ?>
                                                </td>
                                                <td>
                                                    <?php if($refund['reference_type'] == 'ADJUSTMENT' && !empty($refund['remarks'])): ?>
                                                        <?php 
                                                        preg_match('/SAL-(\d+)/', $refund['remarks'], $matches);
                                                        if(!empty($matches[1])):
                                                        ?>
                                                        <span class="refund-badge">SAL-<?php echo str_pad($matches[1], 5, '0', STR_PAD_LEFT); ?></span>
                                                        <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="product-name"><?php echo htmlspecialchars($refund['product_name'] ?? 'N/A'); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($refund['product_code'] ?? ''); ?></small>
                                                </td>
                                                <td><?php echo number_format(abs($refund['qty_out']), 2); ?> <?php echo ($refund['qty_out'] > 0) ? 'Pcs' : ''; ?></td>
                                                <td class="text-right refund-amount">₨ <?php echo number_format($refund['refund_amount'], 2); ?></td>
                                                <td>
                                                    <small><?php echo htmlspecialchars($refund['remarks'] ?? 'No remarks'); ?></small>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-5">
                                                    <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                                    <h5>No refunds found</h5>
                                                    <p class="text-muted">No refund transactions in selected period</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr style="background: linear-gradient(135deg, #e8f5e9, #e3f2fd); font-weight: bold;">
                                            <td colspan="4" class="text-right">Total Refunds:</td>
                                            <td class="text-right refund-amount">₨ <?php echo number_format($total_refunds, 2); ?></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Info Note -->
                    <div class="alert alert-info mt-3 no-print">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Note:</strong> Refunds are recorded as adjustments in inventory ledger. 
                        When a sale is refunded, stock is added back and a refund entry is created.
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
            $('#refundTable').DataTable({
                "pageLength": 25,
                "order": [[0, 'desc']],
                "language": {
                    "search": "🔍 Search:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "emptyTable": "No refund data available",
                    "zeroRecords": "No matching refunds found"
                }
            });
            
            $('#exportExcelBtn').click(function() {
                var table = document.getElementById('refundTable');
                var html = table.cloneNode(true);
                var url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html.outerHTML);
                var link = document.createElement('a');
                link.download = 'refund_report_' + new Date().toISOString().slice(0,19) + '.xls';
                link.href = url;
                link.click();
                Swal.fire('Success!', 'Export completed!', 'success');
            });
            
            $('#exportCSVBtn').click(function() {
                var csv = [];
                var rows = document.querySelectorAll('#refundTable tr');
                for (var i = 0; i < rows.length; i++) {
                    var row = [], cols = rows[i].querySelectorAll('td, th');
                    for (var j = 0; j < cols.length; j++) {
                        var text = cols[j].innerText.replace(/₨/g, '').trim();
                        row.push('"' + text + '"');
                    }
                    csv.push(row.join(','));
                }
                var blob = new Blob([csv.join('\n')], {type: 'text/csv'});
                var link = document.createElement('a');
                link.download = 'refund_report_' + new Date().toISOString().slice(0,19) + '.csv';
                link.href = URL.createObjectURL(blob);
                link.click();
                URL.revokeObjectURL(link.href);
                Swal.fire('Success!', 'Export completed!', 'success');
            });
        });
    </script>
</body>
</html>