<?php
/**
 * Add Customer Page
 * Faysal Glass And Aluminium Centre
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$page_title = "Add Customer";
$success_msg = '';
$error_msg = '';

// Generate Customer Code
function generateCustomerCode($conn) {
    $prefix = "CUS";
    $query = "SELECT customer_code FROM customers WHERE customer_code LIKE '{$prefix}%' ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $last_code = $row['customer_code'];
        $number = intval(substr($last_code, 4)) + 1;
        return $prefix . "-" . str_pad($number, 4, '0', STR_PAD_LEFT);
    } else {
        return $prefix . "-0001";
    }
}

// Handle Save Customer
if(isset($_POST['save_customer'])) {
    // Sanitize inputs
    $customer_name = trim($_POST['customer_name']);
    $company_name = trim($_POST['company_name']);
    $contact_person = trim($_POST['contact_person']);
    $mobile = trim($_POST['mobile']);
    $cnic = trim($_POST['cnic']);
    $ntn = trim($_POST['ntn']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $opening_balance = floatval($_POST['opening_balance']);
    $balance_type = in_array($_POST['balance_type'] ?? '', ['receivable', 'payable']) ? $_POST['balance_type'] : 'receivable';
    $status = isset($_POST['status']) ? 1 : 0;
    $notes = trim($_POST['notes']);
    
    // Validation
    if(empty($customer_name)) {
        $error_msg = "Customer name is required!";
    } elseif(empty($mobile)) {
        $error_msg = "Mobile number is required!";
    } else {
        // Escape for SQL
        $customer_name = mysqli_real_escape_string($conn, $customer_name);
        $company_name = mysqli_real_escape_string($conn, $company_name);
        $contact_person = mysqli_real_escape_string($conn, $contact_person);
        $mobile = mysqli_real_escape_string($conn, $mobile);
        $cnic = mysqli_real_escape_string($conn, $cnic);
        $ntn = mysqli_real_escape_string($conn, $ntn);
        $email = mysqli_real_escape_string($conn, $email);
        $address = mysqli_real_escape_string($conn, $address);
        $notes = mysqli_real_escape_string($conn, $notes);
        
        // Check for duplicate customer name
        $check_query = "SELECT id FROM customers WHERE customer_name = '$customer_name'";
        $check_result = mysqli_query($conn, $check_query);
        
        if(mysqli_num_rows($check_result) > 0) {
            $error_msg = "Customer name already exists!";
        } else {
            // Calculate current balance based on opening balance and type
            $current_balance = ($balance_type == 'receivable') ? $opening_balance : -$opening_balance;
            
            // Generate customer code
            $customer_code = generateCustomerCode($conn);
            
            // Insert query
            $insert_query = "INSERT INTO customers (
                customer_code, 
                customer_name, 
                company_name, 
                contact_person,
                mobile, 
                cnic, 
                ntn,
                email, 
                address, 
                opening_balance, 
                balance_type, 
                current_balance, 
                status, 
                notes
            ) VALUES (
                '$customer_code',
                '$customer_name',
                '$company_name',
                '$contact_person',
                '$mobile',
                '$cnic',
                '$ntn',
                '$email',
                '$address',
                '$opening_balance',
                '$balance_type',
                '$current_balance',
                '$status',
                '$notes'
            )";
            
            if(mysqli_query($conn, $insert_query)) {
                $customer_id = mysqli_insert_id($conn);
                $current_date = date('Y-m-d');
                
                // Create opening entry in customer ledger (if balance > 0)
                if($opening_balance > 0) {
                    $debit = 0;
                    $credit = 0;
                    $description = "";
                    
                    if($balance_type == 'receivable') {
                        $debit = $opening_balance;
                        $description = "Opening Balance - Receivable (Customer owes company)";
                    } elseif($balance_type == 'payable') {
                        $credit = $opening_balance;
                        $description = "Opening Balance - Payable (Company owes customer)";
                    }
                    
                    $ledger_query = "INSERT INTO customer_ledger (
                        date, 
                        customer_id, 
                        reference_type, 
                        reference_id, 
                        description, 
                        debit, 
                        credit, 
                        balance
                    ) VALUES (
                        '$current_date',
                        '$customer_id',
                        'OPENING',
                        '$customer_id',
                        '$description',
                        '$debit',
                        '$credit',
                        '$current_balance'
                    )";
                    
                    mysqli_query($conn, $ledger_query);
                }
                
                $success_msg = "Customer added successfully! Customer Code: $customer_code";
                echo "<script>setTimeout(() => { window.location.href = 'view_customer.php'; }, 2000);</script>";
            } else {
                $error_msg = "Failed to add customer: " . mysqli_error($conn);
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
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        .btn-green { background-color: #1e7e34; border-color: #1e7e34; color: white; }
        .btn-green:hover { background-color: #155724; border-color: #155724; color: white; }
        .card-header-custom { background: linear-gradient(135deg, #1e7e34, #0066cc); color: white; border-radius: 10px 10px 0 0; padding: 15px 20px; font-weight: 600; }
        .form-card { border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 30px; }
        .required-field::after { content: " *"; color: red; }
        .preview-code { background: #e8f5e9; padding: 8px 15px; border-radius: 8px; display: inline-block; font-weight: bold; color: #1e7e34; }
        .balance-info { background: #f8f9fc; border-left: 4px solid #1e7e34; padding: 15px; border-radius: 8px; margin-top: 15px; }
    </style>
</head>
<body id="page-top">

<div id="wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <div class="container-fluid mt-4">
                
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-user-plus text-success mr-2"></i> Add Customer
                    </h1>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="view_customer.php">Customers</a></li>
                        <li class="breadcrumb-item active">Add Customer</li>
                    </ol>
                </div>
                
                <?php if($success_msg): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle mr-2"></i> <?php echo $success_msg; ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
                <?php endif; ?>
                
                <?php if($error_msg): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error_msg; ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
                <?php endif; ?>
                
                <div class="card form-card">
                    <div class="card-header-custom">
                        <i class="fas fa-plus-circle mr-2"></i> Add New Customer
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="customerForm">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <div class="preview-code">
                                        <i class="fas fa-barcode mr-2"></i> Auto-generated Customer Code: 
                                        <strong><?php echo generateCustomerCode($conn); ?></strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="required-field">
                                            <i class="fas fa-user text-success mr-1"></i> Customer Name
                                        </label>
                                        <input type="text" name="customer_name" class="form-control" 
                                               placeholder="Enter customer name" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>
                                            <i class="fas fa-building text-success mr-1"></i> Company Name
                                        </label>
                                        <input type="text" name="company_name" class="form-control" 
                                               placeholder="Enter company name">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>
                                            <i class="fas fa-user-tie text-success mr-1"></i> Contact Person
                                        </label>
                                        <input type="text" name="contact_person" class="form-control" 
                                               placeholder="Enter contact person">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="required-field">
                                            <i class="fas fa-phone text-success mr-1"></i> Mobile Number
                                        </label>
                                        <input type="tel" name="mobile" class="form-control" 
                                               placeholder="Enter mobile number" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>
                                            <i class="fas fa-id-card text-success mr-1"></i> CNIC
                                        </label>
                                        <input type="text" name="cnic" class="form-control" 
                                               placeholder="Enter CNIC number">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>
                                            <i class="fas fa-file-invoice text-success mr-1"></i> NTN
                                        </label>
                                        <input type="text" name="ntn" class="form-control" 
                                               placeholder="Enter NTN number">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>
                                            <i class="fas fa-envelope text-success mr-1"></i> Email
                                        </label>
                                        <input type="email" name="email" class="form-control" 
                                               placeholder="Enter email address">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>
                                            <i class="fas fa-toggle-on text-success mr-1"></i> Status
                                        </label>
                                        <div class="custom-control custom-switch mt-2">
                                            <input type="checkbox" class="custom-control-input" id="status" 
                                                   name="status" checked>
                                            <label class="custom-control-label font-weight-bold" for="status">Active</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>
                                            <i class="fas fa-map-marker-alt text-success mr-1"></i> Address
                                        </label>
                                        <textarea name="address" class="form-control" rows="2" 
                                                  placeholder="Enter complete address"></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>
                                            <i class="fas fa-money-bill-wave text-success mr-1"></i> Opening Balance (Rs)
                                        </label>
                                        <input type="number" step="0.01" name="opening_balance" 
                                               class="form-control" id="openingBalance" value="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>
                                            <i class="fas fa-balance-scale text-success mr-1"></i> Balance Type
                                        </label>
                                        <select name="balance_type" class="form-control" id="balanceType">
                                            <option value="receivable">Receivable (+) - Customer owes company</option>
                                            <option value="payable">Payable (-) - Company owes customer</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>
                                            <i class="fas fa-sticky-note text-success mr-1"></i> Notes
                                        </label>
                                        <input type="text" name="notes" class="form-control" 
                                               placeholder="Any additional notes">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="balance-info" id="balanceInfo">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div id="balanceExplanation" class="text-muted small">
                                            <i class="fas fa-lightbulb"></i> Selected: 
                                            <strong id="selectedType">Receivable</strong> - 
                                            <span id="selectedExplanation">Customer owes company this amount (Debit)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-md-12 text-right">
                                    <button type="reset" class="btn btn-secondary">
                                        <i class="fas fa-undo-alt mr-1"></i> Reset
                                    </button>
                                    <button type="submit" name="save_customer" class="btn btn-green ml-2">
                                        <i class="fas fa-save mr-1"></i> Save Customer
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <?php include('../includes/footer.php'); ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/js/sb-admin-2.min.js"></script>

<script>
    function updateBalanceExplanation() {
        var type = $('#balanceType').val();
        if(type === 'receivable') {
            $('#selectedType').text('Receivable (+)').css('color', '#1e7e34');
            $('#selectedExplanation').text('Customer owes company this amount (Debit)');
            $('#balanceInfo').css('border-left-color', '#1e7e34');
        } else {
            $('#selectedType').text('Payable (-)').css('color', '#dc3545');
            $('#selectedExplanation').text('Company owes customer this amount (Credit/Advance)');
            $('#balanceInfo').css('border-left-color', '#dc3545');
        }
    }
    
    $('#balanceType').on('change', updateBalanceExplanation);
    $(document).ready(function() {
        updateBalanceExplanation();
    });

    $('#customerForm').on('submit', function(e) {
        var customerName = $('input[name="customer_name"]').val().trim();
        var mobile = $('input[name="mobile"]').val().trim();
        
        if(customerName === '') {
            e.preventDefault();
            Swal.fire({ title: 'Error!', text: 'Customer name is required!', icon: 'error', confirmButtonColor: '#1e7e34' });
            return false;
        }
        if(mobile === '') {
            e.preventDefault();
            Swal.fire({ title: 'Error!', text: 'Mobile number is required!', icon: 'error', confirmButtonColor: '#1e7e34' });
            return false;
        }
        return true;
    });
</script>
</body>
</html>
