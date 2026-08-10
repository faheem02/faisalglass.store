<?php
/**
 * Add Company Page
 * Faysal Glass And Aluminium Centre
 * 
 * Add and manage companies/brands
 * Page: Add Company
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$page_title = "Add Company";
$success_msg = '';
$error_msg = '';

// Handle Save Company
if(isset($_POST['save_company'])) {
    $company_name = mysqli_real_escape_string($conn, trim($_POST['company_name']));
    $contact_person = mysqli_real_escape_string($conn, trim($_POST['contact_person']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $address = mysqli_real_escape_string($conn, trim($_POST['address']));
    $status = isset($_POST['status']) ? 1 : 0;
    
    // Check for duplicate
    $check_query = "SELECT id FROM companies WHERE company_name = '$company_name'";
    $check_result = mysqli_query($conn, $check_query);
    
    if(mysqli_num_rows($check_result) > 0) {
        $error_msg = "Company name already exists!";
    } else {
        $insert_query = "INSERT INTO companies (company_name, contact_person, phone, address, status) 
                         VALUES ('$company_name', '$contact_person', '$phone', '$address', '$status')";
        if(mysqli_query($conn, $insert_query)) {
            $success_msg = "Company added successfully!";
        } else {
            $error_msg = "Failed to add company: " . mysqli_error($conn);
        }
    }
}

// Handle Delete Company
if(isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // Check if company is used in products
    $check_products = "SELECT id FROM products WHERE company_id = $delete_id LIMIT 1";
    $products_result = mysqli_query($conn, $check_products);
    
    if(mysqli_num_rows($products_result) > 0) {
        $error_msg = "Cannot delete! This company has products assigned to it.";
    } else {
        $delete_query = "DELETE FROM companies WHERE id = $delete_id";
        if(mysqli_query($conn, $delete_query)) {
            $success_msg = "Company deleted successfully!";
        } else {
            $error_msg = "Failed to delete company!";
        }
    }
}

// Handle Edit Company
$edit_company = null;
if(isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $edit_query = "SELECT * FROM companies WHERE id = $edit_id";
    $edit_result = mysqli_query($conn, $edit_query);
    $edit_company = mysqli_fetch_assoc($edit_result);
}

// Handle Update Company
if(isset($_POST['update_company'])) {
    $edit_id = intval($_POST['company_id']);
    $company_name = mysqli_real_escape_string($conn, trim($_POST['company_name']));
    $contact_person = mysqli_real_escape_string($conn, trim($_POST['contact_person']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $address = mysqli_real_escape_string($conn, trim($_POST['address']));
    $status = isset($_POST['status']) ? 1 : 0;
    
    // Check for duplicate excluding current
    $check_query = "SELECT id FROM companies WHERE company_name = '$company_name' AND id != $edit_id";
    $check_result = mysqli_query($conn, $check_query);
    
    if(mysqli_num_rows($check_result) > 0) {
        $error_msg = "Company name already exists!";
    } else {
        $update_query = "UPDATE companies SET company_name = '$company_name', contact_person = '$contact_person', 
                         phone = '$phone', address = '$address', status = '$status' WHERE id = $edit_id";
        if(mysqli_query($conn, $update_query)) {
            $success_msg = "Company updated successfully!";
            header("Location: company.php");
            exit();
        } else {
            $error_msg = "Failed to update company!";
        }
    }
}

// Fetch all companies
$companies_query = "SELECT * FROM companies ORDER BY id DESC";
$companies_result = mysqli_query($conn, $companies_query);
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
        .company-icon {
            font-size: 50px;
            color: #1e7e34;
            opacity: 0.5;
            position: absolute;
            right: 20px;
            bottom: 10px;
        }
        .form-container {
            position: relative;
            overflow: hidden;
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
                <i class="fas fa-building text-success mr-2"></i> Add Company / Brand
            </h1>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Products</a></li>
                <li class="breadcrumb-item active">Add Company</li>
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
        
        <!-- Add Company Form -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-plus-circle mr-2"></i> 
                <?php echo $edit_company ? 'Edit Company' : 'Add New Company / Brand'; ?>
            </div>
            <div class="card-body form-container">
                <div class="company-icon">
                    <i class="fas fa-building"></i>
                </div>
                <form method="POST" action="" id="companyForm">
                    <?php if($edit_company): ?>
                        <input type="hidden" name="company_id" value="<?php echo $edit_company['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-building text-success mr-1"></i> Company Name <span class="text-danger">*</span></label>
                                <input type="text" name="company_name" class="form-control" 
                                       value="<?php echo $edit_company ? htmlspecialchars($edit_company['company_name']) : ''; ?>" 
                                       placeholder="Enter company/brand name" required>
                                <small class="text-muted">Example: Al-Fayez Glass, Master Glass, etc.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-user text-success mr-1"></i> Contact Person</label>
                                <input type="text" name="contact_person" class="form-control" 
                                       value="<?php echo $edit_company ? htmlspecialchars($edit_company['contact_person']) : ''; ?>" 
                                       placeholder="Enter contact person name">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-phone text-success mr-1"></i> Phone Number</label>
                                <input type="tel" name="phone" class="form-control" 
                                       value="<?php echo $edit_company ? htmlspecialchars($edit_company['phone']) : ''; ?>" 
                                       placeholder="Enter phone number">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-toggle-on text-success mr-1"></i> Status</label>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="status" name="status" 
                                           <?php echo ($edit_company && $edit_company['status'] == 1) ? 'checked' : 'checked'; ?>>
                                    <label class="custom-control-label" for="status">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label><i class="fas fa-map-marker-alt text-success mr-1"></i> Address</label>
                                <textarea name="address" class="form-control" rows="3" 
                                          placeholder="Enter complete address"><?php echo $edit_company ? htmlspecialchars($edit_company['address']) : ''; ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 text-right">
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo-alt mr-1"></i> Reset
                            </button>
                            <?php if($edit_company): ?>
                                <a href="company.php" class="btn btn-info">
                                    <i class="fas fa-plus-circle mr-1"></i> Add New
                                </a>
                                <button type="submit" name="update_company" class="btn btn-green">
                                    <i class="fas fa-save mr-1"></i> Update Company
                                </button>
                            <?php else: ?>
                                <button type="submit" name="save_company" class="btn btn-green">
                                    <i class="fas fa-save mr-1"></i> Save Company
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Companies List Table -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-list mr-2"></i> Companies / Brands List
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="companiesTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Company Name</th>
                                <th>Contact Person</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($company = mysqli_fetch_assoc($companies_result)): ?>
                            <tr>
                                <td><?php echo $company['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($company['company_name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($company['contact_person']); ?></td>
                                <td><?php echo htmlspecialchars($company['phone']); ?></td>
                                <td><?php echo htmlspecialchars(substr($company['address'], 0, 50)) . (strlen($company['address']) > 50 ? '...' : ''); ?></td>
                                <td>
                                    <?php if($company['status'] == 1): ?>
                                        <span class="badge badge-success"><i class="fas fa-check-circle"></i> Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary"><i class="fas fa-times-circle"></i> Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="company.php?edit_id=<?php echo $company['id']; ?>" class="btn btn-sm btn-info" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $company['id']; ?>)" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($companies_result) == 0): ?>
                            <tr>
                                <td colspan="7" class="text-center">No companies found. Add your first company!</td>
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
$(document).ready(function() {
    $('#companiesTable').DataTable({
        "order": [[0, "desc"]],
        "language": {
            "search": "Search:",
            "lengthMenu": "Show _MENU_ entries",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries"
        }
    });
});

function confirmDelete(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this! This company will be deleted permanently.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'company.php?delete_id=' + id;
        }
    });
}

// Form validation
document.getElementById('companyForm').addEventListener('submit', function(e) {
    var companyName = document.querySelector('input[name="company_name"]').value.trim();
    if(companyName === '') {
        e.preventDefault();
        Swal.fire({
            title: 'Error!',
            text: 'Company name is required!',
            icon: 'error',
            confirmButtonColor: '#1e7e34'
        });
    }
});
</script>

</body>
</html>

<?php mysqli_close($conn); ?>