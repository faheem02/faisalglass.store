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

// Edit mode
$edit_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
$is_edit = $edit_id > 0;
$edit_data = null;
$edit_items = [];

if($is_edit) {
    $eq = mysqli_query($conn, "SELECT * FROM quotation_master WHERE id = $edit_id");
    if($eq && mysqli_num_rows($eq) > 0) {
        $edit_data = mysqli_fetch_assoc($eq);
        $page_title = "Edit Quotation - " . $edit_data['quotation_no'];

        $dq = mysqli_query($conn, "SELECT * FROM quotation_details WHERE quotation_id = $edit_id ORDER BY id");
        if($dq) {
            while($d = mysqli_fetch_assoc($dq)) {
                $edit_items[] = $d;
            }
        }
    } else {
        header("Location: view_quotation.php");
        exit();
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

// Fetch customers for dropdown (including walk-in at top)
$customers_query = "SELECT id, customer_name, customer_code, mobile, address, current_balance FROM customers WHERE status = 1 ORDER BY CASE WHEN customer_code = 'WALK-IN' OR customer_name LIKE 'Walk-In%' OR customer_name LIKE 'TMP%' THEN 0 ELSE 1 END, customer_name";
$customers_result = mysqli_query($conn, $customers_query);

// Fetch products for dropdown
$products_query = "SELECT p.*, c.category_name, u.short_name as unit_name, u.id as unit_id
                   FROM products p
                   LEFT JOIN categories c ON p.category_id = c.id
                   LEFT JOIN units u ON p.unit_id = u.id
                   WHERE p.status = 1 
                   ORDER BY p.product_name";
$products_result = mysqli_query($conn, $products_query);

// Fetch bank accounts
$bank_query = "SELECT id, bank_name, account_title, account_number FROM bank_accounts WHERE status = 1";
$bank_result = mysqli_query($conn, $bank_query);
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
        .input-group > .select2-container--bootstrap4,
        .input-group > .select2-container {
            flex: 1 1 auto !important;
            width: 1% !important;
            min-width: 0 !important;
        }
        .input-group > .select2-container--bootstrap4 .select2-selection--single,
        .input-group > .select2-container .select2-selection--single {
            height: 38px !important;
            border-top-right-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
        }
        .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered,
        .select2-container .select2-selection--single .select2-selection__rendered {
            line-height: 36px !important;
            padding-left: 12px !important;
            padding-right: 24px !important;
        }
        .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow,
        .select2-container .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
            right: 8px !important;
        }
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
                        <i class="fas fa-file-alt text-success mr-2"></i> <?php echo $is_edit ? 'Edit Quotation' : 'Add Quotation'; ?>
                    </h1>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="view_quotation.php">Quotations</a></li>
                        <li class="breadcrumb-item active"><?php echo $is_edit ? 'Edit' : 'Add'; ?></li>
                    </ol>
                </div>
                
                <div id="alertMessage"></div>
                
                <form method="POST" action="" id="quotationForm">
                    <!-- Hidden fields for status and edit mode -->
                    <input type="hidden" name="quotation_status" id="quotation_status" value="<?php echo $is_edit ? htmlspecialchars($edit_data['status']) : 'draft'; ?>">
                    <?php if($is_edit): ?>
                        <input type="hidden" name="edit_id" value="<?php echo $edit_id; ?>">
                    <?php endif; ?>
                    
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
                                        <input type="date" name="quotation_date" class="form-control" value="<?php echo $is_edit ? $edit_data['quotation_date'] : date('Y-m-d'); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><i class="fas fa-barcode text-success mr-1"></i> Quotation Number</label>
                                        <input type="text" id="quotation_no_display" class="form-control" value="<?php echo $is_edit ? $edit_data['quotation_no'] : generateQuotationNo($conn); ?>" readonly style="background:#e8f5e9; font-weight:bold;">
                                        <input type="hidden" name="quotation_no" id="quotation_no" value="<?php echo $is_edit ? $edit_data['quotation_no'] : generateQuotationNo($conn); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><i class="fas fa-hourglass-half text-success mr-1"></i> Valid Until</label>
                                        <input type="date" name="valid_until" class="form-control" value="<?php echo $is_edit ? ($edit_data['valid_until'] ?? '') : date('Y-m-d', strtotime('+30 days')); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><i class="fas fa-receipt text-success mr-1"></i> Reference Number</label>
                                        <input type="text" name="reference_no" class="form-control" placeholder="Enter reference number" value="<?php echo htmlspecialchars($edit_data['reference_no'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group mb-0">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="required-field mb-0 font-weight-bold"><i class="fas fa-user text-success mr-1"></i> Customer</label>
                                            <div>
                                                <button type="button" class="btn btn-sm btn-success font-weight-bold py-0 px-2 mr-1" id="quickWalkInBtn" title="Add Walk-in Customer Name & Phone" style="height: 26px; font-size: 12px;">
                                                    <i class="fas fa-walking mr-1"></i> Walk-In
                                                </button>
                                                <button type="button" class="btn btn-sm btn-warning font-weight-bold py-0 px-2" id="newCustomerBtn" title="New Customer Account" style="height: 26px; font-size: 12px;">
                                                    <i class="fas fa-plus mr-1"></i> New
                                                </button>
                                            </div>
                                        </div>
                                        <select name="customer_id" id="customer_id" class="form-control" style="width: 100%;" required>
                                            <option value="">-- Select or Type to Search Customer --</option>
                                            <?php 
                                            while($cust = mysqli_fetch_assoc($customers_result)): ?>
                                                <option value="<?php echo $cust['id']; ?>" 
                                                        data-mobile="<?php echo $cust['mobile']; ?>"
                                                        data-address="<?php echo htmlspecialchars($cust['address'] ?? ''); ?>"
                                                        data-balance="<?php echo $cust['current_balance']; ?>"
                                                        <?php echo ($cust['customer_code'] == 'WALK-IN' || strpos($cust['customer_name'], 'Walk-in') !== false) ? 'data-walkin="1"' : ''; ?>
                                                        <?php echo ($is_edit && $edit_data['customer_id'] == $cust['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cust['customer_name'] . ' (' . $cust['customer_code'] . ')'); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <small class="text-muted d-block mt-1"><i class="fas fa-info-circle text-info mr-1"></i> Click <b>Walk-In</b> above for quick walk-in quotation or search customer</small>
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
                            
                            <input type="hidden" name="walk_in_customer_name" id="walk_in_customer_name" value="<?php echo htmlspecialchars($edit_data['walk_in_customer_name'] ?? ''); ?>">
                            <input type="hidden" name="walk_in_customer_phone" id="walk_in_customer_phone" value="<?php echo htmlspecialchars($edit_data['walk_in_customer_phone'] ?? ''); ?>">
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label><i class="fas fa-comment text-success mr-1"></i> Remarks</label>
                                        <textarea name="remarks" class="form-control" rows="2" placeholder="Enter remarks"><?php echo htmlspecialchars($edit_data['remarks'] ?? ''); ?></textarea>
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
                                        <th style="min-width:90px;">Std Height (Inch)</th>
                                        <th style="min-width:90px;">Std Width (Inch)</th>
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
                            <i class="fas fa-calculator mr-2"></i> Quotation & Payment Summary
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-7">
                                    <div class="calculation-panel">
                                        <div class="calculation-row">
                                            <div class="row"><div class="col-6">Subtotal:</div><div class="col-6 text-right"><strong id="subtotal">₨ 0.00</strong></div></div>
                                        </div>
                                        <div class="calculation-row">
                                            <div class="row"><div class="col-6">Discount Amount:</div><div class="col-6 text-right"><strong id="discountAmount">₨ 0.00</strong></div></div>
                                        </div>
                                        <div class="calculation-row">
                                            <div class="row"><div class="col-6">Discount %:</div><div class="col-6"><input type="number" step="0.01" name="discount_percentage" id="global_discount" class="form-control form-control-sm" value="<?php echo $edit_data['discount_percentage'] ?? 0; ?>" min="0" max="100" style="width:100px; display:inline-block;"> <span>%</span></div></div>
                                        </div>
                                        <div class="calculation-row">
                                            <div class="row"><div class="col-6">Other Charges:</div><div class="col-6"><input type="number" step="0.01" name="other_charges" id="other_charges" class="form-control form-control-sm" value="<?php echo $edit_data['other_charges'] ?? 0; ?>" min="0" style="width:120px; display:inline-block;"></div></div>
                                        </div>
                                        <div class="calculation-row" id="advance_row" style="display: none;">
                                            <div class="row"><div class="col-6">Advance / Received:</div><div class="col-6"><input type="number" step="0.01" name="received_amount" id="received_amount" class="form-control form-control-sm" value="<?php echo $edit_data['received_amount'] ?? 0; ?>" min="0" style="width:120px; display:inline-block;"></div></div>
                                        </div>
                                        <div class="calculation-row" style="background: #e8f5e9;">
                                            <div class="row"><div class="col-6"><strong>Grand Total:</strong></div><div class="col-6 text-right"><strong id="grandTotal" style="font-size:18px; color:#1e7e34;">₨ 0.00</strong></div></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="calculation-panel">
                                        <div class="calculation-row">
                                            <div class="row">
                                                <div class="col-6">Payment Method:</div>
                                                <div class="col-6">
                                                    <select name="payment_type" id="payment_type" class="form-control form-control-sm">
                                                        <option value="credit" <?php echo ($edit_data['payment_type'] ?? 'credit') == 'credit' ? 'selected' : ''; ?>>Credit</option>
                                                        <option value="cash" <?php echo ($edit_data['payment_type'] ?? '') == 'cash' ? 'selected' : ''; ?>>Cash</option>
                                                        <option value="bank" <?php echo ($edit_data['payment_type'] ?? '') == 'bank' ? 'selected' : ''; ?>>Bank</option>
                                                        <option value="partial" <?php echo ($edit_data['payment_type'] ?? '') == 'partial' ? 'selected' : ''; ?>>Partial</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="calculation-row" id="bank_div" style="display: none;">
                                            <div class="row">
                                                <div class="col-6">Select Bank:</div>
                                                <div class="col-6">
                                                    <select name="bank_account_id" class="form-control form-control-sm">
                                                        <option value="">Select Bank</option>
                                                        <?php while($bank = mysqli_fetch_assoc($bank_result)): ?>
                                                            <option value="<?php echo $bank['id']; ?>" <?php echo ($edit_data['bank_account_id'] ?? '') == $bank['id'] ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($bank['bank_name'] . ' - ' . ($bank['account_number'] ?? $bank['account_title'])); ?>
                                                            </option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="calculation-row">
                                            <div class="row"><div class="col-6">Previous Balance:</div><div class="col-6 text-right"><strong id="prevBalance">₨ 0.00</strong></div></div>
                                        </div>
                                        <div class="calculation-row">
                                            <div class="row"><div class="col-6">Quotation Amount:</div><div class="col-6 text-right"><strong id="quotationAmount">₨ 0.00</strong></div></div>
                                        </div>
                                        <div class="calculation-row">
                                            <div class="row"><div class="col-6">Remaining Bill:</div><div class="col-6 text-right"><strong id="remainingBill" style="color:#dc3545;">₨ 0.00</strong></div></div>
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
                                    <button type="button" id="holdQuotationBtn" class="btn btn-hold"><i class="fas fa-pause-circle mr-1"></i> Hold Quotation</button>
                                    <button type="button" id="saveQuotationBtn" class="btn btn-green"><i class="fas fa-save mr-1"></i> <?php echo $is_edit ? 'Update Quotation' : 'Save Quotation'; ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="subtotal" id="subtotal_input" value="0">
                    <input type="hidden" name="discount_amount" id="discount_amount_input" value="0">
                    <input type="hidden" name="grand_total" id="grand_total_input" value="0">
                    <input type="hidden" name="remaining_amount" id="remaining_amount_input" value="0">
                    <input type="hidden" name="quotation_status" id="quotation_status" value="draft">
                </form>
                
            </div>
        </div>
        
        <footer class="sticky-footer bg-white">
            <div class="container my-auto"><div class="copyright text-center my-auto"><span>&copy; <?php echo date('Y'); ?> <?php echo $software_name; ?> - All Rights Reserved</span></div></div>
        </footer>
    </div>
</div>

<!-- Walk-In Customer Modal -->
<div class="modal fade" id="walkinModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title text-white"><i class="fas fa-walking"></i> Walk-In Customer</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Customer Name *</label>
                    <input type="text" id="walkin_customer_name" class="form-control" placeholder="Enter walk-in customer name">
                </div>
                <div class="form-group">
                    <label>Phone Number (Optional)</label>
                    <input type="text" id="walkin_customer_phone" class="form-control" placeholder="Enter phone number">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="saveWalkinBtn">Save Walk-In</button>
            </div>
        </div>
    </div>
</div>

<!-- New Customer Modal -->
<div class="modal fade" id="newCustomerModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="fas fa-user-plus"></i> New Customer Account</h5>
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
let productCount = 0;
var editItems = <?php echo json_encode($edit_items); ?>;
var isEdit = <?php echo $is_edit ? 'true' : 'false'; ?>;

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
    var paymentType = $('#payment_type').val();
    var receivedAmount = parseFloat($('#received_amount').val()) || 0;
    var remainingAmount = 0;
    
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
            $('#received_amount').val(receivedAmount.toFixed(2));
        }
        remainingAmount = Math.max(0, grandTotal - receivedAmount);
    }
    
    var newBalance = prevBalance + remainingAmount;
    
    $('#subtotal').text('₨ ' + subtotal.toFixed(2));
    $('#discountAmount').text('₨ ' + globalDiscountAmt.toFixed(2));
    $('#grandTotal').text('₨ ' + grandTotal.toFixed(2));
    $('#quotationAmount').text('₨ ' + grandTotal.toFixed(2));
    $('#remainingBill').text('₨ ' + remainingAmount.toFixed(2));
    $('#newBalance').text('₨ ' + newBalance.toFixed(2));
    $('#subtotal_input').val(subtotal.toFixed(2));
    $('#discount_amount_input').val(globalDiscountAmt.toFixed(2));
    $('#grand_total_input').val(grandTotal.toFixed(2));
    $('#remaining_amount_input').val(remainingAmount.toFixed(2));
    
    if(newBalance > 0) $('#newBalance').css('color', '#dc3545');
    else if(newBalance < 0) $('#newBalance').css('color', '#28a745');
    else $('#newBalance').css('color', '#1a1a1a');
}

