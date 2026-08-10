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
    $company_name = mysqli_real_escape_string($conn, trim($_POST['company_name']));
    $mobile = mysqli_real_escape_string($conn, trim($_POST['mobile']));
    $cnic = mysqli_real_escape_string($conn, trim($_POST['cnic']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $address = mysqli_real_escape_string($conn, trim($_POST['address']));
    $status = isset($_POST['status']) ? 1 : 0;
    $notes = mysqli_real_escape_string($conn, trim($_POST['notes']));
    
    // Validation
    if(empty($customer_name)) {
        $error_msg = "Customer name is required!";
    } elseif(empty($mobile)) {
        $error_msg = "Mobile number is required!";
    } else {
        // Update customer
        $update_query = "UPDATE customers SET 
                            customer_name = '$customer_name',
                            company_name = '$company_name',
                            mobile = '$mobile',
                            cnic = '$cnic',
                            email = '$email',
                            address = '$address',
                            status = '$status',
                            notes = '$notes'
                        WHERE id = $customer_id";
        
        if(mysqli_query($conn, $update_query)) {
            $success_msg = "Customer updated successfully!";
            // Refresh customer data
            $query = "SELECT * FROM customers WHERE id = $customer_id";
            $result = mysqli_query($conn, $query);
            $customer = mysqli_fetch_assoc($result);
        } else {
            $error_msg = "Failed to update customer: " . mysqli_error($conn);
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
        .card-header-custom { background: linear-gradient(135deg, #1e7e34, #0066cc); color: white; border-radius: 10px 10px 0 0; padding: 15px 20px; }
        .form-card { border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 30px; }
        .required-field::after { content: " *"; color: red; }
        .topbar {
            height: 60px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <div class="topbar">
                    <div class="welcome-text" style="color: #1e7e34;">
                        <i class="fas fa-store"></i> <?php echo $software_name; ?>
                    </div>
                    <div class="user-info">
                        <span style="color: #4e73df;">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user_name); ?>
                        </span>
                        <a href="../logout.php" style="color: #dc3545; text-decoration: none;">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
                
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4 mt-4">
                        <h1 class="h3 mb-0" style="color: #1e7e34;">
                            <i class="fas fa-edit"></i> Edit Customer
                        </h1>
                        <a href="view_customer.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Customers
                        </a>
                    </div>
                    
                    <?php if($success_msg): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle"></i> <?php echo $success_msg; ?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($error_msg): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card form-card">
                        <div class="card-header-custom">
                            <i class="fas fa-user-edit"></i> Edit Customer Information
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" id="customerForm">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <div class="alert alert-info">
                                            <i class="fas fa-barcode"></i> Customer Code: 
                                            <strong><?php echo htmlspecialchars($customer['customer_code']); ?></strong>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="required-field">Customer Name</label>
                                            <input type="text" name="customer_name" class="form-control" 
                                                   value="<?php echo htmlspecialchars($customer['customer_name']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Company Name</label>
                                            <input type="text" name="company_name" class="form-control" 
                                                   value="<?php echo htmlspecialchars($customer['company_name'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="required-field">Mobile Number</label>
                                            <input type="tel" name="mobile" class="form-control" 
                                                   value="<?php echo htmlspecialchars($customer['mobile']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>CNIC</label>
                                            <input type="text" name="cnic" class="form-control" 
                                                   value="<?php echo htmlspecialchars($customer['cnic'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Email</label>
                                            <input type="email" name="email" class="form-control" 
                                                   value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Status</label>
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="status" 
                                                       name="status" <?php echo ($customer['status'] == 1) ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="status">Active</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Address</label>
                                            <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($customer['address'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Notes</label>
                                            <textarea name="notes" class="form-control" rows="2"><?php echo htmlspecialchars($customer['notes'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mt-4">
                                    <div class="col-md-12 text-right">
                                        <a href="view_customer.php" class="btn btn-secondary">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                        <button type="submit" name="update_customer" class="btn btn-green ml-2">
                                            <i class="fas fa-save"></i> Update Customer
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