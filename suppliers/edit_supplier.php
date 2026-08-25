<?php
/**
 * Edit Supplier Page
 * Faysal Glass And Aluminium Centre
 * 
 * Edit existing supplier information
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

// Handle Update Supplier
if(isset($_POST['update_supplier'])) {
    $supplier_name = mysqli_real_escape_string($conn, trim($_POST['supplier_name']));
    $company_name = mysqli_real_escape_string($conn, trim($_POST['company_name'] ?? ''));
    $contact_person = mysqli_real_escape_string($conn, trim($_POST['contact_person'] ?? ''));
    $mobile = mysqli_real_escape_string($conn, trim($_POST['mobile']));
    $cnic = mysqli_real_escape_string($conn, trim($_POST['cnic'] ?? ''));
    $ntn = mysqli_real_escape_string($conn, trim($_POST['ntn'] ?? ''));
    $email = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
    $address = mysqli_real_escape_string($conn, trim($_POST['address'] ?? ''));
    $status = isset($_POST['status']) ? 1 : 0;
    $notes = mysqli_real_escape_string($conn, trim($_POST['notes'] ?? ''));
    
    // Opening balance fields
    $opening_balance = floatval($_POST['opening_balance'] ?? 0);
    $balance_type = in_array($_POST['balance_type'] ?? '', ['payable', 'receivable']) ? $_POST['balance_type'] : 'payable';
    
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
            mysqli_begin_transaction($conn);
            
            try {
                // Update supplier basic and opening balance info
                $update_query = "UPDATE suppliers SET 
                                supplier_name = '$supplier_name',
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
                                WHERE id = $supplier_id";
                
                if(!mysqli_query($conn, $update_query)) {
                    throw new Exception("Failed to update supplier information: " . mysqli_error($conn));
                }
                
                // Update opening entry in supplier_ledger
                // Delete existing opening entry
                $delete_opening = "DELETE FROM supplier_ledger WHERE supplier_id = $supplier_id AND reference_type = 'OPENING'";
                mysqli_query($conn, $delete_opening);
                
                // Create new opening entry if balance > 0
                if($opening_balance > 0) {
                    $current_date = date('Y-m-d');
                    $debit = 0;
                    $credit = 0;
                    $description = "";
                    
                    if($balance_type == 'payable') {
                        $credit = $opening_balance;
                        $description = "Opening Balance - Payable (Company owes supplier)";
                    } elseif($balance_type == 'receivable') {
                        $debit = $opening_balance;
                        $description = "Opening Balance - Receivable (Supplier owes company)";
                    }
                    
                    $ledger_query = "INSERT INTO supplier_ledger (date, supplier_id, reference_type, reference_id, 
                                      description, debit, credit, balance) 
                                      VALUES ('$current_date', '$supplier_id', 'OPENING', '$supplier_id', 
                                      '$description', '$debit', '$credit', '$opening_balance')";
                    
                    if(!mysqli_query($conn, $ledger_query)) {
                        throw new Exception("Failed to update opening ledger entry: " . mysqli_error($conn));
                    }
                }
                
                // Recalculate net current_balance from supplier_ledger
                $ledger_calc = mysqli_query($conn, "SELECT COALESCE(SUM(credit) - SUM(debit), 0) as net_bal FROM supplier_ledger WHERE supplier_id = $supplier_id");
                $new_current_balance = floatval(mysqli_fetch_assoc($ledger_calc)['net_bal'] ?? 0);
                
                mysqli_query($conn, "UPDATE suppliers SET current_balance = '$new_current_balance' WHERE id = $supplier_id");
                
                mysqli_commit($conn);
                
                $success_msg = "Supplier information and opening balance updated successfully!";
                
                // Refresh supplier data
                $result = mysqli_query($conn, "SELECT * FROM suppliers WHERE id = $supplier_id");
                $supplier = mysqli_fetch_assoc($result);
                
            } catch(Exception $e) {
                mysqli_rollback($conn);
                $error_msg = $e->getMessage();
            }
        }
    }
}

// Function to format currency
if(!function_exists('formatCurrency')) {
    function formatCurrency($amount) {
        return 'Rs ' . number_format(abs($amount), 2);
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
                        <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
                    </span>
                    <a href="../logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
            
            <div class="container-fluid mt-4">
                
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-truck text-success mr-2"></i> Edit Supplier
                    </h1>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="supplier_view.php">Suppliers</a></li>
                        <li class="breadcrumb-item active">Edit Supplier</li>
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
                        <i class="fas fa-user-edit mr-2"></i> Edit Supplier Information
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="supplierForm">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <div class="preview-code">
                                        <i class="fas fa-barcode mr-2"></i> Supplier Code: 
                                        <strong><?php echo htmlspecialchars($supplier['supplier_code']); ?></strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="required-field">
                                            <i class="fas fa-user text-success mr-1"></i> Supplier Name
                                        </label>
                                        <input type="text" name="supplier_name" class="form-control" 
                                               value="<?php echo htmlspecialchars($supplier['supplier_name']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>
                                            <i class="fas fa-building text-success mr-1"></i> Company Name
                                        </label>
                                        <input type="text" name="company_name" class="form-control" 
                                               value="<?php echo htmlspecialchars($supplier['company_name'] ?? ''); ?>"
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
                                               value="<?php echo htmlspecialchars($supplier['contact_person'] ?? ''); ?>"
                                               placeholder="Enter contact person">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="required-field">
                                            <i class="fas fa-phone text-success mr-1"></i> Mobile Number
                                        </label>
                                        <input type="tel" name="mobile" class="form-control" 
                                               value="<?php echo htmlspecialchars($supplier['mobile']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>
                                            <i class="fas fa-id-card text-success mr-1"></i> CNIC
                                        </label>
                                        <input type="text" name="cnic" class="form-control" 
                                               value="<?php echo htmlspecialchars($supplier['cnic'] ?? ''); ?>"
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
                                               value="<?php echo htmlspecialchars($supplier['ntn'] ?? ''); ?>"
                                               placeholder="Enter NTN number">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>
                                            <i class="fas fa-envelope text-success mr-1"></i> Email
                                        </label>
                                        <input type="email" name="email" class="form-control" 
                                               value="<?php echo htmlspecialchars($supplier['email'] ?? ''); ?>"
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
                                                   name="status" <?php echo ($supplier['status'] == 1) ? 'checked' : ''; ?>>
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
                                                  placeholder="Enter complete address"><?php echo htmlspecialchars($supplier['address'] ?? ''); ?></textarea>
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
                                               value="<?php echo htmlspecialchars($supplier['opening_balance'] ?? 0); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>
                                            <i class="fas fa-balance-scale text-success mr-1"></i> Balance Type
                                        </label>
                                        <select name="balance_type" class="form-control" id="balanceType">
                                            <option value="payable" <?php echo (($supplier['balance_type'] ?? 'payable') == 'payable') ? 'selected' : ''; ?>>
                                                Payable (+) - Company owes supplier
                                            </option>
                                            <option value="receivable" <?php echo (($supplier['balance_type'] ?? '') == 'receivable') ? 'selected' : ''; ?>>
                                                Receivable (-) - Supplier owes company
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>
                                            <i class="fas fa-chart-line text-success mr-1"></i> Current Net Balance
                                        </label>
                                        <input type="text" class="form-control bg-light font-weight-bold" 
                                               value="<?php echo formatCurrency($supplier['current_balance']) . ' (' . ucfirst($supplier['balance_type']) . ')'; ?>" 
                                               readonly>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="balance-info" id="balanceInfo">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div id="balanceExplanation" class="text-muted small">
                                            <i class="fas fa-lightbulb"></i> Selected: 
                                            <strong id="selectedType">Payable</strong> - 
                                            <span id="selectedExplanation">Company owes supplier this amount (Credit)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>
                                            <i class="fas fa-sticky-note text-success mr-1"></i> Notes
                                        </label>
                                        <textarea name="notes" class="form-control" rows="2" 
                                                  placeholder="Any additional notes"><?php echo htmlspecialchars($supplier['notes'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-md-12 text-right">
                                    <a href="supplier_view.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left mr-1"></i> Back to Suppliers
                                    </a>
                                    <button type="submit" name="update_supplier" class="btn btn-green ml-2">
                                        <i class="fas fa-save mr-1"></i> Update Supplier
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
        if(type === 'payable') {
            $('#selectedType').text('Payable (+)').css('color', '#1e7e34');
            $('#selectedExplanation').text('Company owes supplier this amount (Credit in ledger)');
            $('#balanceInfo').css('border-left-color', '#1e7e34');
        } else {
            $('#selectedType').text('Receivable (-)').css('color', '#dc3545');
            $('#selectedExplanation').text('Supplier owes company this amount / Advance (Debit in ledger)');
            $('#balanceInfo').css('border-left-color', '#dc3545');
        }
    }
    
    $('#balanceType').on('change', updateBalanceExplanation);
    $(document).ready(function() {
        updateBalanceExplanation();
    });

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
        return true;
    });
</script>
</body>
</html>
