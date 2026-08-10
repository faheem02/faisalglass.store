<?php
/**
 * Payable Amount Page
 * Faysal Glass And Aluminium Centre
 * 
 * Display all employees with payable/advance amounts and salary management
 * Page: Payable Amount (Salary Management)
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$page_title = "Payable Amount";
$success_msg = '';
$error_msg = '';

// Handle Process Monthly Salary
if(isset($_POST['process_salary'])) {
    $salary_month = mysqli_real_escape_string($conn, $_POST['salary_month']);
    $employee_ids = isset($_POST['employee_ids']) ? $_POST['employee_ids'] : array();
    
    if(empty($salary_month)) {
        $error_msg = "Please select salary month!";
    } elseif(empty($employee_ids)) {
        $error_msg = "Please select at least one employee!";
    } else {
        $processed = 0;
        $skipped = 0;
        
        foreach($employee_ids as $emp_id) {
            $emp_id = intval($emp_id);
            
            // Check if salary already processed for this month
            $check_query = "SELECT id FROM employee_salary WHERE employee_id = $emp_id AND month_year = '$salary_month'";
            $check_result = mysqli_query($conn, $check_query);
            
            if(mysqli_num_rows($check_result) > 0) {
                $skipped++;
                continue;
            }
            
            // Get employee details
            $emp_query = "SELECT basic_salary, allowances, deductions, net_salary FROM employees WHERE id = $emp_id AND status = 1";
            $emp_result = mysqli_query($conn, $emp_query);
            
            if($emp_result && mysqli_num_rows($emp_result) > 0) {
                $employee = mysqli_fetch_assoc($emp_result);
                $net_salary = $employee['net_salary'];
                
                // Insert into employee_salary
                $insert_query = "INSERT INTO employee_salary (employee_id, month_year, basic_salary, allowances, deductions, 
                                net_salary, paid_amount, remaining_amount, status) 
                                VALUES ('$emp_id', '$salary_month', '{$employee['basic_salary']}', '{$employee['allowances']}', 
                                '{$employee['deductions']}', '$net_salary', 0, '$net_salary', 'pending')";
                
                if(mysqli_query($conn, $insert_query)) {
                    // Update employee ledger (Credit entry for salary earned)
                    $current_date = date('Y-m-d');
                    
                    // Get current balance
                    $bal_query = "SELECT SUM(credit) - SUM(debit) as balance FROM employee_ledger WHERE employee_id = $emp_id";
                    $bal_result = mysqli_query($conn, $bal_query);
                    $current_balance = 0;
                    if($bal_result && mysqli_num_rows($bal_result) > 0) {
                        $bal_data = mysqli_fetch_assoc($bal_result);
                        $current_balance = floatval($bal_data['balance']);
                    }
                    
                    $new_balance = $current_balance + $net_salary;
                    
                    $ledger_query = "INSERT INTO employee_ledger (date, employee_id, reference_type, reference_id, 
                                      description, debit, credit, balance, month_year) 
                                      VALUES ('$current_date', '$emp_id', 'SALARY', '$emp_id', 
                                      'Salary for month: $salary_month', 0, '$net_salary', '$new_balance', '$salary_month')";
                    mysqli_query($conn, $ledger_query);
                    
                    // Update employee current balance
                    $update_emp = "UPDATE employees SET current_balance = $new_balance WHERE id = $emp_id";
                    mysqli_query($conn, $update_emp);
                    
                    $processed++;
                }
            }
        }
        
        $success_msg = "Salary processed for $processed employee(s). Skipped: $skipped (already processed)";
    }
}

// Handle Delete Salary Record
if(isset($_GET['delete_salary'])) {
    $salary_id = intval($_GET['delete_salary']);
    
    // Get salary record details first
    $get_query = "SELECT * FROM employee_salary WHERE id = $salary_id";
    $get_result = mysqli_query($conn, $get_query);
    
    if($get_result && mysqli_num_rows($get_result) > 0) {
        $salary_record = mysqli_fetch_assoc($get_result);
        
        // Check if any payment was made against this salary
        if($salary_record['paid_amount'] > 0) {
            $error_msg = "Cannot delete! Payment has already been made against this salary record.";
        } else {
            // Delete from employee_salary
            $delete_query = "DELETE FROM employee_salary WHERE id = $salary_id";
            if(mysqli_query($conn, $delete_query)) {
                // Delete from employee_ledger
                $delete_ledger = "DELETE FROM employee_ledger WHERE reference_type = 'SALARY' AND month_year = '{$salary_record['month_year']}' AND employee_id = {$salary_record['employee_id']}";
                mysqli_query($conn, $delete_ledger);
                
                // Update employee current balance
                $bal_query = "SELECT SUM(credit) - SUM(debit) as balance FROM employee_ledger WHERE employee_id = {$salary_record['employee_id']}";
                $bal_result = mysqli_query($conn, $bal_query);
                $new_balance = 0;
                if($bal_result && mysqli_num_rows($bal_result) > 0) {
                    $bal_data = mysqli_fetch_assoc($bal_result);
                    $new_balance = floatval($bal_data['balance']);
                }
                
                $update_emp = "UPDATE employees SET current_balance = $new_balance WHERE id = {$salary_record['employee_id']}";
                mysqli_query($conn, $update_emp);
                
                $success_msg = "Salary record deleted successfully!";
            } else {
                $error_msg = "Failed to delete salary record!";
            }
        }
    }
}

// Get filter parameters
$filter_month = isset($_GET['month']) ? mysqli_real_escape_string($conn, $_GET['month']) : date('Y-m');
$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

// Fetch employees with balance
$employees_query = "SELECT e.*, 
                    (SELECT SUM(credit) - SUM(debit) FROM employee_ledger WHERE employee_id = e.id) as current_balance
                    FROM employees e 
                    WHERE e.status = 1 
                    ORDER BY e.employee_name";
$employees_result = mysqli_query($conn, $employees_query);

// Fetch salary records
$salary_query = "SELECT s.*, e.employee_name, e.employee_code 
                 FROM employee_salary s
                 LEFT JOIN employees e ON s.employee_id = e.id
                 WHERE 1=1";
if($filter_month) {
    $salary_query .= " AND s.month_year = '$filter_month'";
}
if($filter_status) {
    $salary_query .= " AND s.status = '$filter_status'";
}
$salary_query .= " ORDER BY s.month_year DESC, e.employee_name ASC";
$salary_result = mysqli_query($conn, $salary_query);

// Calculate summary
$summary_query = "SELECT 
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN status = 'partial' THEN 1 ELSE 0 END) as partial_count,
                    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count,
                    SUM(net_salary) as total_salary,
                    SUM(paid_amount) as total_paid,
                    SUM(remaining_amount) as total_remaining
                  FROM employee_salary
                  WHERE month_year = '$filter_month'";
$summary_result = mysqli_query($conn, $summary_query);
$summary = mysqli_fetch_assoc($summary_result);
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
        .status-paid {
            background-color: #28a745;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
        }
        .status-pending {
            background-color: #dc3545;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
        }
        .status-partial {
            background-color: #ffc107;
            color: #1a1a1a;
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
        .summary-card {
            text-align: center;
            padding: 15px;
            border-radius: 10px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            height: 100%;
        }
        .summary-number {
            font-size: 24px;
            font-weight: bold;
        }
        .select-all-row {
            background: #f8f9fc;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
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
                <i class="fas fa-chart-line text-success mr-2"></i> Payable Amount
            </h1>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="view_ledger.php">Salary Ledger</a></li>
                <li class="breadcrumb-item active">Payable Amount</li>
            </ol>
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
        
        <!-- Process Monthly Salary Section -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-calendar-alt mr-2"></i> Process Monthly Salary
            </div>
            <div class="card-body">
                <form method="POST" action="" id="processSalaryForm">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-calendar text-success mr-1"></i> Select Month</label>
                                <input type="month" name="salary_month" class="form-control" 
                                       value="<?php echo date('Y-m'); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="selectAllEmployees">
                                    <label class="custom-control-label" for="selectAllEmployees">Select All Employees</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" name="process_salary" class="btn btn-green form-control">
                                    <i class="fas fa-calculator mr-1"></i> Process Salary
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="select-all-row">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm" id="employeesTable">
                                <thead>
                                    <tr>
                                        <th width="5%"><input type="checkbox" id="selectAllCheckbox"></th>
                                        <th width="15%">Code</th>
                                        <th width="30%">Employee Name</th>
                                        <th width="20%">Designation</th>
                                        <th width="15%" class="text-right">Net Salary</th>
                                        <th width="15%" class="text-right">Current Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($emp = mysqli_fetch_assoc($employees_result)): 
                                        $current_bal = floatval($emp['current_balance']);
                                    ?>
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" name="employee_ids[]" value="<?php echo $emp['id']; ?>" class="employee-checkbox">
                                        </td>
                                        <td><?php echo $emp['employee_code']; ?></td>
                                        <td><?php echo htmlspecialchars($emp['employee_name']); ?></td>
                                        <td><?php echo htmlspecialchars($emp['designation']); ?></td>
                                        <td class="text-right"><?php echo formatCurrency($emp['net_salary']); ?></td>
                                        <td class="text-right <?php echo $current_bal > 0 ? 'balance-payable' : ($current_bal < 0 ? 'balance-advance' : ''); ?>">
                                            <?php 
                                            if($current_bal > 0) {
                                                echo 'Payable: ' . formatCurrency($current_bal);
                                            } elseif($current_bal < 0) {
                                                echo 'Advance: ' . formatCurrency(abs($current_bal));
                                            } else {
                                                echo formatCurrency(0);
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Salary Summary Cards -->
        <div class="row">
            <div class="col-xl-2 col-md-4 mb-4">
                <div class="summary-card">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Pending</div>
                    <div class="summary-number text-danger"><?php echo $summary['pending_count'] ?? 0; ?></div>
                    <small>Employees</small>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 mb-4">
                <div class="summary-card">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Partial</div>
                    <div class="summary-number text-warning"><?php echo $summary['partial_count'] ?? 0; ?></div>
                    <small>Employees</small>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 mb-4">
                <div class="summary-card">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Paid</div>
                    <div class="summary-number text-success"><?php echo $summary['paid_count'] ?? 0; ?></div>
                    <small>Employees</small>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 mb-4">
                <div class="summary-card">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Salary</div>
                    <div class="summary-number text-info"><?php echo formatCurrency($summary['total_salary'] ?? 0); ?></div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 mb-4">
                <div class="summary-card">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Paid</div>
                    <div class="summary-number text-success"><?php echo formatCurrency($summary['total_paid'] ?? 0); ?></div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 mb-4">
                <div class="summary-card">
                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Remaining</div>
                    <div class="summary-number text-danger"><?php echo formatCurrency($summary['total_remaining'] ?? 0); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-filter mr-2"></i> Filter Salary Records
            </div>
            <div class="card-body">
                <form method="GET" action="" class="form-inline">
                    <div class="row w-100">
                        <div class="col-md-4">
                            <div class="form-group w-100">
                                <label class="mr-2"><i class="fas fa-calendar-alt text-success"></i> Month:</label>
                                <input type="month" name="month" class="form-control" value="<?php echo $filter_month; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group w-100">
                                <label class="mr-2"><i class="fas fa-chart-line text-success"></i> Status:</label>
                                <select name="status" class="form-control">
                                    <option value="">All</option>
                                    <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="partial" <?php echo $filter_status == 'partial' ? 'selected' : ''; ?>>Partial</option>
                                    <option value="paid" <?php echo $filter_status == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-green w-100">
                                <i class="fas fa-search mr-1"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Salary Records Table -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-list mr-2"></i> Salary Records - <?php echo $filter_month; ?>
                <span class="float-right">
                    <a href="paid_amount.php" class="btn btn-sm btn-light mr-2">
                        <i class="fas fa-money-bill-wave"></i> Make Payment
                    </a>
                    <button type="button" class="btn btn-sm btn-light" id="exportSalaryBtn">
                        <i class="fas fa-file-excel"></i> Export
                    </button>
                </span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="salaryTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Employee Code</th>
                                <th>Employee Name</th>
                                <th>Month</th>
                                <th class="text-right">Net Salary</th>
                                <th class="text-right">Paid Amount</th>
                                <th class="text-right">Remaining</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($salary_result && mysqli_num_rows($salary_result) > 0): ?>
                                <?php while($record = mysqli_fetch_assoc($salary_result)): ?>
                                <tr>
                                    <td><?php echo $record['employee_code']; ?></td>
                                    <td><?php echo htmlspecialchars($record['employee_name']); ?></td>
                                    <td><?php echo date('M Y', strtotime($record['month_year'] . '-01')); ?> (<?php echo $record['month_year']; ?>)</td>
                                    <td class="text-right"><?php echo formatCurrency($record['net_salary']); ?></td>
                                    <td class="text-right text-success"><?php echo formatCurrency($record['paid_amount']); ?></td>
                                    <td class="text-right text-danger"><?php echo formatCurrency($record['remaining_amount']); ?></td>
                                    <td class="text-center">
                                        <?php if($record['status'] == 'paid'): ?>
                                            <span class="status-paid"><i class="fas fa-check-circle"></i> Paid</span>
                                        <?php elseif($record['status'] == 'partial'): ?>
                                            <span class="status-partial"><i class="fas fa-hourglass-half"></i> Partial</span>
                                        <?php else: ?>
                                            <span class="status-pending"><i class="fas fa-clock"></i> Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if($record['remaining_amount'] > 0): ?>
                                            <a href="paid_amount.php?id=<?php echo $record['employee_id']; ?>&month=<?php echo $record['month_year']; ?>" 
                                               class="btn btn-sm btn-success" title="Make Payment">
                                                <i class="fas fa-money-bill-wave"></i> Pay
                                            </a>
                                        <?php endif; ?>
                                        <?php if($record['paid_amount'] == 0): ?>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteSalary(<?php echo $record['id']; ?>)" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                        <a href="employee_detail.php?id=<?php echo $record['employee_id']; ?>" 
                                           class="btn btn-sm btn-info" title="View Employee">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">No salary records found for the selected month</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background: #f8f9fc; font-weight: bold;">
                                <td colspan="3" class="text-right"><strong>Totals:</strong></td>
                                <td class="text-right"><strong><?php echo formatCurrency($summary['total_salary'] ?? 0); ?></strong></td>
                                <td class="text-right"><strong><?php echo formatCurrency($summary['total_paid'] ?? 0); ?></strong></td>
                                <td class="text-right"><strong><?php echo formatCurrency($summary['total_remaining'] ?? 0); ?></strong></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Balance Formula Explanation -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-calculator mr-2"></i> Balance Formula
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3">
                        <h6>Opening Balance</h6>
                        <p class="small text-muted">Initial balance when employee was added</p>
                    </div>
                    <div class="col-md-3">
                        <h6>+ Salary (Credits)</h6>
                        <p class="small text-muted">Monthly salary earned</p>
                    </div>
                    <div class="col-md-3">
                        <h6>- Payments (Debits)</h6>
                        <p class="small text-muted">Salary payments made</p>
                    </div>
                    <div class="col-md-3">
                        <h6>= Current Balance</h6>
                        <p class="small text-muted">
                            Positive = Payable (Company owes)<br>
                            Negative = Advance (Employee owes)
                        </p>
                    </div>
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
$(document).ready(function() {
    // Initialize DataTable for employees
    $('#employeesTable').DataTable({
        "pageLength": 10,
        "language": {
            "search": "Search:",
            "lengthMenu": "Show _MENU_ entries",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries"
        }
    });
    
    // Initialize DataTable for salary records
    if($('#salaryTable tbody tr').length > 0) {
        $('#salaryTable').DataTable({
            "order": [[2, "desc"]],
            "pageLength": 25,
            "language": {
                "search": "Search:",
                "lengthMenu": "Show _MENU_ entries",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries"
            }
        });
    }
    
    // Select All Checkbox functionality
    $('#selectAllCheckbox').on('change', function() {
        $('.employee-checkbox').prop('checked', $(this).is(':checked'));
        updateSelectAllText();
    });
    
    $('.employee-checkbox').on('change', function() {
        if($('.employee-checkbox:checked').length === $('.employee-checkbox').length) {
            $('#selectAllCheckbox').prop('checked', true);
        } else {
            $('#selectAllCheckbox').prop('checked', false);
        }
        updateSelectAllText();
    });
    
    $('#selectAllEmployees').on('change', function() {
        if($(this).is(':checked')) {
            $('.employee-checkbox').prop('checked', true);
            $('#selectAllCheckbox').prop('checked', true);
        } else {
            $('.employee-checkbox').prop('checked', false);
            $('#selectAllCheckbox').prop('checked', false);
        }
        updateSelectAllText();
    });
    
    function updateSelectAllText() {
        var count = $('.employee-checkbox:checked').length;
        $('#selectAllEmployees').next('label').text('Select All Employees (' + count + ' selected)');
    }
    
    updateSelectAllText();
});

// Delete Salary Record
function deleteSalary(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This salary record will be permanently deleted!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'payable_amount.php?delete_salary=' + id;
        }
    });
}

// Export Salary Records
$('#exportSalaryBtn').on('click', function() {
    var tableData = [];
    var headers = ['Employee Code', 'Employee Name', 'Month', 'Net Salary', 'Paid Amount', 'Remaining', 'Status'];
    tableData.push(headers);
    
    $('#salaryTable tbody tr').each(function() {
        var row = [];
        $(this).find('td').each(function(index) {
            if(index < 7) {
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
    a.download = 'salary_records_<?php echo $filter_month; ?>.csv';
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

// Process Salary Form Validation
$('#processSalaryForm').on('submit', function(e) {
    var month = $('input[name="salary_month"]').val();
    var checkedCount = $('.employee-checkbox:checked').length;
    
    if(!month) {
        e.preventDefault();
        Swal.fire({ title: 'Error!', text: 'Please select salary month!', icon: 'error', confirmButtonColor: '#1e7e34' });
        return false;
    }
    
    if(checkedCount === 0) {
        e.preventDefault();
        Swal.fire({ title: 'Error!', text: 'Please select at least one employee!', icon: 'error', confirmButtonColor: '#1e7e34' });
        return false;
    }
    
    // Confirm
    e.preventDefault();
    Swal.fire({
        title: 'Confirm Process Salary',
        text: 'Are you sure you want to process salary for ' + checkedCount + ' employee(s) for ' + month + '?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1e7e34',
        cancelButtonColor: '#dc3545',
        confirmButtonText: 'Yes, Process!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $('#processSalaryForm').off('submit').submit();
        }
    });
});
</script>

</body>
</html>

<?php mysqli_close($conn); ?>