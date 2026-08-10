<?php
/**
 * Add Opening Stock Page - MODIFIED: Stock quantity = Area × Pieces
 * Faysal Glass And Aluminium Centre
 * 
 * Now the inventory tracks total area (sq ft) instead of piece count.
 * Area (sq ft) × Opening Quantity (pieces) = Total Stock Area (sq ft)
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

// Fetch categories, companies, units, suppliers (as before)
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
    $opening_qty_pieces = floatval($_POST['opening_qty']); // number of pieces
    $min_stock_alert = intval($_POST['min_stock_alert']);
    $location_rack = mysqli_real_escape_string($conn, trim($_POST['location_rack']));
    
    // Size fields
    $length_inch = floatval($_POST['length_inch']);
    $width_inch = floatval($_POST['width_inch']);
    $length_feet = floatval($_POST['length_feet']);
    $width_feet = floatval($_POST['width_feet']);
    $area_sqft = floatval($_POST['area_sqft']);
    
    // Calculate total stock area (sq ft) = area per piece × number of pieces
    $total_stock_area = $area_sqft * $opening_qty_pieces;
    
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
    } elseif($opening_qty_pieces < 0) {
        $error_msg = "Opening quantity cannot be negative!";
    } elseif($area_sqft <= 0 && $opening_qty_pieces > 0) {
        $error_msg = "Area must be greater than 0 when opening quantity is positive!";
    } else {
        // Check for duplicate product name
        $check_query = "SELECT id FROM products WHERE product_name = '$product_name'";
        $check_result = mysqli_query($conn, $check_query);
        
        if(mysqli_num_rows($check_result) > 0) {
            $error_msg = "Product name already exists!";
        } else {
            // Generate product code
            $product_code = generateProductCode($conn);
            
            // Insert Product with size fields (store area per piece and also the opening pieces as a note)
            $insert_product = "INSERT INTO products (product_code, product_name, category_id, company_id, unit_id, 
                               supplier_id, purchase_price, sale_price, min_stock_alert, location_rack,
                               length_inch, width_inch, length_feet, width_feet, area_sqft, status) 
                               VALUES ('$product_code', '$product_name', '$category_id', '$company_id', '$unit_id', 
                               '$supplier_id', '$purchase_price', '$sale_price', '$min_stock_alert', '$location_rack',
                               '$length_inch', '$width_inch', '$length_feet', '$width_feet', '$area_sqft', 1)";
            
            if(mysqli_query($conn, $insert_product)) {
                $product_id = mysqli_insert_id($conn);
                
                // Insert Opening Stock if quantity > 0
                if($opening_qty_pieces > 0 && $total_stock_area > 0) {
                    $total_amount = $total_stock_area * $purchase_price; // amount based on total area
                    $current_date = date('Y-m-d');
                    
                    // Insert into opening_stock table (store total area as quantity, and pieces as a separate column if needed)
                    // We'll add a column `pieces` if not exists, but to keep compatibility, we'll store total area as quantity
                    // and put piece info in remarks.
                    $remarks = "Opening Stock: $opening_qty_pieces pieces, each $area_sqft sq ft, total $total_stock_area sq ft";
                    $insert_opening = "INSERT INTO opening_stock (product_id, quantity, unit_price, total_amount, date, remarks, created_by) 
                                       VALUES ('$product_id', '$total_stock_area', '$purchase_price', '$total_amount', '$current_date', '$remarks', '{$_SESSION['user_id']}')";
                    mysqli_query($conn, $insert_opening);
                    
                    // Insert into inventory_ledger – qty_in = total area (sq ft)
                    $insert_ledger = "INSERT INTO inventory_ledger (date, product_id, reference_type, reference_id, 
                                       qty_in, qty_out, balance_qty, unit_price, total_amount, remarks) 
                                       VALUES ('$current_date', '$product_id', 'OPENING', '$product_id', 
                                       '$total_stock_area', 0, '$total_stock_area', '$purchase_price', '$total_amount', 'Opening Stock Entry - $remarks')";
                    mysqli_query($conn, $insert_ledger);
                }
                
                $success_msg = "Product added successfully! Product Code: $product_code. Stock tracked as total area ($total_stock_area sq ft).";
                
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
                                <input type="text" name="product_name" class="form-control" placeholder="Enter product name" required>
                                <small class="text-muted">Don't use special characters</small>
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
                    
                    <!-- Size Information -->
                    <div class="size-card">
                        <h5 class="size-title"><i class="fas fa-arrows-alt"></i> Product Size Information</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-md-6"><div class="form-group"><label>Length (Inch)</label><input type="number" step="0.01" name="length_inch" id="length_inch" class="form-control" value="0" onkeyup="calculateArea()"></div></div>
                                    <div class="col-md-6"><div class="form-group"><label>Width (Inch)</label><input type="number" step="0.01" name="width_inch" id="width_inch" class="form-control" value="0" onkeyup="calculateArea()"></div></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-md-6"><div class="form-group"><label>Length (Feet)</label><input type="number" step="0.01" name="length_feet" id="length_feet" class="form-control" value="0" onkeyup="calculateAreaFromFeet()"><small class="text-muted">1 Feet = 12 Inches</small></div></div>
                                    <div class="col-md-6"><div class="form-group"><label>Width (Feet)</label><input type="number" step="0.01" name="width_feet" id="width_feet" class="form-control" value="0" onkeyup="calculateAreaFromFeet()"><small class="text-muted">1 Feet = 12 Inches</small></div></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="required-field"><i class="fas fa-draw-polygon text-success mr-1"></i> Area per Piece (Square Feet)</label>
                                    <input type="number" step="0.01" name="area_sqft" id="area_sqft" class="form-control" readonly style="background:#e8f5e9; font-weight:bold;">
                                    <small class="text-muted">Area = (Length × Width) ÷ 144 (inches) OR Length × Width (feet)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6"><div class="form-group"><label class="required-field">Purchase Price (₨)</label><input type="number" step="0.01" name="purchase_price" id="purchase_price" class="form-control" required onkeyup="calculateTotal()"></div></div>
                        <div class="col-md-6"><div class="form-group"><label class="required-field">Sale Price (₨)</label><input type="number" step="0.01" name="sale_price" class="form-control" required></div></div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4"><div class="form-group"><label><i class="fas fa-boxes"></i> Opening Quantity (Pieces)</label><input type="number" step="0.01" name="opening_qty" id="opening_qty" class="form-control" value="0" onkeyup="calculateTotal()"><small class="text-muted">Number of pieces</small></div></div>
                        <div class="col-md-4"><div class="form-group"><label>Minimum Stock Alert</label><input type="number" name="min_stock_alert" class="form-control" value="0"></div></div>
                        <div class="col-md-4"><div class="form-group"><label>Location / Rack</label><input type="text" name="location_rack" class="form-control" placeholder="Enter storage location"></div></div>
                    </div>
                    
                    <div class="calculation-box">
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-calculator"></i> Opening Stock Value</h6>
                                <p class="mb-0">Total Stock Area = Area per piece × Pieces</p>
                                <p class="small text-muted mt-1">Total Area: <span id="displayTotalArea">0.00</span> sq ft</p>
                            </div>
                            <div class="col-md-6 text-right">
                                <h6 class="calculation-value" id="totalAmountDisplay">₨ 0.00</h6>
                                <input type="hidden" name="total_amount" id="totalAmount" value="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="info-note">
                        <i class="fas fa-info-circle"></i> <strong>Stock Management Note:</strong> Inventory will be tracked in <strong>square feet (total area)</strong>, not in pieces. When you sell or purchase, the quantity entered will be treated as total area. The per‑piece area is stored for reference and calculations.
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
                    <div class="col-md-3"><div class="text-center"><i class="fas fa-arrows-alt fa-2x text-success mb-2"></i><h6>Size Management</h6><p class="small">Length, width, area per piece</p></div></div>
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
function calculateArea() {
    var lenIn = parseFloat($('#length_inch').val()) || 0;
    var widIn = parseFloat($('#width_inch').val()) || 0;
    var area = (lenIn * widIn) / 144;
    $('#area_sqft').val(area.toFixed(2));
    $('#displayArea').text(area.toFixed(2));
    if(lenIn > 0) $('#length_feet').val((lenIn / 12).toFixed(2));
    if(widIn > 0) $('#width_feet').val((widIn / 12).toFixed(2));
    calculateTotal();
}
function calculateAreaFromFeet() {
    var lenFt = parseFloat($('#length_feet').val()) || 0;
    var widFt = parseFloat($('#width_feet').val()) || 0;
    var area = lenFt * widFt;
    $('#area_sqft').val(area.toFixed(2));
    $('#displayArea').text(area.toFixed(2));
    if(lenFt > 0) $('#length_inch').val((lenFt * 12).toFixed(2));
    if(widFt > 0) $('#width_inch').val((widFt * 12).toFixed(2));
    calculateTotal();
}
function calculateTotal() {
    var areaPerPiece = parseFloat($('#area_sqft').val()) || 0;
    var pieces = parseFloat($('#opening_qty').val()) || 0;
    var totalArea = areaPerPiece * pieces;
    var price = parseFloat($('#purchase_price').val()) || 0;
    var totalValue = totalArea * price;
    $('#displayTotalArea').text(totalArea.toFixed(2));
    $('#totalAmount').val(totalValue.toFixed(2));
    $('#totalAmountDisplay').html('₨ ' + totalValue.toFixed(2));
    if(totalValue > 0) $('#totalAmountDisplay').css('color', '#1e7e34');
    else $('#totalAmountDisplay').css('color', '#0066cc');
}
function resetForm() {
    $('#productForm')[0].reset();
    $('#area_sqft').val('0.00');
    $('#displayTotalArea').text('0.00');
    $('#totalAmountDisplay').html('₨ 0.00');
    $('#totalAmount').val('0');
}
$('input[name="product_name"]').on('keyup', function() { var v = $(this).val(); if(v.length) $(this).val(v.charAt(0).toUpperCase() + v.slice(1)); });
$('#productForm').on('submit', function(e) {
    var fields = ['product_name','category_id','company_id','supplier_id','unit_id'];
    for(var f of fields) if(!$(this).find('[name="'+f+'"]').val()) { e.preventDefault(); Swal.fire({title:'Error!', text:'Please fill all required fields!', icon:'error'}); return false; }
    var pp = parseFloat($('input[name="purchase_price"]').val()), sp = parseFloat($('input[name="sale_price"]').val());
    if(isNaN(pp) || pp<=0 || isNaN(sp) || sp<=0) { e.preventDefault(); Swal.fire({title:'Error!', text:'Prices must be greater than 0!', icon:'error'}); return false; }
});
</script>

</body>
</html>
<?php mysqli_close($conn); ?>