function addProductRow(data) {
    var pid = data ? data.product_id : '';
    var cH = data && data.client_height > 0 ? data.client_height : '';
    var cW = data && data.client_width > 0 ? data.client_width : '';
    var sH = data && data.std_height > 0 ? data.std_height : '';
    var sW = data && data.std_width > 0 ? data.std_width : '';
    var qty = data ? parseFloat(data.quantity) || 1 : 1;
    var uPrice = data && data.unit_price > 0 ? data.unit_price : '';
    var ar = data ? parseFloat(data.area) || 0 : 0;
    var totAr = data ? (ar * qty) : 0;
    var amt = data ? parseFloat(data.amount) || 0 : 0;
    var dPct = data && data.discount_percentage > 0 ? data.discount_percentage : '';
    var netAmt = data ? parseFloat(data.net_amount) || 0 : 0;
    
    var optionsHtml = '<option value="">Search product...</option>';
    <?php 
    mysqli_data_seek($products_result, 0);
    while($prod = mysqli_fetch_assoc($products_result)): ?>
    optionsHtml += '<option value="<?php echo $prod['id']; ?>" data-price="<?php echo $prod['purchase_price']; ?>" ' + (pid == '<?php echo $prod['id']; ?>' ? 'selected' : '') + '><?php echo addslashes(htmlspecialchars($prod['product_name'])); ?></option>';
    <?php endwhile; ?>
    
    var row = productCount;
    var html = '<tr id="product_row_' + row + '" class="product-row">' +
        '<td><select name="product_id[]" class="form-control product-select" data-row="' + row + '" required>' + optionsHtml + '</select></td>' +
        '<td><input type="number" step="0.01" name="client_height[]" class="form-control client-height" data-row="' + row + '" placeholder="H" value="' + cH + '"></td>' +
        '<td><input type="number" step="0.01" name="client_width[]" class="form-control client-width" data-row="' + row + '" placeholder="W" value="' + cW + '"></td>' +
        '<td><input type="number" step="0.01" name="std_height[]" class="form-control std-height" data-row="' + row + '" placeholder="Std H" style="background:#fff3cd;" value="' + sH + '"></td>' +
        '<td><input type="number" step="0.01" name="std_width[]" class="form-control std-width" data-row="' + row + '" placeholder="Std W" style="background:#fff3cd;" value="' + sW + '"></td>' +
        '<td><input type="number" step="0.01" name="quantity[]" class="form-control quantity" data-row="' + row + '" value="' + qty + '" min="0"></td>' +
        '<td><input type="number" step="0.01" name="unit_price[]" class="form-control unit-price" data-row="' + row + '" placeholder="Price" value="' + uPrice + '" min="0"></td>' +
        '<td><input type="number" step="0.01" name="area[]" class="form-control area" data-row="' + row + '" value="' + ar.toFixed(2) + '" readonly style="background:#e9ecef;"></td>' +
        '<td><input type="number" step="0.01" name="total_area[]" class="form-control total-area" data-row="' + row + '" value="' + totAr.toFixed(2) + '" readonly style="background:#e9ecef;"></td>' +
        '<td><input type="number" step="0.01" name="amount[]" class="form-control row-amount" data-row="' + row + '" value="' + amt.toFixed(2) + '" readonly style="background:#e9ecef;"></td>' +
        '<td><input type="number" step="0.01" name="discount_percent[]" class="form-control discount-percent" data-row="' + row + '" placeholder="0" value="' + dPct + '" min="0" max="100"></td>' +
        '<td><input type="number" step="0.01" name="net_amount[]" class="form-control net-amount" data-row="' + row + '" value="' + netAmt.toFixed(2) + '" readonly style="background:#e9ecef;"></td>' +
        '<td class="text-center"><i class="fas fa-trash text-danger remove-product" data-row="' + row + '" style="cursor:pointer; font-size:18px;"></i></td>' +
        '</tr>';
    $('#productsBody').append(html);
    
    $(`.product-select[data-row="${row}"]`).select2({ theme: 'bootstrap4', placeholder: 'Search product...', width: '100%' });
    bindRowEvents(row);
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
    
    $(`.client-height[data-row="${row}"]`).on('input keyup change', function() {
        var r = $(this).data('row');
        var val = $(this).val();
        $(`.std-height[data-row="${r}"]`).val(val);
        updateArea(r);
        calculateRowAmount(r);
        calculateTotals();
    });
    
    $(`.client-width[data-row="${row}"]`).on('input keyup change', function() {
        var r = $(this).data('row');
        var val = $(this).val();
        $(`.std-width[data-row="${r}"]`).val(val);
        updateArea(r);
        calculateRowAmount(r);
        calculateTotals();
    });
    
    $(`.std-height[data-row="${row}"], .std-width[data-row="${row}"]`).on('input keyup change', function() {
        var r = $(this).data('row');
        updateArea(r);
        calculateRowAmount(r);
        calculateTotals();
    });
    
    $(`.quantity[data-row="${row}"], .unit-price[data-row="${row}"], .discount-percent[data-row="${row}"]`).on('input keyup change', function() {
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
    // Initialize customer select2
    $('#customer_id').select2({
        theme: 'bootstrap4',
        placeholder: '-- Select or Type to Search Customer --',
        width: '100%'
    });
    
    // Remember the original WALK-IN option label so we can restore it later
    const walkinOptEl = $('#customer_id option[data-walkin="1"]');
    window.walkinOriginalText = walkinOptEl.length ? walkinOptEl.text() : '';

    // Load edit items or add one empty row
    if(isEdit && editItems.length > 0) {
        for(var i = 0; i < editItems.length; i++) {
            addProductRow(editItems[i]);
        }
        // Trigger customer change to show balance
        $('#customer_id').trigger('change');
        // Trigger payment type to show/hide bank/advance
        $('#payment_type').trigger('change');
    } else {
        addProductRow();
    }
    
    // Show the walk-in customer name/phone in the customer field + mobile info
    // Renames the WALK-IN option label when a name is set, restores it otherwise
    function toggleWalkinFields() {
        const selected = $('#customer_id').find(':selected');
        const isWalkin = (selected.data('walkin') == '1');
        const walkinOpt = $('#customer_id option[data-walkin="1"]');
        const walkinName = $('#walk_in_customer_name').val() || '';
        if(isWalkin && walkinName) {
            const walkinPhone = $('#walk_in_customer_phone').val() || '';
            const label = walkinName + ' (Walk-In)' + (walkinPhone ? ' - ' + walkinPhone : '');
            if(walkinOpt.length && walkinOpt.text() !== label) {
                walkinOpt.text(label);
                $('#customer_id').trigger('change.select2');
            }
            $('#customer_mobile').text(walkinPhone || '-');
        } else if(walkinOpt.length && window.walkinOriginalText && walkinOpt.text() !== window.walkinOriginalText) {
            walkinOpt.text(window.walkinOriginalText);
            $('#customer_id').trigger('change.select2');
        }
        return isWalkin;
    }

    $('#customer_id').on('change', function() {
        var selected = $(this).find(':selected');
        var mobile = selected.data('mobile') || '-';
        var address = selected.data('address') || '-';
        var balance = selected.data('balance') || 0;
        $('#customer_mobile').text(mobile);
        $('#customer_address').text(address);
        $('#prevBalance').text('₨ ' + parseFloat(balance).toFixed(2));
        $('#prevBalance').data('value', balance);
        // Clear walk-in details if a non-walk-in customer is selected
        if(selected.data('walkin') != '1') {
            $('#walk_in_customer_name').val('');
            $('#walk_in_customer_phone').val('');
        }
        toggleWalkinFields();
        calculateTotals();
    });
    
    // Quick Walk-In Customer Button → opens walk-in modal
    $('#quickWalkInBtn').on('click', function() {
        $('#walkin_customer_name').val('');
        $('#walkin_customer_phone').val('');
        $('#walkinModal').modal('show');
    });

    // Save Walk-In Customer (name/phone from modal)
    $('#saveWalkinBtn').on('click', function() {
        const walkinName = $('#walkin_customer_name').val().trim();
        if(walkinName === '') {
            Swal.fire({ title: 'Error!', text: 'Please enter walk-in customer name!', icon: 'error' });
            return;
        }
        const walkinPhone = $('#walkin_customer_phone').val().trim();
        
        // Store walk-in details in hidden fields (sent with form)
        $('#walk_in_customer_name').val(walkinName);
        $('#walk_in_customer_phone').val(walkinPhone);
        
        // Select the WALK-IN customer in the dropdown
        let walkinId = $('#customer_id option[data-walkin="1"]').val();
        if(walkinId) {
            $('#customer_id').val(walkinId).trigger('change');
        } else {
            $('#customer_id option').each(function() {
                if($(this).text().toLowerCase().includes('walk-in')) {
                    $('#customer_id').val($(this).val()).trigger('change');
                    return false;
                }
            });
        }
        
        // Close the walk-in modal (field + mobile are updated by the change event)
        $('#walkinModal').modal('hide');
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
    
    $('#global_discount, #other_charges, #received_amount').on('keyup change', function() { calculateTotals(); });
    $('#addProductBtn').on('click', function() { addProductRow(); });
    $('#refreshBtn').on('click', function() { location.reload(); });
    
    // Payment method toggle
    $('#payment_type').on('change', function() {
        var pt = $(this).val();
        if(pt === 'bank') {
            $('#bank_div').show();
            $('select[name="bank_account_id"]').prop('required', true);
        } else {
            $('#bank_div').hide();
            $('select[name="bank_account_id"]').prop('required', false).val('');
        }
        
        if(pt === 'partial') {
            $('#advance_row').show();
            $('#received_amount').prop('readonly', false).focus();
        } else if(pt === 'cash' || pt === 'bank') {
            $('#advance_row').show();
            $('#received_amount').prop('readonly', true);
        } else { // credit
            $('#advance_row').hide();
            $('#received_amount').val(0);
        }
        calculateTotals();
    });
    
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
    
    // --- HOLD QUOTATION (hold) ---
    $('#holdQuotationBtn').on('click', function(e) {
        e.preventDefault();
        // Set status to hold
        $('#quotation_status').val('hold');
        submitQuotation('Hold Quotation');
    });
    
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