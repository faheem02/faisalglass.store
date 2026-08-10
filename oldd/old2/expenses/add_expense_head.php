<?php
/**
 * Add Expense Head Page
 * Faysal Glass And Aluminium Centre
 * 
 * Create and manage expense categories
 * Page: Add Expense Head
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$page_title = "Add Expense Head";
$success_msg = '';
$error_msg = '';

// Handle Save Expense Head
if(isset($_POST['save_head'])) {
    $head_name = mysqli_real_escape_string($conn, trim($_POST['head_name']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $status = isset($_POST['status']) ? 1 : 0;
    
    // Check for duplicate
    $check_query = "SELECT id FROM expense_heads WHERE head_name = '$head_name'";
    $check_result = mysqli_query($conn, $check_query);
    
    if(mysqli_num_rows($check_result) > 0) {
        $error_msg = "Expense head name already exists!";
    } else {
        $insert_query = "INSERT INTO expense_heads (head_name, description, status) VALUES ('$head_name', '$description', '$status')";
        if(mysqli_query($conn, $insert_query)) {
            $success_msg = "Expense head added successfully!";
        } else {
            $error_msg = "Failed to add expense head: " . mysqli_error($conn);
        }
    }
}

// Handle Delete Expense Head
if(isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // Check if head is used in expenses
    $check_expenses = "SELECT id FROM expenses WHERE head_id = $delete_id LIMIT 1";
    $expenses_result = mysqli_query($conn, $check_expenses);
    
    if(mysqli_num_rows($expenses_result) > 0) {
        $error_msg = "Cannot delete! This expense head has expenses recorded against it.";
    } else {
        $delete_query = "DELETE FROM expense_heads WHERE id = $delete_id";
        if(mysqli_query($conn, $delete_query)) {
            $success_msg = "Expense head deleted successfully!";
        } else {
            $error_msg = "Failed to delete expense head!";
        }
    }
}

// Handle Edit Expense Head
$edit_head = null;
if(isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $edit_query = "SELECT * FROM expense_heads WHERE id = $edit_id";
    $edit_result = mysqli_query($conn, $edit_query);
    $edit_head = mysqli_fetch_assoc($edit_result);
}

// Handle Update Expense Head
if(isset($_POST['update_head'])) {
    $edit_id = intval($_POST['head_id']);
    $head_name = mysqli_real_escape_string($conn, trim($_POST['head_name']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $status = isset($_POST['status']) ? 1 : 0;
    
    // Check for duplicate excluding current
    $check_query = "SELECT id FROM expense_heads WHERE head_name = '$head_name' AND id != $edit_id";
    $check_result = mysqli_query($conn, $check_query);
    
    if(mysqli_num_rows($check_result) > 0) {
        $error_msg = "Expense head name already exists!";
    } else {
        $update_query = "UPDATE expense_heads SET head_name = '$head_name', description = '$description', status = '$status' WHERE id = $edit_id";
        if(mysqli_query($conn, $update_query)) {
            $success_msg = "Expense head updated successfully!";
            header("Location: add_expense_head.php");
            exit();
        } else {
            $error_msg = "Failed to update expense head!";
        }
    }
}

// Fetch all expense heads
$heads_query = "SELECT * FROM expense_heads ORDER BY id DESC";
$heads_result = mysqli_query($conn, $heads_query);
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
        .required-field::after {
            content: " *";
            color: red;
        }
        .status-badge-active {
            background-color: #28a745;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
        }
        .status-badge-inactive {
            background-color: #dc3545;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
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
                <i class="fas fa-tags text-success mr-2"></i> Add Expense Head
            </h1>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Expense</a></li>
                <li class="breadcrumb-item active">Add Expense Head</li>
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
        
        <!-- Add Expense Head Form -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-plus-circle mr-2"></i> 
                <?php echo $edit_head ? 'Edit Expense Head' : 'Add New Expense Head'; ?>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="headForm">
                    <?php if($edit_head): ?>
                        <input type="hidden" name="head_id" value="<?php echo $edit_head['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-tag text-success mr-1"></i> Expense Head Name</label>
                                <input type="text" name="head_name" class="form-control" 
                                       value="<?php echo $edit_head ? htmlspecialchars($edit_head['head_name']) : ''; ?>" 
                                       placeholder="Enter expense head name" required>
                                <small class="text-muted">Examples: Electricity Bill, Office Expense, Fuel Expense, Rent</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-toggle-on text-success mr-1"></i> Status</label>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="status" name="status" 
                                           <?php echo ($edit_head && $edit_head['status'] == 1) ? 'checked' : 'checked'; ?>>
                                    <label class="custom-control-label" for="status">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label><i class="fas fa-align-left text-success mr-1"></i> Description</label>
                                <textarea name="description" class="form-control" rows="3" 
                                          placeholder="Enter expense head description"><?php echo $edit_head ? htmlspecialchars($edit_head['description']) : ''; ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 text-right">
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo-alt mr-1"></i> Reset
                            </button>
                            <?php if($edit_head): ?>
                                <a href="add_expense_head.php" class="btn btn-info">
                                    <i class="fas fa-plus-circle mr-1"></i> Add New
                                </a>
                                <button type="submit" name="update_head" class="btn btn-green">
                                    <i class="fas fa-save mr-1"></i> Update Head
                                </button>
                            <?php else: ?>
                                <button type="submit" name="save_head" class="btn btn-green">
                                    <i class="fas fa-save mr-1"></i> Save Head
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Expense Heads List Table -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-list mr-2"></i> Expense Heads List
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="headsTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Expense Head</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Created Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($head = mysqli_fetch_assoc($heads_result)): ?>
                            <tr>
                                <td><?php echo $head['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($head['head_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($head['description']); ?></td>
                                <td>
                                    <?php if($head['status'] == 1): ?>
                                        <span class="status-badge-active"><i class="fas fa-check-circle"></i> Active</span>
                                    <?php else: ?>
                                        <span class="status-badge-inactive"><i class="fas fa-times-circle"></i> Inactive</span>
                                    <?php endif; ?>
                                 </td>
                                <td><?php echo date('d-m-Y', strtotime($head['created_at'])); ?></td>
                                <td>
                                    <a href="add_expense_head.php?edit_id=<?php echo $head['id']; ?>" class="btn btn-sm btn-info" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $head['id']; ?>)" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                 </td>
                             </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Common Expense Examples -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-lightbulb mr-2"></i> Common Expense Examples
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <span class="badge badge-success p-2 m-1">Electricity Bill</span>
                        <span class="badge badge-success p-2 m-1">Office Expense</span>
                        <span class="badge badge-success p-2 m-1">Tea Expense</span>
                    </div>
                    <div class="col-md-3">
                        <span class="badge badge-success p-2 m-1">Fuel Expense</span>
                        <span class="badge badge-success p-2 m-1">Rent Expense</span>
                        <span class="badge badge-success p-2 m-1">Internet Expense</span>
                    </div>
                    <div class="col-md-3">
                        <span class="badge badge-success p-2 m-1">Labour Expense</span>
                        <span class="badge badge-success p-2 m-1">Transport Expense</span>
                        <span class="badge badge-success p-2 m-1">Loading Expense</span>
                    </div>
                    <div class="col-md-3">
                        <span class="badge badge-success p-2 m-1">Maintenance Expense</span>
                        <span class="badge badge-success p-2 m-1">Water Bill</span>
                        <span class="badge badge-success p-2 m-1">Gas Bill</span>
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
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/js/sb-admin-2.min.js"></script>

<script>
$(document).ready(function() {
    $('#headsTable').DataTable({
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
        text: "You won't be able to revert this! This expense head will be deleted permanently.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'add_expense_head.php?delete_id=' + id;
        }
    });
}

// Form validation
$('#headForm').on('submit', function(e) {
    var headName = $('input[name="head_name"]').val().trim();
    if(headName === '') {
        e.preventDefault();
        Swal.fire({ title: 'Error!', text: 'Expense head name is required!', icon: 'error', confirmButtonColor: '#1e7e34' });
        return false;
    }
});
</script>

</body>
</html>

<?php mysqli_close($conn); ?>