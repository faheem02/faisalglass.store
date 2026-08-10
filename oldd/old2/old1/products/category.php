<?php
/**
 * Add Category Page
 * Faysal Glass And Aluminium Centre
 * 
 * Add and manage product categories
 * Page: Add Category
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$page_title = "Add Category";
$success_msg = '';
$error_msg = '';

// Handle Save Category
if(isset($_POST['save_category'])) {
    $category_name = mysqli_real_escape_string($conn, trim($_POST['category_name']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $status = isset($_POST['status']) ? 1 : 0;
    
    // Check for duplicate
    $check_query = "SELECT id FROM categories WHERE category_name = '$category_name'";
    $check_result = mysqli_query($conn, $check_query);
    
    if(mysqli_num_rows($check_result) > 0) {
        $error_msg = "Category name already exists!";
    } else {
        $insert_query = "INSERT INTO categories (category_name, description, status) VALUES ('$category_name', '$description', '$status')";
        if(mysqli_query($conn, $insert_query)) {
            $success_msg = "Category added successfully!";
        } else {
            $error_msg = "Failed to add category: " . mysqli_error($conn);
        }
    }
}

// Handle Delete Category
if(isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // Check if category is used in products
    $check_products = "SELECT id FROM products WHERE category_id = $delete_id LIMIT 1";
    $products_result = mysqli_query($conn, $check_products);
    
    if(mysqli_num_rows($products_result) > 0) {
        $error_msg = "Cannot delete! This category has products assigned to it.";
    } else {
        $delete_query = "DELETE FROM categories WHERE id = $delete_id";
        if(mysqli_query($conn, $delete_query)) {
            $success_msg = "Category deleted successfully!";
        } else {
            $error_msg = "Failed to delete category!";
        }
    }
}

// Handle Edit Category
$edit_category = null;
if(isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $edit_query = "SELECT * FROM categories WHERE id = $edit_id";
    $edit_result = mysqli_query($conn, $edit_query);
    $edit_category = mysqli_fetch_assoc($edit_result);
}

// Handle Update Category
if(isset($_POST['update_category'])) {
    $edit_id = intval($_POST['category_id']);
    $category_name = mysqli_real_escape_string($conn, trim($_POST['category_name']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $status = isset($_POST['status']) ? 1 : 0;
    
    // Check for duplicate excluding current
    $check_query = "SELECT id FROM categories WHERE category_name = '$category_name' AND id != $edit_id";
    $check_result = mysqli_query($conn, $check_query);
    
    if(mysqli_num_rows($check_result) > 0) {
        $error_msg = "Category name already exists!";
    } else {
        $update_query = "UPDATE categories SET category_name = '$category_name', description = '$description', status = '$status' WHERE id = $edit_id";
        if(mysqli_query($conn, $update_query)) {
            $success_msg = "Category updated successfully!";
            header("Location: category.php");
            exit();
        } else {
            $error_msg = "Failed to update category!";
        }
    }
}

// Fetch all categories
$categories_query = "SELECT * FROM categories ORDER BY id DESC";
$categories_result = mysqli_query($conn, $categories_query);
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
    </style>
</head>
<body id="page-top">

<div id="wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <div class="container-fluid">
        
        <!-- Page Header -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-tags text-success mr-2"></i> Add Category
            </h1>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Add Category</li>
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
        
        <!-- Add Category Form -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-plus-circle mr-2"></i> 
                <?php echo $edit_category ? 'Edit Category' : 'Add New Category'; ?>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="categoryForm">
                    <?php if($edit_category): ?>
                        <input type="hidden" name="category_id" value="<?php echo $edit_category['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-tag text-success mr-1"></i> Category Name <span class="text-danger">*</span></label>
                                <input type="text" name="category_name" class="form-control" 
                                       value="<?php echo $edit_category ? htmlspecialchars($edit_category['category_name']) : ''; ?>" 
                                       placeholder="Enter category name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-toggle-on text-success mr-1"></i> Status</label>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="status" name="status" 
                                           <?php echo ($edit_category && $edit_category['status'] == 1) ? 'checked' : 'checked'; ?>>
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
                                          placeholder="Enter category description"><?php echo $edit_category ? htmlspecialchars($edit_category['description']) : ''; ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 text-right">
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo-alt mr-1"></i> Reset
                            </button>
                            <?php if($edit_category): ?>
                                <a href="category.php" class="btn btn-info">
                                    <i class="fas fa-plus-circle mr-1"></i> Add New
                                </a>
                                <button type="submit" name="update_category" class="btn btn-green">
                                    <i class="fas fa-save mr-1"></i> Update Category
                                </button>
                            <?php else: ?>
                                <button type="submit" name="save_category" class="btn btn-green">
                                    <i class="fas fa-save mr-1"></i> Save Category
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Categories List Table -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-list mr-2"></i> Categories List
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="categoriesTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Category Name</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Created Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($category = mysqli_fetch_assoc($categories_result)): ?>
                            <tr>
                                <td><?php echo $category['id']; ?></td>
                                <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                <td><?php echo htmlspecialchars($category['description']); ?></td>
                                <td>
                                    <?php if($category['status'] == 1): ?>
                                        <span class="badge badge-success"><i class="fas fa-check-circle"></i> Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary"><i class="fas fa-times-circle"></i> Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d-m-Y', strtotime($category['created_at'])); ?></td>
                                <td>
                                    <a href="category.php?edit_id=<?php echo $category['id']; ?>" class="btn btn-sm btn-info" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $category['id']; ?>)" title="Delete">
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
    $('#categoriesTable').DataTable({
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
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'category.php?delete_id=' + id;
        }
    });
}
</script>

</body>
</html>

<?php mysqli_close($conn); ?>