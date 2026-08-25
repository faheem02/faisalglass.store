<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($customer_id == 0) {
    header("Location: view_customer.php");
    exit();
}

// Fetch customer details
$query = "SELECT * FROM customers WHERE id = $customer_id";
$result = mysqli_query($conn, $query);
$customer = mysqli_fetch_assoc($result);

if(!$customer) {
    header("Location: view_customer.php");
    exit();
}

$success_msg = '';
$error_msg = '';

// Handle Update Customer
if(isset($_POST['update_customer'])) {
    $customer_name = mysqli_real_escape_string($conn, trim($_POST['customer_name']));
    $company_name = mysqli_real_escape_string($conn, trim($_POST['company_name'] ?? ''));
    $contact_person = mysqli_real_escape_string($conn, trim($_POST['contact_person'] ?? ''));
    $mobile = mysqli_real_escape_string($conn, trim($_POST['mobile']));
    $cnic = mysqli_real_escape_string($conn, trim($_POST['cnic'] ?? ''));
    $ntn = mysqli_real_escape_string($conn, trim($_POST['ntn'] ?? ''));
    $email = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
    $address = mysqli_real_escape_string($conn, trim($_POST['address'] ?? ''));
    $opening_balance = floatval($_POST['opening_balance'] ?? 0);
    $balance_type = in_array($_POST['balance_type'] ?? '', ['receivable', 'payable']) ? $_POST['balance_type'] : 'receivable';
    $status = isset($_POST['status']) ? 1 : 0;
    $notes = mysqli_real_escape_string($conn, trim($_POST['notes'] ?? ''));
    
    // Validation
    if(empty($customer_name)) {
        $error_msg = "Customer name is required!";
    } elseif(empty($mobile)) {
        $error_msg = "Mobile number is required!";
    } else {
        // Check for duplicate customer name (other than this customer)
        $dup_check = mysqli_query($conn, "SELECT id FROM customers WHERE customer_name = '$customer_name' AND id != $customer_id");
        if(mysqli_num_rows($dup_check) > 0) {
            $error_msg = "Another customer with this name already exists!";
        } else {
            // Update customer table
            $update_query = "UPDATE customers SET 
                                customer_name = '$customer_name',
                                company_name = '$company_name',
                                contact_person = '$contact_person',
                                mobile = '$mobile',
                                cnic = '$cnic',
                                ntn = '$ntn',
                                email = '$email',
                                address = '$address',
                                opening_balance = '$opening_balance',
                                balance_type = '$balance_type',
                                status = '$status',
                                notes = '$notes'
                            WHERE id = $customer_id";
            
            if(mysqli_query($conn, $update_query)) {
                // Update / Sync opening balance in customer_ledger
                $debit = ($balance_type == 'receivable') ? $opening_balance : 0;
                $credit = ($balance_type == 'payable') ? $opening_balance : 0;
                $description = "Opening Balance - " . ucfirst($balance_type);
                $current_date = date('Y-m-d');
                
                $check_opening_entry = mysqli_query($conn, "SELECT id FROM customer_ledger WHERE customer_id = $customer_id AND reference_type = 'OPENING' LIMIT 1");
                
                if(mysqli_num_rows($check_opening_entry) > 0) {
                    if($opening_balance > 0) {
                        mysqli_query($conn, "UPDATE customer_ledger SET 
                                                debit = '$debit', 
                                                credit = '$credit', 
                                                description = '$description' 
                                            WHERE customer_id = $customer_id AND reference_type = 'OPENING'");
                    } else {
                        // If balance set to 0, remove opening entry
                        mysqli_query($conn, "DELETE FROM customer_ledger WHERE customer_id = $customer_id AND reference_type = 'OPENING'");
                    }
                } else {
                    if($opening_balance > 0) {
                        mysqli_query($conn, "INSERT INTO customer_ledger (
                                                date, customer_id, reference_type, reference_id, description, debit, credit, balance
                                            ) VALUES (
                                                '$current_date', '$customer_id', 'OPENING', '$customer_id', '$description', '$debit', '$credit', '$opening_balance'
                                            )");
                    }
                }
                
                // Recalculate current_balance from customer_ledger
                $ledger_sum_res = mysqli_query($conn, "SELECT COALESCE(SUM(debit) - SUM(credit), 0) as net_bal FROM customer_ledger WHERE customer_id = $customer_id");
                $net_balance = floatval(mysqli_fetch_assoc($ledger_sum_res)['net_bal'] ?? 0);
                
                mysqli_query($conn, "UPDATE customers SET current_balance = '$net_balance' WHERE id = $customer_id");
                
                $success_msg = "Customer information updated successfully!";
                
                // Refresh customer data
                $result = mysqli_query($conn, "SELECT * FROM customers WHERE id = $customer_id");
                $customer = mysqli_fetch_assoc($result);
            } else {
                $error_msg = "Failed to update customer: " . mysqli_error($conn);
            }
        }
    }
}

// Get current user info
$user_id = $_SESSION['user_id'] ?? 0;
$user_name = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'staff';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Customer | <?php echo $software_name; ?></title>
    
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
        .topbar .welcome-text { color: #1e7e34; font-weight: 500; font-size: 16px; }
        .topbar .user-name { color: #4e73df; font-weight: 600; background: #e3f2fd; padding: 6px 12px; border-radius: 20px; }
        .topbar .logout-btn { color: #dc3545; text-decoration: none; padding: 6px 12px; border-radius: 20px; background: #fee; }
        .topbar .logout-btn:hover { background-color: #dc3545; color: white; }
    </style>
</head>
<body id="page-top">

<div id="wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <!-- Topbar -->
            <div class="topbar">
                <div class="welcome-text">
                    <i class="fas fa-store"></i> <?php echo $software_name; ?>
                </div>
                <div>
                    <span class="user-name mr-2">
                        <i class="fas fa-user-circle"></i> 
                        <?php echo htmlspecialchars($user_name); ?>
                    </span>
                    <a href="../logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
            
            <div class="container-fluid mt-4">
                
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-user-edit text-success mr-2"></i> Edit Customer
                    </h1>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="view_customer.php">Customers</a></li>
                        <li class="breadcrumb-item active">Edit Customer</li>
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
                        <i class="fas fa-user-edit mr-2"></i> Edit Customer Information
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="customerForm">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <div class="preview-code">
                                        <i class="fas fa-barcode mr-2"></i> Customer Code: 
                                        <strong><?php echo htmlspecialchars($customer['customer_code'] ?? ''); ?></strong>
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
                                               value="<?php echo htmlspecialchars($customer['customer_name'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>
                                            <i class="fas fa-building text-success mr-1"></i> Company Name
                                        </label>
                                        <input type="text" name="company_name" class="form-control" 
                                               value="<?php echo htmlspecialchars($customer['company_name'] ?? ''); ?>"
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
                                               value="<?php echo htmlspecialchars($customer['contact_person'] ?? ''); ?>"
                                               placeholder="Enter contact person">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="required-field">
                                            <i class="fas fa-phone text-success mr-1"></i> Mobile Number
                                        </label>
                                        <input type="tel" name="mobile" class="form-control" 
                                               value="<?php echo htmlspecialchars($customer['mobile'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>
                                            <i class="fas fa-id-card text-success mr-1"></i> CNIC
                                        </label>
                                        <input type="text" name="cnic" class="form-control" 
                                               value="<?php echo htmlspecialchars($customer['cnic'] ?? ''); ?>"
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
                                               value="<?php echo htmlspecialchars($customer['ntn'] ?? ''); ?>"
                                               placeholder="Enter NTN number">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>
                                            <i class="fas fa-envelope text-success mr-1"></i> Email
                                        </label>
                                        <input type="email" name="email" class="form-control" 
                                               value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>"
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
                                                   name="status" <?php echo (($customer['status'] ?? 1) == 1) ? 'checked' : ''; ?>>
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
                                                  placeholder="Enter complete address"><?php echo htmlspecialchars($customer['address'] ?? ''); ?></textarea>
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
                                               class="form-control" id="openingBalance" 
                                               value="<?php echo htmlspecialchars($customer['opening_balance'] ?? 0); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>
                                            <i class="fas fa-balance-scale text-success mr-1"></i> Balance Type
                                        </label>
                                        <select name="balance_type" class="form-control" id="balanceType">
                                            <option value="receivable" <?php echo (($customer['balance_type'] ?? 'receivable') == 'receivable') ? 'selected' : ''; ?>>
                                                Receivable (+) - Customer owes company
                                            </option>
                                            <option value="payable" <?php echo (($customer['balance_type'] ?? '') == 'payable') ? 'selected' : ''; ?>>
                                                Payable (-) - Company owes customer
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>
                                            <i class="fas fa-sticky-note text-success mr-1"></i> Notes
                                        </label>
                                        <input type="text" name="notes" class="form-control" 
                                               value="<?php echo htmlspecialchars($customer['notes'] ?? ''); ?>"
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
                                    <a href="view_customer.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left mr-1"></i> Back to Customers
                                    </a>
                                    <button type="submit" name="update_customer" class="btn btn-green ml-2">
                                        <i class="fas fa-save mr-1"></i> Update Customer
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
            Swal.fire({ 
                title: 'Error!', 
                text: 'Customer name is required!', 
                icon: 'error', 
                confirmButtonColor: '#1e7e34' 
            });
            return false;
        }
        if(mobile === '') {
            e.preventDefault();
            Swal.fire({ 
                title: 'Error!', 
                text: 'Mobile number is required!', 
                icon: 'error', 
                confirmButtonColor: '#1e7e34' 
            });
            return false;
        }
        return true;
    });
</script>
</body>
</html>
