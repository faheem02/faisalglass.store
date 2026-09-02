<?php
/**
 * Edit Product Page
 * Faysal Glass And Aluminium Centre
 * 
 * Edit existing product details
 * Page: Edit Product
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$page_title = "Edit Product";
$success_msg = '';
$error_msg = '';

// Check if product ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: addproduct.php");
    exit();
}

$product_id = intval($_GET['id']);

// Fetch product details
$query = "SELECT p.*, c.category_name, comp.company_name, u.unit_name, u.short_name 
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.id
          LEFT JOIN companies comp ON p.company_id = comp.id
          LEFT JOIN units u ON p.unit_id = u.id
          WHERE p.id = $product_id";

$result = mysqli_query($conn, $query);

if(mysqli_num_rows($result) == 0) {
    header("Location: addproduct.php");
    exit();
}

$product = mysqli_fetch_assoc($result);

// PRG success messages (shown after GET redirect so stock is added only once)
if(isset($_GET['msg'])) {
    switch($_GET['msg']) {
        case 'updated':
            $success_msg = "Product updated successfully!";
            break;
        case 'stock':
            $success_msg = "Stock added successfully! New stock quantity: " . number_format(floatval($_GET['new'] ?? 0), 2) . " sq ft";
            break;
        case 'size':
            $success_msg = "New size added successfully!";
            if(isset($_GET['new']) && floatval($_GET['new']) > 0) {
                $success_msg .= " Opening stock: " . number_format(floatval($_GET['new']), 2) . " sq ft";
            }
            break;
    }
}

// Get current stock
$stock_query = "SELECT balance_qty FROM inventory_ledger WHERE product_id = $product_id ORDER BY id DESC LIMIT 1";
$stock_result = mysqli_query($conn, $stock_query);
$current_stock = 0;
if(mysqli_num_rows($stock_result) > 0) {
    $stock_row = mysqli_fetch_assoc($stock_result);
    $current_stock = floatval($stock_row['balance_qty']);
}

// Fetch categories for dropdown
$categories_query = "SELECT id, category_name FROM categories WHERE status = 1 ORDER BY category_name";
$categories_result = mysqli_query($conn, $categories_query);

// Fetch companies for dropdown
$companies_query = "SELECT id, company_name FROM companies WHERE status = 1 ORDER BY company_name";
$companies_result = mysqli_query($conn, $companies_query);

// Fetch units for dropdown
$units_query = "SELECT id, unit_name, short_name FROM units ORDER BY unit_name";
$units_result = mysqli_query($conn, $units_query);

// Fetch sizes for this product
$product_sizes = [];
$sizes_query = "SELECT ps.*, 
                (SELECT COALESCE(SUM(os.pieces), 0) FROM opening_stock os WHERE os.product_size_id = ps.id) as opening_pieces
                FROM product_sizes ps WHERE ps.product_id = $product_id ORDER BY ps.id ASC";
$sizes_result = mysqli_query($conn, $sizes_query);
while($sz = mysqli_fetch_assoc($sizes_result)) {
    $product_sizes[] = $sz;
}

// Handle Update Product
if(isset($_POST['update_product'])) {
    $product_name = mysqli_real_escape_string($conn, trim($_POST['product_name']));
    $category_id = intval($_POST['category_id']);
    $company_id = intval($_POST['company_id']);
    $unit_id = intval($_POST['unit_id']);
    $purchase_price = floatval($_POST['purchase_price']);
    $sale_price = floatval($_POST['sale_price']);
    $min_stock_alert = intval($_POST['min_stock_alert']);
    $location_rack = mysqli_real_escape_string($conn, trim($_POST['location_rack']));
    $status = isset($_POST['status']) ? 1 : 0;
    
    // Validation
    if(empty($product_name)) {
        $error_msg = "Product name is required!";
    } elseif($category_id <= 0) {
        $error_msg = "Please select a category!";
    } elseif($company_id <= 0) {
        $error_msg = "Please select a company!";
    } elseif($unit_id <= 0) {
        $error_msg = "Please select a unit!";
    } elseif($purchase_price <= 0) {
        $error_msg = "Purchase price must be greater than 0!";
    } elseif($sale_price <= 0) {
        $error_msg = "Sale price must be greater than 0!";
    } else {
        // Check for duplicate product name (excluding current)
        $check_query = "SELECT id FROM products WHERE product_name = '$product_name' AND id != $product_id";
        $check_result = mysqli_query($conn, $check_query);
        
        if(mysqli_num_rows($check_result) > 0) {
            $error_msg = "Product name already exists!";
        } else {
            // Update product
            $update_query = "UPDATE products SET 
                            product_name = '$product_name',
                            category_id = '$category_id',
                            company_id = '$company_id',
                            unit_id = '$unit_id',
                            purchase_price = '$purchase_price',
                            sale_price = '$sale_price',
                            min_stock_alert = '$min_stock_alert',
                            location_rack = '$location_rack',
                            status = '$status'
                            WHERE id = $product_id";
            
            if(mysqli_query($conn, $update_query)) {
                // GET redirect - never re-submits the POST (prevents stock/records from being re-added)
                header("Location: edit_product.php?id=$product_id&msg=updated");
                exit();
            } else {
                $error_msg = "Failed to update product: " . mysqli_error($conn);
            }
        }
    }
}

// Handle Add Opening Stock (Additional Stock) - per size or product level
if(isset($_POST['add_stock'])) {
    $stock_size_id = intval($_POST['stock_size_id'] ?? 0);
    $additional_qty = floatval($_POST['additional_qty']);
    $stock_purchase_price = floatval($_POST['stock_purchase_price']);
    $stock_remarks = mysqli_real_escape_string($conn, trim($_POST['stock_remarks']));
    
    if($additional_qty <= 0) {
        $error_msg = "Quantity must be greater than 0!";
    } elseif($stock_purchase_price <= 0) {
        $error_msg = "Purchase price must be greater than 0!";
    } else {
        // If a size is selected, quantity = pieces; total area = pieces x size area
        $total_amount = $additional_qty * $stock_purchase_price;
        $size_id_sql = "NULL";
        $size_label = "";
        if($stock_size_id > 0) {
            $size_check = mysqli_query($conn, "SELECT * FROM product_sizes WHERE id = $stock_size_id AND product_id = $product_id");
            $size_row = mysqli_fetch_assoc($size_check);
            if(!$size_row) {
                $error_msg = "Invalid size selected!";
            } else {
                $size_id_sql = $stock_size_id;
                $size_label = $size_row['size_label'];
                $total_area = $size_row['area_sqft'] * $additional_qty;
                $total_amount = $total_area * $stock_purchase_price;
            }
        }
        
        if(empty($error_msg)) {
            $current_date = date('Y-m-d');
            $new_balance = $current_stock + ($stock_size_id > 0 ? $total_area : $additional_qty);
            
            $remarks_text = ($stock_size_id > 0 ? "Additional Stock - Size: $size_label, " : "Additional Stock: ")
                          . ($stock_size_id > 0 ? "$additional_qty pieces, total $total_area sq ft" : "$additional_qty sq ft")
                          . ($stock_remarks ? " | $stock_remarks" : "");
            $pieces_val = $stock_size_id > 0 ? floatval($additional_qty) : 0;
            $qty_val = $stock_size_id > 0 ? $total_area : $additional_qty;
            
            // Insert into opening_stock (as additional stock)
            $insert_opening = "INSERT INTO opening_stock (product_id, product_size_id, quantity, pieces, unit_price, total_amount, date, remarks, created_by) 
                              VALUES ('$product_id', $size_id_sql, '$qty_val', '$pieces_val', '$stock_purchase_price', '$total_amount', 
                              '$current_date', '" . mysqli_real_escape_string($conn, $remarks_text) . "', '{$_SESSION['user_id']}')";
            
            if(mysqli_query($conn, $insert_opening)) {
                // Insert into inventory_ledger
                $insert_ledger = "INSERT INTO inventory_ledger (date, product_id, reference_type, reference_id, 
                                  qty_in, qty_out, balance_qty, unit_price, total_amount, remarks) 
                                  VALUES ('$current_date', '$product_id', 'ADJUSTMENT', '$product_id', 
                                  '$qty_val', 0, '$new_balance', '$stock_purchase_price', '$total_amount', 
                                  '" . mysqli_real_escape_string($conn, $remarks_text) . "')";
                
                if(mysqli_query($conn, $insert_ledger)) {
                    // GET redirect - prevents window.location.reload() from re-POSTing and doubling stock endlessly
                    header("Location: edit_product.php?id=$product_id&msg=stock&new=$new_balance");
                    exit();
                } else {
                    $error_msg = "Failed to update inventory ledger!";
                }
            } else {
                $error_msg = "Failed to add stock!";
            }
        }
    }
}

// Handle Add New Size (with optional opening stock)
if(isset($_POST['add_size'])) {
    $size_length_feet = floatval($_POST['size_length_feet']);
    $size_width_feet = floatval($_POST['size_width_feet']);
    $size_opening_qty = floatval($_POST['size_opening_qty']);
    $size_purchase_price = floatval($_POST['size_purchase_price']);
    $size_sale_rate = floatval($_POST['size_sale_rate'] ?? 0);
    if($size_sale_rate <= 0) $size_sale_rate = floatval($product['sale_price']);
    
    if($size_length_feet <= 0 || $size_width_feet <= 0) {
        $error_msg = "Length and width must be greater than 0!";
    } elseif($size_opening_qty < 0) {
        $error_msg = "Opening quantity cannot be negative!";
    } else {
        $length_inch = $size_length_feet * 12;
        $width_inch = $size_width_feet * 12;
        $area_sqft = $size_length_feet * $size_width_feet;
        $size_label = trim(rtrim(rtrim(number_format($size_length_feet, 2, '.', ''), '0'), '.')) . ' x ' .
                      trim(rtrim(rtrim(number_format($size_width_feet, 2, '.', ''), '0'), '.')) . ' ft';
        
        $insert_size = "INSERT INTO product_sizes (product_id, size_label, length_inch, width_inch, length_feet, width_feet, area_sqft, purchase_rate, sale_rate) 
                        VALUES ('$product_id', '" . mysqli_real_escape_string($conn, $size_label) . "', '$length_inch', '$width_inch', 
                        '$size_length_feet', '$size_width_feet', '$area_sqft', '$size_purchase_price', '$size_sale_rate')";
        
        if(mysqli_query($conn, $insert_size)) {
            $size_id = mysqli_insert_id($conn);
            
            if($size_opening_qty > 0) {
                $total_area = $area_sqft * $size_opening_qty;
                $total_amount = $total_area * $size_purchase_price;
                $current_date = date('Y-m-d');
                $new_balance = $current_stock + $total_area;
                $remarks_text = "Opening Stock: $size_opening_qty pieces of $size_label, total $total_area sq ft";
                
                $insert_opening = "INSERT INTO opening_stock (product_id, product_size_id, quantity, pieces, unit_price, total_amount, date, remarks, created_by) 
                                   VALUES ('$product_id', '$size_id', '$total_area', '$size_opening_qty', '$size_purchase_price', '$total_amount', 
                                   '$current_date', '" . mysqli_real_escape_string($conn, $remarks_text) . "', '{$_SESSION['user_id']}')";
                mysqli_query($conn, $insert_opening);
                
                $insert_ledger = "INSERT INTO inventory_ledger (date, product_id, reference_type, reference_id, 
                                   qty_in, qty_out, balance_qty, unit_price, total_amount, remarks) 
                                   VALUES ('$current_date', '$product_id', 'OPENING', '$product_id', 
                                   '$total_area', 0, '$new_balance', '$size_purchase_price', '$total_amount', 
                                   'Opening Stock Entry - $remarks_text')";
                mysqli_query($conn, $insert_ledger);
                $current_stock = $new_balance;
            }
            
            $new_param = $size_opening_qty > 0 ? "&new=$current_stock" : "";
            // GET redirect - prevents window.location.reload() from re-POSTing and duplicating the size/stock endlessly
            header("Location: edit_product.php?id=$product_id&msg=size$new_param");
            exit();
        } else {
            $error_msg = "Failed to add size: " . mysqli_error($conn);
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
            background: linear-gradient(135deg, #e8f5e9, #e3f2fd);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .info-item {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #1e7e34;
        }
        .info-value {
            font-weight: 500;
            color: #0066cc;
        }
        .stock-badge {
            font-size: 24px;
            font-weight: bold;
            color: #1e7e34;
        }
        .product-code {
            font-family: monospace;
            font-size: 18px;
            font-weight: bold;
            color: #0066cc;
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
                <i class="fas fa-edit text-success mr-2"></i> Edit Product
            </h1>
            <div>
                <a href="addproduct.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Back to List
                </a>
                <a href="addproducts.php" class="btn btn-green ml-2">
                    <i class="fas fa-plus-circle mr-1"></i> Add New Product
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
        
        <!-- Product Information Box -->
        <div class="info-box">
            <div class="row">
                <div class="col-md-4">
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-barcode"></i> Product Code</div>
                        <div class="product-code"><?php echo $product['product_code']; ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-boxes"></i> Current Stock</div>
                        <div class="stock-badge">
                            <?php echo number_format($current_stock, 2); ?> <?php echo $product['short_name']; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-chart-line"></i> Stock Value</div>
                        <div class="info-value">
                            <?php echo formatCurrency($current_stock * $product['purchase_price']); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Edit Product Form -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-pen mr-2"></i> Edit Product Details
            </div>
            <div class="card-body">
                <form method="POST" action="" id="productForm">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-tag text-success mr-1"></i> Product Name</label>
                                <input type="text" name="product_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($product['product_name']); ?>" 
                                       placeholder="Enter product name" required>
                                <small class="text-muted">Don't use special characters like -, /, *, %, ^, #, @, !, =</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-tags text-success mr-1"></i> Category</label>
                                <select name="category_id" class="form-control" required>
                                    <option value="">Select Category</option>
                                    <?php while($cat = mysqli_fetch_assoc($categories_result)): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo ($product['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['category_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-building text-success mr-1"></i> Company</label>
                                <select name="company_id" class="form-control" required>
                                    <option value="">Select Company</option>
                                    <?php while($comp = mysqli_fetch_assoc($companies_result)): ?>
                                        <option value="<?php echo $comp['id']; ?>" <?php echo ($product['company_id'] == $comp['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($comp['company_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-ruler-combined text-success mr-1"></i> Unit</label>
                                <select name="unit_id" class="form-control" required>
                                    <option value="">Select Unit</option>
                                    <?php while($unit = mysqli_fetch_assoc($units_result)): ?>
                                        <option value="<?php echo $unit['id']; ?>" <?php echo ($product['unit_id'] == $unit['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($unit['unit_name'] . ' (' . $unit['short_name'] . ')'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-money-bill-wave text-success mr-1"></i> Purchase Price (₨)</label>
                                <input type="number" step="0.01" name="purchase_price" class="form-control" 
                                       value="<?php echo $product['purchase_price']; ?>" 
                                       placeholder="Enter purchase price" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-tag text-success mr-1"></i> Sale Price (₨)</label>
                                <input type="number" step="0.01" name="sale_price" class="form-control" 
                                       value="<?php echo $product['sale_price']; ?>" 
                                       placeholder="Enter sale price" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-exclamation-triangle text-success mr-1"></i> Minimum Stock Alert</label>
                                <input type="number" name="min_stock_alert" class="form-control" 
                                       value="<?php echo $product['min_stock_alert']; ?>" 
                                       placeholder="Enter minimum stock limit">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-map-marker-alt text-success mr-1"></i> Location / Rack (Optional)</label>
                                <input type="text" name="location_rack" class="form-control" 
                                       value="<?php echo htmlspecialchars($product['location_rack']); ?>" 
                                       placeholder="Enter storage location">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-toggle-on text-success mr-1"></i> Status</label>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="status" name="status" 
                                           <?php echo ($product['status'] == 1) ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="status">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-12 text-right">
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo-alt mr-1"></i> Reset
                            </button>
                            <button type="submit" name="update_product" class="btn btn-green">
                                <i class="fas fa-save mr-1"></i> Update Product
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Add Additional Stock Form -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-plus-circle mr-2"></i> Add Additional Stock
            </div>
            <div class="card-body">
                <form method="POST" action="" id="stockForm">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><i class="fas fa-arrows-alt text-success mr-1"></i> Size (Optional)</label>
                                <select name="stock_size_id" id="stock_size_id" class="form-control">
                                    <option value="0">Product Level (area)</option>
                                    <?php foreach($product_sizes as $sz): ?>
                                        <option value="<?php echo $sz['id']; ?>"><?php echo htmlspecialchars($sz['size_label']); ?> (<?php echo number_format($sz['area_sqft'], 2); ?> sq ft)</option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Select a size to add stock in pieces for that size</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-boxes text-success mr-1"></i> Quantity</label>
                                <input type="number" step="0.01" name="additional_qty" id="additional_qty" class="form-control" 
                                       placeholder="Enter quantity" required>
                                <small class="text-muted" id="qtyUnitHint">Pieces (if size selected) / sq ft (product level)</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-money-bill-wave text-success mr-1"></i> Purchase Price (₨)</label>
                                <input type="number" step="0.01" name="stock_purchase_price" class="form-control" 
                                       value="<?php echo $product['purchase_price']; ?>" 
                                       placeholder="Enter purchase price" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><i class="fas fa-comment text-success mr-1"></i> Remarks</label>
                                <input type="text" name="stock_remarks" class="form-control" 
                                       placeholder="Optional remarks">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 text-right">
                            <button type="submit" name="add_stock" class="btn btn-info">
                                <i class="fas fa-plus-circle mr-1"></i> Add Stock
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Product Sizes Section -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-arrows-alt mr-2"></i> Product Sizes
            </div>
            <div class="card-body">
                <?php if(count($product_sizes) > 0): ?>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered" id="sizesTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Size</th>
                                <th>Length (Feet)</th>
                                <th>Width (Feet)</th>
                                <th>Area (sq ft)</th>
                                <th>Opening Pieces</th>
                                <th>Purchase Rate (₨/sq ft)</th>
                                <th>Sale Rate (₨/sq ft)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($product_sizes as $sz): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($sz['size_label']); ?></strong></td>
                                <td class="text-right"><?php echo number_format($sz['length_feet'], 2); ?></td>
                                <td class="text-right"><?php echo number_format($sz['width_feet'], 2); ?></td>
                                <td class="text-right"><?php echo number_format($sz['area_sqft'], 2); ?></td>
                                <td class="text-right"><?php echo number_format($sz['opening_pieces'], 2); ?></td>
                                <td class="text-right"><?php echo formatCurrency(floatval($sz['purchase_rate']) > 0 ? $sz['purchase_rate'] : $product['purchase_price']); ?></td>
                                <td class="text-right"><?php echo formatCurrency(floatval($sz['sale_rate']) > 0 ? $sz['sale_rate'] : $product['sale_price']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info"><i class="fas fa-info-circle mr-2"></i> No sizes added yet. Add sizes below.</div>
                <?php endif; ?>
                
                <hr>
                <h6 class="text-success font-weight-bold mb-3"><i class="fas fa-plus-circle"></i> Add New Size</h6>
                <form method="POST" action="" id="sizeForm">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label class="required-field">Length (Feet)</label>
                                <input type="number" step="0.01" name="size_length_feet" id="size_length_feet" class="form-control" placeholder="e.g. 4" required>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label class="required-field">Width (Feet)</label>
                                <input type="number" step="0.01" name="size_width_feet" id="size_width_feet" class="form-control" placeholder="e.g. 8" required>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Area (sq ft)</label>
                                <input type="number" step="0.01" id="size_area_preview" class="form-control" readonly style="background:#e8f5e9; font-weight:bold;" value="0.00">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Opening Qty (Pieces)</label>
                                <input type="number" step="0.01" name="size_opening_qty" class="form-control" value="0">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Purchase Rate (₨/sq ft)</label>
                                <input type="number" step="0.01" name="size_purchase_price" class="form-control" value="<?php echo $product['purchase_price']; ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Sale Rate (₨/sq ft)</label>
                                <input type="number" step="0.01" name="size_sale_rate" class="form-control" value="<?php echo $product['sale_price']; ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 text-right">
                            <button type="submit" name="add_size" class="btn btn-success"><i class="fas fa-plus"></i> Add Size</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Stock History (Recent Transactions) -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-history mr-2"></i> Recent Stock Transactions
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="historyTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Reference Type</th>
                                <th>Qty In</th>
                                <th>Qty Out</th>
                                <th>Balance</th>
                                <th>Unit Price</th>
                                <th>Total Amount</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $history_query = "SELECT * FROM inventory_ledger WHERE product_id = $product_id ORDER BY id DESC LIMIT 20";
                            $history_result = mysqli_query($conn, $history_query);
                            while($history = mysqli_fetch_assoc($history_result)):
                            ?>
                            <tr>
                                <td><?php echo date('d-m-Y', strtotime($history['date'])); ?></td>
                                <td>
                                    <?php 
                                    $badge_class = '';
                                    switch($history['reference_type']) {
                                        case 'OPENING':
                                            $badge_class = 'badge-success';
                                            break;
                                        case 'PURCHASE':
                                            $badge_class = 'badge-primary';
                                            break;
                                        case 'SALE':
                                            $badge_class = 'badge-warning';
                                            break;
                                        case 'ADJUSTMENT':
                                            $badge_class = 'badge-info';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo $history['reference_type']; ?></span>
                                </td>
                                <td class="text-right"><?php echo $history['qty_in'] > 0 ? number_format($history['qty_in'], 2) : '-'; ?></td>
                                <td class="text-right"><?php echo $history['qty_out'] > 0 ? number_format($history['qty_out'], 2) : '-'; ?></td>
                                <td class="text-right"><strong><?php echo number_format($history['balance_qty'], 2); ?></strong></td>
                                <td class="text-right"><?php echo formatCurrency($history['unit_price']); ?></td>
                                <td class="text-right"><?php echo formatCurrency($history['total_amount']); ?></td>
                                <td><?php echo htmlspecialchars($history['remarks']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($history_result) == 0): ?>
                            <tr>
                                <td colspan="8" class="text-center">No stock transactions found</td>
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
    $('#historyTable').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 10,
        "language": {
            "search": "Search:",
            "lengthMenu": "Show _MENU_ entries",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries"
        }
    });
    
    // Auto capitalize product name first letter
    $('input[name="product_name"]').on('keyup', function() {
        var value = $(this).val();
        if(value.length > 0) {
            $(this).val(value.charAt(0).toUpperCase() + value.slice(1));
        }
    });
});

// Form validation for product edit
$('#productForm').on('submit', function(e) {
    var productName = $('input[name="product_name"]').val().trim();
    var category = $('select[name="category_id"]').val();
    var company = $('select[name="company_id"]').val();
    var unit = $('select[name="unit_id"]').val();
    var purchasePrice = parseFloat($('input[name="purchase_price"]').val());
    var salePrice = parseFloat($('input[name="sale_price"]').val());
    
    if(productName === '') {
        e.preventDefault();
        Swal.fire({ title: 'Error!', text: 'Product name is required!', icon: 'error', confirmButtonColor: '#1e7e34' });
        return false;
    }
    
    if(category === '') {
        e.preventDefault();
        Swal.fire({ title: 'Error!', text: 'Please select a category!', icon: 'error', confirmButtonColor: '#1e7e34' });
        return false;
    }
    
    if(company === '') {
        e.preventDefault();
        Swal.fire({ title: 'Error!', text: 'Please select a company!', icon: 'error', confirmButtonColor: '#1e7e34' });
        return false;
    }
    
    if(unit === '') {
        e.preventDefault();
        Swal.fire({ title: 'Error!', text: 'Please select a unit!', icon: 'error', confirmButtonColor: '#1e7e34' });
        return false;
    }
    
    if(isNaN(purchasePrice) || purchasePrice <= 0) {
        e.preventDefault();
        Swal.fire({ title: 'Error!', text: 'Purchase price must be greater than 0!', icon: 'error', confirmButtonColor: '#1e7e34' });
        return false;
    }
    
    if(isNaN(salePrice) || salePrice <= 0) {
        e.preventDefault();
        Swal.fire({ title: 'Error!', text: 'Sale price must be greater than 0!', icon: 'error', confirmButtonColor: '#1e7e34' });
        return false;
    }
});

// Stock form validation
$('#stockForm').on('submit', function(e) {
    var qty = parseFloat($('input[name="additional_qty"]').val());
    var price = parseFloat($('input[name="stock_purchase_price"]').val());
    
    if(isNaN(qty) || qty <= 0) {
        e.preventDefault();
        Swal.fire({ title: 'Error!', text: 'Quantity must be greater than 0!', icon: 'error', confirmButtonColor: '#1e7e34' });
        return false;
    }
    
    if(isNaN(price) || price <= 0) {
        e.preventDefault();
        Swal.fire({ title: 'Error!', text: 'Purchase price must be greater than 0!', icon: 'error', confirmButtonColor: '#1e7e34' });
        return false;
    }
});

// Update quantity unit hint based on selected size
$('#stock_size_id').on('change', function() {
    if($(this).val() > 0) {
        $('#qtyUnitHint').text('Quantity is in pieces for this size');
    } else {
        $('#qtyUnitHint').text('Pieces (if size selected) / sq ft (product level)');
    }
});

// Add New Size form validation + area preview
$('#size_length_feet, #size_width_feet').on('keyup change', function() {
    var len = parseFloat($('#size_length_feet').val()) || 0;
    var wid = parseFloat($('#size_width_feet').val()) || 0;
    $('#size_area_preview').val((len * wid).toFixed(2));
});

$('#sizeForm').on('submit', function(e) {
    var len = parseFloat($('input[name="size_length_feet"]').val());
    var wid = parseFloat($('input[name="size_width_feet"]').val());
    
    if(isNaN(len) || len <= 0 || isNaN(wid) || wid <= 0) {
        e.preventDefault();
        Swal.fire({ title: 'Error!', text: 'Length and width must be greater than 0!', icon: 'error', confirmButtonColor: '#1e7e34' });
        return false;
    }
});

// Disable special characters in product name
$('input[name="product_name"]').on('keypress', function(e) {
    var regex = /^[a-zA-Z0-9\s]+$/;
    var key = String.fromCharCode(!e.charCode ? e.which : e.charCode);
    if (!regex.test(key) && e.keyCode !== 8 && e.keyCode !== 46) {
        e.preventDefault();
        return false;
    }
});
</script>

</body>
</html>

<?php mysqli_close($conn); ?>