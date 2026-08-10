<?php
/**
 * Add Units Page
 * Faysal Glass And Aluminium Centre
 * 
 * Add and manage product units (Sheet, Sq Ft, Piece, Box, Meter, Kg)
 * Page: Add Units
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$page_title = "Add Units";
$success_msg = '';
$error_msg = '';

// Handle Save Unit
if(isset($_POST['save_unit'])) {
    $unit_name = mysqli_real_escape_string($conn, trim($_POST['unit_name']));
    $short_name = mysqli_real_escape_string($conn, trim($_POST['short_name']));
    
    // Check for duplicate
    $check_query = "SELECT id FROM units WHERE unit_name = '$unit_name' OR short_name = '$short_name'";
    $check_result = mysqli_query($conn, $check_query);
    
    if(mysqli_num_rows($check_result) > 0) {
        $error_msg = "Unit name or short name already exists!";
    } else {
        $insert_query = "INSERT INTO units (unit_name, short_name) VALUES ('$unit_name', '$short_name')";
        if(mysqli_query($conn, $insert_query)) {
            $success_msg = "Unit added successfully!";
        } else {
            $error_msg = "Failed to add unit: " . mysqli_error($conn);
        }
    }
}

// Handle Delete Unit
if(isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // Check if unit is used in products
    $check_products = "SELECT id FROM products WHERE unit_id = $delete_id LIMIT 1";
    $products_result = mysqli_query($conn, $check_products);
    
    if(mysqli_num_rows($products_result) > 0) {
        $error_msg = "Cannot delete! This unit has products assigned to it.";
    } else {
        $delete_query = "DELETE FROM units WHERE id = $delete_id";
        if(mysqli_query($conn, $delete_query)) {
            $success_msg = "Unit deleted successfully!";
        } else {
            $error_msg = "Failed to delete unit!";
        }
    }
}

// Handle Edit Unit
$edit_unit = null;
if(isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $edit_query = "SELECT * FROM units WHERE id = $edit_id";
    $edit_result = mysqli_query($conn, $edit_query);
    $edit_unit = mysqli_fetch_assoc($edit_result);
}

// Handle Update Unit
if(isset($_POST['update_unit'])) {
    $edit_id = intval($_POST['unit_id']);
    $unit_name = mysqli_real_escape_string($conn, trim($_POST['unit_name']));
    $short_name = mysqli_real_escape_string($conn, trim($_POST['short_name']));
    
    // Check for duplicate excluding current
    $check_query = "SELECT id FROM units WHERE (unit_name = '$unit_name' OR short_name = '$short_name') AND id != $edit_id";
    $check_result = mysqli_query($conn, $check_query);
    
    if(mysqli_num_rows($check_result) > 0) {
        $error_msg = "Unit name or short name already exists!";
    } else {
        $update_query = "UPDATE units SET unit_name = '$unit_name', short_name = '$short_name' WHERE id = $edit_id";
        if(mysqli_query($conn, $update_query)) {
            $success_msg = "Unit updated successfully!";
            header("Location: units.php");
            exit();
        } else {
            $error_msg = "Failed to update unit!";
        }
    }
}

// Fetch all units
$units_query = "SELECT * FROM units ORDER BY id DESC";
$units_result = mysqli_query($conn, $units_query);
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
        .example-badge {
            background: #e8f5e9;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            color: #1e7e34;
            margin-right: 8px;
            margin-bottom: 8px;
            display: inline-block;
        }
        .example-container {
            background: #f8f9fc;
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
                <i class="fas fa-ruler-combined text-success mr-2"></i> Add Units
            </h1>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Products</a></li>
                <li class="breadcrumb-item active">Add Units</li>
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
        
        <!-- Add Unit Form -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-plus-circle mr-2"></i> 
                <?php echo $edit_unit ? 'Edit Unit' : 'Add New Unit'; ?>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="unitForm">
                    <?php if($edit_unit): ?>
                        <input type="hidden" name="unit_id" value="<?php echo $edit_unit['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-tag text-success mr-1"></i> Unit Name <span class="text-danger">*</span></label>
                                <input type="text" name="unit_name" class="form-control" 
                                       value="<?php echo $edit_unit ? htmlspecialchars($edit_unit['unit_name']) : ''; ?>" 
                                       placeholder="Enter unit name (e.g., Sheet, Square Feet, Piece)" required>
                                <small class="text-muted">Examples: Sheet, Square Feet, Piece, Box, Meter, Kilogram</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-shortcode text-success mr-1"></i> Short Name <span class="text-danger">*</span></label>
                                <input type="text" name="short_name" class="form-control" 
                                       value="<?php echo $edit_unit ? htmlspecialchars($edit_unit['short_name']) : ''; ?>" 
                                       placeholder="Enter short name (e.g., Sht, Sq Ft, Pcs)" required>
                                <small class="text-muted">Examples: Sht, Sq Ft, Pcs, Box, M, Kg</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="example-container">
                        <label class="text-success font-weight-bold mb-2">
                            <i class="fas fa-lightbulb"></i> Common Examples:
                        </label>
                        <div>
                            <span class="example-badge"><i class="fas fa-check-circle text-success"></i> Sheet → Sht</span>
                            <span class="example-badge"><i class="fas fa-check-circle text-success"></i> Square Feet → Sq Ft</span>
                            <span class="example-badge"><i class="fas fa-check-circle text-success"></i> Piece → Pcs</span>
                            <span class="example-badge"><i class="fas fa-check-circle text-success"></i> Box → Box</span>
                            <span class="example-badge"><i class="fas fa-check-circle text-success"></i> Meter → M</span>
                            <span class="example-badge"><i class="fas fa-check-circle text-success"></i> Kilogram → Kg</span>
                            <span class="example-badge"><i class="fas fa-check-circle text-success"></i> Dozen → Doz</span>
                            <span class="example-badge"><i class="fas fa-check-circle text-success"></i> Roll → Rl</span>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-12 text-right">
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo-alt mr-1"></i> Reset
                            </button>
                            <?php if($edit_unit): ?>
                                <a href="units.php" class="btn btn-info">
                                    <i class="fas fa-plus-circle mr-1"></i> Add New
                                </a>
                                <button type="submit" name="update_unit" class="btn btn-green">
                                    <i class="fas fa-save mr-1"></i> Update Unit
                                </button>
                            <?php else: ?>
                                <button type="submit" name="save_unit" class="btn btn-green">
                                    <i class="fas fa-save mr-1"></i> Save Unit
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Units List Table -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-list mr-2"></i> Units List
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="unitsTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th width="5%">ID</th>
                                <th width="35%">Unit Name</th>
                                <th width="35%">Short Name</th>
                                <th width="25%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($unit = mysqli_fetch_assoc($units_result)): ?>
                            <tr>
                                <td><?php echo $unit['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($unit['unit_name']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge badge-primary badge-pill px-3 py-2">
                                        <?php echo htmlspecialchars($unit['short_name']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="units.php?edit_id=<?php echo $unit['id']; ?>" class="btn btn-sm btn-info" title="Edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $unit['id']; ?>)" title="Delete">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($units_result) == 0): ?>
                            <tr>
                                <td colspan="4" class="text-center">No units found. Add your first unit!</td>
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
    $('#unitsTable').DataTable({
        "order": [[0, "desc"]],
        "language": {
            "search": "Search:",
            "lengthMenu": "Show _MENU_ entries",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries"
        }
    });
    
    // Auto capitalize first letter of unit name
    $('input[name="unit_name"]').on('keyup', function() {
        var value = $(this).val();
        if(value.length > 0) {
            $(this).val(value.charAt(0).toUpperCase() + value.slice(1));
        }
    });
    
    // Auto uppercase short name
    $('input[name="short_name"]').on('keyup', function() {
        var value = $(this).val();
        $(this).val(value.toUpperCase());
    });
});

function confirmDelete(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this! This unit will be deleted permanently.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'units.php?delete_id=' + id;
        }
    });
}

// Form validation
document.getElementById('unitForm').addEventListener('submit', function(e) {
    var unitName = document.querySelector('input[name="unit_name"]').value.trim();
    var shortName = document.querySelector('input[name="short_name"]').value.trim();
    
    if(unitName === '') {
        e.preventDefault();
        Swal.fire({
            title: 'Error!',
            text: 'Unit name is required!',
            icon: 'error',
            confirmButtonColor: '#1e7e34'
        });
        return false;
    }
    
    if(shortName === '') {
        e.preventDefault();
        Swal.fire({
            title: 'Error!',
            text: 'Short name is required!',
            icon: 'error',
            confirmButtonColor: '#1e7e34'
        });
        return false;
    }
});
</script>

</body>
</html>

<?php mysqli_close($conn); ?>