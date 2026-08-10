<?php
/**
 * Add Sale Page - MODIFIED VERSION (UOM Removed)
 * Faysal Glass And Aluminium Centre
 * 
 * Create sale invoices with full accounting integration
 * Page: Add Sale
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

// Handle Hold Bill AJAX requests
if(isset($_GET['action'])) {
    header('Content-Type: application/json');
    require_once('../includes/database.php');
    $response = ['success' => false, 'message' => ''];
    
    // Save Hold Bill
    if($_GET['action'] == 'save_hold' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $hold_date = date('Y-m-d');
        $customer_id = intval($data['customer_id']);
        $subtotal = floatval($data['subtotal']);
        $discount_percentage = floatval($data['discount_percentage']);
        $discount_amount = floatval($data['discount_amount']);
        $other_charges = floatval($data['other_charges']);
        $grand_total = floatval($data['grand_total']);
        $remarks = mysqli_real_escape_string($conn, $data['remarks']);
        $products = $data['products'];
        $created_by = $_SESSION['user_id'];
        
        // Generate Hold No
        $result = mysqli_query($conn, "SELECT MAX(CAST(SUBSTRING(hold_no, 6) AS UNSIGNED)) as last_num FROM hold_sales_master");
        $row = mysqli_fetch_assoc($result);
        $next_num = str_pad(($row['last_num'] + 1), 5, '0', STR_PAD_LEFT);
        $hold_no = "HOLD-" . $next_num;
        
        mysqli_begin_transaction($conn);
        try {
            $stmt = mysqli_prepare($conn, "INSERT INTO hold_sales_master (hold_no, hold_date, customer_id, subtotal, discount_percentage, discount_amount, other_charges, grand_total, remarks, created_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'hold')");
            mysqli_stmt_bind_param($stmt, "ssidddddsi", $hold_no, $hold_date, $customer_id, $subtotal, $discount_percentage, $discount_amount, $other_charges, $grand_total, $remarks, $created_by);
            mysqli_stmt_execute($stmt);
            $hold_id = mysqli_insert_id($conn);
            
            $stmt_detail = mysqli_prepare($conn, "INSERT INTO hold_sales_details (hold_id, product_id, client_height, client_width, multiple_of, std_height, std_width, uom, quantity, area, rate, discount_percentage, amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach($products as $prod) {
                mysqli_stmt_bind_param($stmt_detail, "iidddsssddddd", 
                    $hold_id,
                    $prod['product_id'],
                    $prod['client_height'],
                    $prod['client_width'],
                    $prod['multiple_of'],
                    $prod['std_height'],
                    $prod['std_width'],
                    $prod['uom'],
                    $prod['quantity'],
                    $prod['area'],
                    $prod['rate'],
                    $prod['discount_percentage'],
                    $prod['amount']
                );
                mysqli_stmt_execute($stmt_detail);
            }
            mysqli_commit($conn);
            $response = ['success' => true, 'message' => 'Hold Bill saved', 'hold_no' => $hold_no];
        } catch(Exception $e) {
            mysqli_rollback($conn);
            $response = ['success' => false, 'message' => $e->getMessage()];
        }
        echo json_encode($response);
        exit;
    }
    
    // Get list of Hold Bills
    if($_GET['action'] == 'get_hold_bills') {
        $sql = "SELECT h.id, h.hold_no, h.hold_date, c.customer_name, h.grand_total 
                FROM hold_sales_master h 
                JOIN customers c ON h.customer_id = c.id 
                WHERE h.status = 'hold' 
                ORDER BY h.hold_date DESC";
        $result = mysqli_query($conn, $sql);
        $bills = [];
        while($row = mysqli_fetch_assoc($result)) {
            $bills[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $bills]);
        exit;
    }
    
    // Load single Hold Bill
    if($_GET['action'] == 'load_hold' && isset($_GET['id'])) {
        $hold_id = intval($_GET['id']);
        $master = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM hold_sales_master WHERE id = $hold_id"));
        if(!$master) {
            echo json_encode(['success' => false, 'message' => 'Hold Bill not found']);
            exit;
        }
        $details = mysqli_query($conn, "SELECT * FROM hold_sales_details WHERE hold_id = $hold_id");
        $products = [];
        while($det = mysqli_fetch_assoc($details)) {
            $products[] = $det;
        }
        echo json_encode([
            'success' => true,
            'hold_id' => $master['id'],
            'customer_id' => $master['customer_id'],
            'subtotal' => $master['subtotal'],
            'discount_percentage' => $master['discount_percentage'],
            'discount_amount' => $master['discount_amount'],
            'other_charges' => $master['other_charges'],
            'grand_total' => $master['grand_total'],
            'remarks' => $master['remarks'],
            'products' => $products
        ]);
        exit;
    }
    
    // Delete Hold Bill (only if status = 'hold')
    if($_GET['action'] == 'delete_hold' && isset($_GET['id'])) {
        $hold_id = intval($_GET['id']);
        $check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM hold_sales_master WHERE id = $hold_id"));
        if($check['status'] !== 'hold') {
            echo json_encode(['success' => false, 'message' => 'Only Hold bills can be deleted']);
            exit;
        }
        mysqli_query($conn, "DELETE FROM hold_sales_master WHERE id = $hold_id");
        echo json_encode(['success' => true, 'message' => 'Hold Bill deleted']);
        exit;
    }
    
    echo json_encode($response);
    exit;
}
include('../includes/database.php');
include('../includes/txt.php');

$page_title = "Add Sale";
$success_msg = '';
$error_msg = '';

// Generate Invoice Number
function generateInvoiceNo($conn) {
    $prefix = "SAL";
    $query = "SELECT invoice_no FROM sale_master WHERE invoice_no LIKE '{$prefix}%' ORDER BY id DESC LIMIT 1";
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

// Fetch customers for dropdown (including walk-in)
$customers_query = "SELECT id, customer_name, customer_code, mobile, current_balance 
                    FROM customers WHERE status = 1 
                    ORDER BY CASE WHEN customer_name LIKE 'Walk-In%' OR customer_name LIKE 'TMP%' THEN 0 ELSE 1 END, customer_name";
$customers_result = mysqli_query($conn, $customers_query);

// Fetch bank accounts
$bank_query = "SELECT id, bank_name, account_title FROM bank_accounts WHERE status = 1";
$bank_result = mysqli_query($conn, $bank_query);

// Fetch products for dropdown
$products_query = "SELECT p.*, c.category_name, u.short_name as unit_name, u.id as unit_id,
                   (SELECT balance_qty FROM inventory_ledger WHERE product_id = p.id ORDER BY id DESC LIMIT 1) as current_stock
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
        .customer-info { background: #e8f5e9; padding: 15px; border-radius: 8px; }
        .required-field::after { content: " *"; color: red; }
        .table thead th { background-color: #1e7e34; color: white; font-weight: 600; font-size: 12px; white-space: nowrap; }
        .remove-row { cursor: pointer; color: #dc3545; }
        .remove-row:hover { color: #a71d2a; }
        .select2-container .select2-selection--single { height: 38px; }
        .table td { padding: 8px; vertical-align: middle; }
        .table input, .table select { width: 100%; min-width: 80px; }
        .std-height, .std-width { background-color: #f0f8ff; }
        .product-group { background-color: #e8f5e9; }
        .product-group td { background-color: #e8f5e9; }
        .add-size-row { background: none; border: none; color: #1e7e34; cursor: pointer; font-size: 18px; }
        .add-size-row:hover { color: #0066cc; }
        .walkin-badge { background-color: #ffc107; color: #1a1a1a; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-left: 5px; }
        .customer-search-container { position: relative; }
        .new-customer-btn { margin-left: 10px; }
    </style>
</head>
<body id="page-top">

<div id="wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <div class="container-fluid">
        
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-shopping-cart text-success mr-2"></i> Add Sale
            </h1>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Add Sale</li>
            </ol>
        </div>
        
        <div id="alertMessage"></div>
        
        <form method="POST" action="" id="saleForm">
            <!-- Invoice Header -->
            <div class="card form-card">
                <div class="card-header-custom">
                    <i class="fas fa-file-invoice mr-2"></i> Sale Invoice
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-calendar text-success mr-1"></i> Sale Date</label>
                                <input type="date" name="sale_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
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
                                <label><i class="fas fa-user text-success mr-1"></i> Customer</label>
                                <div class="input-group">
                                    <select name="customer_id" id="customer_id" class="form-control">
                                        <option value="">-- Select or Type to Search --</option>
                                        <?php while($cust = mysqli_fetch_assoc($customers_result)): ?>
                                            <option value="<?php echo $cust['id']; ?>" 
                                                    data-mobile="<?php echo $cust['mobile']; ?>"
                                                    data-balance="<?php echo $cust['current_balance']; ?>">
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
                                <div id="customer_mobile" class="font-weight-bold">-</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="customer-info">
                                <small class="text-muted">Previous Balance</small>
                                <div id="customer_balance" class="font-weight-bold">₨ 0.00</div>
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
            
            <!-- Products Section - Grouped by Product -->
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
                                    <div class="row"><div class="col-4">DELIVERY / OTHER CHARGES:</div><div class="col-8"><input type="number" step="0.01" name="other_charges" id="other_charges" class="form-control form-control-sm" value="0" min="0" style="width:150px; display:inline-block; text-align:right;"></div></div>
                                </div>
                                <div class="calculation-row">
                                    <div class="row"><div class="col-4">ADVANCE / RECEIVED:</div><div class="col-8"><input type="number" step="0.01" name="received_amount" id="received_amount" class="form-control form-control-sm" value="0" min="0" style="width:150px; display:inline-block; text-align:right;"></div></div>
                                </div>
                                <div class="calculation-row" style="background: #e8f5e9;">
                                    <div class="row"><div class="col-4"><strong>BALANCE:</strong></div><div class="col-8 text-right"><strong id="grandTotal" style="font-size:20px; color:#dc3545;">₨ 0.00</strong></div></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="calculation-panel">
                                <div class="calculation-row">
                                    <div class="row"><div class="col-6">Payment Method:</div><div class="col-6"><select name="payment_type" id="payment_type" class="form-control form-control-sm"><option value="cash" selected>Cash</option><option value="bank">Bank</option><option value="credit">Credit</option><option value="partial">Partial</option></select></div></div>
                                </div>
                                <div class="calculation-row" id="bank_div" style="display: none;">
                                    <div class="row"><div class="col-6">Select Bank:</div><div class="col-6"><select name="bank_account_id" class="form-control form-control-sm"><option value="">Select Bank</option><?php while($bank = mysqli_fetch_assoc($bank_result)): ?><option value="<?php echo $bank['id']; ?>"><?php echo $bank['bank_name']; ?></option><?php endwhile; ?></select></div></div>
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
                            <button type="button" id="generateInvoiceBtn" class="btn btn-green"><i class="fas fa-save mr-1"></i> Generate Invoice</button>
                            <!-- Inside the card-header-custom div, below the existing buttons -->
                            <div class="float-right">
                                <button type="button" class="btn btn-warning btn-sm" id="holdBillBtn">
                                    <i class="fas fa-pause-circle"></i> Hold Bill
                                </button>
                                <button type="button" class="btn btn-info btn-sm" id="loadHoldBillBtn" data-toggle="modal" data-target="#holdBillsModal">
                                    <i class="fas fa-folder-open"></i> Load Hold Bill
                                </button>
                                <button type="button" id="generateInvoiceBtn" class="btn btn-green btn-sm">
                                    <i class="fas fa-save mr-1"></i> Save Sale
                                </button>
                            </div>
                        
                        </div>
                    </div>
                </div>
            </div>
            
            <input type="hidden" name="subtotal" id="subtotal_input" value="0">
            <input type="hidden" name="discount_amount" id="discount_amount_input" value="0">
            <input type="hidden" name="grand_total" id="grand_total_input" value="0">
            <input type="hidden" name="product_data" id="product_data" value="">
            <input type="hidden" name="is_new_customer" id="is_new_customer" value="0">
            <input type="hidden" name="new_customer_name" id="new_customer_name" value="">
        </form>
        
    </div>
    <!-- Load Hold Bills Modal -->
<div class="modal fade" id="holdBillsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-folder-open"></i> Load Hold Bill</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="holdBillsTable" width="100%">
                        <thead>
                            <tr>
                                <th>Hold No</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Amount (₨)</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
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
let productRateMap = {}; // Store rate for each product

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

// Function to round up to next multiple
function roundUpToMultiple(value, multiple) {
    if (value <= 0) return 1;
    return Math.ceil(value / multiple) * multiple;
}

// Calculate area from Std Height and Std Width (always in Inches -> sq ft)
function calculateArea(height, width) {
    if (height > 0 && width > 0) {
        return (height * width) / 144;
    }
    return 0;
}

// Add new product group
function addProductGroup(savedRate = null) {
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
                            <tr class="size-row" data-row-id="0" data-group-id="${groupId}">
                                <td><input type="number" step="0.01" class="form-control client-height" data-group-id="${groupId}" data-row-id="0" placeholder="Height (Inch)" value="0"></td>
                                <td><input type="number" step="0.01" class="form-control client-width" data-group-id="${groupId}" data-row-id="0" placeholder="Width (Inch)" value="0"></td>
                                <td>
                                    <select class="form-control multiple-of" data-group-id="${groupId}" data-row-id="0">
                                        <option value="3">3</option><option value="6" selected>6</option>
                                        <option value="9">9</option><option value="12">12</option><option value="24">24</option>
                                    </select>
                                </td>
                                <td><input type="number" step="0.01" class="form-control std-height" data-group-id="${groupId}" data-row-id="0" readonly style="background:#f0f8ff;" value="0"></td>
                                <td><input type="number" step="0.01" class="form-control std-width" data-group-id="${groupId}" data-row-id="0" readonly style="background:#f0f8ff;" value="0"></td>
                                <td class="area-cell text-right" data-group-id="${groupId}" data-row-id="0"><strong>0.00</strong><br><small>sq ft</small></td>
                                <td><input type="number" step="0.01" class="form-control quantity" data-group-id="${groupId}" data-row-id="0" value="1" min="0.01"></td>
                                <td class="total-area-cell text-right" data-group-id="${groupId}" data-row-id="0"><strong>0.00</strong><br><small>sq ft</small></td>
                                <td><input type="number" step="0.01" class="form-control rate" data-group-id="${groupId}" data-row-id="0" value="0" min="0"></td>
                                <td class="amount-cell text-right" data-group-id="${groupId}" data-row-id="0"><strong>₨ 0.00</strong></td>
                                <td><input type="number" step="0.01" class="form-control discount" data-group-id="${groupId}" data-row-id="0" value="0" min="0" max="100"></td>
                                <td class="net-amount-cell text-right" data-group-id="${groupId}" data-row-id="0"><strong>₨ 0.00</strong></td>
                                <td><button type="button" class="btn btn-sm btn-danger remove-size-row" data-group-id="${groupId}" data-row-id="0"><i class="fas fa-trash"></i></button></td>
                            </tr>
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
    
    // Initialize select2 for the product select
    $(`.product-select[data-group-id="${groupId}"]`).select2({ theme: 'bootstrap4', placeholder: 'Search product...', width: '100%' });
    
    // Bind events for this group
    bindGroupEvents(groupId);
    
    // Set saved rate if provided
    if(savedRate && savedRate > 0) {
        $(`.rate[data-group-id="${groupId}"]`).val(savedRate);
    }
    
    return groupId;
}

// Bind events for a product group
function bindGroupEvents(groupId) {
    // Product selection change
    $(`.product-select[data-group-id="${groupId}"]`).on('change', function() {
        const selected = $(this).find(':selected');
        const productId = selected.val();
        const price = selected.data('price') || 0;
        const stock = selected.data('stock') || 0;
        
        // Store rate for this product
        if(productId && price > 0) {
            productRateMap[productId] = price;
        }
        
        // Update all rate fields in this group
        $(`.rate[data-group-id="${groupId}"]`).val(price);
        
        // Update all calculations
        $(`.size-row[data-group-id="${groupId}"]`).each(function() {
            const rowId = $(this).data('row-id');
            updateStdAndArea(groupId, rowId);
            calculateRowAmount(groupId, rowId);
        });
        calculateAllTotals();
    });
    
    // Remove product group
    $(`.remove-product-group[data-group-id="${groupId}"]`).on('click', function() {
        // Remove stored rate for this group's product
        const productSelect = $(`.product-select[data-group-id="${groupId}"]`);
        const productId = productSelect.val();
        if(productId) {
            delete productRateMap[productId];
        }
        $(`.product-group-card[data-group-id="${groupId}"]`).remove();
        calculateAllTotals();
    });
    
    // Add size row
    $(`.add-size-row[data-group-id="${groupId}"]`).on('click', function() {
        addSizeRow(groupId);
    });
    
    // Bind size row events
    bindSizeRowEvents(groupId);
}

// Add new size row to a product group
function addSizeRow(groupId) {
    const container = $(`.size-rows-container[data-group-id="${groupId}"]`);
    const rowCount = container.children('.size-row').length;
    const newRowId = rowCount;
    
    // Get current rate from first row (if exists)
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

// Bind events for size rows in a group
function bindSizeRowEvents(groupId) {
    // Client size change
    $(`.client-height[data-group-id="${groupId}"], .client-width[data-group-id="${groupId}"], .multiple-of[data-group-id="${groupId}"]`).off('keyup change').on('keyup change', function() {
        const rowId = $(this).data('row-id');
        updateStdAndArea(groupId, rowId);
        calculateRowAmount(groupId, rowId);
        calculateAllTotals();
    });
    
    // Quantity, rate, discount change
    $(`.quantity[data-group-id="${groupId}"], .rate[data-group-id="${groupId}"], .discount[data-group-id="${groupId}"]`).off('keyup change').on('keyup change', function() {
        const rowId = $(this).data('row-id');
        calculateRowAmount(groupId, rowId);
        calculateAllTotals();
    });
    
    // Remove size row
    $(`.remove-size-row[data-group-id="${groupId}"]`).off('click').on('click', function() {
        const rowId = $(this).data('row-id');
        $(`.size-row[data-group-id="${groupId}"][data-row-id="${rowId}"]`).remove();
        calculateAllTotals();
    });
}

// Update std dimensions and area
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

// Calculate row amount - FIXED: Discount properly affects amount
function calculateRowAmount(groupId, rowId) {
    const area = parseFloat($(`.area-cell[data-group-id="${groupId}"][data-row-id="${rowId}"]`).data('value')) || 0;
    const quantity = parseFloat($(`.quantity[data-group-id="${groupId}"][data-row-id="${rowId}"]`).val()) || 0;
    const rate = parseFloat($(`.rate[data-group-id="${groupId}"][data-row-id="${rowId}"]`).val()) || 0;
    const disc = parseFloat($(`.discount[data-group-id="${groupId}"][data-row-id="${rowId}"]`).val()) || 0;
    
    const totalArea = area * quantity;
    // Amount = Total Area × Rate
    const amount = totalArea * rate;
    // Discount Amount = Amount × (discount percentage / 100)
    const discountAmt = amount * (disc / 100);
    // Net Amount = Amount - Discount Amount
    const netAmount = amount - discountAmt;
    
    // Update Total Area display
    $(`.total-area-cell[data-group-id="${groupId}"][data-row-id="${rowId}"]`).html('<strong>' + totalArea.toFixed(2) + '</strong><br><small>sq ft</small>');
    $(`.total-area-cell[data-group-id="${groupId}"][data-row-id="${rowId}"]`).data('value', totalArea);
    
    // Update Amount display (original amount before discount)
    $(`.amount-cell[data-group-id="${groupId}"][data-row-id="${rowId}"]`).html('<strong>₨ ' + amount.toFixed(2) + '</strong>');
    $(`.amount-cell[data-group-id="${groupId}"][data-row-id="${rowId}"]`).data('value', amount);
    
    // Update Net Amount display (after discount)
    $(`.net-amount-cell[data-group-id="${groupId}"][data-row-id="${rowId}"]`).html('<strong>₨ ' + netAmount.toFixed(2) + '</strong>');
    $(`.net-amount-cell[data-group-id="${groupId}"][data-row-id="${rowId}"]`).data('value', netAmount);
    
    return netAmount;
}

// Calculate all totals - FIXED: Uses net amounts after discount
function calculateAllTotals() {
    let subtotal = 0;
    let totalDiscount = 0;
    
    // Sum all net amounts (after individual discounts)
    $('.net-amount-cell').each(function() {
        const amount = parseFloat($(this).data('value')) || 0;
        subtotal += amount;
    });
    
    // Calculate total discount from all rows
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
    const paymentType = $('#payment_type').val();
    let receivedAmount = parseFloat($('#received_amount').val()) || 0;
    let remainingAmount = 0;
    
    // Calculate based on payment type
    if(paymentType === 'cash' || paymentType === 'bank') {
        remainingAmount = 0;
        receivedAmount = grandTotal;
        $('#received_amount').val(receivedAmount.toFixed(2));
    } else if(paymentType === 'credit') {
        remainingAmount = grandTotal;
        receivedAmount = 0;
        $('#received_amount').val(0);
    } else if(paymentType === 'partial') {
        if(receivedAmount > grandTotal) {
            receivedAmount = grandTotal;
            $('#received_amount').val(receivedAmount);
        }
        remainingAmount = grandTotal - receivedAmount;
    }
    
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
    
    // Collect all product data for submission
    collectProductData();
}

// Collect all product data for form submission
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

// ==================== HOLD BILL FUNCTIONS ====================

// Save current sale as Hold Bill (no inventory/ledger updates)
$('#holdBillBtn').on('click', function(e) {
    e.preventDefault();
    
    // Validate at least one product and customer
    let hasProduct = false;
    $('.product-select').each(function() { if($(this).val() !== '') hasProduct = true; });
    if(!hasProduct) {
        Swal.fire('Error', 'Please add at least one product!', 'error');
        return;
    }
    if(!$('#customer_id').val()) {
        Swal.fire('Error', 'Please select a customer!', 'error');
        return;
    }
    
    collectProductData(); // fills #product_data
    
    const holdData = {
        customer_id: $('#customer_id').val(),
        subtotal: $('#subtotal_input').val(),
        discount_percentage: $('#discount_amount').text().replace('₨ ', ''),
        discount_amount: $('#discount_amount').text().replace('₨ ', ''),
        other_charges: $('#other_charges').val(),
        grand_total: $('#grand_total_input').val(),
        remarks: $('textarea[name="remarks"]').val(),
        products: JSON.parse($('#product_data').val())
    };
    
    Swal.fire({ title: 'Saving Hold Bill...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    
    $.ajax({
        url: 'add_sale.php?action=save_hold',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(holdData),
        dataType: 'json',
        success: function(res) {
            if(res.success) {
                Swal.fire('Hold Bill Saved', `Number: ${res.hold_no}`, 'info');
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Network error while saving hold bill', 'error');
        }
    });
});

// Load Hold Bills into modal
$('#loadHoldBillBtn').on('click', function() {
    loadHoldBillsList();
});

function loadHoldBillsList() {
    $.getJSON('add_sale.php?action=get_hold_bills', function(res) {
        if(res.success) {
            let tbody = $('#holdBillsTable tbody');
            tbody.empty();
            $.each(res.data, function(i, bill) {
                let row = `<tr>
                    <td>${bill.hold_no}</td>
                    <td>${bill.hold_date}</td>
                    <td>${bill.customer_name}</td>
                    <td>₨ ${parseFloat(bill.grand_total).toFixed(2)}</td>
                    <td>
                        <button class="btn btn-sm btn-success load-hold" data-id="${bill.id}"><i class="fas fa-download"></i> Load</button>
                        <button class="btn btn-sm btn-danger delete-hold" data-id="${bill.id}"><i class="fas fa-trash"></i> Delete</button>
                    </td>
                </tr>`;
                tbody.append(row);
            });
            $('#holdBillsModal').modal('show');
        } else {
            Swal.fire('Error', 'Failed to load hold bills', 'error');
        }
    });
}

// Load a specific hold bill into the form
$(document).on('click', '.load-hold', function() {
    let id = $(this).data('id');
    $.getJSON(`add_sale.php?action=load_hold&id=${id}`, function(res) {
        if(res.success) {
            // Set hold_id hidden field
            $('#hold_id').remove();
            $('<input>').attr({ type: 'hidden', id: 'hold_id', name: 'hold_id', value: res.hold_id }).appendTo('#saleForm');
            
            // Set customer
            $('#customer_id').val(res.customer_id).trigger('change');
            
            // Set totals
            $('#other_charges').val(res.other_charges);
            $('#remarks').val(res.remarks);
            
            // Clear existing product groups
            $('#productGroupsContainer').empty();
            productGroupId = 0;
            
            // Rebuild product groups from hold details
            let productsByGroup = {};
            $.each(res.products, function(i, item) {
                let key = item.product_id;
                if(!productsByGroup[key]) productsByGroup[key] = [];
                productsByGroup[key].push(item);
            });
            
            // Create a group for each product
            $.each(productsByGroup, function(productId, items) {
                let groupId = addProductGroup(items[0].rate); // pass rate
                let groupCard = $(`.product-group-card[data-group-id="${groupId}"]`);
                // Set product select
                let productSelect = groupCard.find('.product-select');
                productSelect.val(productId).trigger('change');
                // Remove default empty row (row 0)
                groupCard.find('.size-rows-container').empty();
                // Add each size row
                $.each(items, function(idx, item) {
                    addSizeRowWithData(groupId, item);
                });
            });
            
            calculateAllTotals();
            $('#holdBillsModal').modal('hide');
            Swal.fire('Loaded', 'Hold bill loaded. You can modify and then Save Sale.', 'success');
        } else {
            Swal.fire('Error', res.message, 'error');
        }
    });
});

// Helper: Add a size row with pre-filled data
function addSizeRowWithData(groupId, item) {
    const container = $(`.size-rows-container[data-group-id="${groupId}"]`);
    const rowCount = container.children('.size-row').length;
    const newRowId = rowCount;
    
    const newRow = `
        <tr class="size-row" data-row-id="${newRowId}" data-group-id="${groupId}">
            <td><input type="number" step="0.01" class="form-control client-height" data-group-id="${groupId}" data-row-id="${newRowId}" value="${item.client_height}"></td>
            <td><input type="number" step="0.01" class="form-control client-width" data-group-id="${groupId}" data-row-id="${newRowId}" value="${item.client_width}"></td>
            <td><select class="form-control multiple-of" data-group-id="${groupId}" data-row-id="${newRowId}">${getMultipleOptions(item.multiple_of)}</select></td>
            <td><input type="number" step="0.01" class="form-control std-height" data-group-id="${groupId}" data-row-id="${newRowId}" readonly value="${item.std_height}"></td>
            <td><input type="number" step="0.01" class="form-control std-width" data-group-id="${groupId}" data-row-id="${newRowId}" readonly value="${item.std_width}"></td>
            <td class="area-cell" data-group-id="${groupId}" data-row-id="${newRowId}"><strong>${item.area}</strong><br><small>sq ft</small></td>
            <td><input type="number" step="0.01" class="form-control quantity" data-group-id="${groupId}" data-row-id="${newRowId}" value="${item.quantity}"></td>
            <td class="total-area-cell" data-group-id="${groupId}" data-row-id="${newRowId}"><strong>${(item.area * item.quantity).toFixed(2)}</strong><br><small>sq ft</small></td>
            <td><input type="number" step="0.01" class="form-control rate" data-group-id="${groupId}" data-row-id="${newRowId}" value="${item.rate}"></td>
            <td class="amount-cell" data-group-id="${groupId}" data-row-id="${newRowId}"><strong>₨ ${(item.area * item.quantity * item.rate).toFixed(2)}</strong></td>
            <td><input type="number" step="0.01" class="form-control discount" data-group-id="${groupId}" data-row-id="${newRowId}" value="${item.discount_percentage}"></td>
            <td class="net-amount-cell" data-group-id="${groupId}" data-row-id="${newRowId}"><strong>₨ ${item.amount}</strong></td>
            <td><button type="button" class="btn btn-sm btn-danger remove-size-row" data-group-id="${groupId}" data-row-id="${newRowId}"><i class="fas fa-trash"></i></button></td>
        </tr>
    `;
    container.append(newRow);
    bindSizeRowEvents(groupId);
}

function getMultipleOptions(selected) {
    let opts = [3,6,9,12,24];
    let html = '';
    $.each(opts, function(i, val) {
        html += `<option value="${val}" ${selected == val ? 'selected' : ''}>${val}</option>`;
    });
    return html;
}

// Delete hold bill
$(document).on('click', '.delete-hold', function() {
    let id = $(this).data('id');
    Swal.fire({
        title: 'Confirm Delete',
        text: 'This hold bill will be permanently deleted.',
        icon: 'warning',
        showCancelButton: true
    }).then((result) => {
        if(result.isConfirmed) {
            $.getJSON(`add_sale.php?action=delete_hold&id=${id}`, function(res) {
                if(res.success) {
                    Swal.fire('Deleted', '', 'success');
                    loadHoldBillsList();
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            });
        }
    });
});
// Initialize
$(document).ready(function() {
    // Initialize select2 for customer dropdown
    $('#customer_id').select2({
        theme: 'bootstrap4',
        placeholder: 'Search customer...',
        allowClear: true
    });
    
    // Add first product group
    addProductGroup();
    
    // Add new product button
    $('#addNewProductBtn').on('click', function() {
        addProductGroup();
    });
    
    // New Customer Button
    $('#newCustomerBtn').on('click', function() {
        $('#newCustomerModal').modal('show');
    });
    
    // Save New Customer
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
                    // Check if customer already exists in dropdown
                    let exists = false;
                    $('#customer_id option').each(function() {
                        if($(this).val() == response.customer_id) {
                            exists = true;
                            return false;
                        }
                    });
                    
                    if(!exists) {
                        // Add new option to select
                        const newOption = new Option(customerName + ' (' + response.customer_code + ')', response.customer_id, true, true);
                        $('#customer_id').append(newOption);
                    }
                    
                    // Select the customer
                    $('#customer_id').val(response.customer_id).trigger('change');
                    
                    // Update customer info
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
            error: function(xhr, status, error) {
                console.log("AJAX Error:", xhr.responseText);
                Swal.fire({ title: 'Error!', text: 'Failed to add customer! Please try again.', icon: 'error' });
            }
        });
    });
    
    // Customer change event
    $('#customer_id').on('change', function() {
        const selected = $(this).find(':selected');
        const mobile = selected.data('mobile') || '-';
        const balance = selected.data('balance') || 0;
        const customerId = $(this).val();
        
        $('#customer_mobile').text(mobile);
        $('#prevBalance').text('₨ ' + parseFloat(balance).toFixed(2));
        $('#prevBalance').data('value', balance);
        calculateAllTotals();
    });
    
    // Payment type change
    $('#payment_type').on('change', function() {
        if($(this).val() === 'bank') $('#bank_div').show();
        else $('#bank_div').hide();
        calculateAllTotals();
    });
    
    $('#other_charges, #received_amount').on('keyup change', function() { calculateAllTotals(); });
    $('#refreshBtn').on('click', function() { location.reload(); });
    $('#customer_id').trigger('change');
});

// Generate Invoice
$('#generateInvoiceBtn').on('click', function(e) {
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
    formData.append('save_sale', '1');
    
    Swal.fire({ 
        title: 'Processing...', 
        text: 'Please wait...', 
        allowOutsideClick: false, 
        didOpen: () => { 
            Swal.showLoading(); 
        } 
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
        error: function(xhr, status, error) {
            let errorMsg = 'Failed to save sale invoice!';
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