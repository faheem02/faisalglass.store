<?php
/**
 * Edit Sale Invoice Page - FIXED: Recalculate area from client dimensions on load
 * Faysal Glass And Aluminium Centre
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$page_title = "Edit Sale Invoice";
$sale_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($sale_id <= 0) {
    header("Location: view_invoice.php");
    exit();
}

// Fetch sale master
$query = "SELECT s.*, c.customer_name, c.customer_code, c.mobile, c.address, c.current_balance 
          FROM sale_master s
          LEFT JOIN customers c ON s.customer_id = c.id
          WHERE s.id = $sale_id";
$result = mysqli_query($conn, $query);
if(mysqli_num_rows($result) == 0) {
    header("Location: view_invoice.php");
    exit();
}
$sale = mysqli_fetch_assoc($result);

// Fetch sale details
$details_query = "SELECT sd.*, p.product_name, p.product_code 
                 FROM sale_details sd
                 LEFT JOIN products p ON sd.product_id = p.id
                 WHERE sd.sale_id = $sale_id";
$details_result = mysqli_query($conn, $details_query);
$details = [];
while($row = mysqli_fetch_assoc($details_result)) {
    $details[] = $row;
}

// Fetch customers for dropdown
$customers_query = "SELECT id, customer_name, customer_code, mobile, current_balance 
                    FROM customers WHERE status = 1 
                    ORDER BY customer_name";
$customers_result = mysqli_query($conn, $customers_query);

// Fetch products
$products_query = "SELECT p.*, c.category_name, u.short_name as unit_name, u.id as unit_id,
                   (SELECT balance_qty FROM inventory_ledger WHERE product_id = p.id ORDER BY id DESC LIMIT 1) as current_stock
                   FROM products p
                   LEFT JOIN categories c ON p.category_id = c.id
                   LEFT JOIN units u ON p.unit_id = u.id
                   WHERE p.status = 1 
                   ORDER BY p.product_name";
$products_result = mysqli_query($conn, $products_query);

// Fetch bank accounts
$bank_query = "SELECT id, bank_name, account_title, account_number FROM bank_accounts WHERE status = 1";
$bank_result = mysqli_query($conn, $bank_query);

$invoice_no = $sale['invoice_no'];
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
        .customer-info { background: #e8f5e9; padding: 15px; border-radius: 8px; }
        .required-field::after { content: " *"; color: red; }
        .table thead th { background-color: #1e7e34; color: white; font-weight: 600; font-size: 12px; white-space: nowrap; }
        .remove-row { cursor: pointer; color: #dc3545; }
        .remove-row:hover { color: #a71d2a; }
        .select2-container .select2-selection--single { height: 38px; }
        .table td { padding: 8px; vertical-align: middle; }
        .table input, .table select { width: 100%; min-width: 80px; }
        .std-height, .std-width { background-color: #f0f8ff; }
        .add-size-row { background: none; border: none; color: #1e7e34; cursor: pointer; font-size: 18px; }
        .add-size-row:hover { color: #0066cc; }
        .edit-mode-badge { background: #ffc107; color: #1a1a1a; padding: 2px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
    </style>
</head>
<body id="page-top">

<div id="wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <div class="container-fluid">
        
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-edit text-warning mr-2"></i> Edit Sale Invoice
                <span class="edit-mode-badge ml-3"><?php echo $invoice_no; ?></span>
            </h1>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="view_invoice.php">View Sales</a></li>
                <li class="breadcrumb-item active">Edit Sale</li>
            </ol>
        </div>
        
        <div id="alertMessage"></div>
        
        <form method="POST" action="" id="saleForm">
            <input type="hidden" name="sale_id" id="sale_id" value="<?php echo $sale_id; ?>">
            <input type="hidden" name="edit_sale" value="1">
            
            <!-- Invoice Header -->
            <div class="card form-card">
                <div class="card-header-custom">
                    <i class="fas fa-file-invoice mr-2"></i> Sale Invoice (Edit Mode)
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-calendar text-success mr-1"></i> Sale Date</label>
                                <input type="date" name="sale_date" class="form-control" value="<?php echo date('Y-m-d', strtotime($sale['sale_date'])); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><i class="fas fa-barcode text-success mr-1"></i> Invoice Number</label>
                                <input type="text" class="form-control" value="<?php echo $invoice_no; ?>" readonly style="background:#e8f5e9; font-weight:bold;">
                                <input type="hidden" name="invoice_no" value="<?php echo $invoice_no; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-user text-success mr-1"></i> Customer</label>
                                <div class="input-group">
                                    <select name="customer_id" id="customer_id" class="form-control">
                                        <option value="">-- Select or Type to Search --</option>
                                        <?php while($cust = mysqli_fetch_assoc($customers_result)): ?>
                                            <option value="<?php echo $cust['id']; ?>" 
                                                    data-mobile="<?php echo $cust['mobile']; ?>"
                                                    data-balance="<?php echo $cust['current_balance']; ?>"
                                                    <?php echo ($cust['id'] == $sale['customer_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cust['customer_name'] . ' (' . $cust['customer_code'] . ')'); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-warning" id="newCustomerBtn" title="New Walk-in Customer">
                                            <i class="fas fa-plus"></i> New
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted">Select existing customer or click "New" for walk-in customer</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="customer-info">
                                <small class="text-muted">Customer Mobile</small>
                                <div id="customer_mobile" class="font-weight-bold"><?php echo $sale['mobile'] ?? '-'; ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="customer-info">
                                <small class="text-muted">Previous Balance</small>
                                <div id="customer_balance" class="font-weight-bold">₨ <?php echo number_format($sale['current_balance'] ?? 0, 2); ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-receipt text-success mr-1"></i> Reference Number</label>
                                <input type="text" name="reference_no" class="form-control" placeholder="Enter reference number" value="<?php echo htmlspecialchars($sale['reference_no'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label><i class="fas fa-comment text-success mr-1"></i> Remarks</label>
                                <textarea name="remarks" class="form-control" rows="2" placeholder="Enter remarks"><?php echo htmlspecialchars($sale['remarks'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Products Section -->
            <div class="card form-card">
                <div class="card-header-custom">
                    <i class="fas fa-boxes mr-2"></i> Products
                </div>
                <div class="card-body" style="overflow-x: auto;">
                    <div id="productGroupsContainer"></div>
                    
                    <div class="text-center mt-3">
                        <button type="button" class="btn btn-success" id="addNewProductBtn">
                            <i class="fas fa-plus-circle"></i> Add New Product
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Calculation Panel -->
            <div class="card form-card">
                <div class="card-header-custom">
                    <i class="fas fa-calculator mr-2"></i> Payment Summary
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-7">
                            <div class="calculation-panel">
                                <div class="calculation-row">
                                    <div class="row"><div class="col-4">SUBTOTAL:</div><div class="col-8 text-right"><strong id="subtotal">₨ 0.00</strong></div></div>
                                </div>
                                <div class="calculation-row">
                                    <div class="row"><div class="col-4">DISCOUNT:</div><div class="col-8 text-right"><strong id="discountAmount">₨ 0.00</strong></div></div>
                                </div>
                                <div class="calculation-row">
                                    <div class="row"><div class="col-4">DELIVERY / OTHER CHARGES:</div><div class="col-8"><input type="number" step="0.01" name="other_charges" id="other_charges" class="form-control form-control-sm" value="<?php echo $sale['other_charges']; ?>" min="0" style="width:150px; display:inline-block; text-align:right;"></div></div>
                                </div>
                                <div class="calculation-row">
                                    <div class="row"><div class="col-4">ADVANCE / RECEIVED:</div><div class="col-8"><input type="number" step="0.01" name="received_amount" id="received_amount" class="form-control form-control-sm" value="<?php echo $sale['received_amount']; ?>" min="0" style="width:150px; display:inline-block; text-align:right;"></div></div>
                                </div>
                                <div class="calculation-row" style="background: #e8f5e9;">
                                    <div class="row"><div class="col-4"><strong>BALANCE:</strong></div><div class="col-8 text-right"><strong id="grandTotal" style="font-size:20px; color:#dc3545;">₨ 0.00</strong></div></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="calculation-panel">
                                <div class="calculation-row">
                                    <div class="row"><div class="col-6">Payment Method:</div><div class="col-6"><select name="payment_type" id="payment_type" class="form-control form-control-sm">
                                        <option value="cash" <?php echo ($sale['payment_type'] == 'cash') ? 'selected' : ''; ?>>Cash</option>
                                        <option value="bank" <?php echo ($sale['payment_type'] == 'bank') ? 'selected' : ''; ?>>Bank</option>
                                        <option value="credit" <?php echo ($sale['payment_type'] == 'credit') ? 'selected' : ''; ?>>Credit</option>
                                        <option value="partial" <?php echo ($sale['payment_type'] == 'partial') ? 'selected' : ''; ?>>Partial</option>
                                    </select></div></div>
                                </div>
                                <div class="calculation-row" id="bank_div" style="<?php echo ($sale['payment_type'] == 'bank') ? 'display:block;' : 'display:none;'; ?>">
                                    <div class="row"><div class="col-6">Select Bank:</div><div class="col-6"><select name="bank_account_id" class="form-control form-control-sm">
                                        <option value="">Select Bank</option>
                                        <?php 
                                        mysqli_data_seek($bank_result, 0);
                                        while($bank = mysqli_fetch_assoc($bank_result)): ?>
                                            <option value="<?php echo $bank['id']; ?>" <?php echo ($sale['bank_account_id'] == $bank['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($bank['bank_name'] . ' - ' . ($bank['account_number'] ?? $bank['account_title'])); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select></div></div>
                                </div>
                                <div class="calculation-row">
                                    <div class="row"><div class="col-6">Previous Balance:</div><div class="col-6 text-right"><strong id="prevBalance">₨ 0.00</strong></div></div>
                                </div>
                                <div class="calculation-row">
                                    <div class="row"><div class="col-6">Current Sale:</div><div class="col-6 text-right"><strong id="currentSale">₨ 0.00</strong></div></div>
                                </div>
                                <div class="calculation-row">
                                    <div class="row"><div class="col-6">New Balance:</div><div class="col-6 text-right"><strong id="newBalance" style="font-size:16px;">₨ 0.00</strong></div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-12 text-right">
                            <button type="button" class="btn btn-secondary" id="refreshBtn"><i class="fas fa-sync-alt mr-1"></i> Refresh</button>
                            <a href="view_invoice.php" class="btn btn-info"><i class="fas fa-list mr-1"></i> View Sales</a>
                            <button type="button" id="updateInvoiceBtn" class="btn btn-green"><i class="fas fa-save mr-1"></i> Update Invoice</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <input type="hidden" name="subtotal" id="subtotal_input" value="<?php echo $sale['subtotal']; ?>">
            <input type="hidden" name="discount_amount" id="discount_amount_input" value="<?php echo $sale['discount_amount']; ?>">
            <input type="hidden" name="grand_total" id="grand_total_input" value="<?php echo $sale['grand_total']; ?>">
            <input type="hidden" name="product_data" id="product_data" value="">
        </form>
        
    </div>
    <footer class="sticky-footer bg-white">
        <div class="container my-auto"><div class="copyright text-center my-auto"><span>&copy; <?php echo date('Y'); ?> <?php echo $software_name; ?> - All Rights Reserved</span></div></div>
    </footer>
</div>

<!-- New Customer Modal -->
<div class="modal fade" id="newCustomerModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="fas fa-user-plus"></i> New Walk-in Customer</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Customer Name</label>
                    <input type="text" id="new_customer_name_input" class="form-control" placeholder="Enter customer name">
                    <small class="text-muted">Example: Ali, Ahmad, Salman, etc.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="saveNewCustomerBtn">Add Customer</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/js/sb-admin-2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
let productGroupId = 0;
let productRateMap = {};

// Product data from PHP
const productsList = {
    <?php 
    mysqli_data_seek($products_result, 0);
    $first = true;
    while($prod = mysqli_fetch_assoc($products_result)): 
        if(!$first) echo ",";
        $first = false;
    ?>
        "<?php echo $prod['id']; ?>": {
            id: "<?php echo $prod['id']; ?>",
            name: "<?php echo htmlspecialchars($prod['product_name']); ?>",
            price: "<?php echo $prod['sale_price']; ?>",
            stock: "<?php echo isset($prod['current_stock']) ? $prod['current_stock'] : 0; ?>"
        }
    <?php endwhile; ?>
};

function roundUpToMultiple(value, multiple) {
    if (value <= 0) return 1;
    return Math.ceil(value / multiple) * multiple;
}

function calculateArea(height, width) {
    if (height > 0 && width > 0) {
        return (height * width) / 144;
    }
    return 0;
}

// Add new product group (with optional pre-filled data)
function addProductGroup(savedRate = null, productId = null, rowsData = null) {
    const groupId = productGroupId++;
    const groupHtml = `
        <div class="card mb-3 product-group-card" data-group-id="${groupId}">
            <div class="card-header bg-success text-white">
                <div class="row">
                    <div class="col-md-8">
                        <select class="form-control product-select" data-group-id="${groupId}" style="width: 100%;">
                            <option value="">Select Product...</option>
                            <?php 
                            mysqli_data_seek($products_result, 0);
                            while($prod = mysqli_fetch_assoc($products_result)): ?>
                                <option value="<?php echo $prod['id']; ?>" data-price="<?php echo $prod['sale_price']; ?>" data-stock="<?php echo isset($prod['current_stock']) ? $prod['current_stock'] : 0; ?>">
                                    <?php echo htmlspecialchars($prod['product_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4 text-right">
                        <button type="button" class="btn btn-danger btn-sm remove-product-group" data-group-id="${groupId}">
                            <i class="fas fa-trash"></i> Remove Product
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0" style="min-width: 1600px;">
                        <thead>
                            <tr>
                                <th>Client Height (Inch)</th>
                                <th>Client Width (Inch)</th>
                                <th>Multiple Of</th>
                                <th>Std Height (Inch)</th>
                                <th>Std Width (Inch)</th>
                                <th>Area (sq ft)</th>
                                <th>QTY</th>
                                <th>Total Area (sq ft)</th>
                                <th>Rate (₨/sq ft)</th>
                                <th>Amount</th>
                                <th>Disc %</th>
                                <th>Net Amount</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody class="size-rows-container" data-group-id="${groupId}">
                        </tbody>
                        <tfoot>
                            <tr><td colspan="13"><button type="button" class="btn btn-sm btn-success add-size-row" data-group-id="${groupId}"><i class="fas fa-plus-circle"></i> Add Size</button></td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    `;
    $('#productGroupsContainer').append(groupHtml);
    
    $(`.product-select[data-group-id="${groupId}"]`).select2({ theme: 'bootstrap4', placeholder: 'Search product...', width: '100%' });
    
    if(productId) {
        $(`.product-select[data-group-id="${groupId}"]`).val(productId).trigger('change');
    }
    
    bindGroupEvents(groupId);
    
    if(rowsData && rowsData.length > 0) {
        const container = $(`.size-rows-container[data-group-id="${groupId}"]`);
        container.empty();
        $.each(rowsData, function(idx, item) {
            addSizeRowWithData(groupId, item);
        });
    } else {
        addSizeRow(groupId);
    }
    
    if(savedRate && savedRate > 0) {
        $(`.rate[data-group-id="${groupId}"]`).val(savedRate);
    }
    
    return groupId;
}

function bindGroupEvents(groupId) {
    $(`.product-select[data-group-id="${groupId}"]`).on('change', function() {
        const selected = $(this).find(':selected');
        const productId = selected.val();
        const price = selected.data('price') || 0;
        
        if(productId && price > 0) {
            productRateMap[productId] = price;
        }
        
        $(`.rate[data-group-id="${groupId}"]`).val(price);
        
        $(`.size-row[data-group-id="${groupId}"]`).each(function() {
            const rowId = $(this).data('row-id');
            updateStdAndArea(groupId, rowId);
            calculateRowAmount(groupId, rowId);
        });
        calculateAllTotals();
    });
    
    $(`.remove-product-group[data-group-id="${groupId}"]`).on('click', function() {
        $(`.product-group-card[data-group-id="${groupId}"]`).remove();
        calculateAllTotals();
    });
    
    $(`.add-size-row[data-group-id="${groupId}"]`).on('click', function() {
        addSizeRow(groupId);
    });
    
    bindSizeRowEvents(groupId);
}

function addSizeRow(groupId) {
    const container = $(`.size-rows-container[data-group-id="${groupId}"]`);
    const rowCount = container.children('.size-row').length;
    const newRowId = rowCount;
    
    let currentRate = 0;
    const firstRowRate = $(`.rate[data-group-id="${groupId}"][data-row-id="0"]`).val();
    if(firstRowRate) currentRate = parseFloat(firstRowRate);
    
    const newRow = `
        <tr class="size-row" data-row-id="${newRowId}" data-group-id="${groupId}">
            <td><input type="number" step="0.01" class="form-control client-height" data-group-id="${groupId}" data-row-id="${newRowId}" placeholder="Height (Inch)" value="0"></td>
            <td><input type="number" step="0.01" class="form-control client-width" data-group-id="${groupId}" data-row-id="${newRowId}" placeholder="Width (Inch)" value="0"></td>
            <td>
                <select class="form-control multiple-of" data-group-id="${groupId}" data-row-id="${newRowId}">
                    <option value="3">3</option><option value="6" selected>6</option>
                    <option value="9">9</option><option value="12">12</option><option value="24">24</option>
                </select>
             </td>
            <td><input type="number" step="0.01" class="form-control std-height" data-group-id="${groupId}" data-row-id="${newRowId}" readonly style="background:#f0f8ff;" value="0"></td>
            <td><input type="number" step="0.01" class="form-control std-width" data-group-id="${groupId}" data-row-id="${newRowId}" readonly style="background:#f0f8ff;" value="0"></td>
            <td class="area-cell text-right" data-group-id="${groupId}" data-row-id="${newRowId}"><strong>0.00</strong><br><small>sq ft</small></td>
            <td><input type="number" step="0.01" class="form-control quantity" data-group-id="${groupId}" data-row-id="${newRowId}" value="1" min="0.01"></td>
            <td class="total-area-cell text-right" data-group-id="${groupId}" data-row-id="${newRowId}"><strong>0.00</strong><br><small>sq ft</small></td>
            <td><input type="number" step="0.01" class="form-control rate" data-group-id="${groupId}" data-row-id="${newRowId}" value="${currentRate}" min="0"></td>
            <td class="amount-cell text-right" data-group-id="${groupId}" data-row-id="${newRowId}"><strong>₨ 0.00</strong></td>
            <td><input type="number" step="0.01" class="form-control discount" data-group-id="${groupId}" data-row-id="${newRowId}" value="0" min="0" max="100"></td>
            <td class="net-amount-cell text-right" data-group-id="${groupId}" data-row-id="${newRowId}"><strong>₨ 0.00</strong></td>
            <td><button type="button" class="btn btn-sm btn-danger remove-size-row" data-group-id="${groupId}" data-row-id="${newRowId}"><i class="fas fa-trash"></i></button></td>
        </tr>
    `;
    container.append(newRow);
    bindSizeRowEvents(groupId);
}

// ============ FIX: Recalculate std and area on load ============
function addSizeRowWithData(groupId, item) {
    const container = $(`.size-rows-container[data-group-id="${groupId}"]`);
    const rowCount = container.children('.size-row').length;
    const newRowId = rowCount;
    
    // Create row with client dimensions, multiple, quantity, rate, discount
    // but leave std fields empty (will be recalculated)
    const newRow = `
        <tr class="size-row" data-row-id="${newRowId}" data-group-id="${groupId}">
            <td><input type="number" step="0.01" class="form-control client-height" data-group-id="${groupId}" data-row-id="${newRowId}" value="${item.client_height}"></td>
            <td><input type="number" step="0.01" class="form-control client-width" data-group-id="${groupId}" data-row-id="${newRowId}" value="${item.client_width}"></td>
            <td><select class="form-control multiple-of" data-group-id="${groupId}" data-row-id="${newRowId}">${getMultipleOptions(item.multiple_of)}</select></td>
            <td><input type="number" step="0.01" class="form-control std-height" data-group-id="${groupId}" data-row-id="${newRowId}" readonly style="background:#f0f8ff;" value="0"></td>
            <td><input type="number" step="0.01" class="form-control std-width" data-group-id="${groupId}" data-row-id="${newRowId}" readonly style="background:#f0f8ff;" value="0"></td>
            <td class="area-cell text-right" data-group-id="${groupId}" data-row-id="${newRowId}"><strong>0.00</strong><br><small>sq ft</small></td>
            <td><input type="number" step="0.01" class="form-control quantity" data-group-id="${groupId}" data-row-id="${newRowId}" value="${item.quantity}"></td>
            <td class="total-area-cell text-right" data-group-id="${groupId}" data-row-id="${newRowId}"><strong>0.00</strong><br><small>sq ft</small></td>
            <td><input type="number" step="0.01" class="form-control rate" data-group-id="${groupId}" data-row-id="${newRowId}" value="${item.rate}"></td>
            <td class="amount-cell text-right" data-group-id="${groupId}" data-row-id="${newRowId}"><strong>₨ 0.00</strong></td>
            <td><input type="number" step="0.01" class="form-control discount" data-group-id="${groupId}" data-row-id="${newRowId}" value="${item.discount_percentage}"></td>
            <td class="net-amount-cell text-right" data-group-id="${groupId}" data-row-id="${newRowId}"><strong>₨ 0.00</strong></td>
            <td><button type="button" class="btn btn-sm btn-danger remove-size-row" data-group-id="${groupId}" data-row-id="${newRowId}"><i class="fas fa-trash"></i></button></td>
        </tr>
    `;
    container.append(newRow);
    
    // Now recalc std and area from client dimensions
    updateStdAndArea(groupId, newRowId);
    // Recalc amounts
    calculateRowAmount(groupId, newRowId);
    
    bindSizeRowEvents(groupId);
}
// =============================================================

function getMultipleOptions(selected) {
    let opts = [3,6,9,12,24];
    let html = '';
    $.each(opts, function(i, val) {
        html += `<option value="${val}" ${selected == val ? 'selected' : ''}>${val}</option>`;
    });
    return html;
}

function bindSizeRowEvents(groupId) {
    $(`.client-height[data-group-id="${groupId}"], .client-width[data-group-id="${groupId}"], .multiple-of[data-group-id="${groupId}"]`).off('keyup change').on('keyup change', function() {
        const rowId = $(this).data('row-id');
        updateStdAndArea(groupId, rowId);
        calculateRowAmount(groupId, rowId);
        calculateAllTotals();
    });
    
    $(`.quantity[data-group-id="${groupId}"], .rate[data-group-id="${groupId}"], .discount[data-group-id="${groupId}"]`).off('keyup change').on('keyup change', function() {
        const rowId = $(this).data('row-id');
        calculateRowAmount(groupId, rowId);
        calculateAllTotals();
    });
    
    $(`.remove-size-row[data-group-id="${groupId}"]`).off('click').on('click', function() {
        const rowId = $(this).data('row-id');
        $(`.size-row[data-group-id="${groupId}"][data-row-id="${rowId}"]`).remove();
        calculateAllTotals();
    });
}

function updateStdAndArea(groupId, rowId) {
    const clientH = parseFloat($(`.client-height[data-group-id="${groupId}"][data-row-id="${rowId}"]`).val()) || 0;
    const clientW = parseFloat($(`.client-width[data-group-id="${groupId}"][data-row-id="${rowId}"]`).val()) || 0;
    const multiple = parseInt($(`.multiple-of[data-group-id="${groupId}"][data-row-id="${rowId}"]`).val()) || 6;
    
    const stdH = roundUpToMultiple(clientH, multiple);
    const stdW = roundUpToMultiple(clientW, multiple);
    const area = calculateArea(stdH, stdW);
    
    $(`.std-height[data-group-id="${groupId}"][data-row-id="${rowId}"]`).val(stdH);
    $(`.std-width[data-group-id="${groupId}"][data-row-id="${rowId}"]`).val(stdW);
    $(`.area-cell[data-group-id="${groupId}"][data-row-id="${rowId}"]`).html('<strong>' + area.toFixed(2) + '</strong><br><small>sq ft</small>');
    $(`.area-cell[data-group-id="${groupId}"][data-row-id="${rowId}"]`).data('value', area);
}

function calculateRowAmount(groupId, rowId) {
    const area = parseFloat($(`.area-cell[data-group-id="${groupId}"][data-row-id="${rowId}"]`).data('value')) || 0;
    const quantity = parseFloat($(`.quantity[data-group-id="${groupId}"][data-row-id="${rowId}"]`).val()) || 0;
    const rate = parseFloat($(`.rate[data-group-id="${groupId}"][data-row-id="${rowId}"]`).val()) || 0;
    const disc = parseFloat($(`.discount[data-group-id="${groupId}"][data-row-id="${rowId}"]`).val()) || 0;
    
    const totalArea = area * quantity;
    const amount = totalArea * rate;
    const discountAmt = amount * (disc / 100);
    const netAmount = amount - discountAmt;
    
    $(`.total-area-cell[data-group-id="${groupId}"][data-row-id="${rowId}"]`).html('<strong>' + totalArea.toFixed(2) + '</strong><br><small>sq ft</small>');
    $(`.total-area-cell[data-group-id="${groupId}"][data-row-id="${rowId}"]`).data('value', totalArea);
    
    $(`.amount-cell[data-group-id="${groupId}"][data-row-id="${rowId}"]`).html('<strong>₨ ' + amount.toFixed(2) + '</strong>');
    $(`.amount-cell[data-group-id="${groupId}"][data-row-id="${rowId}"]`).data('value', amount);
    
    $(`.net-amount-cell[data-group-id="${groupId}"][data-row-id="${rowId}"]`).html('<strong>₨ ' + netAmount.toFixed(2) + '</strong>');
    $(`.net-amount-cell[data-group-id="${groupId}"][data-row-id="${rowId}"]`).data('value', netAmount);
    
    return netAmount;
}

function calculateAllTotals() {
    let subtotal = 0;
    let totalDiscount = 0;
    
    $('.net-amount-cell').each(function() {
        const amount = parseFloat($(this).data('value')) || 0;
        subtotal += amount;
    });
    
    $('.discount').each(function() {
        const rowId = $(this).data('row-id');
        const groupId = $(this).data('group-id');
        const amount = parseFloat($(`.amount-cell[data-group-id="${groupId}"][data-row-id="${rowId}"]`).data('value')) || 0;
        const discPercent = parseFloat($(this).val()) || 0;
        const discountAmount = amount * (discPercent / 100);
        totalDiscount += discountAmount;
    });
    
    const otherCharges = parseFloat($('#other_charges').val()) || 0;
    const grandTotal = subtotal + otherCharges;
    const prevBalance = parseFloat($('#prevBalance').data('value')) || 0;
    
    let receivedAmount = parseFloat($('#received_amount').val()) || 0;
    if (receivedAmount > grandTotal) {
        receivedAmount = grandTotal;
        $('#received_amount').val(receivedAmount.toFixed(2));
    }
    let remainingAmount = grandTotal - receivedAmount;
    
    const newBalance = prevBalance + remainingAmount;
    
    $('#subtotal').text('₨ ' + subtotal.toFixed(2));
    $('#discountAmount').text('₨ ' + totalDiscount.toFixed(2));
    $('#grandTotal').text('₨ ' + grandTotal.toFixed(2));
    $('#currentSale').text('₨ ' + grandTotal.toFixed(2));
    $('#newBalance').text('₨ ' + newBalance.toFixed(2));
    $('#subtotal_input').val(subtotal.toFixed(2));
    $('#discount_amount_input').val(totalDiscount.toFixed(2));
    $('#grand_total_input').val(grandTotal.toFixed(2));
    
    if(newBalance > 0) $('#newBalance').css('color', '#dc3545');
    else if(newBalance < 0) $('#newBalance').css('color', '#28a745');
    else $('#newBalance').css('color', '#1a1a1a');
    if(grandTotal > 0) $('#grandTotal').css('color', '#dc3545');
    
    collectProductData();
}

function collectProductData() {
    const productData = [];
    $('.product-group-card').each(function() {
        const groupId = $(this).data('group-id');
        const productId = $(`.product-select[data-group-id="${groupId}"]`).val();
        const productName = $(`.product-select[data-group-id="${groupId}"] option:selected`).text();
        if(productId && productId !== '') {
            $(`.size-row[data-group-id="${groupId}"]`).each(function() {
                const rowId = $(this).data('row-id');
                const clientHeight = $(`.client-height[data-group-id="${groupId}"][data-row-id="${rowId}"]`).val() || 0;
                const clientWidth = $(`.client-width[data-group-id="${groupId}"][data-row-id="${rowId}"]`).val() || 0;
                const multiple = $(`.multiple-of[data-group-id="${groupId}"][data-row-id="${rowId}"]`).val();
                const stdHeight = $(`.std-height[data-group-id="${groupId}"][data-row-id="${rowId}"]`).val();
                const stdWidth = $(`.std-width[data-group-id="${groupId}"][data-row-id="${rowId}"]`).val();
                const quantity = $(`.quantity[data-group-id="${groupId}"][data-row-id="${rowId}"]`).val();
                const area = parseFloat($(`.area-cell[data-group-id="${groupId}"][data-row-id="${rowId}"]`).data('value')) || 0;
                const totalArea = area * quantity;
                const rate = $(`.rate[data-group-id="${groupId}"][data-row-id="${rowId}"]`).val();
                const discount = $(`.discount[data-group-id="${groupId}"][data-row-id="${rowId}"]`).val();
                const amount = parseFloat($(`.amount-cell[data-group-id="${groupId}"][data-row-id="${rowId}"]`).data('value')) || 0;
                const netAmount = $(`.net-amount-cell[data-group-id="${groupId}"][data-row-id="${rowId}"]`).data('value') || 0;
                productData.push({
                    product_id: productId,
                    product_name: productName,
                    client_height: clientHeight,
                    client_width: clientWidth,
                    client_size: clientHeight + ' x ' + clientWidth,
                    multiple_of: multiple,
                    std_height: stdHeight,
                    std_width: stdWidth,
                    uom: 'Inch',
                    raw_area: 0,
                    quantity: quantity,
                    area: area,
                    total_area: totalArea,
                    rate: rate,
                    discount_percent: discount,
                    amount: netAmount
                });
            });
        }
    });
    $('#product_data').val(JSON.stringify(productData));
}

$(document).ready(function() {
    $('#customer_id').select2({
        theme: 'bootstrap4',
        placeholder: 'Search customer...',
        allowClear: true
    });
    
    // Load existing products into the form
    <?php
    $grouped = [];
    foreach($details as $detail) {
        $pid = $detail['product_id'];
        if(!isset($grouped[$pid])) {
            $grouped[$pid] = [];
        }
        $grouped[$pid][] = $detail;
    }
    ?>
    var grouped = <?php echo json_encode($grouped); ?>;
    
    $.each(grouped, function(productId, items) {
        let rate = items[0].rate;
        let groupId = addProductGroup(rate, productId, items);
    });
    
    if($('.product-group-card').length === 0) {
        addProductGroup();
    }
    
    var selectedCustomer = $('#customer_id').find(':selected');
    var mobile = selectedCustomer.data('mobile') || '-';
    var balance = selectedCustomer.data('balance') || 0;
    $('#customer_mobile').text(mobile);
    $('#prevBalance').text('₨ ' + parseFloat(balance).toFixed(2));
    $('#prevBalance').data('value', balance);
    
    $('#addNewProductBtn').on('click', function() {
        addProductGroup();
    });
    
    $('#newCustomerBtn').on('click', function() {
        $('#newCustomerModal').modal('show');
    });
    
    $('#saveNewCustomerBtn').on('click', function() {
        const customerName = $('#new_customer_name_input').val().trim();
        if(customerName === '') {
            Swal.fire({ title: 'Error!', text: 'Please enter customer name!', icon: 'error' });
            return;
        }
        
        Swal.fire({ title: 'Processing...', text: 'Adding customer...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
        
        $.ajax({
            url: 'add_customer_ajax.php',
            type: 'POST',
            data: { customer_name: customerName, mobile: '0000000000' },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    let exists = false;
                    $('#customer_id option').each(function() {
                        if($(this).val() == response.customer_id) {
                            exists = true;
                            return false;
                        }
                    });
                    
                    if(!exists) {
                        const newOption = new Option(customerName + ' (' + response.customer_code + ')', response.customer_id, true, true);
                        $('#customer_id').append(newOption);
                    }
                    
                    $('#customer_id').val(response.customer_id).trigger('change');
                    $('#customer_mobile').text('0000000000');
                    $('#prevBalance').text('₨ 0.00');
                    $('#prevBalance').data('value', 0);
                    
                    $('#newCustomerModal').modal('hide');
                    $('#new_customer_name_input').val('');
                    
                    let message = response.already_exists ? 'Customer already exists! Selected existing customer.' : 'Customer added successfully!';
                    Swal.fire({ title: 'Success!', text: message, icon: 'success', timer: 1500 });
                } else {
                    Swal.fire({ title: 'Error!', text: response.message, icon: 'error' });
                }
            },
            error: function() {
                Swal.fire({ title: 'Error!', text: 'Failed to add customer!', icon: 'error' });
            }
        });
    });
    
    $('#customer_id').on('change', function() {
        const selected = $(this).find(':selected');
        const mobile = selected.data('mobile') || '-';
        const balance = selected.data('balance') || 0;
        $('#customer_mobile').text(mobile);
        $('#prevBalance').text('₨ ' + parseFloat(balance).toFixed(2));
        $('#prevBalance').data('value', balance);
        calculateAllTotals();
    });
    
    $('#payment_type').on('change', function() {
        if($(this).val() === 'bank') $('#bank_div').show();
        else $('#bank_div').hide();
        calculateAllTotals();
    });
    
    $('#other_charges, #received_amount').on('keyup change', function() { calculateAllTotals(); });
    $('#refreshBtn').on('click', function() { location.reload(); });
    
    calculateAllTotals();
});

$('#updateInvoiceBtn').on('click', function(e) {
    e.preventDefault();
    
    let hasProduct = false;
    $('.product-select').each(function() { if($(this).val() !== '') hasProduct = true; });
    
    if(!hasProduct) {
        Swal.fire({ title: 'Error!', text: 'Please add at least one product!', icon: 'error' });
        return false;
    }
    
    const grandTotal = parseFloat($('#grand_total_input').val());
    if(grandTotal <= 0) {
        Swal.fire({ title: 'Error!', text: 'Grand total must be greater than 0!', icon: 'error' });
        return false;
    }
    
    const customerId = $('#customer_id').val();
    if(!customerId) {
        Swal.fire({ title: 'Error!', text: 'Please select or add a customer!', icon: 'error' });
        return false;
    }
    
    const paymentType = $('#payment_type').val();
    if(paymentType === 'bank') {
        const bankAccount = $('select[name="bank_account_id"]').val();
        if(!bankAccount) {
            Swal.fire({ title: 'Error!', text: 'Please select a bank account!', icon: 'error' });
            return false;
        }
    }
    
    collectProductData();
    
    const formData = new FormData($('#saleForm')[0]);
    formData.append('update_sale', '1');
    
    Swal.fire({ 
        title: 'Processing...', 
        text: 'Updating invoice...', 
        allowOutsideClick: false, 
        didOpen: () => { Swal.showLoading(); } 
    });
    
    $.ajax({
        url: 'save_sale.php',
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
        error: function(xhr) {
            let errorMsg = 'Failed to update invoice!';
            try {
                const response = JSON.parse(xhr.responseText);
                if(response.message) errorMsg = response.message;
            } catch(e) {}
            Swal.fire({ title: 'Error!', text: errorMsg, icon: 'error' });
        }
    });
});
</script>

</body>
</html>
<?php mysqli_close($conn); ?>