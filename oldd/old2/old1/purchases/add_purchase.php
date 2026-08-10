<?php
/**
 * Add Purchase Page - UOM REMOVED
 * Faysal Glass And Aluminium Centre
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$page_title = "Add Purchase";
$success_msg = '';
$error_msg = '';

// Generate Invoice Number
function generateInvoiceNo($conn) {
    $prefix = "PUR";
    $query = "SELECT invoice_no FROM purchase_master WHERE invoice_no LIKE '{$prefix}%' ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $last_no = $row['invoice_no'];
        $number = intval(substr($last_no, 4)) + 1;
        return $prefix . "-" . str_pad($number, 5, '0', STR_PAD_LEFT);
    } else {
        return $prefix . "-00001";
    }
}

// Fetch suppliers for dropdown
$suppliers_query = "SELECT id, supplier_name, supplier_code, mobile, current_balance FROM suppliers WHERE status = 1 ORDER BY supplier_name";
$suppliers_result = mysqli_query($conn, $suppliers_query);

// Fetch bank accounts
$bank_query = "SELECT id, bank_name, account_title FROM bank_accounts WHERE status = 1";
$bank_result = mysqli_query($conn, $bank_query);

// Fetch products for dropdown
$products_query = "SELECT p.*, c.category_name, u.short_name as unit_name, u.id as unit_id
                   FROM products p
                   LEFT JOIN categories c ON p.category_id = c.id
                   LEFT JOIN units u ON p.unit_id = u.id
                   WHERE p.status = 1 
                   ORDER BY p.product_name";
$products_result = mysqli_query($conn, $products_query);
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
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.0.2/dist/select2-bootstrap4.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        .btn-green { background-color: #1e7e34; border-color: #1e7e34; color: white; }
        .btn-green:hover { background-color: #155724; border-color: #155724; color: white; }
        .card-header-custom { background: linear-gradient(135deg, #1e7e34, #0066cc); color: white; border-radius: 10px 10px 0 0; padding: 15px 20px; }
        .form-card { border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 30px; }
        .calculation-panel { background: linear-gradient(135deg, #f8f9fc, #e9ecef); padding: 20px; border-radius: 10px; }
        .calculation-row { padding: 8px 0; border-bottom: 1px solid #ddd; }
        .calculation-row:last-child { border-bottom: none; }
        .supplier-info { background: #e8f5e9; padding: 15px; border-radius: 8px; }
        .required-field::after { content: " *"; color: red; }
        .table thead th { background-color: #1e7e34; color: white; font-weight: 600; font-size: 12px; white-space: nowrap; }
        .remove-product { cursor: pointer; color: #dc3545; }
        .remove-product:hover { color: #a71d2a; }
        .select2-container .select2-selection--single { height: 38px; }
        .table td { padding: 8px; vertical-align: middle; }
        .table input, .table select { width: 100%; min-width: 80px; }
        .std-height, .std-width { background-color: #f0f8ff; }
    </style>
</head>
<body id="page-top">

<div id="wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <div class="container-fluid">
        
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-shopping-cart text-success mr-2"></i> Add Purchase
            </h1>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Add Purchase</li>
            </ol>
        </div>
        
        <div id="alertMessage"></div>
        
        <form method="POST" action="" id="purchaseForm">
            <!-- Invoice Header -->
            <div class="card form-card">
                <div class="card-header-custom">
                    <i class="fas fa-file-invoice mr-2"></i> Purchase Invoice
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-calendar text-success mr-1"></i> Purchase Date</label>
                                <input type="date" name="purchase_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><i class="fas fa-barcode text-success mr-1"></i> Invoice Number</label>
                                <input type="text" id="invoice_no_display" class="form-control" value="<?php echo generateInvoiceNo($conn); ?>" readonly style="background:#e8f5e9; font-weight:bold;">
                                <input type="hidden" name="invoice_no" id="invoice_no" value="<?php echo generateInvoiceNo($conn); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-truck text-success mr-1"></i> Supplier</label>
                                <select name="supplier_id" id="supplier_id" class="form-control" required>
                                    <option value="">Select Supplier</option>
                                    <?php while($sup = mysqli_fetch_assoc($suppliers_result)): ?>
                                        <option value="<?php echo $sup['id']; ?>" data-mobile="<?php echo $sup['mobile']; ?>" data-balance="<?php echo $sup['current_balance']; ?>">
                                            <?php echo htmlspecialchars($sup['supplier_name'] . ' (' . $sup['supplier_code'] . ')'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="supplier-info">
                                <small class="text-muted">Supplier Mobile</small>
                                <div id="supplier_mobile" class="font-weight-bold">-</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="supplier-info">
                                <small class="text-muted">Previous Balance</small>
                                <div id="supplier_balance" class="font-weight-bold">₨ 0.00</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-receipt text-success mr-1"></i> Reference Number</label>
                                <input type="text" name="reference_no" class="form-control" placeholder="Enter reference number">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label><i class="fas fa-comment text-success mr-1"></i> Remarks</label>
                                <textarea name="remarks" class="form-control" rows="2" placeholder="Enter remarks"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Products Section - UOM COLUMN REMOVED -->
            <div class="card form-card">
                <div class="card-header-custom">
                    <i class="fas fa-boxes mr-2"></i> Products
                </div>
                <div class="card-body" style="overflow-x: auto;">
                    <table class="table table-bordered" id="productsTable" style="min-width: 1600px;">
                        <thead>
                            <tr>
                                <th style="min-width:180px;">Product</th>
                                <th style="min-width:100px;">Client Height (H)</th>
                                <th style="min-width:100px;">Client Width (W)</th>
                                <th style="min-width:90px;">Std Height</th>
                                <th style="min-width:90px;">Std Width</th>
                                <th style="min-width:70px;">QTY</th>
                                <th style="min-width:100px;">Unit Price</th>
                                <th style="min-width:100px;">Retail Price</th>
                                <th style="min-width:90px;">Area (sq ft)</th>
                                <th style="min-width:100px;">Amount</th>
                                <th style="min-width:70px;">Dis %</th>
                                <th style="min-width:100px;">Net Amount</th>
                                <th style="min-width:60px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="productsBody">
                            <tr id="product_row_0" class="product-row">
                                <td>
                                    <select name="product_id[]" class="form-control product-select" data-row="0" required>
                                        <option value="">Search product...</option>
                                        <?php 
                                        mysqli_data_seek($products_result, 0);
                                        while($prod = mysqli_fetch_assoc($products_result)): 
                                            $stock_query = "SELECT balance_qty FROM inventory_ledger WHERE product_id = {$prod['id']} ORDER BY id DESC LIMIT 1";
                                            $stock_res = mysqli_query($conn, $stock_query);
                                            $current_stock = 0;
                                            if($stock_res && mysqli_num_rows($stock_res) > 0) {
                                                $stock_data = mysqli_fetch_assoc($stock_res);
                                                $current_stock = floatval($stock_data['balance_qty']);
                                            }
                                        ?>
                                            <option value="<?php echo $prod['id']; ?>" 
                                                    data-price="<?php echo $prod['purchase_price']; ?>"
                                                    data-stock="<?php echo $current_stock; ?>"
                                                    data-name="<?php echo htmlspecialchars($prod['product_name']); ?>">
                                                <?php echo htmlspecialchars($prod['product_name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </td>
                                <td><input type="number" step="0.01" name="client_height[]" class="form-control client-height" data-row="0" placeholder="H" value="0"></td>
                                <td><input type="number" step="0.01" name="client_width[]" class="form-control client-width" data-row="0" placeholder="W" value="0"></td>
                                <td><input type="number" step="0.01" name="std_height[]" class="form-control std-height" data-row="0" placeholder="Std H" readonly style="background:#f0f8ff;" value="0"></td>
                                <td><input type="number" step="0.01" name="std_width[]" class="form-control std-width" data-row="0" placeholder="Std W" readonly style="background:#f0f8ff;" value="0"></td>
                                <td><input type="number" step="0.01" name="quantity[]" class="form-control quantity" data-row="0" value="1" min="0"></td>
                                <td><input type="number" step="0.01" name="unit_price[]" class="form-control unit-price" data-row="0" value="0" min="0"></td>
                                <td><input type="number" step="0.01" name="retail_price[]" class="form-control retail-price" data-row="0" value="0" min="0"></td>
                                <td><input type="number" step="0.01" name="area[]" class="form-control area" data-row="0" value="0" readonly style="background:#e9ecef;"></td>
                                <td><input type="number" step="0.01" name="amount[]" class="form-control row-amount" data-row="0" value="0" readonly style="background:#e9ecef;"></td>
                                <td><input type="number" step="0.01" name="discount_percent[]" class="form-control discount-percent" data-row="0" value="0" min="0" max="100"></td>
                                <td><input type="number" step="0.01" name="net_amount[]" class="form-control net-amount" data-row="0" value="0" readonly style="background:#e9ecef;"></td>
                                <td class="text-center"><i class="fas fa-trash text-danger remove-product" data-row="0" style="cursor:pointer; font-size:18px;"></i></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="13">
                                    <button type="button" class="btn btn-success btn-sm" id="addProductBtn">
                                        <i class="fas fa-plus-circle"></i> Add New Product
                                    </button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            
            <!-- Calculation Panel (same) -->
            <div class="card form-card">
                <div class="card-header-custom">
                    <i class="fas fa-calculator mr-2"></i> Payment Summary
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="calculation-panel">
                                <div class="calculation-row">
                                    <div class="row"><div class="col-6">Total Bill Amount:</div><div class="col-6 text-right"><strong id="subtotal">₨ 0.00</strong></div></div>
                                </div>
                                <div class="calculation-row">
                                    <div class="row"><div class="col-6">Discount Amount:</div><div class="col-6 text-right"><strong id="discountAmount">₨ 0.00</strong></div></div>
                                </div>
                                <div class="calculation-row">
                                    <div class="row"><div class="col-6">Discount %:</div><div class="col-6"><input type="number" step="0.01" name="discount_percentage" id="global_discount" class="form-control form-control-sm" value="0" min="0" max="100" style="width:100px; display:inline-block;"> <span>%</span></div></div>
                                </div>
                                <div class="calculation-row">
                                    <div class="row"><div class="col-6">Other Charges:</div><div class="col-6"><input type="number" step="0.01" name="other_charges" id="other_charges" class="form-control form-control-sm" value="0" min="0" style="width:120px; display:inline-block;"></div></div>
                                </div>
                                <div class="calculation-row">
                                    <div class="row"><div class="col-6"><strong>Grand Total:</strong></div><div class="col-6 text-right"><strong id="grandTotal" style="font-size:18px; color:#1e7e34;">₨ 0.00</strong></div></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="calculation-panel">
                                <div class="calculation-row">
                                    <div class="row"><div class="col-6">Previous Balance:</div><div class="col-6 text-right"><strong id="prevBalance">₨ 0.00</strong></div></div>
                                </div>
                                <div class="calculation-row">
                                    <div class="row"><div class="col-6">Current Purchase:</div><div class="col-6 text-right"><strong id="currentPurchase">₨ 0.00</strong></div></div>
                                </div>
                                <div class="calculation-row">
                                    <div class="row"><div class="col-6">New Balance:</div><div class="col-6 text-right"><strong id="newBalance" style="font-size:18px;">₨ 0.00</strong></div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-money-bill-wave text-success mr-1"></i> Payment Type</label>
                                <select name="payment_type" id="payment_type" class="form-control" required>
                                    <option value="credit">Credit Purchase</option>
                                    <option value="cash">Cash</option>
                                    <option value="bank">Bank Transfer</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4" id="bank_div" style="display: none;">
                            <div class="form-group">
                                <label><i class="fas fa-university text-success mr-1"></i> Select Bank</label>
                                <select name="bank_account_id" class="form-control">
                                    <option value="">Select Bank Account</option>
                                    <?php while($bank = mysqli_fetch_assoc($bank_result)): ?>
                                        <option value="<?php echo $bank['id']; ?>"><?php echo htmlspecialchars($bank['bank_name'] . ' - ' . $bank['account_title']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-rupee-sign text-success mr-1"></i> Paid Amount</label>
                                <input type="number" step="0.01" name="paid_amount" id="paid_amount" class="form-control" value="0" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-chart-line text-success mr-1"></i> Remaining Amount</label>
                                <input type="text" name="remaining_amount" id="remaining_amount" class="form-control" readonly style="background:#e8f5e9;">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-12 text-right">
                            <button type="button" class="btn btn-secondary" id="refreshBtn"><i class="fas fa-sync-alt mr-1"></i> Refresh</button>
                            <a href="view_invoice.php" class="btn btn-info"><i class="fas fa-list mr-1"></i> View Purchases</a>
                            <button type="button" id="generateInvoiceBtn" class="btn btn-green"><i class="fas fa-save mr-1"></i> Generate Invoice</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <input type="hidden" name="subtotal" id="subtotal_input" value="0">
            <input type="hidden" name="discount_amount" id="discount_amount_input" value="0">
            <input type="hidden" name="grand_total" id="grand_total_input" value="0">
        </form>
        
    </div>
    
    <footer class="sticky-footer bg-white">
        <div class="container my-auto"><div class="copyright text-center my-auto"><span>&copy; <?php echo date('Y'); ?> <?php echo $software_name; ?> - All Rights Reserved</span></div></div>
    </footer>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/js/sb-admin-2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
let productCount = 1;

function roundUpToMultipleOf6(value) {
    if (value <= 0) return 6;
    return Math.ceil(value / 6) * 6;
}

function calculateArea(height, width) {
    if (height > 0 && width > 0) {
        return (height * width) / 144;
    }
    return 0;
}

function calculateRowAmount(row) {
    var area = parseFloat($(`.area[data-row="${row}"]`).val()) || 0;
    var unitPrice = parseFloat($(`.unit-price[data-row="${row}"]`).val()) || 0;
    var quantity = parseFloat($(`.quantity[data-row="${row}"]`).val()) || 0;
    var discountPercent = parseFloat($(`.discount-percent[data-row="${row}"]`).val()) || 0;
    
    var amount = area * unitPrice * quantity;
    var discountAmount = amount * (discountPercent / 100);
    var netAmount = amount - discountAmount;
    
    $(`.row-amount[data-row="${row}"]`).val(amount.toFixed(2));
    $(`.net-amount[data-row="${row}"]`).val(netAmount.toFixed(2));
    
    return netAmount;
}

function calculateTotals() {
    var subtotal = 0;
    
    for(var i = 0; i < productCount; i++) {
        if($(`#product_row_${i}`).length) {
            var netAmount = parseFloat($(`.net-amount[data-row="${i}"]`).val()) || 0;
            subtotal += netAmount;
        }
    }
    
    var globalDiscPercent = parseFloat($('#global_discount').val()) || 0;
    var globalDiscountAmt = subtotal * (globalDiscPercent / 100);
    var afterGlobalDiscount = subtotal - globalDiscountAmt;
    var otherCharges = parseFloat($('#other_charges').val()) || 0;
    var grandTotal = afterGlobalDiscount + otherCharges;
    var prevBalance = parseFloat($('#prevBalance').data('value')) || 0;
    var newBalance = prevBalance + grandTotal;
    var paidAmount = parseFloat($('#paid_amount').val()) || 0;
    
    if(paidAmount > grandTotal) { paidAmount = grandTotal; $('#paid_amount').val(paidAmount); }
    var remainingAmount = grandTotal - paidAmount;
    
    $('#subtotal').text('₨ ' + subtotal.toFixed(2));
    $('#discountAmount').text('₨ ' + globalDiscountAmt.toFixed(2));
    $('#grandTotal').text('₨ ' + grandTotal.toFixed(2));
    $('#currentPurchase').text('₨ ' + grandTotal.toFixed(2));
    $('#newBalance').text('₨ ' + newBalance.toFixed(2));
    $('#remaining_amount').val(remainingAmount.toFixed(2));
    $('#subtotal_input').val(subtotal.toFixed(2));
    $('#discount_amount_input').val(globalDiscountAmt.toFixed(2));
    $('#grand_total_input').val(grandTotal.toFixed(2));
    
    if(newBalance > 0) $('#newBalance').css('color', '#dc3545');
    else if(newBalance < 0) $('#newBalance').css('color', '#28a745');
    else $('#newBalance').css('color', '#1a1a1a');
}

function updateStdAndArea(row) {
    var clientH = parseFloat($(`.client-height[data-row="${row}"]`).val()) || 0;
    var clientW = parseFloat($(`.client-width[data-row="${row}"]`).val()) || 0;
    
    var stdH = roundUpToMultipleOf6(clientH);
    var stdW = roundUpToMultipleOf6(clientW);
    var area = calculateArea(stdH, stdW);
    
    $(`.std-height[data-row="${row}"]`).val(stdH);
    $(`.std-width[data-row="${row}"]`).val(stdW);
    $(`.area[data-row="${row}"]`).val(area.toFixed(2));
}

function addProductRow() {
    var newRow = `
        <tr id="product_row_${productCount}" class="product-row">
            <td>
                <select name="product_id[]" class="form-control product-select" data-row="${productCount}" required>
                    <option value="">Search product...</option>
                    <?php 
                    mysqli_data_seek($products_result, 0);
                    while($prod = mysqli_fetch_assoc($products_result)): ?>
                        <option value="<?php echo $prod['id']; ?>" data-price="<?php echo $prod['purchase_price']; ?>"><?php echo htmlspecialchars($prod['product_name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </td>
            <td><input type="number" step="0.01" name="client_height[]" class="form-control client-height" data-row="${productCount}" placeholder="H" value="0"></td>
            <td><input type="number" step="0.01" name="client_width[]" class="form-control client-width" data-row="${productCount}" placeholder="W" value="0"></td>
            <td><input type="number" step="0.01" name="std_height[]" class="form-control std-height" data-row="${productCount}" placeholder="Std H" readonly style="background:#f0f8ff;" value="0"></td>
            <td><input type="number" step="0.01" name="std_width[]" class="form-control std-width" data-row="${productCount}" placeholder="Std W" readonly style="background:#f0f8ff;" value="0"></td>
            <td><input type="number" step="0.01" name="quantity[]" class="form-control quantity" data-row="${productCount}" value="1" min="0"></td>
            <td><input type="number" step="0.01" name="unit_price[]" class="form-control unit-price" data-row="${productCount}" value="0" min="0"></td>
            <td><input type="number" step="0.01" name="retail_price[]" class="form-control retail-price" data-row="${productCount}" value="0" min="0"></td>
            <td><input type="number" step="0.01" name="area[]" class="form-control area" data-row="${productCount}" value="0" readonly style="background:#e9ecef;"></td>
            <td><input type="number" step="0.01" name="amount[]" class="form-control row-amount" data-row="${productCount}" value="0" readonly style="background:#e9ecef;"></td>
            <td><input type="number" step="0.01" name="discount_percent[]" class="form-control discount-percent" data-row="${productCount}" value="0" min="0" max="100"></td>
            <td><input type="number" step="0.01" name="net_amount[]" class="form-control net-amount" data-row="${productCount}" value="0" readonly style="background:#e9ecef;"></td>
            <td class="text-center"><i class="fas fa-trash text-danger remove-product" data-row="${productCount}" style="cursor:pointer; font-size:18px;"></i></td>
        </tr>
    `;
    $('#productsBody').append(newRow);
    
    $(`.product-select[data-row="${productCount}"]`).select2({ theme: 'bootstrap4', placeholder: 'Search product...', width: '100%' });
    
    $(`.product-select[data-row="${productCount}"]`).on('change', function() {
        var row = $(this).data('row');
        var selected = $(this).find(':selected');
        var price = selected.data('price') || 0;
        $(`.unit-price[data-row="${row}"]`).val(price);
        calculateRowAmount(row);
        calculateTotals();
    });
    
    $(`.client-height[data-row="${productCount}"], .client-width[data-row="${productCount}"]`).on('keyup change', function() {
        var row = $(this).data('row');
        updateStdAndArea(row);
        calculateRowAmount(row);
        calculateTotals();
    });
    
    $(`.quantity[data-row="${productCount}"], .unit-price[data-row="${productCount}"], .discount-percent[data-row="${productCount}"]`).on('keyup change', function() {
        var row = $(this).data('row');
        calculateRowAmount(row);
        calculateTotals();
    });
    
    $(`.remove-product[data-row="${productCount}"]`).on('click', function() {
        var row = $(this).data('row');
        $(`#product_row_${row}`).remove();
        calculateTotals();
    });
    
    productCount++;
}

$(document).ready(function() {
    $('.product-select').select2({ theme: 'bootstrap4', placeholder: 'Search product...', width: '100%' });
    
    $('.client-height[data-row="0"], .client-width[data-row="0"]').on('keyup change', function() {
        var row = $(this).data('row');
        updateStdAndArea(row);
        calculateRowAmount(row);
        calculateTotals();
    });
    
    $('.product-select[data-row="0"]').on('change', function() {
        var row = $(this).data('row');
        var selected = $(this).find(':selected');
        var price = selected.data('price') || 0;
        $(`.unit-price[data-row="${row}"]`).val(price);
        calculateRowAmount(row);
        calculateTotals();
    });
    
    $('.quantity[data-row="0"], .unit-price[data-row="0"], .discount-percent[data-row="0"]').on('keyup change', function() {
        var row = $(this).data('row');
        calculateRowAmount(row);
        calculateTotals();
    });
    
    $('#supplier_id').on('change', function() {
        var selected = $(this).find(':selected');
        var mobile = selected.data('mobile') || '-';
        var balance = selected.data('balance') || 0;
        $('#supplier_mobile').text(mobile);
        $('#prevBalance').text('₨ ' + parseFloat(balance).toFixed(2));
        $('#prevBalance').data('value', balance);
        calculateTotals();
    });
    
    $('#payment_type').on('change', function() {
        if($(this).val() === 'bank') $('#bank_div').show();
        else { $('#bank_div').hide(); $('select[name="bank_account_id"]').val(''); }
        calculateTotals();
    });
    
    $('#global_discount, #other_charges, #paid_amount').on('keyup change', function() { calculateTotals(); });
    $('#addProductBtn').on('click', function() { addProductRow(); });
    $('#refreshBtn').on('click', function() { location.reload(); });
    
    updateStdAndArea(0);
    calculateRowAmount(0);
    calculateTotals();
    
    // Submit via AJAX
    $('#generateInvoiceBtn').on('click', function(e) {
        e.preventDefault();
        
        var supplier = $('#supplier_id').val();
        var hasProduct = false;
        $('select[name="product_id[]"]').each(function() { if($(this).val() !== '') hasProduct = true; });
        
        if(!supplier) {
            Swal.fire({ title: 'Error!', text: 'Please select a supplier!', icon: 'error' });
            return false;
        }
        if(!hasProduct) {
            Swal.fire({ title: 'Error!', text: 'Please add at least one product!', icon: 'error' });
            return false;
        }
        
        var grandTotal = parseFloat($('#grand_total_input').val());
        if(grandTotal <= 0) {
            Swal.fire({ title: 'Error!', text: 'Grand total must be greater than 0!', icon: 'error' });
            return false;
        }
        
        var paymentType = $('#payment_type').val();
        var paidAmount = parseFloat($('#paid_amount').val());
        
        if(paymentType !== 'credit' && paidAmount <= 0) {
            Swal.fire({ title: 'Error!', text: 'Paid amount is required for cash/bank payment!', icon: 'error' });
            return false;
        }
        if(paymentType === 'bank') {
            var bankAccount = $('select[name="bank_account_id"]').val();
            if(!bankAccount) {
                Swal.fire({ title: 'Error!', text: 'Please select a bank account!', icon: 'error' });
                return false;
            }
        }
        
        // Collect form data
        var formData = new FormData($('#purchaseForm')[0]);
        formData.append('save_purchase', '1');
        
        Swal.fire({
            title: 'Processing...',
            text: 'Please wait while we save your purchase invoice',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        $.ajax({
            url: 'save_purchase.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: response.message,
                        icon: 'success',
                        confirmButtonColor: '#1e7e34'
                    }).then((result) => {
                        if(result.isConfirmed) {
                            window.location.href = 'print_invoice.php?invoice_no=' + response.invoice_no;
                        }
                    });
                } else {
                    Swal.fire({ title: 'Error!', text: response.message, icon: 'error' });
                }
            },
            error: function() {
                Swal.fire({ title: 'Error!', text: 'Failed to save purchase invoice!', icon: 'error' });
            }
        });
    });
});
</script>

</body>
</html>

<?php mysqli_close($conn); ?>