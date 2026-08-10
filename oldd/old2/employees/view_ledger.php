<?php
/**
 * View Employee Ledger Page
 * Faysal Glass And Aluminium Centre
 * 
 * Display all employees with balance summary and actions
 * Page: View Employee Ledger
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$page_title = "View Employee Ledger";
$success_msg = '';
$error_msg = '';

// Handle Delete Employee
if(isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // Check if employee has salary or payment records
    $check_salary = "SELECT id FROM employee_salary WHERE employee_id = $delete_id LIMIT 1";
    $salary_result = mysqli_query($conn, $check_salary);
    
    $check_payments = "SELECT id FROM employee_payments WHERE employee_id = $delete_id LIMIT 1";
    $payments_result = mysqli_query($conn, $check_payments);
    
    if(($salary_result && mysqli_num_rows($salary_result) > 0) || ($payments_result && mysqli_num_rows($payments_result) > 0)) {
        $error_msg = "Cannot delete! This employee has salary or payment records.";
    } else {
        // Delete ledger entries first
        mysqli_query($conn, "DELETE FROM employee_ledger WHERE employee_id = $delete_id");
        // Delete employee
        $delete_query = "DELETE FROM employees WHERE id = $delete_id";
        if(mysqli_query($conn, $delete_query)) {
            $success_msg = "Employee deleted successfully!";
        } else {
            $error_msg = "Failed to delete employee!";
        }
    }
}

// Handle Status Toggle
if(isset($_GET['toggle_status'])) {
    $employee_id = intval($_GET['toggle_status']);
    $current_status = intval($_GET['current_status']);
    $new_status = $current_status == 1 ? 0 : 1;
    
    $update_query = "UPDATE employees SET status = $new_status WHERE id = $employee_id";
    if(mysqli_query($conn, $update_query)) {
        $success_msg = $new_status == 1 ? "Employee activated successfully!" : "Employee deactivated successfully!";
    } else {
        $error_msg = "Failed to update status!";
    }
}

// Fetch all employees with balance calculation
$employees_query = "SELECT e.*, 
                    (SELECT SUM(credit) - SUM(debit) FROM employee_ledger WHERE employee_id = e.id) as calculated_balance
                    FROM employees e 
                    ORDER BY e.id DESC";
$employees_result = mysqli_query($conn, $employees_query);
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
        .balance-advance {
            color: #28a745;
            font-weight: bold;
        }
        .employee-code {
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
    </style>
</head>
<body id="page-top">

<div id="wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <div class="container-fluid">
        
        <!-- Page Header -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-users text-success mr-2"></i> View Employee Ledger
            </h1>
            <div>
                <a href="add_employee.php" class="btn btn-green">
                    <i class="fas fa-plus-circle mr-1"></i> Add New Employee
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
        $total_employees = 0;
        $total_payable = 0;
        $total_advance = 0;
        $active_employees = 0;
        
        $summary_query = "SELECT e.*, 
                          (SELECT SUM(credit) - SUM(debit) FROM employee_ledger WHERE employee_id = e.id) as balance
                          FROM employees e";
        $summary_result = mysqli_query($conn, $summary_query);
        
        if($summary_result) {
            while($emp = mysqli_fetch_assoc($summary_result)) {
                $total_employees++;
                $balance = floatval($emp['balance']);
                if($balance > 0) {
                    $total_payable += $balance;
                } elseif($balance < 0) {
                    $total_advance += abs($balance);
                }
                if($emp['status'] == 1) $active_employees++;
            }
        }
        
        // Reset pointer for main query
        if($employees_result) {
            mysqli_data_seek($employees_result, 0);
        }
        ?>
        
        <div class="row">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Employees</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_employees; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
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
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Advances</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo formatCurrency($total_advance); ?></div>
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
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Active Employees</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $active_employees; ?></div>
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
                <i class="fas fa-filter mr-2"></i> Filter Employees
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
                                <option value="advance">Advance (Employee owes)</option>
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
        
        <!-- Employees List Table -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-list mr-2"></i> Employees List
                <span class="float-right">
                    <i class="fas fa-chart-line mr-1"></i> 
                    Total: <strong id="totalCount">0</strong>
                </span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="employeesTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Employee Name</th>
                                <th>Designation</th>
                                <th>Department</th>
                                <th>Mobile</th>
                                <th>Net Salary</th>
                                <th>Current Balance</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($employees_result && mysqli_num_rows($employees_result) > 0): ?>
                                <?php while($employee = mysqli_fetch_assoc($employees_result)): 
                                    // Calculate current balance from ledger
                                    $balance_query = "SELECT SUM(debit) as total_debit, SUM(credit) as total_credit 
                                                      FROM employee_ledger WHERE employee_id = {$employee['id']}";
                                    $balance_result = mysqli_query($conn, $balance_query);
                                    $total_debit = 0;
                                    $total_credit = 0;
                                    if($balance_result && mysqli_num_rows($balance_result) > 0) {
                                        $balance_data = mysqli_fetch_assoc($balance_result);
                                        $total_debit = floatval($balance_data['total_debit']);
                                        $total_credit = floatval($balance_data['total_credit']);
                                    }
                                    $current_balance = $total_credit - $total_debit;
                                    
                                    // Determine balance display
                                    $balance_display = '';
                                    $balance_class = '';
                                    if($current_balance > 0) {
                                        $balance_display = 'Payable: ' . formatCurrency($current_balance);
                                        $balance_class = 'balance-payable';
                                    } elseif($current_balance < 0) {
                                        $balance_display = 'Advance: ' . formatCurrency(abs($current_balance));
                                        $balance_class = 'balance-advance';
                                    } else {
                                        $balance_display = 'Zero Balance';
                                        $balance_class = '';
                                    }
                                ?>
                                <tr>
                                    <td class="employee-code"><?php echo $employee['employee_code']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($employee['employee_name']); ?></strong>
                                        <br><small class="text-muted"><i class="fas fa-user"></i> <?php echo htmlspecialchars($employee['father_name']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($employee['designation']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['department']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['mobile']); ?></td>
                                    <td class="text-right"><?php echo formatCurrency($employee['net_salary']); ?></td>
                                    <td class="text-right <?php echo $balance_class; ?>">
                                        <?php echo $balance_display; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if($employee['status'] == 1): ?>
                                            <span class="status-badge-active"><i class="fas fa-check-circle"></i> Active</span>
                                        <?php else: ?>
                                            <span class="status-badge-inactive"><i class="fas fa-times-circle"></i> Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="action-buttons">
                                        <a href="employee_detail.php?id=<?php echo $employee['id']; ?>" class="btn btn-sm btn-info" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="paid_amount.php?id=<?php echo $employee['id']; ?>" class="btn btn-sm btn-success" title="Make Payment">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </a>
                                        <a href="employee_ledger.php?id=<?php echo $employee['id']; ?>" class="btn btn-sm btn-primary" title="View Ledger">
                                            <i class="fas fa-book"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-warning" onclick="editEmployee(<?php echo $employee['id']; ?>)" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm <?php echo $employee['status'] == 1 ? 'btn-secondary' : 'btn-success'; ?>" 
                                                onclick="toggleStatus(<?php echo $employee['id']; ?>, <?php echo $employee['status']; ?>)" 
                                                title="<?php echo $employee['status'] == 1 ? 'Deactivate' : 'Activate'; ?>">
                                            <i class="fas <?php echo $employee['status'] == 1 ? 'fa-ban' : 'fa-check-circle'; ?>"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $employee['id']; ?>)" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center">No employees found. Click "Add New Employee" to add.</td>
                                </tr>
                            <?php endif; ?>
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
var employeesTable;

$(document).ready(function() {
    // Initialize DataTable
    if($('#employeesTable tbody tr').length > 0) {
        employeesTable = $('#employeesTable').DataTable({
            "order": [[0, "asc"]],
            "pageLength": 25,
            "language": {
                "search": "Search:",
                "lengthMenu": "Show _MENU_ entries",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                "infoEmpty": "Showing 0 to 0 of 0 entries",
                "infoFiltered": "(filtered from _MAX_ total entries)",
                "zeroRecords": "No employees found"
            },
            "columnDefs": [
                { "orderable": false, "targets": [8] }
            ],
            "drawCallback": function() {
                $('#totalCount').text(employeesTable.rows().count());
            }
        });
        
        // Update total count
        $('#totalCount').text(employeesTable.rows().count());
        
        // Search filter
        $('#searchInput').on('keyup', function() {
            employeesTable.search(this.value).draw();
        });
        
        // Balance filter
        $('#balanceFilter').on('change', function() {
            var filter = this.value;
            
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                if(filter === '') return true;
                
                var balanceText = data[6]; // Current Balance column
                var isPayable = balanceText.indexOf('Payable') !== -1;
                var isAdvance = balanceText.indexOf('Advance') !== -1;
                var isZero = balanceText.indexOf('Zero') !== -1;
                
                if(filter === 'payable' && isPayable) return true;
                if(filter === 'advance' && isAdvance) return true;
                if(filter === 'zero' && isZero) return true;
                
                return false;
            });
            employeesTable.draw();
            $.fn.dataTable.ext.search.pop();
        });
        
        // Status filter
        $('#statusFilter').on('change', function() {
            var filter = this.value;
            
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                if(filter === '') return true;
                
                var statusHtml = data[7]; // Status column
                var isActive = statusHtml.indexOf('Active') !== -1;
                var isInactive = statusHtml.indexOf('Inactive') !== -1;
                
                if(filter === '1' && isActive) return true;
                if(filter === '0' && isInactive) return true;
                
                return false;
            });
            employeesTable.draw();
            $.fn.dataTable.ext.search.pop();
        });
        
        // Reset filters
        $('#resetFilters').on('click', function() {
            $('#searchInput').val('');
            $('#balanceFilter').val('');
            $('#statusFilter').val('');
            employeesTable.search('').columns().search('').draw();
        });
    } else {
        $('#totalCount').text('0');
    }
});

// Edit Employee
function editEmployee(id) {
    window.location.href = 'edit_employee.php?id=' + id;
}

// Toggle Status
function toggleStatus(id, currentStatus) {
    var action = currentStatus == 1 ? 'deactivate' : 'activate';
    Swal.fire({
        title: 'Are you sure?',
        text: "You want to " + action + " this employee!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#1e7e34',
        cancelButtonColor: '#dc3545',
        confirmButtonText: 'Yes, ' + action + ' it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'view_ledger.php?toggle_status=' + id + '&current_status=' + currentStatus;
        }
    });
}

// Confirm Delete
function confirmDelete(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this! This employee will be deleted permanently.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'view_ledger.php?delete_id=' + id;
        }
    });
}

// Export to Excel
$('#exportBtn').on('click', function() {
    var tableData = [];
    var headers = ['Employee Code', 'Employee Name', 'Designation', 'Department', 'Mobile', 'Net Salary', 'Current Balance', 'Status'];
    tableData.push(headers);
    
    $('#employeesTable tbody tr').each(function() {
        var row = [];
        $(this).find('td').each(function(index) {
            if(index < 8) {
                row.push($(this).text().trim());
            }
        });
        tableData.push(row);
    });
    
    // Create CSV
    var csv = tableData.map(row => row.join(',')).join('\n');
    var blob = new Blob([csv], { type: 'text/csv' });
    var url = window.URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = 'employees_export.csv';
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