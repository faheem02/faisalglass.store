<?php
/**
 * Add Sale Page - MODIFIED VERSION (UOM Removed)
 * Faysal Glass And Aluminium Centre
 * 
 * Create sale invoices with full accounting integration
 * Page: Add Sale
 *
 * FIXED VERSION - bugs corrected (see chat notes):
 * 1. Removed duplicate id="generateInvoiceBtn" button (invalid HTML, unreliable click binding)
 * 2. Fixed discount key mismatch: JS was sending "discount_percent" but PHP read "discount_percentage"
 * 3. Fixed missing/undefined array keys (remarks, uom, product fields) with safe defaults
 * 4. Fixed header-level discount_percentage being wrongly set to a currency string instead of a %
 * 5. Widened catch(Exception) to catch(Throwable) so DB/prepare errors return proper JSON instead of a raw PHP fatal error (which breaks $.ajax's dataType:'json' parsing)
 * 6. Added mysqli_stmt_close() calls
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
        $customer_id = intval($data['customer_id'] ?? 0);
        $subtotal = floatval($data['subtotal'] ?? 0);
        $discount_percentage = floatval($data['discount_percentage'] ?? 0);
        $discount_amount = floatval($data['discount_amount'] ?? 0);
        $other_charges = floatval($data['other_charges'] ?? 0);
        $grand_total = floatval($data['grand_total'] ?? 0);
        $remarks = mysqli_real_escape_string($conn, $data['remarks'] ?? '');
        $products = $data['products'] ?? [];
        $created_by = $_SESSION['user_id'];
        
        if(empty($products)) {
            echo json_encode(['success' => false, 'message' => 'No products to hold']);
            exit;
        }
        
        // Generate Hold No
        $result = mysqli_query($conn, "SELECT MAX(CAST(SUBSTRING(hold_no, 6) AS UNSIGNED)) as last_num FROM hold_sales_master");
        $row = mysqli_fetch_assoc($result);
        $next_num = str_pad((intval($row['last_num']) + 1), 5, '0', STR_PAD_LEFT);
        $hold_no = "HOLD-" . $next_num;
        
        mysqli_begin_transaction($conn);
        try {
            $stmt = mysqli_prepare($conn, "INSERT INTO hold_sales_master (hold_no, hold_date, customer_id, subtotal, discount_percentage, discount_amount, other_charges, grand_total, remarks, created_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'hold')");
            if(!$stmt) {
                throw new Exception('Prepare failed (hold_sales_master): ' . mysqli_error($conn));
            }
            mysqli_stmt_bind_param($stmt, "ssidddddsi", $hold_no, $hold_date, $customer_id, $subtotal, $discount_percentage, $discount_amount, $other_charges, $grand_total, $remarks, $created_by);
            mysqli_stmt_execute($stmt);
            $hold_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            
            $stmt_detail = mysqli_prepare($conn, "INSERT INTO hold_sales_details (hold_id, product_id, client_height, client_width, multiple_of, std_height, std_width, uom, quantity, area, rate, discount_percentage, amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if(!$stmt_detail) {
                throw new Exception('Prepare failed (hold_sales_details): ' . mysqli_error($conn));
            }
            foreach($products as $prod) {
                $p_product_id           = intval($prod['product_id'] ?? 0);
                $p_client_height        = floatval($prod['client_height'] ?? 0);
                $p_client_width         = floatval($prod['client_width'] ?? 0);
                $p_multiple_of          = floatval($prod['multiple_of'] ?? 0);
                $p_std_height           = strval($prod['std_height'] ?? 0);
                $p_std_width            = strval($prod['std_width'] ?? 0);
                $p_uom                  = strval($prod['uom'] ?? '');
                $p_quantity             = floatval($prod['quantity'] ?? 0);
                $p_area                 = floatval($prod['area'] ?? 0);
                $p_rate                 = floatval($prod['rate'] ?? 0);
                $p_discount_percentage  = floatval($prod['discount_percentage'] ?? 0);
                $p_amount               = floatval($prod['amount'] ?? 0);
                
                mysqli_stmt_bind_param($stmt_detail, "iidddsssddddd", 
                    $hold_id,
                    $p_product_id,
                    $p_client_height,
                    $p_client_width,
                    $p_multiple_of,
                    $p_std_height,
                    $p_std_width,
                    $p_uom,
                    $p_quantity,
                    $p_area,
                    $p_rate,
                    $p_discount_percentage,
                    $p_amount
                );
                mysqli_stmt_execute($stmt_detail);
            }
            mysqli_stmt_close($stmt_detail);
            mysqli_commit($conn);
            $response = ['success' => true, 'message' => 'Hold Bill saved', 'hold_no' => $hold_no];
        } catch(Throwable $e) {
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
        if(!$check || $check['status'] !== 'hold') {
            echo json_encode(['success' => false, 'message' => 'Only Hold bills can be deleted']);
            exit;
        }
        mysqli_query($conn, "DELETE FROM hold_sales_details WHERE hold_id = $hold_id");
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

$edit_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
$edit_data = null;
$edit_products = [];
if ($edit_id > 0) {
    $page_title = "Edit Sale";
    $edit_query = "SELECT s.*, c.customer_name FROM sale_master s LEFT JOIN customers c ON s.customer_id = c.id WHERE s.id = $edit_id";
    $edit_result = mysqli_query($conn, $edit_query);
    if ($edit_result && mysqli_num_rows($edit_result) > 0) {
        $edit_data = mysqli_fetch_assoc($edit_result);
        $edit_details_query = "SELECT sd.*, p.product_name FROM sale_details sd LEFT JOIN products p ON sd.product_id = p.id WHERE sd.sale_id = $edit_id";
        $edit_details_result = mysqli_query($conn, $edit_details_query);
        while ($det = mysqli_fetch_assoc($edit_details_result)) {
            $det['area_per_unit'] = $det['quantity'] > 0 ? $det['area'] / $det['quantity'] : 0;
            $edit_products[] = $det;
        }
    }
}

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
$bank_query = "SELECT id, bank_name, account_title, account_number FROM bank_accounts WHERE status = 1";
$bank_result = mysqli_query($conn, $bank_query);


// Products list for per-row dropdown
$row_products_query = "SELECT id, product_name, product_code, sale_price FROM products WHERE status = 1 ORDER BY product_name";
$row_products_result = mysqli_query($conn, $row_products_query);
$row_products_data = [];
while ($rp = mysqli_fetch_assoc($row_products_result)) {
    $row_products_data[] = $rp;
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
                <i class="fas fa-shopping-cart text-success mr-2"></i> <?php echo $edit_id > 0 ? 'Edit' : 'Add'; ?> Sale
            </h1>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active"><?php echo $edit_id > 0 ? 'Edit' : 'Add'; ?> Sale</li>
            </ol>
        </div>
        
        <div id="alertMessage"></div>
        
        <form method="POST" action="" id="saleForm">
            <!-- Invoice Header -->
            <div class="card form-card">
                <div class="card-header-custom">
                    <i class="fas fa-file-invoice mr-2"></i> <?php echo $edit_id > 0 ? 'Edit' : 'Sale'; ?> Invoice
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="required-field"><i class="fas fa-calendar text-success mr-1"></i> Sale Date</label>
                                <input type="date" name="sale_date" class="form-control" value="<?php echo $edit_data ? $edit_data['sale_date'] : date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><i class="fas fa-barcode text-success mr-1"></i> Invoice Number</label>
                                <input type="text" id="invoice_no_display" class="form-control" value="<?php echo $edit_data ? $edit_data['invoice_no'] : generateInvoiceNo($conn); ?>" readonly style="background:#e8f5e9; font-weight:bold;">
                                <input type="hidden" name="invoice_no" id="invoice_no" value="<?php echo $edit_data ? $edit_data['invoice_no'] : generateInvoiceNo($conn); ?>">
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
                                <textarea name="remarks" class="form-control" rows="2" placeholder="Enter remarks"><?php echo $edit_data ? htmlspecialchars($edit_data['remarks']) : ''; ?></textarea>
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
                                    <div class="row"><div class="col-4">DELIVERY / OTHER CHARGES:</div><div class="col-8"><input type="number" step="0.01" name="other_charges" id="other_charges" class="form-control form-control-sm" value="<?php echo $edit_data ? $edit_data['other_charges'] : 0; ?>" min="0" style="width:150px; display:inline-block; text-align:right;"></div></div>
                                </div>
                                <div class="calculation-row" id="advance_row">
                                    <div class="row"><div class="col-4">ADVANCE / RECEIVED:</div><div class="col-8"><input type="number" step="0.01" name="received_amount" id="received_amount" class="form-control form-control-sm" value="<?php echo $edit_data ? $edit_data['received_amount'] : 0; ?>" min="0" style="width:150px; display:inline-block; text-align:right;"></div></div>
                                </div>
                                <div class="calculation-row" style="background: #e8f5e9;">
                                    <div class="row"><div class="col-4"><strong>BALANCE:</strong></div><div class="col-8 text-right"><strong id="grandTotal" style="font-size:20px; color:#dc3545;">₨ 0.00</strong></div></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="calculation-panel">
                                <div class="calculation-row">
                                    <div class="row"><div class="col-6">Payment Method:</div><div class="col-6"><select name="payment_type" id="payment_type" class="form-control form-control-sm"><?php $pt = $edit_data ? $edit_data['payment_type'] : 'cash'; ?><option value="cash" <?php echo $pt == 'cash' ? 'selected' : ''; ?>>Cash</option><option value="bank" <?php echo $pt == 'bank' ? 'selected' : ''; ?>>Bank</option><option value="credit" <?php echo $pt == 'credit' ? 'selected' : ''; ?>>Credit</option><option value="partial" <?php echo $pt == 'partial' ? 'selected' : ''; ?>>Partial</option></select></div></div>
                                </div>
                                <div class="calculation-row" id="bank_div" style="display: none;">
                                    <div class="row"><div class="col-6">Select Bank:</div><div class="col-6"><select name="bank_account_id" class="form-control form-control-sm"><option value="">Select Bank</option><?php while($bank = mysqli_fetch_assoc($bank_result)): ?><option value="<?php echo $bank['id']; ?>"><?php echo htmlspecialchars($bank['bank_name'] . ' - ' . ($bank['account_number'] ?? $bank['account_title'])); ?></option><?php endwhile; ?></select></div></div>
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
                            <button type="button" class="btn btn-warning" id="holdBillBtn">
                                <i class="fas fa-pause-circle"></i> Hold Bill
                            </button>
                            <button type="button" class="btn btn-info" id="loadHoldBillBtn" data-toggle="modal" data-target="#holdBillsModal">
                                <i class="fas fa-folder-open"></i> Load Hold Bill
                            </button>
                            <button type="button" id="generateInvoiceBtn" class="btn btn-green">
                                <i class="fas fa-save mr-1"></i> Save Sale
                            </button>
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
            <?php if ($edit_id > 0): ?>
            <input type="hidden" name="edit_id" id="edit_id" value="<?php echo $edit_id; ?>">
            <?php endif; ?>
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
let lastSelectedProductId = 0;

// Products list for product group dropdown
const rowProductsList = <?php echo json_encode($row_products_data); ?>;

function getProductOptions(selectedId) {
    let html = '<option value="">Select Product</option>';
    rowProductsList.forEach(function(p) {
        html += `<option value="${p.id}" data-price="${p.sale_price}" ${selectedId == p.id ? 'selected' : ''}>${p.product_name}</option>`;
    });
    return html;
}

// Edit mode data (from PHP)
<?php if ($edit_data): ?>
const editData = <?php echo json_encode([
    'sale_id' => $edit_data['id'],
    'customer_id' => $edit_data['customer_id'],
    'subtotal' => $edit_data['subtotal'],
    'other_charges' => $edit_data['other_charges'],
    'grand_total' => $edit_data['grand_total'],
    'received_amount' => $edit_data['received_amount'],
    'remaining_amount' => $edit_data['remaining_amount'],
    'payment_type' => $edit_data['payment_type'],
    'bank_account_id' => $edit_data['bank_account_id'],
    'reference_no' => $edit_data['reference_no'],
    'remarks' => $edit_data['remarks'],
    'products' => $edit_products
]); ?>;
<?php else: ?>
const editData = null;
<?php endif; ?>

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

// Add new product group (product dropdown at top, sizes below)
function addProductGroup(savedRate = null) {
    const groupId = productGroupId++;
    const selectedId = lastSelectedProductId || 0;
    const groupHtml = `
        <div class="card mb-3 product-group-card" data-group-id="${groupId}">
            <div class="card-header bg-success text-white">
                <div class="row">
                    <div class="col-md-8">
                        <select class="form-control product-select" data-group-id="${groupId}" style="width: 100%;">${getProductOptions(selectedId)}</select>
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
                                <th style="min-width:150px;">Actual Client Size (Inch)</th>
                                <th>QTY</th>
                                <th>Multiple Of</th>
                                <th>Std Height (Feet)</th>
                                <th>Std Width (Feet)</th>
                                <th>Area (sq ft)</th>
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
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.01" class="form-control client-height" data-group-id="${groupId}" data-row-id="0" placeholder="H" value="0" style="min-width:60px;">
                                        <div class="input-group-append input-group-prepend">
                                            <span class="input-group-text" style="padding:2px 6px;">x</span>
                                        </div>
                                        <input type="number" step="0.01" class="form-control client-width" data-group-id="${groupId}" data-row-id="0" placeholder="W" value="0" style="min-width:60px;">
                                    </div>
                                </td>
                                <td><input type="number" step="0.01" class="form-control quantity" data-group-id="${groupId}" data-row-id="0" value="1" min="0.01"></td>
                                <td>
                                    <select class="form-control multiple-of" data-group-id="${groupId}" data-row-id="0">
                                        <option value="3">3</option><option value="6" selected>6</option>
                                        <option value="9">9</option><option value="12">12</option><option value="24">24</option>
                                    </select>
                                </td>
                                <td><input type="number" step="0.01" class="form-control std-height" data-group-id="${groupId}" data-row-id="0" readonly style="background:#f0f8ff;" value="0"></td>
                                <td><input type="number" step="0.01" class="form-control std-width" data-group-id="${groupId}" data-row-id="0" readonly style="background:#f0f8ff;" value="0"></td>
                                <td class="area-cell text-right" data-group-id="${groupId}" data-row-id="0"><strong>0.00</strong><br><small>sq ft</small></td>
                                <td class="total-area-cell text-right" data-group-id="${groupId}" data-row-id="0"><strong>0.00</strong><br><small>sq ft</small></td>
                                <td><input type="number" step="0.01" class="form-control rate" data-group-id="${groupId}" data-row-id="0" value="0" min="0"></td>
                                <td class="amount-cell text-right" data-group-id="${groupId}" data-row-id="0"><strong>₨ 0.00</strong></td>
                                <td><input type="number" step="0.01" class="form-control discount" data-group-id="${groupId}" data-row-id="0" value="0" min="0" max="100"></td>
                                <td class="net-amount-cell text-right" data-group-id="${groupId}" data-row-id="0"><strong>₨ 0.00</strong></td>
                                <td><button type="button" class="btn btn-sm btn-danger remove-size-row" data-group-id="${groupId}" data-row-id="0"><i class="fas fa-trash"></i></button></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr><td colspan="12"><button type="button" class="btn btn-sm btn-success add-size-row" data-group-id="${groupId}"><i class="fas fa-plus-circle"></i> Add Size</button></td></tr>
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
    
    // Pre-selected last product → auto-fill rate
    if(selectedId) {
        $(`.product-select[data-group-id="${groupId}"]`).trigger('change');
    }
    
    return groupId;
}

// Bind events for a product group
function bindGroupEvents(groupId) {
    // Product selection change → set rate for all size rows in this group
    // NOTE: no .off() here — it would remove select2's own change listener
    // and the selected product would never show in the box.
    $(`.product-select[data-group-id="${groupId}"]`).on('change', function() {
        const selected = $(this).find(':selected');
        const productId = selected.val();
        const price = selected.data('price') || 0;
        lastSelectedProductId = productId;
        
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
            <td>
                <div class="input-group input-group-sm">
                    <input type="number" step="0.01" class="form-control client-height" data-group-id="${groupId}" data-row-id="${newRowId}" placeholder="H" value="0" style="min-width:60px;">
                    <div class="input-group-append input-group-prepend">
                        <span class="input-group-text" style="padding:2px 6px;">x</span>
                    </div>
                    <input type="number" step="0.01" class="form-control client-width" data-group-id="${groupId}" data-row-id="${newRowId}" placeholder="W" value="0" style="min-width:60px;">
                </div>
            </td>
            <td><input type="number" step="0.01" class="form-control quantity" data-group-id="${groupId}" data-row-id="${newRowId}" value="1" min="0.01"></td>
            <td>
                <select class="form-control multiple-of" data-group-id="${groupId}" data-row-id="${newRowId}">
                    <option value="3">3</option><option value="6" selected>6</option>
                    <option value="9">9</option><option value="12">12</option><option value="24">24</option>
                </select>
             </td>
            <td><input type="number" step="0.01" class="form-control std-height" data-group-id="${groupId}" data-row-id="${newRowId}" readonly style="background:#f0f8ff;" value="0"></td>
            <td><input type="number" step="0.01" class="form-control std-width" data-group-id="${groupId}" data-row-id="${newRowId}" readonly style="background:#f0f8ff;" value="0"></td>
            <td class="area-cell text-right" data-group-id="${groupId}" data-row-id="${newRowId}"><strong>0.00</strong><br><small>sq ft</small></td>
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
    // Client size + multiple change
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
                    discount_percentage: discount,
                    amount: netAmount
                });
            });
        }
    });
    
    $('#product_data').val(JSON.stringify(productData));
}

// ==================== HOLD BILL FUNCTIONS ====================

// Save current sale as Hold Bill (no inventory/ledger updates)
// FIX: header-level discount_percentage was previously set to the rupee
// discount text (e.g. "₨ 250.00"), same as discount_amount. Now it's a
// real weighted percentage.
$('#holdBillBtn').on('click', function(e) {
    e.preventDefault();
    
    // Validate at least one product has been selected
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
    
    const subtotalVal = parseFloat($('#subtotal_input').val()) || 0;
    const totalDiscountVal = parseFloat($('#discount_amount_input').val()) || 0;
    const grossVal = subtotalVal + totalDiscountVal;
    const discPercentOverall = grossVal > 0 ? (totalDiscountVal / grossVal * 100) : 0;
    
    const holdData = {
        customer_id: $('#customer_id').val(),
        subtotal: subtotalVal,
        discount_percentage: discPercentOverall.toFixed(2),
        discount_amount: totalDiscountVal,
        other_charges: $('#other_charges').val(),
        grand_total: $('#grand_total_input').val(),
        remarks: $('textarea[name="remarks"]').val() || '',
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
            <td>
                <div class="input-group input-group-sm">
                    <input type="number" step="0.01" class="form-control client-height" data-group-id="${groupId}" data-row-id="${newRowId}" value="${item.client_height}" style="min-width:60px;">
                    <div class="input-group-append input-group-prepend">
                        <span class="input-group-text" style="padding:2px 6px;">x</span>
                    </div>
                    <input type="number" step="0.01" class="form-control client-width" data-group-id="${groupId}" data-row-id="${newRowId}" value="${item.client_width}" style="min-width:60px;">
                </div>
            </td>
            <td><input type="number" step="0.01" class="form-control quantity" data-group-id="${groupId}" data-row-id="${newRowId}" value="${item.quantity}"></td>
            <td><select class="form-control multiple-of" data-group-id="${groupId}" data-row-id="${newRowId}">${getMultipleOptions(item.multiple_of)}</select></td>
            <td><input type="number" step="0.01" class="form-control std-height" data-group-id="${groupId}" data-row-id="${newRowId}" readonly value="${item.std_height}"></td>
            <td><input type="number" step="0.01" class="form-control std-width" data-group-id="${groupId}" data-row-id="${newRowId}" readonly value="${item.std_width}"></td>
            <td class="area-cell" data-group-id="${groupId}" data-row-id="${newRowId}"><strong>${(item.area_per_unit || item.area).toFixed(2)}</strong><br><small>sq ft</small></td>
            <td class="total-area-cell" data-group-id="${groupId}" data-row-id="${newRowId}"><strong>${((item.area_per_unit || item.area) * item.quantity).toFixed(2)}</strong><br><small>sq ft</small></td>
            <td><input type="number" step="0.01" class="form-control rate" data-group-id="${groupId}" data-row-id="${newRowId}" value="${item.rate}"></td>
            <td class="amount-cell" data-group-id="${groupId}" data-row-id="${newRowId}"><strong>₨ ${((item.area_per_unit || item.area) * item.quantity * item.rate).toFixed(2)}</strong></td>
            <td><input type="number" step="0.01" class="form-control discount" data-group-id="${groupId}" data-row-id="${newRowId}" value="${item.discount_percentage}"></td>
            <td class="net-amount-cell" data-group-id="${groupId}" data-row-id="${newRowId}"><strong>₨ ${item.amount}</strong></td>
            <td><button type="button" class="btn btn-sm btn-danger remove-size-row" data-group-id="${groupId}" data-row-id="${newRowId}"><i class="fas fa-trash"></i></button></td>
        </tr>
    `;
    container.append(newRow);
    
    // Set data-value attributes for calculation functions
    const perUnitArea = parseFloat(item.area_per_unit || item.area) || 0;
    const qty = parseFloat(item.quantity) || 0;
    const rate = parseFloat(item.rate) || 0;
    const totArea = perUnitArea * qty;
    const amt = totArea * rate;
    const discPct = parseFloat(item.discount_percentage) || 0;
    const netAmt = amt - (amt * (discPct / 100));
    
    $(`.area-cell[data-group-id="${groupId}"][data-row-id="${newRowId}"]`).data('value', perUnitArea);
    $(`.total-area-cell[data-group-id="${groupId}"][data-row-id="${newRowId}"]`).data('value', totArea);
    $(`.amount-cell[data-group-id="${groupId}"][data-row-id="${newRowId}"]`).data('value', amt);
    $(`.net-amount-cell[data-group-id="${groupId}"][data-row-id="${newRowId}"]`).data('value', netAmt);
    
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
    
    // Add New Product button → new product group
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
        $('#customer_balance').text('₨ ' + parseFloat(balance).toFixed(2));
        $('#prevBalance').text('₨ ' + parseFloat(balance).toFixed(2));
        $('#prevBalance').data('value', balance);
        calculateAllTotals();
    });
    
    // Payment type change
    $('#payment_type').on('change', function() {
        const val = $(this).val();
        if(val === 'bank') $('#bank_div').show();
        else $('#bank_div').hide();
        if(val === 'credit') $('#advance_row').hide();
        else $('#advance_row').show();
        calculateAllTotals();
    });
    
    $('#other_charges, #received_amount').on('keyup change', function() { calculateAllTotals(); });
    $('#refreshBtn').on('click', function() { location.reload(); });
    
    // Load edit data if in edit mode
    if(editData) {
        // Set customer
        $('#customer_id').val(editData.customer_id).trigger('change');
        
        // Set reference no
        $('input[name="reference_no"]').val(editData.reference_no || '');
        
        // Set bank account
        if(editData.bank_account_id) {
            $('select[name="bank_account_id"]').val(editData.bank_account_id);
        }
        
        // Clear existing product groups
        $('#productGroupsContainer').empty();
        productGroupId = 0;
        
        // Rebuild product groups from edit data
        let productsByGroup = {};
        $.each(editData.products, function(i, item) {
            let key = item.product_id;
            if(!productsByGroup[key]) productsByGroup[key] = [];
            productsByGroup[key].push(item);
        });
        
        // Create a group for each product
        $.each(productsByGroup, function(productId, items) {
            let groupId = addProductGroup(parseFloat(items[0].rate));
            let groupCard = $(`.product-group-card[data-group-id="${groupId}"]`);
            let productSelect = groupCard.find('.product-select');
            productSelect.val(productId).trigger('change');
            groupCard.find('.size-rows-container').empty();
            $.each(items, function(idx, item) {
                addSizeRowWithData(groupId, item);
            });
        });
        
        // Set payment type and trigger change to show/hide bank/advance
        $('#payment_type').val(editData.payment_type).trigger('change');
    }
    
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
            console.log('save_sale.php raw response:', xhr.responseText);
            Swal.fire({ title: 'Error!', text: errorMsg, icon: 'error' });
        }
    });
});
</script>

</body>
</html>
 
<?php mysqli_close($conn); ?>