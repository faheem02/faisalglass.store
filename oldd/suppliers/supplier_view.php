<?php
/**
 * View Suppliers Page
 * Faysal Glass And Aluminium Centre
 * 
 * Display all suppliers with search, filter, and actions
 * Page: View Supplier Ledgers
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$page_title = "View Suppliers";
$success_msg = '';
$error_msg = '';

// Handle Delete Supplier
if(isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // Check if supplier has purchases
    $check_purchases = "SELECT id FROM purchases WHERE supplier_id = $delete_id LIMIT 1";
    $purchases_result = mysqli_query($conn, $check_purchases);
    
    if(mysqli_num_rows($purchases_result) > 0) {
        $error_msg = "Cannot delete! This supplier has purchase records.";
    } else {
        // Delete ledger entries first
        mysqli_query($conn, "DELETE FROM supplier_ledger WHERE supplier_id = $delete_id");
        // Delete payments
        mysqli_query($conn, "DELETE FROM supplier_payments WHERE supplier_id = $delete_id");
        // Delete supplier
        $delete_query = "DELETE FROM suppliers WHERE id = $delete_id";
        if(mysqli_query($conn, $delete_query)) {
            $success_msg = "Supplier deleted successfully!";
        } else {
            $error_msg = "Failed to delete supplier!";
        }
    }
}

// Handle Status Toggle
if(isset($_GET['toggle_status'])) {
    $supplier_id = intval($_GET['toggle_status']);
    $current_status = intval($_GET['current_status']);
    $new_status = $current_status == 1 ? 0 : 1;
    
    $update_query = "UPDATE suppliers SET status = $new_status WHERE id = $supplier_id";
    if(mysqli_query($conn, $update_query)) {
        $success_msg = $new_status == 1 ? "Supplier activated successfully!" : "Supplier deactivated successfully!";
    } else {
        $error_msg = "Failed to update status!";
    }
}

// Fetch all suppliers
$suppliers_query = "SELECT s.*, 
                    (SELECT SUM(credit) - SUM(debit) FROM supplier_ledger WHERE supplier_id = s.id) as calculated_balance
                    FROM suppliers s 
                    ORDER BY s.id DESC";
$suppliers_result = mysqli_query($conn, $suppliers_query);
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
        .status-badge-active {
            background-color: #28a745;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
        }
        .status-badge-inactive {
            background-color: #dc3545;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
        }
        .balance-payable {
            color: #dc3545;
            font-weight: bold;
        }
        .balance-receivable {
            color: #28a745;
            font-weight: bold;
        }
        .supplier-code {
            font-family: monospace;
            font-weight: bold;
            color: #0066cc;
        }
        .action-buttons .btn {
            margin: 2px;
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
        .summary-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        .summary-number {
            font-size: 24px;
            font-weight: bold;
            color: #1e7e34;
        }
        .summary-label {
            color: #4a5568;
            font-size: 14px;
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
                <i class="fas fa-truck text-success mr-2"></i> View Supplier Ledgers
            </h1>
            <div>
                <a href="supplier.php" class="btn btn-green">
                    <i class="fas fa-plus-circle mr-1"></i> Add New Supplier
                </a>
                <button type="button" class="btn btn-outline-success ml-2" onclick="window.print()">
                    <i class="fas fa-print mr-1"></i> Print
                </button>
                <button type="button" class="btn btn-outline-info ml-2" id="exportBtn">
                    <i class="fas fa-file-excel mr-1"></i> Export
                </button>
            </div>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if($success_msg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-2"></i> <?php echo $success_msg; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>
        
        <?php if($error_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error_msg; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>
        
        <!-- Summary Cards -->
        <?php
        // Calculate totals
        $total_suppliers = 0;
        $total_payable = 0;
        $total_receivable = 0;
        $active_suppliers = 0;
        
        $summary_query = "SELECT s.*, 
                          (SELECT SUM(credit) - SUM(debit) FROM supplier_ledger WHERE supplier_id = s.id) as balance
                          FROM suppliers s";
        $summary_result = mysqli_query($conn, $summary_query);
        
        while($sup = mysqli_fetch_assoc($summary_result)) {
            $total_suppliers++;
            $balance = floatval($sup['balance']);
            if($balance > 0) {
                $total_payable += $balance;
            } elseif($balance < 0) {
                $total_receivable += abs($balance);
            }
            if($sup['status'] == 1) $active_suppliers++;
        }
        
        // Reset pointer for main query
        mysqli_data_seek($suppliers_result, 0);
        ?>
        
        <div class="row">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Suppliers</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_suppliers; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-truck fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-danger shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Payable</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo formatCurrency($total_payable); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-arrow-up fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Receivable</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo formatCurrency($total_receivable); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-arrow-down fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Active Suppliers</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $active_suppliers; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-filter mr-2"></i> Filter Suppliers
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><i class="fas fa-search"></i> Search</label>
                            <input type="text" id="searchInput" class="form-control" placeholder="Search by name, code, mobile...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><i class="fas fa-chart-line"></i> Balance Type</label>
                            <select id="balanceFilter" class="form-control">
                                <option value="">All</option>
                                <option value="payable">Payable (Company owes)</option>
                                <option value="receivable">Receivable (Supplier owes)</option>
                                <option value="zero">Zero Balance</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><i class="fas fa-toggle-on"></i> Status</label>
                            <select id="statusFilter" class="form-control">
                                <option value="">All</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="button" id="resetFilters" class="btn btn-secondary form-control">
                                <i class="fas fa-undo-alt"></i> Reset Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Suppliers List Table -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-list mr-2"></i> Suppliers List
                <span class="float-right">
                    <i class="fas fa-chart-line mr-1"></i> 
                    Total: <strong id="totalCount">0</strong>
                </span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="suppliersTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Supplier Name</th>
                                <th>Company</th>
                                <th>Mobile</th>
                                <th>Opening Balance</th>
                                <th>Current Balance</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($supplier = mysqli_fetch_assoc($suppliers_result)): 
                                // Calculate current balance from ledger
                                $balance_query = "SELECT SUM(debit) as total_debit, SUM(credit) as total_credit 
                                                  FROM supplier_ledger WHERE supplier_id = {$supplier['id']}";
                                $balance_result = mysqli_query($conn, $balance_query);
                                $balance_data = mysqli_fetch_assoc($balance_result);
                                $current_balance = floatval($balance_data['total_credit']) - floatval($balance_data['total_debit']);
                                
                                // Determine balance display
                                $balance_display = '';
                                $balance_class = '';
                                if($current_balance > 0) {
                                    $balance_display = 'Payable: ' . formatCurrency($current_balance);
                                    $balance_class = 'balance-payable';
                                } elseif($current_balance < 0) {
                                    $balance_display = 'Receivable: ' . formatCurrency(abs($current_balance));
                                    $balance_class = 'balance-receivable';
                                } else {
                                    $balance_display = 'Zero Balance';
                                    $balance_class = '';
                                }
                            ?>
                            <tr>
                                <td class="supplier-code"><?php echo $supplier['supplier_code']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($supplier['supplier_name']); ?></strong>
                                    <?php if($supplier['contact_person']): ?>
                                        <br><small class="text-muted"><i class="fas fa-user"></i> <?php echo htmlspecialchars($supplier['contact_person']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($supplier['company_name']); ?></td>
                                <td><?php echo htmlspecialchars($supplier['mobile']); ?></td>
                                <td class="text-right">
                                    <?php 
                                    if($supplier['balance_type'] == 'payable' && $supplier['opening_balance'] > 0) {
                                        echo formatCurrency($supplier['opening_balance']) . ' (Payable)';
                                    } elseif($supplier['balance_type'] == 'receivable' && $supplier['opening_balance'] > 0) {
                                        echo formatCurrency($supplier['opening_balance']) . ' (Receivable)';
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td class="text-right <?php echo $balance_class; ?>">
                                    <?php echo $balance_display; ?>
                                </td>
                                <td class="text-center">
                                    <?php if($supplier['status'] == 1): ?>
                                        <span class="status-badge-active"><i class="fas fa-check-circle"></i> Active</span>
                                    <?php else: ?>
                                        <span class="status-badge-inactive"><i class="fas fa-times-circle"></i> Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="action-buttons">
                                    <a href="supplier_detail.php?id=<?php echo $supplier['id']; ?>" class="btn btn-sm btn-info" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="credit_pay.php?id=<?php echo $supplier['id']; ?>" class="btn btn-sm btn-success" title="Make Payment">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </a>
                                    <a href="supplier_ledger.php?id=<?php echo $supplier['id']; ?>" class="btn btn-sm btn-primary" title="View Ledger">
                                        <i class="fas fa-book"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-warning" onclick="editSupplier(<?php echo $supplier['id']; ?>)" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm <?php echo $supplier['status'] == 1 ? 'btn-secondary' : 'btn-success'; ?>" 
                                            onclick="toggleStatus(<?php echo $supplier['id']; ?>, <?php echo $supplier['status']; ?>)" 
                                            title="<?php echo $supplier['status'] == 1 ? 'Deactivate' : 'Activate'; ?>">
                                        <i class="fas <?php echo $supplier['status'] == 1 ? 'fa-ban' : 'fa-check-circle'; ?>"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $supplier['id']; ?>)" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/js/sb-admin-2.min.js"></script>

<script>
var suppliersTable;

$(document).ready(function() {
    // Initialize DataTable
    suppliersTable = $('#suppliersTable').DataTable({
        "order": [[0, "asc"]],
        "pageLength": 25,
        "language": {
            "search": "Search:",
            "lengthMenu": "Show _MENU_ entries",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries",
            "infoEmpty": "Showing 0 to 0 of 0 entries",
            "infoFiltered": "(filtered from _MAX_ total entries)",
            "zeroRecords": "No suppliers found"
        },
        "columnDefs": [
            { "orderable": false, "targets": [7] }
        ],
        "drawCallback": function() {
            $('#totalCount').text(suppliersTable.rows().count());
        }
    });
    
    // Update total count
    $('#totalCount').text(suppliersTable.rows().count());
    
    // Search filter
    $('#searchInput').on('keyup', function() {
        suppliersTable.search(this.value).draw();
    });
    
    // Balance filter
    $('#balanceFilter').on('change', function() {
        var filter = this.value;
        
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            if(filter === '') return true;
            
            var balanceText = data[5]; // Current Balance column
            var isPayable = balanceText.indexOf('Payable') !== -1;
            var isReceivable = balanceText.indexOf('Receivable') !== -1;
            var isZero = balanceText.indexOf('Zero') !== -1;
            
            if(filter === 'payable' && isPayable) return true;
            if(filter === 'receivable' && isReceivable) return true;
            if(filter === 'zero' && isZero) return true;
            
            return false;
        });
        suppliersTable.draw();
        $.fn.dataTable.ext.search.pop();
    });
    
    // Status filter
    $('#statusFilter').on('change', function() {
        var filter = this.value;
        
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            if(filter === '') return true;
            
            var statusHtml = data[6]; // Status column
            var isActive = statusHtml.indexOf('Active') !== -1;
            var isInactive = statusHtml.indexOf('Inactive') !== -1;
            
            if(filter === '1' && isActive) return true;
            if(filter === '0' && isInactive) return true;
            
            return false;
        });
        suppliersTable.draw();
        $.fn.dataTable.ext.search.pop();
    });
    
    // Reset filters
    $('#resetFilters').on('click', function() {
        $('#searchInput').val('');
        $('#balanceFilter').val('');
        $('#statusFilter').val('');
        suppliersTable.search('').columns().search('').draw();
    });
});

// Edit Supplier
function editSupplier(id) {
    window.location.href = 'edit_supplier.php?id=' + id;
}

// Toggle Status
function toggleStatus(id, currentStatus) {
    var action = currentStatus == 1 ? 'deactivate' : 'activate';
    Swal.fire({
        title: 'Are you sure?',
        text: "You want to " + action + " this supplier!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#1e7e34',
        cancelButtonColor: '#dc3545',
        confirmButtonText: 'Yes, ' + action + ' it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'supplier_view.php?toggle_status=' + id + '&current_status=' + currentStatus;
        }
    });
}

// Confirm Delete
function confirmDelete(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this! This supplier will be deleted permanently.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'supplier_view.php?delete_id=' + id;
        }
    });
}

// Export to Excel
$('#exportBtn').on('click', function() {
    var tableData = [];
    var headers = [];
    
    $('#suppliersTable thead th').each(function() {
        headers.push($(this).text());
    });
    tableData.push(headers);
    
    $('#suppliersTable tbody tr').each(function() {
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
    a.download = 'suppliers_export.csv';
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