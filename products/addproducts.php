<?php
/**
 * Add Opening Stock Page - MODIFIED: Multi-size products + autocomplete name
 * Faysal Glass And Aluminium Centre
 * 
 * A single product can have multiple sizes. Each size has its own
 * opening stock (pieces). Inventory still tracks total area (sq ft)
 * per product in inventory_ledger for backward compatibility.
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$page_title = "Add Opening Stock";
$success_msg = '';
$error_msg = '';

// Generate Product Code
function generateProductCode($conn) {
    $prefix = "FG";
    $query = "SELECT product_code FROM products WHERE product_code LIKE '{$prefix}%' ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $last_code = $row['product_code'];
        $number = intval(substr($last_code, 2)) + 1;
        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    } else {
        return $prefix . "0001";
    }
}

// Fetch categories, companies, units, suppliers
$categories_query = "SELECT id, category_name FROM categories WHERE status = 1 ORDER BY category_name";
$categories_result = mysqli_query($conn, $categories_query);

$companies_query = "SELECT id, company_name FROM companies WHERE status = 1 ORDER BY company_name";
$companies_result = mysqli_query($conn, $companies_query);

$units_query = "SELECT id, unit_name, short_name FROM units ORDER BY unit_name";
$units_result = mysqli_query($conn, $units_query);

$suppliers_query = "SELECT id, supplier_name FROM suppliers WHERE status = 1 ORDER BY supplier_name";
$suppliers_result = mysqli_query($conn, $suppliers_query);

// Handle Save Product
if(isset($_POST['save_product'])) {
    $product_name = mysqli_real_escape_string($conn, trim($_POST['product_name']));
    $category_id = intval($_POST['category_id']);
    $company_id = intval($_POST['company_id']);
    $unit_id = intval($_POST['unit_id']);
    $supplier_id = intval($_POST['supplier_id']);
    $purchase_price = floatval($_POST['purchase_price']);
    $sale_price = floatval($_POST['sale_price']);
    $min_stock_alert = intval($_POST['min_stock_alert']);
    $location_rack = mysqli_real_escape_string($conn, trim($_POST['location_rack']));
    
    // Sizes come as JSON from the dynamic table
    $sizes_data = isset($_POST['sizes_data']) ? json_decode($_POST['sizes_data'], true) : [];
    if(!is_array($sizes_data)) $sizes_data = [];
    // Handle single-size object (no array wrapper) defensively
    if(isset($sizes_data['length_feet']) || isset($sizes_data['width_feet']) || isset($sizes_data['opening_qty'])) {
        $sizes_data = [$sizes_data];
    }
    
    // Normalize size rows (only keep valid ones)
    $sizes = [];
    foreach($sizes_data as $row) {
        $length_feet = floatval($row['length_feet'] ?? 0);
        $width_feet  = floatval($row['width_feet'] ?? 0);
        $opening_qty = floatval($row['opening_qty'] ?? 0);
        $purchase_rate = floatval($row['purchase_rate'] ?? 0);
        $sale_rate = floatval($row['sale_rate'] ?? 0);
        if($purchase_rate <= 0) $purchase_rate = $purchase_price;
        if($sale_rate <= 0) $sale_rate = $sale_price;
        if($length_feet > 0 && $width_feet > 0) {
            $length_inch = $length_feet * 12;
            $width_inch  = $width_feet * 12;
            $area_sqft   = $length_feet * $width_feet;
            $sizes[] = [
                'length_inch' => $length_inch,
                'width_inch'  => $width_inch,
                'length_feet' => $length_feet,
                'width_feet'  => $width_feet,
                'area_sqft'   => $area_sqft,
                'opening_qty' => $opening_qty,
                'purchase_rate' => $purchase_rate,
                'sale_rate' => $sale_rate
            ];
        }
    }
    
    // Validation
    if(empty($product_name)) {
        $error_msg = "Product name is required!";
    } elseif($category_id <= 0) {
        $error_msg = "Please select a category!";
    } elseif($company_id <= 0) {
        $error_msg = "Please select a company!";
    } elseif($unit_id <= 0) {
        $error_msg = "Please select a unit!";
    } elseif($supplier_id <= 0) {
        $error_msg = "Please select a supplier!";
    } elseif($purchase_price <= 0) {
        $error_msg = "Purchase price must be greater than 0!";
    } elseif($sale_price <= 0) {
        $error_msg = "Sale price must be greater than 0!";
    } elseif(empty($sizes)) {
        $error_msg = "Please add at least one size with valid length and width!";
    } else {
        // Check for duplicate product name
        $check_query = "SELECT id FROM products WHERE product_name = '$product_name'";
        $check_result = mysqli_query($conn, $check_query);
        
        if(mysqli_num_rows($check_result) > 0) {
            $error_msg = "Product name already exists!";
        } else {
            // Generate product code
            $product_code = generateProductCode($conn);
            
            // First size = default size stored on products table (backward compat)
            $first = $sizes[0];
            
            // Insert Product with default (first) size fields
            $insert_product = "INSERT INTO products (product_code, product_name, category_id, company_id, unit_id, 
                               supplier_id, purchase_price, sale_price, min_stock_alert, location_rack,
                               length_inch, width_inch, length_feet, width_feet, area_sqft, status) 
                               VALUES ('$product_code', '$product_name', '$category_id', '$company_id', '$unit_id', 
                               '$supplier_id', '$purchase_price', '$sale_price', '$min_stock_alert', '$location_rack',
                               '{$first['length_inch']}', '{$first['width_inch']}', '{$first['length_feet']}', 
                               '{$first['width_feet']}', '{$first['area_sqft']}', 1)";
            
            if(mysqli_query($conn, $insert_product)) {
                $product_id = mysqli_insert_id($conn);
                
                // Transaction-like: insert sizes + opening stock + ledger
                $total_stock_area = 0;
                $total_amount = 0;
                $opening_count = 0;
                $running_balance = 0;
                $current_date = date('Y-m-d');
                
                foreach($sizes as $size) {
                    // Insert size variant
                    $size_label = trim(rtrim(rtrim(number_format($size['length_feet'], 2, '.', ''), '0'), '.')) . ' x ' .
                                  trim(rtrim(rtrim(number_format($size['width_feet'], 2, '.', ''), '0'), '.')) . ' ft';
                    $insert_size = "INSERT INTO product_sizes (product_id, size_label, length_inch, width_inch, 
                                    length_feet, width_feet, area_sqft, purchase_rate, sale_rate) 
                                    VALUES ('$product_id', '" . mysqli_real_escape_string($conn, $size_label) . "', 
                                    '{$size['length_inch']}', '{$size['width_inch']}', 
                                    '{$size['length_feet']}', '{$size['width_feet']}', '{$size['area_sqft']}',
                                    '{$size['purchase_rate']}', '{$size['sale_rate']}')";
                    if(mysqli_query($conn, $insert_size)) {
                        $size_id = mysqli_insert_id($conn);
                    } else {
                        $size_id = 0;
                    }
                    
                    // Opening stock per size (only if pieces > 0)
                    if($size['opening_qty'] > 0) {
                        $size_area = $size['area_sqft'] * $size['opening_qty'];
                        $size_amount = $size_area * $size['purchase_rate'];
                        $remarks = "Opening Stock: {$size['opening_qty']} pieces of $size_label, total $size_area sq ft";
                        
                        $size_id_sql = $size_id > 0 ? $size_id : "NULL";
                        $insert_opening = "INSERT INTO opening_stock (product_id, product_size_id, quantity, pieces, unit_price, total_amount, date, remarks, created_by) 
                                           VALUES ('$product_id', $size_id_sql, '$size_area', '{$size['opening_qty']}', '{$size['purchase_rate']}', 
                                           '$size_amount', '$current_date', '" . mysqli_real_escape_string($conn, $remarks) . "', '{$_SESSION['user_id']}')";
                        mysqli_query($conn, $insert_opening);
                        
                        // Inventory ledger – qty_in = total area (sq ft) for this size
                        $running_balance += $size_area;
                        $insert_ledger = "INSERT INTO inventory_ledger (date, product_id, reference_type, reference_id, 
                                           qty_in, qty_out, balance_qty, unit_price, total_amount, remarks) 
                                           VALUES ('$current_date', '$product_id', 'OPENING', '$product_id', 
                                           '$size_area', 0, '$running_balance', '{$size['purchase_rate']}', '$size_amount', 
                                           'Opening Stock Entry - $remarks')";
                        mysqli_query($conn, $insert_ledger);
                        
                        $total_stock_area += $size_area;
                        $total_amount += $size_amount;
                        $opening_count++;
                    }
                }
                
                $success_msg = "Product added successfully! Product Code: $product_code. Total opening stock: $total_stock_area sq ft across " . count($sizes) . " size(s).";
            } else {
                $error_msg = "Failed to add product: " . mysqli_error($conn);
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
        .calculation-box { background: #f8f9fc; border-left: 4px solid #1e7e34; padding: 15px; border-radius: 8px; margin-top: 20px; }
        .calculation-box h6 { color: #1e7e34; font-weight: bold; }
        .calculation-value { font-size: 18px; font-weight: bold; color: #0066cc; }
        .preview-code { background: #e8f5e9; padding: 8px 15px; border-radius: 8px; display: inline-block; font-weight: bold; color: #1e7e34; }
        .size-card { background: #f0f2f5; border-radius: 10px; padding: 15px; margin-bottom: 20px; }
        .size-title { font-weight: bold; color: #1e7e34; margin-bottom: 15px; border-bottom: 2px solid #1e7e34; padding-bottom: 8px; display: inline-block; }
        .info-note { background: #fff3cd; padding: 8px 12px; border-radius: 5px; margin-top: 10px; font-size: 12px; }
        .autocomplete-wrap { position: relative; }
        .autocomplete-suggestions { position: absolute; z-index: 1050; width: 100%; max-height: 220px; overflow-y: auto; background: #fff; border: 1px solid #ddd; border-top: none; border-radius: 0 0 6px 6px; box-shadow: 0 6px 12px rgba(0,0,0,0.1); display: none; }
        .autocomplete-suggestions .suggestion-item { padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #f1f1f1; font-size: 14px; }
        .autocomplete-suggestions .suggestion-item:hover, .autocomplete-suggestions .suggestion-item.active { background: #e8f5e9; color: #1e7e34; }
        .autocomplete-suggestions .suggestion-item .fa { margin-right: 6px; color: #6c757d; }
        .size-table input { min-width: 70px; }
        .duplicate-warning { background: #f8d7da; color: #721c24; padding: 6px 12px; border-radius: 6px; font-size: 13px; display: none; margin-top: 6px; }
        .size-table th { background: #1e7e34; color: white; font-weight: 600; font-size: 13px; }
    </style>
</head>
<body id="page-top">

<div id="wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <div class="container-fluid">
        
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-boxes text-success mr-2"></i> Add Opening Stock
            </h1>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Products</a></li>
                <li class="breadcrumb-item active">Add Opening Stock</li>
            </ol>
        </div>
        
        <?php if($success_msg): ?>
        <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle mr-2"></i> <?php echo $success_msg; ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
        <?php endif; ?>
        <?php if($error_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error_msg; ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
        <?php endif; ?>
        
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-plus-circle mr-2"></i> Add New Product with Opening Stock
            </div>
            <div class="card-body">
                <form method="POST" action="" id="productForm">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="preview-code">
                                <i class="fas fa-barcode mr-2"></i> Auto-generated Product Code: 
                                <strong><?php echo generateProductCode($conn); ?></strong>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-tag text-success mr-1"></i> Product Name</label>
                                <div class="autocomplete-wrap">
                                    <input type="text" name="product_name" id="product_name" class="form-control" placeholder="Type product name, e.g. 6mm Clear Glass" required autocomplete="off">
                                    <div class="autocomplete-suggestions" id="nameSuggestions"></div>
                                </div>
                                <div class="duplicate-warning" id="duplicateWarning"><i class="fas fa-exclamation-triangle mr-1"></i> This product name already exists in the database!</div>
                                <small class="text-muted">Existing product names are suggested as you type.</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-tags text-success mr-1"></i> Category</label>
                                <select name="category_id" class="form-control" required><option value="">Select Category</option><?php while($cat = mysqli_fetch_assoc($categories_result)): ?><option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option><?php endwhile; ?></select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-building text-success mr-1"></i> Company</label>
                                <select name="company_id" class="form-control" required><option value="">Select Company</option><?php while($comp = mysqli_fetch_assoc($companies_result)): ?><option value="<?php echo $comp['id']; ?>"><?php echo htmlspecialchars($comp['company_name']); ?></option><?php endwhile; ?></select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-truck text-success mr-1"></i> Supplier</label>
                                <select name="supplier_id" class="form-control" required><option value="">Select Supplier</option><?php while($sup = mysqli_fetch_assoc($suppliers_result)): ?><option value="<?php echo $sup['id']; ?>"><?php echo htmlspecialchars($sup['supplier_name']); ?></option><?php endwhile; ?></select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-ruler-combined text-success mr-1"></i> Product Unit</label>
                                <select name="unit_id" class="form-control" required><option value="">Select Unit</option><?php while($unit = mysqli_fetch_assoc($units_result)): ?><option value="<?php echo $unit['id']; ?>"><?php echo htmlspecialchars($unit['unit_name'] . ' (' . $unit['short_name'] . ')'); ?></option><?php endwhile; ?></select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sizes & Opening Stock -->
                    <div class="size-card">
                        <h5 class="size-title"><i class="fas fa-arrows-alt"></i> Product Sizes &amp; Opening Stock</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered size-table">
                                <thead>
                                    <tr>
                                        <th style="width:40px;">#</th>
                                        <th>Length (Feet)</th>
                                        <th>Width (Feet)</th>
                                        <th>Area (sq ft)</th>
                                        <th>Opening Qty (Pieces)</th>
                                        <th>Purchase Rate (₨/sq ft)</th>
                                        <th>Sale Rate (₨/sq ft)</th>
                                        <th>Total Area (sq ft)</th>
                                        <th>Amount (₨)</th>
                                        <th style="width:60px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="sizesBody">
                                    <!-- Size rows added dynamically by JS -->
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-sm btn-success" id="addSizeBtn"><i class="fas fa-plus-circle mr-1"></i> Add Size</button>
                        <button type="button" class="btn btn-sm btn-secondary" id="removeLastSizeBtn"><i class="fas fa-minus-circle mr-1"></i> Remove Last Size</button>
                        <input type="hidden" name="sizes_data" id="sizes_data" value="">
                        <div class="info-note mt-2">
                            <i class="fas fa-info-circle"></i> <strong>Per Size:</strong> Enter separate opening quantity (pieces) and separate purchase/sale rate (₨/sq ft) for each size. The amount for each size is calculated automatically.
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6"><div class="form-group"><label class="required-field">Purchase Price (₨) / sq ft</label><input type="number" step="0.01" name="purchase_price" id="purchase_price" class="form-control" required></div></div>
                        <div class="col-md-6"><div class="form-group"><label class="required-field">Sale Price (₨) / sq ft</label><input type="number" step="0.01" name="sale_price" id="sale_price" class="form-control" required></div></div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4"><div class="form-group"><label><i class="fas fa-exclamation-triangle"></i> Minimum Stock Alert (sq ft)</label><input type="number" name="min_stock_alert" class="form-control" value="0"></div></div>
                        <div class="col-md-4"><div class="form-group"><label>Location / Rack</label><input type="text" name="location_rack" class="form-control" placeholder="Enter storage location"></div></div>
                    </div>
                    
                    <div class="calculation-box">
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-calculator"></i> Opening Stock Value</h6>
                                <p class="mb-0">Per size: Amount = (Length × Width × Pieces) × Purchase Rate</p>
                                <p class="small text-muted mt-1">Total Area: <span id="displayTotalArea">0.00</span> sq ft</p>
                            </div>
                            <div class="col-md-6 text-right">
                                <h6 class="calculation-value" id="totalAmountDisplay">₨ 0.00</h6>
                            </div>
                        </div>
                    </div>
                    
                    <div class="info-note">
                        <i class="fas fa-info-circle"></i> <strong>Stock Management Note:</strong> Inventory will be tracked in <strong>square feet (total area)</strong>, not in pieces. Opening stock entries record each size separately. Sales/purchases use area per size.
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-12 text-right">
                            <button type="reset" class="btn btn-secondary" onclick="resetForm()"><i class="fas fa-undo-alt"></i> Reset</button>
                            <button type="submit" name="save_product" class="btn btn-green"><i class="fas fa-save"></i> Save Product</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Information Card -->
        <div class="card form-card">
            <div class="card-header-custom"><i class="fas fa-info-circle"></i> Important Information</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3"><div class="text-center"><i class="fas fa-barcode fa-2x text-success mb-2"></i><h6>Auto Product Code</h6><p class="small">FG-0001, FG-0002...</p></div></div>
                    <div class="col-md-3"><div class="text-center"><i class="fas fa-arrows-alt fa-2x text-success mb-2"></i><h6>Multi-Size Support</h6><p class="small">One product, many sizes</p></div></div>
                    <div class="col-md-3"><div class="text-center"><i class="fas fa-chart-line fa-2x text-success mb-2"></i><h6>Inventory in Sq Ft</h6><p class="small">Stock quantity = total area</p></div></div>
                    <div class="col-md-3"><div class="text-center"><i class="fas fa-shield-alt fa-2x text-success mb-2"></i><h6>Accounting Ready</h6><p class="small">Compatible with purchases & sales</p></div></div>
                </div>
            </div>
        </div>
        
    </div>
    
    <footer class="sticky-footer bg-white"><div class="container my-auto"><div class="copyright text-center my-auto"><span>&copy; <?php echo date('Y'); ?> <?php echo $software_name; ?> - All Rights Reserved</span></div></div></footer>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/js/sb-admin-2.min.js"></script>

<script>
var sizeRowCount = 0;
var autocompleteTimer = null;

// ==================== SIZE ROWS ====================

function addSizeRow(lengthFeet, widthFeet, openingQty, purchaseRate, saleRate) {
    var rowId = sizeRowCount++;
    var len = lengthFeet || '';
    var wid = widthFeet || '';
    var qty = (openingQty !== undefined && openingQty !== null) ? openingQty : '';
    var pRate = (purchaseRate !== undefined && purchaseRate !== null) ? purchaseRate : (parseFloat($('#purchase_price').val()) || '');
    var sRate = (saleRate !== undefined && saleRate !== null) ? saleRate : (parseFloat($('#sale_price').val()) || '');
    
    var row = `
        <tr class="size-row" data-row-id="${rowId}">
            <td class="text-center"><strong class="size-number">${rowId + 1}</strong></td>
            <td><input type="number" step="0.01" class="form-control size-length" data-row-id="${rowId}" value="${len}" placeholder="e.g. 4" onkeyup="recalcSizeRow(${rowId}); recalcAll();"></td>
            <td><input type="number" step="0.01" class="form-control size-width" data-row-id="${rowId}" value="${wid}" placeholder="e.g. 8" onkeyup="recalcSizeRow(${rowId}); recalcAll();"></td>
            <td><input type="number" step="0.01" class="form-control size-area" data-row-id="${rowId}" value="0.00" readonly style="background:#e8f5e9; font-weight:bold;"></td>
            <td><input type="number" step="0.01" class="form-control size-qty" data-row-id="${rowId}" value="${qty}" placeholder="Pieces" onkeyup="recalcSizeRow(${rowId}); recalcAll();"></td>
            <td><input type="number" step="0.01" class="form-control size-purchase-rate" data-row-id="${rowId}" value="${pRate}" placeholder="e.g. 340" onkeyup="recalcSizeRow(${rowId}); recalcAll();"></td>
            <td><input type="number" step="0.01" class="form-control size-sale-rate" data-row-id="${rowId}" value="${sRate}" placeholder="e.g. 350" onkeyup="recalcSizeRow(${rowId}); recalcAll();"></td>
            <td><input type="number" step="0.01" class="form-control size-total-area" data-row-id="${rowId}" value="0.00" readonly style="background:#e3f2fd; font-weight:bold;"></td>
            <td><input type="number" step="0.01" class="form-control size-amount" data-row-id="${rowId}" value="0.00" readonly style="background:#d4edda; font-weight:bold;"></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-size-row" data-row-id="${rowId}"><i class="fas fa-trash"></i></button></td>
        </tr>`;
    $('#sizesBody').append(row);
    
    recalcSizeRow(rowId);
    renumberSizes();
    recalcAll();
}

function recalcSizeRow(rowId) {
    var len = parseFloat($(`.size-length[data-row-id="${rowId}"]`).val()) || 0;
    var wid = parseFloat($(`.size-width[data-row-id="${rowId}"]`).val()) || 0;
    var qty = parseFloat($(`.size-qty[data-row-id="${rowId}"]`).val()) || 0;
    var rate = parseFloat($(`.size-purchase-rate[data-row-id="${rowId}"]`).val()) || 0;
    var area = len * wid;
    var totalArea = area * qty;
    var amount = totalArea * rate;
    $(`.size-area[data-row-id="${rowId}"]`).val(area.toFixed(2));
    $(`.size-total-area[data-row-id="${rowId}"]`).val(totalArea.toFixed(2));
    $(`.size-amount[data-row-id="${rowId}"]`).val(amount.toFixed(2));
}

function renumberSizes() {
    $('#sizesBody .size-row').each(function(index) {
        $(this).find('.size-number').text(index + 1);
    });
}

function recalcAll() {
    var totalArea = 0;
    var totalAmount = 0;
    $('#sizesBody .size-row').each(function() {
        var rowId = $(this).data('row-id');
        totalArea += parseFloat($(`.size-total-area[data-row-id="${rowId}"]`).val()) || 0;
        totalAmount += parseFloat($(`.size-amount[data-row-id="${rowId}"]`).val()) || 0;
    });
    $('#displayTotalArea').text(totalArea.toFixed(2));
    $('#totalAmountDisplay').html('₨ ' + totalAmount.toFixed(2));
    if(totalAmount > 0) $('#totalAmountDisplay').css('color', '#1e7e34');
    else $('#totalAmountDisplay').css('color', '#0066cc');
}

function collectSizes() {
    var sizes = [];
    $('#sizesBody .size-row').each(function() {
        var rowId = $(this).data('row-id');
        var len = parseFloat($(`.size-length[data-row-id="${rowId}"]`).val()) || 0;
        var wid = parseFloat($(`.size-width[data-row-id="${rowId}"]`).val()) || 0;
        var qty = parseFloat($(`.size-qty[data-row-id="${rowId}"]`).val()) || 0;
        var pRate = parseFloat($(`.size-purchase-rate[data-row-id="${rowId}"]`).val()) || 0;
        var sRate = parseFloat($(`.size-sale-rate[data-row-id="${rowId}"]`).val()) || 0;
        sizes.push({ length_feet: len, width_feet: wid, opening_qty: qty, purchase_rate: pRate, sale_rate: sRate });
    });
    $('#sizes_data').val(JSON.stringify(sizes));
}

$(document).ready(function() {
    // Start with one empty size row
    addSizeRow('', '', '');
    
    $('#addSizeBtn').on('click', function() {
        addSizeRow('', '', '');
    });
    
    $('#removeLastSizeBtn').on('click', function() {
        var rows = $('#sizesBody .size-row');
        if(rows.length > 1) {
            rows.last().remove();
            renumberSizes();
            recalcAll();
        } else {
            Swal.fire('Info', 'At least one size is required!', 'info');
        }
    });
    
    $(document).on('click', '.remove-size-row', function() {
        var rows = $('#sizesBody .size-row');
        if(rows.length > 1) {
            $(this).closest('.size-row').remove();
            renumberSizes();
            recalcAll();
        } else {
            Swal.fire('Info', 'At least one size is required!', 'info');
        }
    });
    
    $('#purchase_price').on('keyup', function() {
        var price = parseFloat($(this).val()) || 0;
        $('.size-purchase-rate').val(price);
        $('#sizesBody .size-row').each(function() {
            recalcSizeRow($(this).data('row-id'));
        });
        recalcAll();
    });
    
    $('#sale_price').on('keyup', function() {
        $('.size-sale-rate').val(parseFloat($(this).val()) || 0);
    });
    
    $(document).on('keyup change', '.size-purchase-rate, .size-sale-rate', function() {
        var rowId = $(this).data('row-id');
        recalcSizeRow(rowId);
        recalcAll();
    });
    
    $('#productForm').on('submit', function(e) {
        collectSizes();
        var sizes = $('#sizes_data').val();
        var parsed = sizes ? JSON.parse(sizes) : [];
        var validSizes = parsed.filter(s => s.length_feet > 0 && s.width_feet > 0);
        
        var fields = ['product_name','category_id','company_id','supplier_id','unit_id'];
        for(var f of fields) if(!$(this).find('[name="'+f+'"]').val()) { e.preventDefault(); Swal.fire({title:'Error!', text:'Please fill all required fields!', icon:'error'}); return false; }
        var pp = parseFloat($('input[name="purchase_price"]').val()), sp = parseFloat($('input[name="sale_price"]').val());
        if(isNaN(pp) || pp<=0 || isNaN(sp) || sp<=0) { e.preventDefault(); Swal.fire({title:'Error!', text:'Prices must be greater than 0!', icon:'error'}); return false; }
        if(validSizes.length === 0) { e.preventDefault(); Swal.fire({title:'Error!', text:'Please add at least one size with valid length and width!', icon:'error'}); return false; }
    });
});

// ==================== NAME AUTOCOMPLETE ====================

function fetchNameSuggestions() {
    var term = $('#product_name').val().trim();
    if(term.length < 1) {
        $('#nameSuggestions').empty().hide();
        $('#duplicateWarning').hide();
        return;
    }
    
    $.ajax({
        url: 'search_product_names.php',
        method: 'GET',
        data: { q: term },
        dataType: 'json',
        success: function(results) {
            var box = $('#nameSuggestions');
            box.empty();
            if(results && results.length > 0) {
                $.each(results, function(i, item) {
                    box.append(`<div class="suggestion-item" data-name="${item.product_name}"><i class="fas fa-tag"></i> ${item.product_name} <small class="text-muted">(${item.product_code})</small></div>`);
                });
                box.show();
                $('#duplicateWarning').hide();
            } else {
                box.hide();
                $('#duplicateWarning').hide();
            }
        },
        error: function() {
            $('#nameSuggestions').empty().hide();
        }
    });
}

$(document).on('keyup', '#product_name', function() {
    var self = this;
    clearTimeout(autocompleteTimer);
    autocompleteTimer = setTimeout(function() {
        fetchNameSuggestions();
        checkDuplicateName($(self).val().trim());
    }, 300);
});

// Click a suggestion to fill the name
$(document).on('mousedown', '.suggestion-item', function() {
    var name = $(this).data('name');
    $('#product_name').val(name);
    $('#nameSuggestions').empty().hide();
    checkDuplicateName(name);
});

// Hide suggestions on outside click
$(document).on('click', function(e) {
    if(!$(e.target).closest('.autocomplete-wrap').length) {
        $('#nameSuggestions').hide();
    }
});

// Keyboard navigation (arrows + enter)
$(document).on('keydown', '#product_name', function(e) {
    var items = $('#nameSuggestions .suggestion-item');
    if(items.length === 0) return;
    
    var active = items.filter('.active');
    if(e.key === 'ArrowDown') {
        e.preventDefault();
        if(active.length) active.removeClass('active').next().addClass('active');
        else items.first().addClass('active');
    } else if(e.key === 'ArrowUp') {
        e.preventDefault();
        if(active.length) active.removeClass('active').prev().addClass('active');
        else items.last().addClass('active');
    } else if(e.key === 'Enter') {
        if(active.length) {
            e.preventDefault();
            $('#product_name').val(active.data('name'));
            $('#nameSuggestions').empty().hide();
        }
    } else if(e.key === 'Escape') {
        $('#nameSuggestions').hide();
    }
});

// Check if exact name already exists in DB
function checkDuplicateName(name) {
    if(!name || name.length < 1) { $('#duplicateWarning').hide(); return; }
    $.ajax({
        url: 'search_product_names.php',
        method: 'GET',
        data: { q: name, exact: 1 },
        dataType: 'json',
        success: function(results) {
            var found = results && results.some(r => r.product_name.toLowerCase() === name.toLowerCase());
            if(found) $('#duplicateWarning').show();
            else $('#duplicateWarning').hide();
        }
    });
}

function resetForm() {
    $('#productForm')[0].reset();
    $('#sizesBody').empty();
    sizeRowCount = 0;
    addSizeRow('', '', '');
    $('#displayTotalArea').text('0.00');
    $('#totalAmountDisplay').html('₨ 0.00');
    $('#nameSuggestions').empty().hide();
    $('#duplicateWarning').hide();
}
</script>

</body>
</html>
<?php mysqli_close($conn); ?>
