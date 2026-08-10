<?php
/**
 * Edit Supplier Page
 * Faysal Glass And Aluminium Centre
 * 
 * Edit existing supplier information
 * Note: Opening balance cannot be edited after transactions
 * Page: Edit Supplier
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$page_title = "Edit Supplier";
$success_msg = '';
$error_msg = '';

// Check if supplier ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: supplier_view.php");
    exit();
}

$supplier_id = intval($_GET['id']);

// Fetch supplier details
$query = "SELECT * FROM suppliers WHERE id = $supplier_id";
$result = mysqli_query($conn, $query);

if(!$result || mysqli_num_rows($result) == 0) {
    header("Location: supplier_view.php");
    exit();
}

$supplier = mysqli_fetch_assoc($result);

// Check if supplier has any transactions
$has_transactions = false;
$transaction_check = "SELECT id FROM supplier_ledger WHERE supplier_id = $supplier_id AND reference_type != 'OPENING' LIMIT 1";
$transaction_result = mysqli_query($conn, $transaction_check);
if($transaction_result && mysqli_num_rows($transaction_result) > 0) {
    $has_transactions = true;
}

// Handle Update Supplier
if(isset($_POST['update_supplier'])) {
    $supplier_name = mysqli_real_escape_string($conn, trim($_POST['supplier_name']));
    $company_name = mysqli_real_escape_string($conn, trim($_POST['company_name']));
    $contact_person = mysqli_real_escape_string($conn, trim($_POST['contact_person']));
    $mobile = mysqli_real_escape_string($conn, trim($_POST['mobile']));
    $cnic = mysqli_real_escape_string($conn, trim($_POST['cnic']));
    $ntn = mysqli_real_escape_string($conn, trim($_POST['ntn']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $address = mysqli_real_escape_string($conn, trim($_POST['address']));
    $status = isset($_POST['status']) ? 1 : 0;
    $notes = mysqli_real_escape_string($conn, trim($_POST['notes']));
    
    // Opening balance fields (only if no transactions)
    $opening_balance = floatval($_POST['opening_balance']);
    $balance_type = $_POST['balance_type'];
    
    // Validation
    if(empty($supplier_name)) {
        $error_msg = "Supplier name is required!";
    } elseif(empty($mobile)) {
        $error_msg = "Mobile number is required!";
    } else {
        // Check for duplicate supplier name (excluding current)
        $check_query = "SELECT id FROM suppliers WHERE supplier_name = '$supplier_name' AND id != $supplier_id";
        $check_result = mysqli_query($conn, $check_query);
        
        if(mysqli_num_rows($check_result) > 0) {
            $error_msg = "Supplier name already exists!";
        } else {
            // Begin transaction if opening balance is being updated
            if(!$has_transactions) {
                mysqli_begin_transaction($conn);
            }
            
            try {
                // Update supplier basic info
                $update_query = "UPDATE suppliers SET 
                                supplier_name = '$supplier_name',
                                company_name = '$company_name',
                                contact_person = '$contact_person',
                                mobile = '$mobile',
                                cnic = '$cnic',
                                ntn = '$ntn',
                                email = '$email',
                                address = '$address',
                                status = '$status',
                                notes = '$notes'
                                WHERE id = $supplier_id";
                
                if(!mysqli_query($conn, $update_query)) {
                    throw new Exception("Failed to update supplier information");
                }
                
                // Update opening balance only if no transactions exist
                if(!$has_transactions) {
                    // Calculate current balance based on opening balance
                    $current_balance = $opening_balance;
                    
                    $update_balance_query = "UPDATE suppliers SET 
                                            opening_balance = '$opening_balance',
                                            balance_type = '$balance_type',
                                            current_balance = '$current_balance'
                                            WHERE id = $supplier_id";
                    
                    if(!mysqli_query($conn, $update_balance_query)) {
                        throw new Exception("Failed to update opening balance");
                    }
                    
                    // Update opening entry in supplier_ledger
                    // First delete existing opening entry
                    $delete_opening = "DELETE FROM supplier_ledger WHERE supplier_id = $supplier_id AND reference_type = 'OPENING'";
                    mysqli_query($conn, $delete_opening);
                    
                    // Create new opening entry
                    $current_date = date('Y-m-d');
                    $debit = 0;
                    $credit = 0;
                    $description = "";
                    
                    if($balance_type == 'payable' && $opening_balance > 0) {
                        $credit = $opening_balance;
                        $description = "Opening Balance - Payable (Company owes supplier)";
                    } elseif($balance_type == 'receivable' && $opening_balance > 0) {
                        $debit = $opening_balance;
                        $description = "Opening Balance - Receivable (Supplier owes company)";
                    }
                    
                    if($opening_balance > 0) {
                        $ledger_query = "INSERT INTO supplier_ledger (date, supplier_id, reference_type, reference_id, 
                                          description, debit, credit, balance) 
                                          VALUES ('$current_date', '$supplier_id', 'OPENING', '$supplier_id', 
                                          '$description', '$debit', '$credit', '$current_balance')";
                        
                        if(!mysqli_query($conn, $ledger_query)) {
                            throw new Exception("Failed to update opening ledger entry");
                        }
                    }
                    
                    mysqli_commit($conn);
                    $success_msg = "Supplier information and opening balance updated successfully!";
                } else {
                    $success_msg = "Supplier information updated successfully! (Opening balance cannot be changed as transactions exist)";
                }
                
                // Refresh supplier data
                $refresh_query = "SELECT * FROM suppliers WHERE id = $supplier_id";
                $refresh_result = mysqli_query($conn, $refresh_query);
                $supplier = mysqli_fetch_assoc($refresh_result);
                
                echo "<script>setTimeout(() => { window.location.href = 'supplier_detail.php?id=$supplier_id'; }, 2000);</script>";
                
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
        .supplier-code {
            font-family: monospace;
            font-size: 18px;
            font-weight: bold;
            color: #0066cc;
        }
        .disabled-field {
            background-color: #e9ecef;
            opacity: 0.7;
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
                <i class="fas fa-edit text-success mr-2"></i> Edit Supplier
            </h1>
            <div>
                <a href="supplier_detail.php?id=<?php echo $supplier_id; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Detail
                </a>
                <a href="supplier_view.php" class="btn btn-info ml-2">
                    <i class="fas fa-list mr-1"></i> All Suppliers
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
        
        <!-- Warning for suppliers with transactions -->
        <?php if($has_transactions): ?>
        <div class="warning-box">
            <i class="fas fa-exclamation-triangle text-warning mr-2"></i>
            <strong>Note:</strong> This supplier has existing purchase or payment transactions. 
            Opening balance cannot be edited after transactions are recorded. 
            You can only edit basic information like name, contact, and address.
        </div>
        <?php endif; ?>
        
        <!-- Edit Supplier Form -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-pen mr-2"></i> Edit Supplier Information
            </div>
            <div class="card-body">
                <form method="POST" action="" id="supplierForm">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="info-box">
                                <div class="row">
                                    <div class="col-md-6">
                                        <small class="text-muted">Supplier Code</small>
                                        <div class="supplier-code">
                                            <i class="fas fa-barcode mr-2"></i><?php echo $supplier['supplier_code']; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">Created Date</small>
                                        <div class="info-value">
                                            <i class="fas fa-calendar-alt mr-2"></i><?php echo date('d-m-Y H:i:s', strtotime($supplier['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-user text-success mr-1"></i> Supplier Name</label>
                                <input type="text" name="supplier_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($supplier['supplier_name']); ?>" 
                                       placeholder="Enter supplier name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-building text-success mr-1"></i> Company Name</label>
                                <input type="text" name="company_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($supplier['company_name']); ?>" 
                                       placeholder="Enter company name">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-user-tie text-success mr-1"></i> Contact Person</label>
                                <input type="text" name="contact_person" class="form-control" 
                                       value="<?php echo htmlspecialchars($supplier['contact_person']); ?>" 
                                       placeholder="Enter contact person name">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-phone text-success mr-1"></i> Mobile Number</label>
                                <input type="tel" name="mobile" class="form-control" 
                                       value="<?php echo htmlspecialchars($supplier['mobile']); ?>" 
                                       placeholder="Enter mobile number" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-id-card text-success mr-1"></i> CNIC</label>
                                <input type="text" name="cnic" class="form-control" 
                                       value="<?php echo htmlspecialchars($supplier['cnic']); ?>" 
                                       placeholder="Enter CNIC number">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-file-invoice text-success mr-1"></i> NTN (Optional)</label>
                                <input type="text" name="ntn" class="form-control" 
                                       value="<?php echo htmlspecialchars($supplier['ntn']); ?>" 
                                       placeholder="Enter NTN number">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-envelope text-success mr-1"></i> Email</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($supplier['email']); ?>" 
                                       placeholder="Enter email address">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-toggle-on text-success mr-1"></i> Status</label>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="status" name="status" 
                                           <?php echo ($supplier['status'] == 1) ? 'checked' : ''; ?>>
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
                                          placeholder="Enter complete address"><?php echo htmlspecialchars($supplier['address']); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Opening Balance Section (Disabled if transactions exist) -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-money-bill-wave text-success mr-1"></i> Opening Balance (₨)</label>
                                <input type="number" step="0.01" name="opening_balance" class="form-control <?php echo $has_transactions ? 'disabled-field' : ''; ?>" 
                                       value="<?php echo $supplier['opening_balance']; ?>" 
                                       placeholder="Enter opening balance"
                                       <?php echo $has_transactions ? 'readonly disabled' : ''; ?>>
                                <?php if($has_transactions): ?>
                                    <small class="text-muted">Cannot edit - Transactions exist</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-balance-scale text-success mr-1"></i> Balance Type</label>
                                <select name="balance_type" class="form-control <?php echo $has_transactions ? 'disabled-field' : ''; ?>" 
                                        <?php echo $has_transactions ? 'disabled' : ''; ?>>
                                    <option value="payable" <?php echo ($supplier['balance_type'] == 'payable') ? 'selected' : ''; ?>>Payable (+) - Company owes supplier</option>
                                    <option value="receivable" <?php echo ($supplier['balance_type'] == 'receivable') ? 'selected' : ''; ?>>Receivable (-) - Supplier owes company</option>
                                </select>
                                <?php if($has_transactions): ?>
                                    <input type="hidden" name="balance_type" value="<?php echo $supplier['balance_type']; ?>">
                                    <input type="hidden" name="opening_balance" value="<?php echo $supplier['opening_balance']; ?>">
                                    <small class="text-muted">Cannot edit - Transactions exist</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-chart-line text-success mr-1"></i> Current Balance</label>
                                <input type="text" class="form-control disabled-field" 
                                       value="<?php echo formatCurrency($supplier['current_balance']) . ' (' . ucfirst($supplier['balance_type']) . ')'; ?>" 
                                       readonly disabled>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label><i class="fas fa-sticky-note text-success mr-1"></i> Notes (Optional)</label>
                                <textarea name="notes" class="form-control" rows="3" 
                                          placeholder="Enter any additional notes"><?php echo htmlspecialchars($supplier['notes']); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-12 text-right">
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo-alt mr-1"></i> Reset
                            </button>
                            <button type="submit" name="update_supplier" class="btn btn-green">
                                <i class="fas fa-save mr-1"></i> Update Supplier
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
                            <p class="small text-muted">Once purchase or payment transactions exist, opening balance cannot be edited to maintain accounting integrity.</p>
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
// Form validation
$('#supplierForm').on('submit', function(e) {
    var supplierName = $('input[name="supplier_name"]').val().trim();
    var mobile = $('input[name="mobile"]').val().trim();
    
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
        text: 'Are you sure you want to update this supplier information?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1e7e34',
        cancelButtonColor: '#dc3545',
        confirmButtonText: 'Yes, Update!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $('#supplierForm').off('submit').submit();
        }
    });
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