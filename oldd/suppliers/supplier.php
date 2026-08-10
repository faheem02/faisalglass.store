<?php
/**
 * Add Supplier Page
 * Faysal Glass And Aluminium Centre
 * 
 * Add new suppliers with opening balance
 * Page: Add Supplier Ledgers
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$page_title = "Add Supplier";
$success_msg = '';
$error_msg = '';

// Generate Supplier Code
function generateSupplierCode($conn) {
    $prefix = "SUP";
    $query = "SELECT supplier_code FROM suppliers WHERE supplier_code LIKE '{$prefix}%' ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $last_code = $row['supplier_code'];
        $number = intval(substr($last_code, 4)) + 1;
        return $prefix . "-" . str_pad($number, 4, '0', STR_PAD_LEFT);
    } else {
        return $prefix . "-0001";
    }
}

// Handle Save Supplier
if(isset($_POST['save_supplier'])) {
    $supplier_name = mysqli_real_escape_string($conn, trim($_POST['supplier_name']));
    $company_name = mysqli_real_escape_string($conn, trim($_POST['company_name']));
    $contact_person = mysqli_real_escape_string($conn, trim($_POST['contact_person']));
    $mobile = mysqli_real_escape_string($conn, trim($_POST['mobile']));
    $cnic = mysqli_real_escape_string($conn, trim($_POST['cnic']));
    $ntn = mysqli_real_escape_string($conn, trim($_POST['ntn']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $address = mysqli_real_escape_string($conn, trim($_POST['address']));
    $opening_balance = floatval($_POST['opening_balance']);
    $balance_type = $_POST['balance_type'];
    $status = isset($_POST['status']) ? 1 : 0;
    $notes = mysqli_real_escape_string($conn, trim($_POST['notes']));
    
    // Validation
    if(empty($supplier_name)) {
        $error_msg = "Supplier name is required!";
    } elseif(empty($mobile)) {
        $error_msg = "Mobile number is required!";
    } else {
        // Check for duplicate supplier name
        $check_query = "SELECT id FROM suppliers WHERE supplier_name = '$supplier_name'";
        $check_result = mysqli_query($conn, $check_query);
        
        if(mysqli_num_rows($check_result) > 0) {
            $error_msg = "Supplier name already exists!";
        } else {
            // Calculate current balance based on opening balance and type
            $current_balance = $opening_balance;
            
            // Generate supplier code
            $supplier_code = generateSupplierCode($conn);
            
            // Insert supplier
            $insert_query = "INSERT INTO suppliers (supplier_code, supplier_name, company_name, contact_person, 
                              mobile, cnic, ntn, email, address, opening_balance, balance_type, current_balance, 
                              status, notes) 
                              VALUES ('$supplier_code', '$supplier_name', '$company_name', '$contact_person', 
                              '$mobile', '$cnic', '$ntn', '$email', '$address', '$opening_balance', '$balance_type', 
                              '$current_balance', '$status', '$notes')";
            
            if(mysqli_query($conn, $insert_query)) {
                $supplier_id = mysqli_insert_id($conn);
                $current_date = date('Y-m-d');
                
                // Create opening entry in supplier ledger
                $debit = 0;
                $credit = 0;
                $description = "";
                
                if($balance_type == 'payable' && $opening_balance > 0) {
                    // Company owes supplier - Credit entry
                    $credit = $opening_balance;
                    $description = "Opening Balance - Payable (Company owes supplier)";
                } elseif($balance_type == 'receivable' && $opening_balance > 0) {
                    // Supplier owes company - Debit entry
                    $debit = $opening_balance;
                    $description = "Opening Balance - Receivable (Supplier owes company)";
                }
                
                if($opening_balance > 0) {
                    $ledger_query = "INSERT INTO supplier_ledger (date, supplier_id, reference_type, reference_id, 
                                      description, debit, credit, balance) 
                                      VALUES ('$current_date', '$supplier_id', 'OPENING', '$supplier_id', 
                                      '$description', '$debit', '$credit', '$current_balance')";
                    mysqli_query($conn, $ledger_query);
                }
                
                $success_msg = "Supplier added successfully! Supplier Code: $supplier_code";
                
                // Redirect after 2 seconds
                echo "<script>setTimeout(() => { window.location.href = 'supplier_view.php'; }, 2000);</script>";
            } else {
                $error_msg = "Failed to add supplier: " . mysqli_error($conn);
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
        .balance-info {
            background: #f8f9fc;
            border-left: 4px solid #1e7e34;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }
        .preview-code {
            background: #e8f5e9;
            padding: 8px 15px;
            border-radius: 8px;
            display: inline-block;
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
                <i class="fas fa-truck text-success mr-2"></i> Add Supplier Ledgers
            </h1>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Supplier Ledger</a></li>
                <li class="breadcrumb-item active">Add Supplier</li>
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
        
        <!-- Add Supplier Form -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-plus-circle mr-2"></i> Add New Supplier
            </div>
            <div class="card-body">
                <form method="POST" action="" id="supplierForm">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="preview-code">
                                <i class="fas fa-barcode mr-2"></i> Auto-generated Supplier Code: 
                                <strong><?php echo generateSupplierCode($conn); ?></strong>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-user text-success mr-1"></i> Supplier Name</label>
                                <input type="text" name="supplier_name" class="form-control" 
                                       placeholder="Enter supplier name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-building text-success mr-1"></i> Company Name</label>
                                <input type="text" name="company_name" class="form-control" 
                                       placeholder="Enter company name">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-user-tie text-success mr-1"></i> Contact Person</label>
                                <input type="text" name="contact_person" class="form-control" 
                                       placeholder="Enter contact person name">
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
                                <label><i class="fas fa-id-card text-success mr-1"></i> CNIC</label>
                                <input type="text" name="cnic" class="form-control" 
                                       placeholder="Enter CNIC number">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-file-invoice text-success mr-1"></i> NTN (Optional)</label>
                                <input type="text" name="ntn" class="form-control" 
                                       placeholder="Enter NTN number">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-envelope text-success mr-1"></i> Email</label>
                                <input type="email" name="email" class="form-control" 
                                       placeholder="Enter email address">
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
                                <label><i class="fas fa-map-marker-alt text-success mr-1"></i> Address</label>
                                <textarea name="address" class="form-control" rows="2" 
                                          placeholder="Enter complete address"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-money-bill-wave text-success mr-1"></i> Opening Balance (₨)</label>
                                <input type="number" step="0.01" name="opening_balance" class="form-control" 
                                       placeholder="Enter opening balance" value="0" id="openingBalance">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-balance-scale text-success mr-1"></i> Balance Type</label>
                                <select name="balance_type" class="form-control" id="balanceType">
                                    <option value="payable">Payable (+) - Company owes supplier</option>
                                    <option value="receivable">Receivable (-) - Supplier owes company</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-sticky-note text-success mr-1"></i> Notes (Optional)</label>
                                <input type="text" name="notes" class="form-control" 
                                       placeholder="Any additional notes">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Balance Information Box -->
                    <div class="balance-info" id="balanceInfo">
                        <div class="row">
                            <div class="col-md-6">
                                <i class="fas fa-info-circle text-success"></i> 
                                <strong>Positive Balance (+):</strong> Company owes supplier (Payable)
                            </div>
                            <div class="col-md-6">
                                <i class="fas fa-info-circle text-danger"></i> 
                                <strong>Negative Balance (-):</strong> Supplier owes company (Receivable)
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <div id="balanceExplanation" class="text-muted small">
                                    <i class="fas fa-lightbulb"></i> 
                                    Selected: <strong id="selectedType">Payable</strong> - 
                                    <span id="selectedExplanation">Company owes supplier this amount</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-12 text-right">
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo-alt mr-1"></i> Reset
                            </button>
                            <button type="submit" name="save_supplier" class="btn btn-green">
                                <i class="fas fa-save mr-1"></i> Save Supplier
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
                    <div class="col-md-4">
                        <div class="text-center">
                            <i class="fas fa-plus-circle fa-2x text-success mb-2"></i>
                            <h6>Positive Balance (+)</h6>
                            <p class="small text-muted">Payable: Company owes supplier<br>Appears in Accounts Payable</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <i class="fas fa-minus-circle fa-2x text-danger mb-2"></i>
                            <h6>Negative Balance (-)</h6>
                            <p class="small text-muted">Receivable: Supplier owes company<br>Appears in Accounts Receivable</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <i class="fas fa-chart-line fa-2x text-success mb-2"></i>
                            <h6>Auto Ledger Entry</h6>
                            <p class="small text-muted">Opening balance automatically creates supplier ledger entry</p>
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
$(document).ready(function() {
    // Update balance explanation when balance type changes
    $('#balanceType').on('change', function() {
        var selected = $(this).val();
        if(selected === 'payable') {
            $('#selectedType').text('Payable').css('color', '#1e7e34');
            $('#selectedExplanation').text('Company owes supplier this amount (Credit)');
        } else {
            $('#selectedType').text('Receivable').css('color', '#dc3545');
            $('#selectedExplanation').text('Supplier owes company this amount (Debit)');
        }
    });
    
    // Trigger on load
    $('#balanceType').trigger('change');
});

// Form validation
$('#supplierForm').on('submit', function(e) {
    var supplierName = $('input[name="supplier_name"]').val().trim();
    var mobile = $('input[name="mobile"]').val().trim();
    var openingBalance = parseFloat($('#openingBalance').val());
    
    if(supplierName === '') {
        e.preventDefault();
        Swal.fire({ title: 'Error!', text: 'Supplier name is required!', icon: 'error', confirmButtonColor: '#1e7e34' });
        return false;
    }
    
    if(mobile === '') {
        e.preventDefault();
        Swal.fire({ title: 'Error!', text: 'Mobile number is required!', icon: 'error', confirmButtonColor: '#1e7e34' });
        return false;
    }
    
    // Validate mobile number (basic)
    var mobileRegex = /^[0-9+\-\s]{10,15}$/;
    if(!mobileRegex.test(mobile)) {
        e.preventDefault();
        Swal.fire({ title: 'Error!', text: 'Please enter a valid mobile number!', icon: 'error', confirmButtonColor: '#1e7e34' });
        return false;
    }
    
    // Validate opening balance
    if(isNaN(openingBalance) || openingBalance < 0) {
        e.preventDefault();
        Swal.fire({ title: 'Error!', text: 'Opening balance cannot be negative!', icon: 'error', confirmButtonColor: '#1e7e34' });
        return false;
    }
});

// Capitalize first letter of supplier name
$('input[name="supplier_name"]').on('keyup', function() {
    var value = $(this).val();
    if(value.length > 0) {
        $(this).val(value.charAt(0).toUpperCase() + value.slice(1));
    }
});
</script>

</body>
</html>

<?php mysqli_close($conn); ?>