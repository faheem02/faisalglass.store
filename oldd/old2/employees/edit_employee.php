<?php
/**
 * Edit Employee Page
 * Faysal Glass And Aluminium Centre
 * 
 * Edit existing employee information
 * Note: Opening balance cannot be edited after salary transactions exist
 * Page: Edit Employee
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$page_title = "Edit Employee";
$success_msg = '';
$error_msg = '';

// Check if employee ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: view_ledger.php");
    exit();
}

$employee_id = intval($_GET['id']);

// Fetch employee details
$query = "SELECT * FROM employees WHERE id = $employee_id";
$result = mysqli_query($conn, $query);

if(!$result || mysqli_num_rows($result) == 0) {
    header("Location: view_ledger.php");
    exit();
}

$employee = mysqli_fetch_assoc($result);

// Check if employee has any salary transactions
$has_transactions = false;
$transaction_check = "SELECT id FROM employee_ledger WHERE employee_id = $employee_id AND reference_type != 'OPENING' LIMIT 1";
$transaction_result = mysqli_query($conn, $transaction_check);
if($transaction_result && mysqli_num_rows($transaction_result) > 0) {
    $has_transactions = true;
}

// Handle Update Employee
if(isset($_POST['update_employee'])) {
    $employee_name = mysqli_real_escape_string($conn, trim($_POST['employee_name']));
    $father_name = mysqli_real_escape_string($conn, trim($_POST['father_name']));
    $designation = mysqli_real_escape_string($conn, trim($_POST['designation']));
    $department = mysqli_real_escape_string($conn, trim($_POST['department']));
    $employee_type = mysqli_real_escape_string($conn, $_POST['employee_type']);
    $cnic = mysqli_real_escape_string($conn, trim($_POST['cnic']));
    $mobile = mysqli_real_escape_string($conn, trim($_POST['mobile']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $address = mysqli_real_escape_string($conn, trim($_POST['address']));
    $joining_date = mysqli_real_escape_string($conn, $_POST['joining_date']);
    $basic_salary = floatval($_POST['basic_salary']);
    $allowances = floatval($_POST['allowances']);
    $deductions = floatval($_POST['deductions']);
    $net_salary = $basic_salary + $allowances - $deductions;
    $bank_name = mysqli_real_escape_string($conn, trim($_POST['bank_name']));
    $bank_account_no = mysqli_real_escape_string($conn, trim($_POST['bank_account_no']));
    $status = isset($_POST['status']) ? 1 : 0;
    $notes = mysqli_real_escape_string($conn, trim($_POST['notes']));
    
    // Opening balance fields (only if no transactions)
    $opening_balance = floatval($_POST['opening_balance']);
    $balance_type = $_POST['balance_type'];
    
    // Validation
    if(empty($employee_name)) {
        $error_msg = "Employee name is required!";
    } elseif(empty($mobile)) {
        $error_msg = "Mobile number is required!";
    } elseif($net_salary < 0) {
        $error_msg = "Net salary cannot be negative!";
    } else {
        // Check for duplicate employee name (excluding current)
        $check_query = "SELECT id FROM employees WHERE employee_name = '$employee_name' AND id != $employee_id";
        $check_result = mysqli_query($conn, $check_query);
        
        if($check_result && mysqli_num_rows($check_result) > 0) {
            $error_msg = "Employee name already exists!";
        } else {
            // Begin transaction if opening balance is being updated
            if(!$has_transactions) {
                mysqli_begin_transaction($conn);
            }
            
            try {
                // Update employee basic info
                $update_query = "UPDATE employees SET 
                                employee_name = '$employee_name',
                                father_name = '$father_name',
                                designation = '$designation',
                                department = '$department',
                                employee_type = '$employee_type',
                                cnic = '$cnic',
                                mobile = '$mobile',
                                email = '$email',
                                address = '$address',
                                joining_date = '$joining_date',
                                basic_salary = '$basic_salary',
                                allowances = '$allowances',
                                deductions = '$deductions',
                                net_salary = '$net_salary',
                                bank_name = '$bank_name',
                                bank_account_no = '$bank_account_no',
                                status = '$status',
                                notes = '$notes'
                                WHERE id = $employee_id";
                
                if(!mysqli_query($conn, $update_query)) {
                    throw new Exception("Failed to update employee information");
                }
                
                // Update salary details and opening balance only if no transactions exist
                if(!$has_transactions) {
                    // Calculate current balance based on opening balance
                    $current_balance = $opening_balance;
                    
                    $update_balance_query = "UPDATE employees SET 
                                            opening_balance = '$opening_balance',
                                            balance_type = '$balance_type',
                                            current_balance = '$current_balance'
                                            WHERE id = $employee_id";
                    
                    if(!mysqli_query($conn, $update_balance_query)) {
                        throw new Exception("Failed to update opening balance");
                    }
                    
                    // Update opening entry in employee_ledger
                    // First delete existing opening entry
                    $delete_opening = "DELETE FROM employee_ledger WHERE employee_id = $employee_id AND reference_type = 'OPENING'";
                    mysqli_query($conn, $delete_opening);
                    
                    // Create new opening entry
                    $current_date = date('Y-m-d');
                    $debit = 0;
                    $credit = 0;
                    $description = "";
                    
                    if($balance_type == 'payable' && $opening_balance > 0) {
                        $credit = $opening_balance;
                        $description = "Opening Balance - Payable (Company owes employee)";
                    } elseif($balance_type == 'advance' && $opening_balance > 0) {
                        $debit = $opening_balance;
                        $description = "Opening Balance - Advance (Employee took advance)";
                    }
                    
                    if($opening_balance > 0) {
                        $ledger_query = "INSERT INTO employee_ledger (date, employee_id, reference_type, reference_id, 
                                          description, debit, credit, balance) 
                                          VALUES ('$current_date', '$employee_id', 'OPENING', '$employee_id', 
                                          '$description', '$debit', '$credit', '$current_balance')";
                        
                        if(!mysqli_query($conn, $ledger_query)) {
                            throw new Exception("Failed to update opening ledger entry");
                        }
                    }
                    
                    mysqli_commit($conn);
                    $success_msg = "Employee information and opening balance updated successfully!";
                } else {
                    $success_msg = "Employee information updated successfully! (Opening balance cannot be changed as salary transactions exist)";
                }
                
                // Refresh employee data
                $refresh_query = "SELECT * FROM employees WHERE id = $employee_id";
                $refresh_result = mysqli_query($conn, $refresh_query);
                if($refresh_result) {
                    $employee = mysqli_fetch_assoc($refresh_result);
                }
                
                echo "<script>setTimeout(() => { window.location.href = 'employee_detail.php?id=$employee_id'; }, 2000);</script>";
                
            } catch (Exception $e) {
                if(!$has_transactions) {
                    mysqli_rollback($conn);
                }
                $error_msg = $e->getMessage();
            }
        }
    }
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
        .required-field::after {
            content: " *";
            color: red;
        }
        .info-box {
            background: #f8f9fc;
            border-left: 4px solid #1e7e34;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .employee-code {
            font-family: monospace;
            font-size: 18px;
            font-weight: bold;
            color: #0066cc;
        }
        .disabled-field {
            background-color: #e9ecef;
            opacity: 0.7;
        }
        .salary-box {
            background: #f8f9fc;
            border-left: 4px solid #1e7e34;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
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
                <i class="fas fa-edit text-success mr-2"></i> Edit Employee
            </h1>
            <div>
                <a href="employee_detail.php?id=<?php echo $employee_id; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Detail
                </a>
                <a href="view_ledger.php" class="btn btn-info ml-2">
                    <i class="fas fa-list mr-1"></i> All Employees
                </a>
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
        
        <!-- Warning for employees with transactions -->
        <?php if($has_transactions): ?>
        <div class="warning-box">
            <i class="fas fa-exclamation-triangle text-warning mr-2"></i>
            <strong>Note:</strong> This employee has existing salary or payment transactions. 
            Opening balance cannot be edited after salary transactions are recorded. 
            You can only edit basic information like name, contact, and address.
        </div>
        <?php endif; ?>
        
        <!-- Edit Employee Form -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-pen mr-2"></i> Edit Employee Information
            </div>
            <div class="card-body">
                <form method="POST" action="" id="employeeForm">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="info-box">
                                <div class="row">
                                    <div class="col-md-6">
                                        <small class="text-muted">Employee Code</small>
                                        <div class="employee-code">
                                            <i class="fas fa-barcode mr-2"></i><?php echo $employee['employee_code']; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">Created Date</small>
                                        <div class="info-value">
                                            <i class="fas fa-calendar-alt mr-2"></i><?php echo date('d-m-Y H:i:s', strtotime($employee['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-user text-success mr-1"></i> Employee Name</label>
                                <input type="text" name="employee_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($employee['employee_name']); ?>" 
                                       placeholder="Enter employee name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-user-friends text-success mr-1"></i> Father Name</label>
                                <input type="text" name="father_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($employee['father_name']); ?>" 
                                       placeholder="Enter father name">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-briefcase text-success mr-1"></i> Designation</label>
                                <input type="text" name="designation" class="form-control" 
                                       value="<?php echo htmlspecialchars($employee['designation']); ?>" 
                                       placeholder="Enter designation">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-building text-success mr-1"></i> Department</label>
                                <input type="text" name="department" class="form-control" 
                                       value="<?php echo htmlspecialchars($employee['department']); ?>" 
                                       placeholder="Enter department">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-clock text-success mr-1"></i> Employee Type</label>
                                <select name="employee_type" class="form-control">
                                    <option value="permanent" <?php echo $employee['employee_type'] == 'permanent' ? 'selected' : ''; ?>>Permanent</option>
                                    <option value="contract" <?php echo $employee['employee_type'] == 'contract' ? 'selected' : ''; ?>>Contract</option>
                                    <option value="daily_wage" <?php echo $employee['employee_type'] == 'daily_wage' ? 'selected' : ''; ?>>Daily Wage</option>
                                    <option value="commission" <?php echo $employee['employee_type'] == 'commission' ? 'selected' : ''; ?>>Commission Based</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-id-card text-success mr-1"></i> CNIC</label>
                                <input type="text" name="cnic" class="form-control" 
                                       value="<?php echo htmlspecialchars($employee['cnic']); ?>" 
                                       placeholder="Enter CNIC number">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-phone text-success mr-1"></i> Mobile Number</label>
                                <input type="tel" name="mobile" class="form-control" 
                                       value="<?php echo htmlspecialchars($employee['mobile']); ?>" 
                                       placeholder="Enter mobile number" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-envelope text-success mr-1"></i> Email</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($employee['email']); ?>" 
                                       placeholder="Enter email address">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label><i class="fas fa-map-marker-alt text-success mr-1"></i> Address</label>
                                <textarea name="address" class="form-control" rows="2" 
                                          placeholder="Enter complete address"><?php echo htmlspecialchars($employee['address']); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-calendar-alt text-success mr-1"></i> Joining Date</label>
                                <input type="date" name="joining_date" class="form-control" 
                                       value="<?php echo $employee['joining_date']; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-university text-success mr-1"></i> Bank Name</label>
                                <input type="text" name="bank_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($employee['bank_name']); ?>" 
                                       placeholder="Enter bank name">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-credit-card text-success mr-1"></i> Bank Account No</label>
                                <input type="text" name="bank_account_no" class="form-control" 
                                       value="<?php echo htmlspecialchars($employee['bank_account_no']); ?>" 
                                       placeholder="Enter bank account number">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Salary Details -->
                    <div class="salary-box">
                        <h6><i class="fas fa-money-bill-wave text-success"></i> Salary Details</h6>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><i class="fas fa-rupee-sign text-success mr-1"></i> Basic Salary (₨)</label>
                                    <input type="number" step="0.01" name="basic_salary" id="basic_salary" class="form-control" 
                                           value="<?php echo $employee['basic_salary']; ?>" onkeyup="calculateNetSalary()">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><i class="fas fa-plus-circle text-success mr-1"></i> Allowances (₨)</label>
                                    <input type="number" step="0.01" name="allowances" id="allowances" class="form-control" 
                                           value="<?php echo $employee['allowances']; ?>" onkeyup="calculateNetSalary()">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><i class="fas fa-minus-circle text-danger mr-1"></i> Deductions (₨)</label>
                                    <input type="number" step="0.01" name="deductions" id="deductions" class="form-control" 
                                           value="<?php echo $employee['deductions']; ?>" onkeyup="calculateNetSalary()">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><i class="fas fa-chart-line text-success mr-1"></i> Net Salary (₨)</label>
                                    <input type="text" id="net_salary_display" class="form-control" 
                                           value="₨ <?php echo number_format($employee['net_salary'], 2); ?>" readonly style="background:#e8f5e9; font-weight:bold;">
                                    <input type="hidden" name="net_salary" id="net_salary" value="<?php echo $employee['net_salary']; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Opening Balance Section (Disabled if transactions exist) -->
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-money-bill-wave text-success mr-1"></i> Opening Balance (₨)</label>
                                <input type="number" step="0.01" name="opening_balance" class="form-control <?php echo $has_transactions ? 'disabled-field' : ''; ?>" 
                                       value="<?php echo $employee['opening_balance']; ?>" 
                                       placeholder="Enter opening balance"
                                       <?php echo $has_transactions ? 'readonly disabled' : ''; ?>>
                                <?php if($has_transactions): ?>
                                    <small class="text-muted">Cannot edit - Salary transactions exist</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-balance-scale text-success mr-1"></i> Balance Type</label>
                                <select name="balance_type" class="form-control <?php echo $has_transactions ? 'disabled-field' : ''; ?>" 
                                        <?php echo $has_transactions ? 'disabled' : ''; ?>>
                                    <option value="payable" <?php echo ($employee['balance_type'] == 'payable') ? 'selected' : ''; ?>>Payable - Company owes employee</option>
                                    <option value="advance" <?php echo ($employee['balance_type'] == 'advance') ? 'selected' : ''; ?>>Advance - Employee took advance</option>
                                </select>
                                <?php if($has_transactions): ?>
                                    <input type="hidden" name="balance_type" value="<?php echo $employee['balance_type']; ?>">
                                    <input type="hidden" name="opening_balance" value="<?php echo $employee['opening_balance']; ?>">
                                    <small class="text-muted">Cannot edit - Salary transactions exist</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-chart-line text-success mr-1"></i> Current Balance</label>
                                <input type="text" class="form-control disabled-field" 
                                       value="<?php 
                                       $current_bal = floatval($employee['current_balance']);
                                       if($current_bal > 0) {
                                           echo formatCurrency($current_bal) . ' (Payable)';
                                       } elseif($current_bal < 0) {
                                           echo formatCurrency(abs($current_bal)) . ' (Advance)';
                                       } else {
                                           echo formatCurrency(0);
                                       }
                                       ?>" 
                                       readonly disabled>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label><i class="fas fa-toggle-on text-success mr-1"></i> Status</label>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="status" name="status" 
                                           <?php echo ($employee['status'] == 1) ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="status">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label><i class="fas fa-sticky-note text-success mr-1"></i> Notes (Optional)</label>
                                <textarea name="notes" class="form-control" rows="3" 
                                          placeholder="Enter any additional notes"><?php echo htmlspecialchars($employee['notes']); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-12 text-right">
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo-alt mr-1"></i> Reset
                            </button>
                            <button type="submit" name="update_employee" class="btn btn-green">
                                <i class="fas fa-save mr-1"></i> Update Employee
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Information Card -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-info-circle mr-2"></i> Important Information
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="text-center">
                            <i class="fas fa-lock fa-2x text-warning mb-2"></i>
                            <h6>Opening Balance Protection</h6>
                            <p class="small text-muted">Once salary or payment transactions exist, opening balance cannot be edited to maintain accounting integrity.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-center">
                            <i class="fas fa-chart-line fa-2x text-success mb-2"></i>
                            <h6>Current Balance</h6>
                            <p class="small text-muted">Current balance is automatically calculated from ledger transactions and cannot be manually edited.</p>
                        </div>
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
<script src="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/js/sb-admin-2.min.js"></script>

<script>
function calculateNetSalary() {
    var basic = parseFloat($('#basic_salary').val()) || 0;
    var allowances = parseFloat($('#allowances').val()) || 0;
    var deductions = parseFloat($('#deductions').val()) || 0;
    var net = basic + allowances - deductions;
    
    $('#net_salary').val(net.toFixed(2));
    $('#net_salary_display').val('₨ ' + net.toFixed(2));
    
    if(net < 0) {
        $('#net_salary_display').css('color', 'red');
    } else {
        $('#net_salary_display').css('color', '#1e7e34');
    }
}

// Capitalize first letter
$('input[name="employee_name"]').on('keyup', function() {
    var value = $(this).val();
    if(value.length > 0) {
        $(this).val(value.charAt(0).toUpperCase() + value.slice(1));
    }
});

// Form validation
$('#employeeForm').on('submit', function(e) {
    var employeeName = $('input[name="employee_name"]').val().trim();
    var mobile = $('input[name="mobile"]').val().trim();
    var netSalary = parseFloat($('#net_salary').val());
    
    if(employeeName === '') {
        e.preventDefault();
        Swal.fire({ title: 'Error!', text: 'Employee name is required!', icon: 'error', confirmButtonColor: '#1e7e34' });
        return false;
    }
    
    if(mobile === '') {
        e.preventDefault();
        Swal.fire({ title: 'Error!', text: 'Mobile number is required!', icon: 'error', confirmButtonColor: '#1e7e34' });
        return false;
    }
    
    if(netSalary < 0) {
        e.preventDefault();
        Swal.fire({ title: 'Error!', text: 'Net salary cannot be negative!', icon: 'error', confirmButtonColor: '#1e7e34' });
        return false;
    }
    
    <?php if(!$has_transactions): ?>
    var openingBalance = parseFloat($('input[name="opening_balance"]').val());
    if(isNaN(openingBalance) || openingBalance < 0) {
        e.preventDefault();
        Swal.fire({ title: 'Error!', text: 'Opening balance cannot be negative!', icon: 'error', confirmButtonColor: '#1e7e34' });
        return false;
    }
    <?php endif; ?>
    
    // Confirm update
    e.preventDefault();
    Swal.fire({
        title: 'Confirm Update',
        text: 'Are you sure you want to update this employee information?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1e7e34',
        cancelButtonColor: '#dc3545',
        confirmButtonText: 'Yes, Update!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $('#employeeForm').off('submit').submit();
        }
    });
});
</script>

</body>
</html>

<?php mysqli_close($conn); ?>