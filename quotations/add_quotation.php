<?php
/**
 * Add Quotation Page - WITH HOLD BILL FEATURE
 * Faysal Glass And Aluminium Centre
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$page_title = "Add Quotation";
$success_msg = '';
$error_msg = '';

// Handle Hold Quotation AJAX requests (mirror of sales hold bills: parked only, no stock/ledger)
if(isset($_GET['action'])) {

    // Save Hold Quotation
    if($_GET['action'] == 'save_hold' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        $data = is_array($json) ? $json : $_POST;
        $hold_date = mysqli_real_escape_string($conn, $data['quotation_date'] ?? date('Y-m-d'));
        $customer_id = intval($data['customer_id'] ?? 0);
        $subtotal = floatval($data['subtotal'] ?? 0);
        $discount_percentage = floatval($data['discount_percentage'] ?? 0);
        $discount_amount = floatval($data['discount_amount'] ?? 0);
        $other_charges = floatval($data['other_charges'] ?? 0);
        $grand_total = floatval($data['grand_total'] ?? 0);
        $valid_until = !empty($data['valid_until']) ? "'" . mysqli_real_escape_string($conn, $data['valid_until']) . "'" : "NULL";
        $reference_no = mysqli_real_escape_string($conn, $data['reference_no'] ?? '');
        $remarks = mysqli_real_escape_string($conn, $data['remarks'] ?? '');
        $products = $data['products'] ?? [];
        $created_by = $_SESSION['user_id'];

        if(empty($products)) {
            echo json_encode(['success' => false, 'message' => 'No products to hold']);
            exit;
        }

        // Generate Hold No
        $result = mysqli_query($conn, "SELECT MAX(CAST(SUBSTRING(hold_no, 7) AS UNSIGNED)) as last_num FROM hold_quotations_master");
        $row = mysqli_fetch_assoc($result);
        $next_num = str_pad((intval($row['last_num']) + 1), 5, '0', STR_PAD_LEFT);
        $hold_no = "HOLDQ-" . $next_num;

        mysqli_begin_transaction($conn);
        try {
            $insert_master = "INSERT INTO hold_quotations_master (hold_no, hold_date, customer_id, valid_until, reference_no, subtotal, discount_percentage, discount_amount, other_charges, grand_total, remarks, status, created_by) VALUES ('$hold_no', '$hold_date', $customer_id, $valid_until, '$reference_no', $subtotal, $discount_percentage, $discount_amount, $other_charges, $grand_total, '$remarks', 'hold', $created_by)";
            if(!mysqli_query($conn, $insert_master)) {
                throw new Exception("Failed to save hold quotation: " . mysqli_error($conn));
            }
            $hold_id = mysqli_insert_id($conn);
            foreach($products as $prod) {
                $p_product_id = intval($prod['product_id'] ?? 0);
                $p_client_height = floatval($prod['client_height'] ?? 0);
                $p_client_width = floatval($prod['client_width'] ?? 0);
                $p_std_height = mysqli_real_escape_string($conn, strval($prod['std_height'] ?? ''));
                $p_std_width = mysqli_real_escape_string($conn, strval($prod['std_width'] ?? ''));
                $p_uom = mysqli_real_escape_string($conn, strval($prod['uom'] ?? 'Inch'));
                $p_quantity = floatval($prod['quantity'] ?? 0);
                $p_unit_price = floatval($prod['unit_price'] ?? 0);
                $p_area = floatval($prod['area'] ?? 0);
                $p_amount = floatval($prod['amount'] ?? 0);
                $p_discount_percentage = floatval($prod['discount_percentage'] ?? 0);
                $p_discount_amount = floatval($prod['discount_amount'] ?? 0);
                $p_net_amount = floatval($prod['net_amount'] ?? 0);
                $insert_detail = "INSERT INTO hold_quotations_details (hold_id, product_id, client_height, client_width, std_height, std_width, uom, quantity, unit_price, area, amount, discount_percentage, discount_amount, net_amount) VALUES ($hold_id, $p_product_id, $p_client_height, $p_client_width, '$p_std_height', '$p_std_width', '$p_uom', $p_quantity, $p_unit_price, $p_area, $p_amount, $p_discount_percentage, $p_discount_amount, $p_net_amount)";
                if(!mysqli_query($conn, $insert_detail)) {
                    throw new Exception("Failed to save hold product details: " . mysqli_error($conn));
                }
            }
            mysqli_commit($conn);
            $response = ['success' => true, 'message' => 'Hold Quotation saved', 'hold_no' => $hold_no];
        } catch(Throwable $e) {
            mysqli_rollback($conn);
            $response = ['success' => false, 'message' => $e->getMessage()];
        }
        echo json_encode($response);
        exit;
    }

    // Get list of Hold Quotations
    if($_GET['action'] == 'get_hold_quotations') {
        $sql = "SELECT h.id, h.hold_no, h.hold_date, COALESCE(c.customer_name, 'Walk-In') as customer_name, h.grand_total 
                FROM hold_quotations_master h 
                LEFT JOIN customers c ON h.customer_id = c.id 
                WHERE h.status = 'hold' 
                ORDER BY h.hold_date DESC";
        $result = mysqli_query($conn, $sql);
        $holds = [];
        while($row = mysqli_fetch_assoc($result)) {
            $holds[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $holds]);
        exit;
    }

    // Load single Hold Quotation (only status = 'hold' can be loaded back)
    if($_GET['action'] == 'load_hold' && isset($_GET['id'])) {
        $hold_id = intval($_GET['id']);
        $master_res = mysqli_query($conn, "SELECT * FROM hold_quotations_master WHERE id = $hold_id AND status = 'hold'");
        $master = $master_res ? mysqli_fetch_assoc($master_res) : null;
        if(!$master) {
            echo json_encode(['success' => false, 'message' => 'Hold Quotation not found (may already be converted or deleted)']);
            exit;
        }
        $details = mysqli_query($conn, "SELECT * FROM hold_quotations_details WHERE hold_id = $hold_id");
        $products = [];
        while($det = mysqli_fetch_assoc($details)) {
            $det['area_per_unit'] = floatval($det['area']);
            $products[] = $det;
        }
        echo json_encode([
            'success' => true,
            'hold_id' => $master['id'],
            'customer_id' => $master['customer_id'],
            'quotation_date' => $master['hold_date'],
            'valid_until' => $master['valid_until'],
            'reference_no' => $master['reference_no'],
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

    // Delete Hold Quotation (only if status = 'hold')
    if($_GET['action'] == 'delete_hold' && isset($_GET['id'])) {
        $hold_id = intval($_GET['id']);
        $check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM hold_quotations_master WHERE id = $hold_id"));
        if(!$check || $check['status'] !== 'hold') {
            echo json_encode(['success' => false, 'message' => 'Only Hold quotations can be deleted']);
            exit;
        }
        mysqli_query($conn, "DELETE FROM hold_quotations_details WHERE hold_id = $hold_id");
        mysqli_query($conn, "DELETE FROM hold_quotations_master WHERE id = $hold_id");
        echo json_encode(['success' => true, 'message' => 'Hold Quotation deleted']);
        exit;
    }
}

// Generate Quotation Number
function generateQuotationNo($conn) {
    $prefix = "QTN";
    $query = "SELECT MAX(CAST(SUBSTRING(quotation_no, 5) AS UNSIGNED)) as max_num 
              FROM quotation_master 
              WHERE quotation_no LIKE '{$prefix}-%'";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $max_num = intval($row['max_num']);
        $next_num = $max_num + 1;
    } else {
        $next_num = 1;
    }
    return $prefix . "-" . str_pad($next_num, 5, '0', STR_PAD_LEFT);
}

// Fetch customers for dropdown
$customers_query = "SELECT id, customer_name, customer_code, mobile, address, current_balance FROM customers WHERE status = 1 ORDER BY customer_name";
$customers_result = mysqli_query($conn, $customers_query);

// Fetch products for dropdown
$products_query = "SELECT p.*, c.category_name, u.short_name as unit_name, u.id as unit_id
                   FROM products p
                   LEFT JOIN categories c ON p.category_id = c.id
                   LEFT JOIN units u ON p.unit_id = u.id
                   WHERE p.status = 1 
                   ORDER BY p.product_name";
$products_result = mysqli_query($conn, $products_query);

// Load an existing quotation for editing via ?edit_id=N
$edit_load_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
$form_load_data = null;

if ($edit_load_id > 0) {
    $page_title = "Edit Quotation";
    $edit_master_res = mysqli_query($conn, "SELECT * FROM quotation_master WHERE id = $edit_load_id");
    if ($edit_master_res && mysqli_num_rows($edit_master_res) > 0) {
        $edit_master = mysqli_fetch_assoc($edit_master_res);
        $edit_details_res = mysqli_query($conn, "SELECT * FROM quotation_details WHERE quotation_id = $edit_load_id");
        $edit_products = [];
        if ($edit_details_res) {
            while ($ed = mysqli_fetch_assoc($edit_details_res)) {
                $ed['area_per_unit'] = floatval($ed['area']);
                $edit_products[] = $ed;
            }
        }
        $form_load_data = [
            'quotation_id' => intval($edit_master['id']),
            'quotation_no' => $edit_master['quotation_no'],
            'customer_id' => intval($edit_master['customer_id']),
            'quotation_date' => $edit_master['quotation_date'],
            'valid_until' => $edit_master['valid_until'],
            'reference_no' => $edit_master['reference_no'],
            'subtotal' => $edit_master['subtotal'],
            'discount_percentage' => $edit_master['discount_percentage'],
            'discount_amount' => $edit_master['discount_amount'],
            'other_charges' => $edit_master['other_charges'],
            'grand_total' => $edit_master['grand_total'],
            'remarks' => $edit_master['remarks'],
            'products' => $edit_products
        ];
    }
}

// Load a Hold Quotation directly via ?load_hold_id=N (used by modal "Load" button)
$hold_load_id = isset($_GET['load_hold_id']) ? intval($_GET['load_hold_id']) : 0;
if ($hold_load_id > 0 && $form_load_data === null) {
    $page_title = "Add Quotation (Loading Hold Quotation)";
    $hold_res = mysqli_query($conn, "SELECT * FROM hold_quotations_master WHERE id = $hold_load_id AND status = 'hold'");
    if ($hold_res && mysqli_num_rows($hold_res) > 0) {
        $hold_master = mysqli_fetch_assoc($hold_res);
        $hold_details_res = mysqli_query($conn, "SELECT * FROM hold_quotations_details WHERE hold_id = $hold_load_id");
        $hold_products = [];
        if ($hold_details_res) {
            while ($hd = mysqli_fetch_assoc($hold_details_res)) {
                $hd['area_per_unit'] = floatval($hd['area']);
                $hold_products[] = $hd;
            }
        }
        $form_load_data = [
            'hold_id' => intval($hold_master['id']),
            'customer_id' => intval($hold_master['customer_id']),
            'quotation_date' => $hold_master['hold_date'],
            'valid_until' => $hold_master['valid_until'],
            'reference_no' => $hold_master['reference_no'],
            'subtotal' => $hold_master['subtotal'],
            'discount_percentage' => $hold_master['discount_percentage'],
            'discount_amount' => $hold_master['discount_amount'],
            'other_charges' => $hold_master['other_charges'],
            'grand_total' => $hold_master['grand_total'],
            'remarks' => $hold_master['remarks'],
            'products' => $hold_products
        ];
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
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.0.2/dist/select2-bootstrap4.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        .btn-green { background-color: #1e7e34; border-color: #1e7e34; color: white; }
        .btn-green:hover { background-color: #155724; border-color: #155724; color: white; }
        .btn-hold { background-color: #ffc107; border-color: #ffc107; color: #212529; }
        .btn-hold:hover { background-color: #e0a800; border-color: #e0a800; color: #212529; }
        .card-header-custom { background: linear-gradient(135deg, #1e7e34, #0066cc); color: white; border-radius: 10px 10px 0 0; padding: 15px 20px; }
        .form-card { border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 30px; }
        .calculation-panel { background: linear-gradient(135deg, #f8f9fc, #e9ecef); padding: 20px; border-radius: 10px; }
        .calculation-row { padding: 8px 0; border-bottom: 1px solid #ddd; }
        .calculation-row:last-child { border-bottom: none; }
        .customer-info { background: #e8f5e9; padding: 15px; border-radius: 8px; }
        .required-field::after { content: " *"; color: red; }
        .table thead th { background-color: #1e7e34; color: white; font-weight: 600; font-size: 12px; white-space: nowrap; }
        .remove-product { cursor: pointer; color: #dc3545; }
        .remove-product:hover { color: #a71d2a; }
        .select2-container .select2-selection--single { height: 38px; }
        .table td { padding: 8px; vertical-align: middle; }
        .table input, .table select { width: 100%; min-width: 80px; }
        .std-height, .std-width { background-color: #fff3cd; }
    </style>
</head>
<body id="page-top">

<div id="wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <div class="container-fluid">
                
                <div class="d-sm-flex align-items-center justify-content-between mb-4 mt-3">
                    <h1 class="h3 mb-0" style="color: #1e7e34;">
                        <i class="fas fa-file-alt text-success mr-2"></i> <?php echo $edit_load_id > 0 ? 'Edit Quotation' : 'Add Quotation'; ?>
                    </h1>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Add Quotation</li>
                    </ol>
                </div>
                
                <div id="alertMessage"></div>
                
                <form method="POST" action="" id="quotationForm">
                    <!-- Hidden field for status -->
                    <input type="hidden" name="quotation_status" id="quotation_status" value="draft">
                    
                    <!-- Quotation Header -->
                    <div class="card form-card">
                        <div class="card-header-custom">
                            <i class="fas fa-file-alt mr-2"></i> Quotation Information
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="required-field"><i class="fas fa-calendar text-success mr-1"></i> Quotation Date</label>
                                        <input type="date" name="quotation_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><i class="fas fa-barcode text-success mr-1"></i> Quotation Number</label>
                                        <input type="text" id="quotation_no_display" class="form-control" value="<?php echo generateQuotationNo($conn); ?>" readonly style="background:#e8f5e9; font-weight:bold;">
                                        <input type="hidden" name="quotation_no" id="quotation_no" value="<?php echo generateQuotationNo($conn); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><i class="fas fa-hourglass-half text-success mr-1"></i> Valid Until</label>
                                        <input type="date" name="valid_until" class="form-control" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><i class="fas fa-receipt text-success mr-1"></i> Reference Number</label>
                                        <input type="text" name="reference_no" class="form-control" placeholder="Enter reference number">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="required-field"><i class="fas fa-user text-success mr-1"></i> Customer</label>
                                        <div class="input-group">
                                            <select name="customer_id" id="customer_id" class="form-control" required>
                                                <option value="">Select Customer</option>
                                                <?php while($cust = mysqli_fetch_assoc($customers_result)): ?>
                                                    <option value="<?php echo $cust['id']; ?>" 
                                                            data-mobile="<?php echo $cust['mobile']; ?>"
                                                            data-address="<?php echo htmlspecialchars($cust['address'] ?? ''); ?>"
                                                            data-balance="<?php echo $cust['current_balance']; ?>">
                                                        <?php echo htmlspecialchars($cust['customer_name'] . ' (' . $cust['customer_code'] . ')'); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-warning" id="newCustomerBtn" title="New Walk-in Customer">
                                                    <i class="fas fa-plus"></i> New Customer
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="customer-info">
                                        <small class="text-muted">Mobile</small>
                                        <div id="customer_mobile" class="font-weight-bold">-</div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="customer-info">
                                        <small class="text-muted">Balance</small>
                                        <div id="customer_balance" class="font-weight-bold">₨ 0.00</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="customer-info mb-2">
                                        <small class="text-muted">Address</small>
                                        <div id="customer_address" class="font-weight-bold">-</div>
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
                    
                    <!-- Products Section -->
                    <div class="card form-card">
                        <div class="card-header-custom">
                            <i class="fas fa-boxes mr-2"></i> Products
                        </div>
                        <div class="card-body" style="overflow-x: auto;">
                            <table class="table table-bordered" id="productsTable" style="min-width: 1500px;">
                                <thead>
                                    <tr>
                                        <th style="min-width:180px;">Product</th>
                                        <th style="min-width:100px;">Client Height (Inch)</th>
                                        <th style="min-width:100px;">Client Width (Inch)</th>
                                        <th style="min-width:90px;">Std Height (Feet)</th>
                                        <th style="min-width:90px;">Std Width (Feet)</th>
                                        <th style="min-width:70px;">QTY</th>
                                        <th style="min-width:100px;">Unit Price</th>
                                        <th style="min-width:90px;">Area (sq ft)</th>
                                        <th style="min-width:100px;">Total Area (sq ft)</th>
                                        <th style="min-width:100px;">Amount</th>
                                        <th style="min-width:70px;">Disc %</th>
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
                                                ?>
                                                    <option value="<?php echo $prod['id']; ?>" 
                                                            data-price="<?php echo $prod['sale_price']; ?>"
                                                            data-name="<?php echo htmlspecialchars($prod['product_name']); ?>">
                                                        <?php echo htmlspecialchars($prod['product_name']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </td>
                                        <td><input type="number" step="0.01" name="client_height[]" class="form-control client-height" data-row="0" placeholder="H" value="0"></td>
                                        <td><input type="number" step="0.01" name="client_width[]" class="form-control client-width" data-row="0" placeholder="W" value="0"></td>
                                        <td><input type="number" step="0.01" name="std_height[]" class="form-control std-height" data-row="0" placeholder="Std H" style="background:#fff3cd;" value="0"></td>
                                        <td><input type="number" step="0.01" name="std_width[]" class="form-control std-width" data-row="0" placeholder="Std W" style="background:#fff3cd;" value="0"></td>
                                        <td><input type="number" step="0.01" name="quantity[]" class="form-control quantity" data-row="0" value="1" min="0"></td>
                                        <td><input type="number" step="0.01" name="unit_price[]" class="form-control unit-price" data-row="0" value="0" min="0"></td>
                                        <td><input type="number" step="0.01" name="area[]" class="form-control area" data-row="0" value="0" readonly style="background:#e9ecef;"></td>
                                        <td><input type="number" step="0.01" name="total_area[]" class="form-control total-area" data-row="0" value="0" readonly style="background:#e9ecef;"></td>
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
                    
                    <!-- Calculation Panel -->
                    <div class="card form-card">
                        <div class="card-header-custom">
                            <i class="fas fa-calculator mr-2"></i> Quotation Summary
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="calculation-panel">
                                        <div class="calculation-row">
                                            <div class="row"><div class="col-6">Subtotal:</div><div class="col-6 text-right"><strong id="subtotal">₨ 0.00</strong></div></div>
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
                                            <div class="row"><div class="col-6">Quotation Amount:</div><div class="col-6 text-right"><strong id="quotationAmount">₨ 0.00</strong></div></div>
                                        </div>
                                        <div class="calculation-row">
                                            <div class="row"><div class="col-6">Balance After Quotation:</div><div class="col-6 text-right"><strong id="newBalance" style="font-size:16px;">₨ 0.00</strong></div></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-md-12 text-right">
                                    <button type="button" class="btn btn-secondary" id="refreshBtn"><i class="fas fa-sync-alt mr-1"></i> Reset</button>
                                    <a href="view_quotation.php" class="btn btn-info"><i class="fas fa-list mr-1"></i> View Quotations</a>
                                    <!-- Hold Quotation Button (parks the bill, no stock/ledger) -->
                                    <button type="button" id="holdQuotationBtn" class="btn btn-hold"><i class="fas fa-pause-circle mr-1"></i> Hold Quotation</button>
                                    <button type="button" class="btn btn-info" id="loadHoldQuotationBtn" data-toggle="modal" data-target="#holdQuotationsModal">
                                        <i class="fas fa-folder-open mr-1"></i> Load Hold Quotation
                                    </button>
                                    <button type="button" id="saveQuotationBtn" class="btn btn-green"><i class="fas fa-save mr-1"></i> Save Quotation</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="subtotal" id="subtotal_input" value="0">
                    <input type="hidden" name="discount_amount" id="discount_amount_input" value="0">
                    <input type="hidden" name="grand_total" id="grand_total_input" value="0">
                    <input type="hidden" name="hold_id" id="hold_id" value="">
                </form>
                
            </div>
        </div>
        
        <footer class="sticky-footer bg-white">
            <div class="container my-auto"><div class="copyright text-center my-auto"><span>&copy; <?php echo date('Y'); ?> <?php echo $software_name; ?> - All Rights Reserved</span></div></div>
        </footer>
    </div>
</div>

<!-- Load Hold Quotations Modal -->
<div class="modal fade" id="holdQuotationsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-folder-open"></i> Load Hold Quotation</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="holdQuotationsTable" width="100%">
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

<!-- New Customer Modal -->
<div class="modal fade" id="newCustomerModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="fas fa-user-plus"></i> New Customer</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Customer Name *</label>
                    <input type="text" id="new_customer_name" class="form-control" placeholder="Enter customer name">
                </div>
                <div class="form-group">
                    <label>Mobile (Optional)</label>
                    <input type="text" id="new_customer_mobile" class="form-control" placeholder="Enter mobile number">
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
let productCount = 1;

// Data passed via ?edit_id=N or ?load_hold_id=N to pre-fill the form
const formLoadData = <?php echo json_encode($form_load_data); ?>;

function calculateArea(height, width) {
    if (height > 0 && width > 0) {
        return height * width;
    }
    return 0;
}

function calculateRowAmount(row) {
    var area = parseFloat($(`.area[data-row="${row}"]`).val()) || 0;
    var unitPrice = parseFloat($(`.unit-price[data-row="${row}"]`).val()) || 0;
    var quantity = parseFloat($(`.quantity[data-row="${row}"]`).val()) || 0;
    var discountPercent = parseFloat($(`.discount-percent[data-row="${row}"]`).val()) || 0;
    
    var totalArea = area * quantity;
    var amount = totalArea * unitPrice;
    var discountAmount = amount * (discountPercent / 100);
    var netAmount = amount - discountAmount;
    
    $(`.total-area[data-row="${row}"]`).val(totalArea.toFixed(2));
    $(`.row-amount[data-row="${row}"]`).val(amount.toFixed(2));
    $(`.net-amount[data-row="${row}"]`).val(netAmount.toFixed(2));
    
    return netAmount;
}

function updateArea(row) {
    var stdH = parseFloat($(`.std-height[data-row="${row}"]`).val()) || 0;
    var stdW = parseFloat($(`.std-width[data-row="${row}"]`).val()) || 0;
    var area = calculateArea(stdH, stdW);
    $(`.area[data-row="${row}"]`).val(area.toFixed(2));
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
    
    $('#subtotal').text('₨ ' + subtotal.toFixed(2));
    $('#discountAmount').text('₨ ' + globalDiscountAmt.toFixed(2));
    $('#grandTotal').text('₨ ' + grandTotal.toFixed(2));
    $('#quotationAmount').text('₨ ' + grandTotal.toFixed(2));
    $('#newBalance').text('₨ ' + newBalance.toFixed(2));
    $('#subtotal_input').val(subtotal.toFixed(2));
    $('#discount_amount_input').val(globalDiscountAmt.toFixed(2));
    $('#grand_total_input').val(grandTotal.toFixed(2));
    
    if(newBalance > 0) $('#newBalance').css('color', '#dc3545');
    else if(newBalance < 0) $('#newBalance').css('color', '#28a745');
    else $('#newBalance').css('color', '#1a1a1a');
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
                        <option value="<?php echo $prod['id']; ?>" data-price="<?php echo $prod['sale_price']; ?>"><?php echo htmlspecialchars($prod['product_name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </td>
            <td><input type="number" step="0.01" name="client_height[]" class="form-control client-height" data-row="${productCount}" placeholder="H" value="0"></td>
            <td><input type="number" step="0.01" name="client_width[]" class="form-control client-width" data-row="${productCount}" placeholder="W" value="0"></td>
            <td><input type="number" step="0.01" name="std_height[]" class="form-control std-height" data-row="${productCount}" placeholder="Std H" style="background:#fff3cd;" value="0"></td>
            <td><input type="number" step="0.01" name="std_width[]" class="form-control std-width" data-row="${productCount}" placeholder="Std W" style="background:#fff3cd;" value="0"></td>
            <td><input type="number" step="0.01" name="quantity[]" class="form-control quantity" data-row="${productCount}" value="1" min="0"></td>
            <td><input type="number" step="0.01" name="unit_price[]" class="form-control unit-price" data-row="${productCount}" value="0" min="0"></td>
            <td><input type="number" step="0.01" name="area[]" class="form-control area" data-row="${productCount}" value="0" readonly style="background:#e9ecef;"></td>
            <td><input type="number" step="0.01" name="total_area[]" class="form-control total-area" data-row="${productCount}" value="0" readonly style="background:#e9ecef;"></td>
            <td><input type="number" step="0.01" name="amount[]" class="form-control row-amount" data-row="${productCount}" value="0" readonly style="background:#e9ecef;"></td>
            <td><input type="number" step="0.01" name="discount_percent[]" class="form-control discount-percent" data-row="${productCount}" value="0" min="0" max="100"></td>
            <td><input type="number" step="0.01" name="net_amount[]" class="form-control net-amount" data-row="${productCount}" value="0" readonly style="background:#e9ecef;"></td>
            <td class="text-center"><i class="fas fa-trash text-danger remove-product" data-row="${productCount}" style="cursor:pointer; font-size:18px;"></i></td>
        </tr>
    `;
    $('#productsBody').append(newRow);
    
    $(`.product-select[data-row="${productCount}"]`).select2({ theme: 'bootstrap4', placeholder: 'Search product...', width: '100%' });
    bindRowEvents(productCount);
    productCount++;
}

function bindRowEvents(row) {
    $(`.product-select[data-row="${row}"]`).on('change', function() {
        var r = $(this).data('row');
        var price = $(this).find(':selected').data('price') || 0;
        $(`.unit-price[data-row="${r}"]`).val(price);
        calculateRowAmount(r);
        calculateTotals();
    });
    
    $(`.client-height[data-row="${row}"], .client-width[data-row="${row}"]`).on('keyup change', function() {
        var r = $(this).data('row');
        updateArea(r);
        calculateRowAmount(r);
        calculateTotals();
    });

    $(`.client-height[data-row="${row}"], .client-width[data-row="${row}"]`).on('blur', function() {
        var r = $(this).data('row');
        var h = parseFloat($(`.client-height[data-row="${r}"]`).val()) || 0;
        var w = parseFloat($(`.client-width[data-row="${r}"]`).val()) || 0;
        $(`.std-height[data-row="${r}"]`).val((h / 12).toFixed(2));
        $(`.std-width[data-row="${r}"]`).val((w / 12).toFixed(2));
        updateArea(r);
        calculateRowAmount(r);
        calculateTotals();
    });
    
    $(`.std-height[data-row="${row}"], .std-width[data-row="${row}"]`).on('keyup change', function() {
        var r = $(this).data('row');
        updateArea(r);
        calculateRowAmount(r);
        calculateTotals();
    });
    
    $(`.quantity[data-row="${row}"], .unit-price[data-row="${row}"], .discount-percent[data-row="${row}"]`).on('keyup change', function() {
        var r = $(this).data('row');
        calculateRowAmount(r);
        calculateTotals();
    });
    
    $(`.remove-product[data-row="${row}"]`).on('click', function() {
        $(`#product_row_${row}`).remove();
        calculateTotals();
    });
}

$(document).ready(function() {
    $('.product-select').select2({ theme: 'bootstrap4', placeholder: 'Search product...', width: '100%' });
    bindRowEvents(0);
    
    $('#customer_id').on('change', function() {
        var selected = $(this).find(':selected');
        var mobile = selected.data('mobile') || '-';
        var address = selected.data('address') || '-';
        var balance = selected.data('balance') || 0;
        $('#customer_mobile').text(mobile);
        $('#customer_address').text(address);
        $('#prevBalance').text('₨ ' + parseFloat(balance).toFixed(2));
        $('#prevBalance').data('value', balance);
        calculateTotals();
    });
    
    // New Customer Button
    $('#newCustomerBtn').on('click', function() {
        $('#newCustomerModal').modal('show');
    });
    
    // Save New Customer
    $('#saveNewCustomerBtn').on('click', function() {
        var custName = $('#new_customer_name').val().trim();
        if(custName === '') {
            Swal.fire({ title: 'Error!', text: 'Please enter customer name!', icon: 'error' });
            return;
        }
        var mobile = $('#new_customer_mobile').val().trim();
        if(mobile === '') mobile = '0000000000';
        
        Swal.fire({ title: 'Processing...', text: 'Adding customer...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
        
        $.ajax({
            url: 'add_customer_ajax.php',
            type: 'POST',
            data: { customer_name: custName, mobile: mobile },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    var newOption = new Option(custName + ' (' + response.customer_code + ')', response.customer_id, true, true);
                    $('#customer_id').append(newOption);
                    $('#customer_id').val(response.customer_id).trigger('change');
                    $('#newCustomerModal').modal('hide');
                    $('#new_customer_name').val('');
                    $('#new_customer_mobile').val('');
                    Swal.fire({ title: 'Success!', text: 'Customer added and selected!', icon: 'success', timer: 1500 });
                } else {
                    Swal.fire({ title: 'Error!', text: response.message, icon: 'error' });
                }
            },
            error: function() {
                Swal.fire({ title: 'Error!', text: 'Failed to add customer!', icon: 'error' });
            }
        });
    });
    
    $('#global_discount, #other_charges').on('keyup change', function() { calculateTotals(); });
    $('#addProductBtn').on('click', function() { addProductRow(); });
    $('#refreshBtn').on('click', function() { location.reload(); });
    
    updateArea(0);
    calculateRowAmount(0);
    calculateTotals();
    
    // --- SAVE QUOTATION (draft) ---
    $('#saveQuotationBtn').on('click', function(e) {
        e.preventDefault();
        // Set status to draft
        $('#quotation_status').val('draft');
        submitQuotation('Save Quotation');
    });
    
    // --- HOLD QUOTATION (parked bill, no stock/ledger) ---
    $('#holdQuotationBtn').on('click', function(e) {
        e.preventDefault();
        holdQuotation();
    });
    
    // --- LOAD HOLD QUOTATION ---
    $('#loadHoldQuotationBtn').on('click', function() {
        loadHoldQuotationsList();
    });
    
    function loadHoldQuotationsList() {
        $.getJSON('add_quotation.php?action=get_hold_quotations', function(res) {
            if(res.success) {
                let tbody = $('#holdQuotationsTable tbody');
                tbody.empty();
                if(res.data.length === 0) {
                    tbody.append('<tr><td colspan="5" class="text-center">No hold quotations found</td></tr>');
                }
                $.each(res.data, function(i, bill) {
                    let row = `<tr>
                        <td><strong>${bill.hold_no}</strong></td>
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
                $('#holdQuotationsModal').modal('show');
            } else {
                Swal.fire('Error', 'Failed to load hold quotations', 'error');
            }
        });
    }
    
    // Populate the quotation form from a quotation or hold payload (shared by modal load, ?load_hold_id and ?edit_id)
    function populateQuotationForm(res) {
        $('#hold_id').remove();
        $('#edit_id').remove();
        if(res.quotation_id) {
            $('<input>').attr({ type: 'hidden', id: 'edit_id', name: 'edit_id', value: res.quotation_id }).appendTo('#quotationForm');
        } else if(res.hold_id) {
            $('<input>').attr({ type: 'hidden', id: 'hold_id', name: 'hold_id', value: res.hold_id }).appendTo('#quotationForm');
        }
        
        if(res.quotation_no) {
            $('#quotation_no_display').val(res.quotation_no);
            $('#quotation_no').val(res.quotation_no);
        }
        if(res.customer_id) $('#customer_id').val(res.customer_id).trigger('change');
        if(res.quotation_date) $('input[name="quotation_date"]').val(res.quotation_date);
        if(res.valid_until) $('input[name="valid_until"]').val(res.valid_until);
        if(res.reference_no) $('input[name="reference_no"]').val(res.reference_no);
        if(res.remarks) $('textarea[name="remarks"]').val(res.remarks);
        $('#global_discount').val(parseFloat(res.discount_percentage) || 0);
        $('#other_charges').val(parseFloat(res.other_charges) || 0);
        
        // Rebuild product rows from hold details
        $('#productsBody').empty();
        productCount = 0;
        
        if(res.products && res.products.length > 0) {
            $.each(res.products, function(i, item) {
                addProductRow();
                populateQuotationRow(i, item);
            });
        }
        
        calculateTotals();
    }
    
    function populateQuotationRow(r, item) {
        const productId = parseInt(item.product_id) || 0;
        const clientH = parseFloat(item.client_height) || 0;
        const clientW = parseFloat(item.client_width) || 0;
        const stdH = item.std_height !== null && item.std_height !== '' ? parseFloat(item.std_height) || 0 : 0;
        const stdW = item.std_width !== null && item.std_width !== '' ? parseFloat(item.std_width) || 0 : 0;
        const qty = parseFloat(item.quantity) || 0;
        const unitPrice = parseFloat(item.unit_price) || 0;
        const area = parseFloat(item.area_per_unit) || parseFloat(item.area) || 0;
        const discountPercent = parseFloat(item.discount_percentage) || 0;
        
        // Set product first (its change handler loads the default price, we override after)
        $(`.product-select[data-row="${r}"]`).val(productId).trigger('change');
        
        $(`.client-height[data-row="${r}"]`).val(clientH);
        $(`.client-width[data-row="${r}"]`).val(clientW);
        $(`.std-height[data-row="${r}"]`).val(stdH);
        $(`.std-width[data-row="${r}"]`).val(stdW);
        $(`.quantity[data-row="${r}"]`).val(qty);
        $(`.unit-price[data-row="${r}"]`).val(unitPrice);
        $(`.area[data-row="${r}"]`).val(area.toFixed(2));
        $(`.discount-percent[data-row="${r}"]`).val(discountPercent);
        
        calculateRowAmount(r);
        calculateTotals();
    }
    
    function holdQuotation() {
        var customer = $('#customer_id').val();
        var hasProduct = false;
        $('select[name="product_id[]"]').each(function() { if($(this).val() !== '') hasProduct = true; });
        
        if(!customer) {
            Swal.fire({ title: 'Error!', text: 'Please select a customer!', icon: 'error' });
            return false;
        }
        if(!hasProduct) {
            Swal.fire({ title: 'Error!', text: 'Please add at least one product!', icon: 'error' });
            return false;
        }
        
        var products = [];
        $('select[name="product_id[]"]').each(function() {
            var r = $(this).data('row');
            var pid = parseInt($(this).val()) || 0;
            if(pid) {
                var amount = parseFloat($(`.row-amount[data-row="${r}"]`).val()) || 0;
                var netAmount = parseFloat($(`.net-amount[data-row="${r}"]`).val()) || 0;
                products.push({
                    product_id: pid,
                    client_height: parseFloat($(`.client-height[data-row="${r}"]`).val()) || 0,
                    client_width: parseFloat($(`.client-width[data-row="${r}"]`).val()) || 0,
                    std_height: $(`.std-height[data-row="${r}"]`).val() || '',
                    std_width: $(`.std-width[data-row="${r}"]`).val() || '',
                    uom: 'Inch',
                    quantity: parseFloat($(`.quantity[data-row="${r}"]`).val()) || 0,
                    unit_price: parseFloat($(`.unit-price[data-row="${r}"]`).val()) || 0,
                    area: parseFloat($(`.area[data-row="${r}"]`).val()) || 0,
                    amount: amount,
                    discount_percentage: parseFloat($(`.discount-percent[data-row="${r}"]`).val()) || 0,
                    discount_amount: amount - netAmount,
                    net_amount: netAmount
                });
            }
        });
        
        var holdData = {
            customer_id: customer,
            quotation_date: $('input[name="quotation_date"]').val(),
            valid_until: $('input[name="valid_until"]').val(),
            reference_no: $('input[name="reference_no"]').val(),
            remarks: $('textarea[name="remarks"]').val() || '',
            subtotal: parseFloat($('#subtotal_input').val()) || 0,
            discount_percentage: parseFloat($('#global_discount').val()) || 0,
            discount_amount: parseFloat($('#discount_amount_input').val()) || 0,
            other_charges: parseFloat($('#other_charges').val()) || 0,
            grand_total: parseFloat($('#grand_total_input').val()) || 0,
            products: products
        };
        
        Swal.fire({ title: 'Saving Hold Quotation...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        
        $.ajax({
            url: 'add_quotation.php?action=save_hold',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(holdData),
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    Swal.fire('Hold Quotation Saved', `Number: ${res.hold_no}`, 'info');
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Network error while saving hold quotation', 'error');
            }
        });
    }
    
    // Load a specific hold quotation into the form
    $(document).on('click', '.load-hold', function() {
        let id = $(this).data('id');
        $.getJSON(`add_quotation.php?action=load_hold&id=${id}`, function(res) {
            if(res.success) {
                populateQuotationForm(res);
                $('#holdQuotationsModal').modal('hide');
                Swal.fire('Loaded', 'Hold quotation loaded. You can modify and then Save Quotation.', 'success');
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        });
    });
    
    // Delete a hold quotation
    $(document).on('click', '.delete-hold', function() {
        let id = $(this).data('id');
        Swal.fire({ title: 'Confirm Delete', text: 'This hold quotation will be permanently deleted.', icon: 'warning', showCancelButton: true }).then((res) => {
            if(res.isConfirmed) {
                $.getJSON(`add_quotation.php?action=delete_hold&id=${id}`, function(response) {
                    if(response.success) {
                        Swal.fire('Deleted', '', 'success');
                        loadHoldQuotationsList();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                });
            }
        });
    });
    
    // Auto-load quotation/hold data passed via ?edit_id=N or ?load_hold_id=N
    if(formLoadData) {
        populateQuotationForm(formLoadData);
    }
    
    function submitQuotation(actionLabel) {
        var customer = $('#customer_id').val();
        var hasProduct = false;
        $('select[name="product_id[]"]').each(function() { if($(this).val() !== '') hasProduct = true; });
        
        if(!customer) {
            Swal.fire({ title: 'Error!', text: 'Please select a customer!', icon: 'error' });
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
        
        var formData = new FormData($('#quotationForm')[0]);
        formData.append('save_quotation', '1');
        
        Swal.fire({
            title: 'Processing...',
            text: 'Please wait while we save your quotation',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
        
        $.ajax({
            url: 'save_quotation.php',
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
                            window.location.href = 'print_quotation.php?id=' + response.quotation_id;
                        }
                    });
                } else {
                    Swal.fire({ title: 'Error!', text: response.message, icon: 'error' });
                }
            },
            error: function() {
                Swal.fire({ title: 'Error!', text: 'Failed to save quotation!', icon: 'error' });
            }
        });
    }
});
</script>

</body>
</html>
<?php mysqli_close($conn); ?>