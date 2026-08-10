<?php
/**
 * Add Employee Page
 * Faysal Glass And Aluminium Centre
 * 
 * Add new employee with opening balance
 * Page: Add Employee
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$page_title = "Add Employee";
$success_msg = '';
$error_msg = '';

// Generate Employee Code
function generateEmployeeCode($conn) {
    $prefix = "EMP";
    
    // Check if employees table exists
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'employees'");
    if(mysqli_num_rows($table_check) == 0) {
        return $prefix . "-0001";
    }
    
    $query = "SELECT employee_code FROM employees WHERE employee_code LIKE '{$prefix}%' ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $last_code = $row['employee_code'];
        $number = intval(substr($last_code, 4)) + 1;
        return $prefix . "-" . str_pad($number, 4, '0', STR_PAD_LEFT);
    } else {
        return $prefix . "-0001";
    }
}

// Handle Save Employee
if(isset($_POST['save_employee'])) {
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
    $opening_balance = floatval($_POST['opening_balance']);
    $balance_type = $_POST['balance_type'];
    $bank_name = mysqli_real_escape_string($conn, trim($_POST['bank_name']));
    $bank_account_no = mysqli_real_escape_string($conn, trim($_POST['bank_account_no']));
    $status = isset($_POST['status']) ? 1 : 0;
    $notes = mysqli_real_escape_string($conn, trim($_POST['notes']));
    
    // Validation
    if(empty($employee_name)) {
        $error_msg = "Employee name is required!";
    } elseif(empty($mobile)) {
        $error_msg = "Mobile number is required!";
    } elseif($net_salary < 0) {
        $error_msg = "Net salary cannot be negative!";
    } else {
        // Check if employees table exists, if not create it first
        $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'employees'");
        if(mysqli_num_rows($table_check) == 0) {
            $error_msg = "Please run the database tables SQL first! The employees table does not exist.";
        } else {
            // Check for duplicate employee name
            $check_query = "SELECT id FROM employees WHERE employee_name = '$employee_name'";
            $check_result = mysqli_query($conn, $check_query);
            
            if($check_result && mysqli_num_rows($check_result) > 0) {
                $error_msg = "Employee name already exists!";
            } else {
                // Calculate current balance based on opening balance and type
                $current_balance = $opening_balance;
                
                // Generate employee code
                $employee_code = generateEmployeeCode($conn);
                
                // Insert employee
                $insert_query = "INSERT INTO employees (employee_code, employee_name, father_name, designation, department, 
                                  employee_type, cnic, mobile, email, address, joining_date, basic_salary, allowances, 
                                  deductions, net_salary, opening_balance, balance_type, current_balance, bank_name, 
                                  bank_account_no, status, notes) 
                                  VALUES ('$employee_code', '$employee_name', '$father_name', '$designation', '$department', 
                                  '$employee_type', '$cnic', '$mobile', '$email', '$address', '$joining_date', '$basic_salary', 
                                  '$allowances', '$deductions', '$net_salary', '$opening_balance', '$balance_type', 
                                  '$current_balance', '$bank_name', '$bank_account_no', '$status', '$notes')";
                
                if(mysqli_query($conn, $insert_query)) {
                    $employee_id = mysqli_insert_id($conn);
                    $current_date = date('Y-m-d');
                    
                    // Check if employee_ledger table exists
                    $ledger_check = mysqli_query($conn, "SHOW TABLES LIKE 'employee_ledger'");
                    if(mysqli_num_rows($ledger_check) > 0) {
                        // Create opening entry in employee ledger
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
                            mysqli_query($conn, $ledger_query);
                        }
                    }
                    
                    $success_msg = "Employee added successfully! Employee Code: $employee_code";
                    
                    echo "<script>setTimeout(() => { window.location.href = 'view_ledger.php'; }, 2000);</script>";
                } else {
                    $error_msg = "Failed to add employee: " . mysqli_error($conn);
                }
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
        .preview-code {
            background: #e8f5e9;
            padding: 8px 15px;
            border-radius: 8px;
            display: inline-block;
            font-weight: bold;
            color: #1e7e34;
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
                <i class="fas fa-user-plus text-success mr-2"></i> Add Employee
            </h1>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Salary Ledger</a></li>
                <li class="breadcrumb-item active">Add Employee</li>
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
        
        <!-- Database Setup Warning -->
        <?php
        $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'employees'");
        if(mysqli_num_rows($table_check) == 0):
        ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <strong>Database tables not found!</strong> Please run the following SQL query to create the required tables:
            <pre class="mt-2 p-2 bg-light" style="border-radius: 5px; overflow-x: auto;">
-- Create employees table and related tables
CREATE TABLE IF NOT EXISTS `employees` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `employee_code` VARCHAR(50) NOT NULL,
    `employee_name` VARCHAR(200) NOT NULL,
    `father_name` VARCHAR(200),
    `designation` VARCHAR(100),
    `department` VARCHAR(100),
    `employee_type` ENUM('permanent', 'contract', 'daily_wage', 'commission') DEFAULT 'permanent',
    `cnic` VARCHAR(20),
    `mobile` VARCHAR(20) NOT NULL,
    `email` VARCHAR(100),
    `address` TEXT,
    `joining_date` DATE,
    `basic_salary` DECIMAL(15,2) DEFAULT 0.00,
    `allowances` DECIMAL(15,2) DEFAULT 0.00,
    `deductions` DECIMAL(15,2) DEFAULT 0.00,
    `net_salary` DECIMAL(15,2) DEFAULT 0.00,
    `opening_balance` DECIMAL(15,2) DEFAULT 0.00,
    `balance_type` ENUM('payable', 'advance') DEFAULT 'payable',
    `current_balance` DECIMAL(15,2) DEFAULT 0.00,
    `bank_name` VARCHAR(100),
    `bank_account_no` VARCHAR(50),
    `status` TINYINT(1) DEFAULT 1,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `employee_code_unique` (`employee_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            </pre>
        </div>
        <?php endif; ?>
        
        <!-- Add Employee Form -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-plus-circle mr-2"></i> Add New Employee
            </div>
            <div class="card-body">
                <form method="POST" action="" id="employeeForm">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="preview-code">
                                <i class="fas fa-barcode mr-2"></i> Auto-generated Employee Code: 
                                <strong><?php echo generateEmployeeCode($conn); ?></strong>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-user text-success mr-1"></i> Employee Name</label>
                                <input type="text" name="employee_name" class="form-control" 
                                       placeholder="Enter employee name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-user-friends text-success mr-1"></i> Father Name</label>
                                <input type="text" name="father_name" class="form-control" 
                                       placeholder="Enter father name">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-briefcase text-success mr-1"></i> Designation</label>
                                <input type="text" name="designation" class="form-control" 
                                       placeholder="Enter designation">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-building text-success mr-1"></i> Department</label>
                                <input type="text" name="department" class="form-control" 
                                       placeholder="Enter department">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-clock text-success mr-1"></i> Employee Type</label>
                                <select name="employee_type" class="form-control">
                                    <option value="permanent">Permanent</option>
                                    <option value="contract">Contract</option>
                                    <option value="daily_wage">Daily Wage</option>
                                    <option value="commission">Commission Based</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-id-card text-success mr-1"></i> CNIC</label>
                                <input type="text" name="cnic" class="form-control" 
                                       placeholder="Enter CNIC number">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-phone text-success mr-1"></i> Mobile Number</label>
                                <input type="tel" name="mobile" class="form-control" 
                                       placeholder="Enter mobile number" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-envelope text-success mr-1"></i> Email</label>
                                <input type="email" name="email" class="form-control" 
                                       placeholder="Enter email address">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label><i class="fas fa-map-marker-alt text-success mr-1"></i> Address</label>
                                <textarea name="address" class="form-control" rows="2" 
                                          placeholder="Enter complete address"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-calendar-alt text-success mr-1"></i> Joining Date</label>
                                <input type="date" name="joining_date" class="form-control" 
                                       value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-university text-success mr-1"></i> Bank Name</label>
                                <input type="text" name="bank_name" class="form-control" 
                                       placeholder="Enter bank name">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-credit-card text-success mr-1"></i> Bank Account No</label>
                                <input type="text" name="bank_account_no" class="form-control" 
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
                                           value="0" onkeyup="calculateNetSalary()">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><i class="fas fa-plus-circle text-success mr-1"></i> Allowances (₨)</label>
                                    <input type="number" step="0.01" name="allowances" id="allowances" class="form-control" 
                                           value="0" onkeyup="calculateNetSalary()">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><i class="fas fa-minus-circle text-danger mr-1"></i> Deductions (₨)</label>
                                    <input type="number" step="0.01" name="deductions" id="deductions" class="form-control" 
                                           value="0" onkeyup="calculateNetSalary()">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><i class="fas fa-chart-line text-success mr-1"></i> Net Salary (₨)</label>
                                    <input type="text" id="net_salary_display" class="form-control" 
                                           value="₨ 0.00" readonly style="background:#e8f5e9; font-weight:bold;">
                                    <input type="hidden" name="net_salary" id="net_salary" value="0">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Opening Balance -->
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-money-bill-wave text-success mr-1"></i> Opening Balance (₨)</label>
                                <input type="number" step="0.01" name="opening_balance" class="form-control" 
                                       value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-balance-scale text-success mr-1"></i> Balance Type</label>
                                <select name="balance_type" class="form-control" id="balanceType">
                                    <option value="payable">Payable - Company owes employee</option>
                                    <option value="advance">Advance - Employee took advance</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-toggle-on text-success mr-1"></i> Status</label>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="status" name="status" checked>
                                    <label class="custom-control-label" for="status">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label><i class="fas fa-sticky-note text-success mr-1"></i> Notes (Optional)</label>
                                <textarea name="notes" class="form-control" rows="2" 
                                          placeholder="Any additional notes about employee"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-12 text-right">
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo-alt mr-1"></i> Reset
                            </button>
                            <button type="submit" name="save_employee" class="btn btn-green">
                                <i class="fas fa-save mr-1"></i> Save Employee
                            </button>
                        </div>
                    </div>
                </form>
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
});
</script>

</body>
</html>

<?php mysqli_close($conn); ?>