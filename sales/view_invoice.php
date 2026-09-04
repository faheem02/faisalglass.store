<?php
session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}
include('../includes/database.php');
include('../includes/txt.php');
$page_title = "View Sale Invoice";

// Handle Delete
if(isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $sale_query = "SELECT * FROM sale_master WHERE id = $delete_id";
    $sale_result = mysqli_query($conn, $sale_query);
    if($sale_result && mysqli_num_rows($sale_result) > 0) {
        $sale = mysqli_fetch_assoc($sale_result);
        mysqli_begin_transaction($conn);
        try {
            $details_query = "SELECT * FROM sale_details WHERE sale_id = $delete_id";
            $details_result = mysqli_query($conn, $details_query);
            while($detail = mysqli_fetch_assoc($details_result)) {
                $stock_query = "SELECT balance_qty FROM inventory_ledger WHERE product_id = {$detail['product_id']} ORDER BY id DESC LIMIT 1";
                $stock_result = mysqli_query($conn, $stock_query);
                $current_stock = 0;
                if($stock_result && mysqli_num_rows($stock_result) > 0) {
                    $stock_data = mysqli_fetch_assoc($stock_result);
                    $current_stock = floatval($stock_data['balance_qty']);
                }
                $new_stock = $current_stock + $detail['quantity'];
                $inventory_query = "INSERT INTO inventory_ledger (date, product_id, reference_type, reference_id, qty_in, qty_out, balance_qty, unit_price, total_amount, remarks) VALUES (CURDATE(), {$detail['product_id']}, 'ADJUSTMENT', $delete_id, {$detail['quantity']}, 0, $new_stock, {$detail['rate']}, 0, 'Sale Deleted: {$sale['invoice_no']}')";
                mysqli_query($conn, $inventory_query);
            }
            mysqli_query($conn, "DELETE FROM sale_details WHERE sale_id = $delete_id");
            mysqli_query($conn, "DELETE FROM sale_master WHERE id = $delete_id");
            mysqli_query($conn, "DELETE FROM customer_ledger WHERE reference_type = 'SALE' AND reference_id = $delete_id");
            mysqli_query($conn, "DELETE FROM cash_book WHERE reference_type = 'SALE' AND reference_id = $delete_id");
            mysqli_query($conn, "DELETE FROM bank_book WHERE reference_type = 'SALE' AND reference_id = $delete_id");
            mysqli_commit($conn);
            $success_msg = "Sale deleted successfully!";
        } catch (Exception $e) { mysqli_rollback($conn); $error_msg = $e->getMessage(); }
    }
}

$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
$filter_customer = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;

$customers_result = mysqli_query($conn, "SELECT id, customer_name FROM customers WHERE status = 1");
$bank_accounts_result = mysqli_query($conn, "SELECT id, bank_name, account_title, account_number FROM bank_accounts WHERE status = 1 ORDER BY bank_name");
$bank_accounts = [];
if($bank_accounts_result) {
    while($b = mysqli_fetch_assoc($bank_accounts_result)) { $bank_accounts[] = $b; }
}

$sales_query = "SELECT s.*, c.customer_name, c.customer_code FROM sale_master s LEFT JOIN customers c ON s.customer_id = c.id WHERE s.sale_date BETWEEN '$from_date' AND '$to_date'";
if($filter_customer > 0) $sales_query .= " AND s.customer_id = $filter_customer";
$sales_query .= " ORDER BY s.id DESC";
$sales_result = mysqli_query($conn, $sales_query);

$summary = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(grand_total) as total_sale, SUM(received_amount) as total_received, SUM(remaining_amount) as total_remaining, COUNT(*) as total_count FROM sale_master WHERE sale_date BETWEEN '$from_date' AND '$to_date'"));
$today_summary = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(grand_total) as today_total FROM sale_master WHERE sale_date = CURDATE()"));
$month_summary = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(grand_total) as month_total FROM sale_master WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())"));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | <?php echo $software_name; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .btn-green{background:#1e7e34;border-color:#1e7e34;color:white}.btn-green:hover{background:#155724}
        .btn-refund{background:#ffc107;border-color:#ffc107;color:#1a1a1a}.btn-refund:hover{background:#e0a800;color:#1a1a1a}
        .action-btns{display:flex;align-items:center;flex-wrap:nowrap;gap:4px}
        .action-btns .btn{display:inline-flex;align-items:center;white-space:nowrap}
        .card-header-custom{background:linear-gradient(135deg,#1e7e34,#0066cc);color:white;border-radius:10px 10px 0 0;padding:15px 20px}
        .summary-card{text-align:center;padding:20px;border-radius:10px;background:white;box-shadow:0 2px 8px rgba(0,0,0,0.08)}
        .summary-number{font-size:28px;font-weight:bold}
        .badge-paid{background:#28a745;color:white;padding:5px 12px;border-radius:20px}
        .badge-partial{background:#ffc107;color:#1a1a1a;padding:5px 12px;border-radius:20px}
        .badge-pending{background:#dc3545;color:white;padding:5px 12px;border-radius:20px}
        .badge-refund{background:#17a2b8;color:white;padding:5px 12px;border-radius:20px}
        .table thead th{background:#1e7e34;color:white}
        .view-info-card { background: #f8faf9; border: 1px solid #e5e7eb; border-left: 3px solid #1e7e34; border-radius: 4px; padding: 8px 12px; }
        .view-info-label { font-size: 10px; font-weight: 700; color: #6b7280; letter-spacing: 1px; text-transform: uppercase; }
        .view-info-value { font-weight: 600; color: #111827; word-break: break-word; }
        .view-total-label { font-size: 12px; font-weight: 600; color: #374151; }
        .view-total-value { font-weight: 700; color: #111827; text-align: right; }
        .view-grand-total { background: #1e7e34; color: #fff; border-radius: 6px; }
        .view-grand-total .view-total-label { color: #fff; }
        .view-grand-total .view-total-value { color: #fff; font-size: 16px; }
        .view-modal-table thead th { background-color: #1e7e34; color: white; font-weight: 600; font-size: 12px; text-align: center; border: none; }
        .view-modal-table td { vertical-align: middle; font-size: 13px; }
        .view-modal-table .size-subheader th { background: #0f6bb5; font-size: 10px; padding: 5px; font-weight: 500; }
        .view-modal-table .product-group-row td {
            background: #eaf5eb !important;
            border-top: 2px solid #1e7e34 !important;
            border-bottom: 1px solid #c3e6cb !important;
            padding: 8px 12px;
        }
        .view-modal-table .product-group-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
            font-weight: 700;
            color: #155724;
        }
        .view-modal-table .product-group-badge {
            background: #1e7e34;
            color: #fff;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            margin-right: 6px;
            display: inline-block;
        }
        .view-modal-table .product-subtotal-row td {
            background: #f8faf9 !important;
            border-top: 1px solid #d1e7dd !important;
            border-bottom: 2px solid #cbd5e1 !important;
            font-weight: 700;
            color: #1b4332;
            padding: 7px 8px;
            font-size: 12px;
        }
        .view-modal-table .table-footer td {
            background: #e8f5e9 !important;
            font-weight: 700;
            border-top: 2px solid #1e7e34;
            font-size: 13px;
        }
    </style>
</head>
<body id="page-top"><div id="wrapper"><?php include('../includes/sidebar.php'); ?><div class="container-fluid">
<div class="d-sm-flex align-items-center justify-content-between mb-4"><h1 class="h3 mb-0 text-gray-800"><i class="fas fa-file-invoice text-success mr-2"></i> View Sale Invoice</h1><div><a href="add_sale.php" class="btn btn-green"><i class="fas fa-plus-circle mr-1"></i> Add Sale</a></div></div>
<div class="row">
<div class="col-xl-3 col-md-6 mb-4"><div class="summary-card"><div class="text-primary text-uppercase mb-1">Today's Sale</div><div class="summary-number text-primary"><?php echo formatCurrency($today_summary['today_total'] ?? 0); ?></div><small><?php echo date('d-m-Y'); ?></small></div></div>
<div class="col-xl-3 col-md-6 mb-4"><div class="summary-card"><div class="text-success text-uppercase mb-1">Monthly Sale</div><div class="summary-number text-success"><?php echo formatCurrency($month_summary['month_total'] ?? 0); ?></div><small><?php echo date('F Y'); ?></small></div></div>
<div class="col-xl-3 col-md-6 mb-4"><div class="summary-card"><div class="text-info text-uppercase mb-1">Selected Period</div><div class="summary-number text-info"><?php echo formatCurrency($summary['total_sale'] ?? 0); ?></div><small><?php echo date('d-m-Y', strtotime($from_date)); ?> to <?php echo date('d-m-Y', strtotime($to_date)); ?></small></div></div>
<div class="col-xl-3 col-md-6 mb-4"><div class="summary-card"><div class="text-warning text-uppercase mb-1">Outstanding</div><div class="summary-number text-warning"><?php echo formatCurrency($summary['total_remaining'] ?? 0); ?></div><small><?php echo $summary['total_count'] ?? 0; ?> Invoices</small></div></div>
</div>
<div class="card form-card"><div class="card-header-custom"><i class="fas fa-filter mr-2"></i> Filter Sales</div><div class="card-body"><form method="GET" class="form-inline"><div class="row w-100"><div class="col-md-3"><input type="date" name="from_date" class="form-control w-100" value="<?php echo $from_date; ?>"></div><div class="col-md-3"><input type="date" name="to_date" class="form-control w-100" value="<?php echo $to_date; ?>"></div><div class="col-md-4"><select name="customer_id" class="form-control w-100"><option value="0">All Customers</option><?php while($c = mysqli_fetch_assoc($customers_result)): ?><option value="<?php echo $c['id']; ?>" <?php echo ($filter_customer == $c['id']) ? 'selected' : ''; ?>><?php echo $c['customer_name']; ?></option><?php endwhile; ?></select></div><div class="col-md-2"><button type="submit" class="btn btn-green w-100"><i class="fas fa-search"></i> Filter</button></div></div></form></div></div>
<div class="card form-card"><div class="card-header-custom"><i class="fas fa-list mr-2"></i> Sale Invoices <span class="float-right">Total: <strong><?php echo formatCurrency($summary['total_sale'] ?? 0); ?></strong></span></div><div class="card-body"><div class="table-responsive"><table class="table table-bordered" id="salesTable"><thead><tr><th>Invoice No</th><th>Date</th><th>Customer</th><th>Grand Total</th><th>Received</th><th>Remaining</th><th>Reference No</th><th>Remarks</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php while($sale = mysqli_fetch_assoc($sales_result)): ?>
<tr>
    <td class="font-weight-bold text-primary"><?php echo $sale['invoice_no']; ?></td>
    <td><?php echo date('d-m-Y', strtotime($sale['sale_date'])); ?></td>
    <td><strong><?php echo $sale['customer_name']; ?></strong><br><small><?php echo $sale['customer_code']; ?></small></td>
    <td class="text-right"><?php echo formatCurrency($sale['grand_total']); ?></td>
    <td class="text-right text-success"><?php echo formatCurrency($sale['received_amount']); ?></td>
    <td class="text-right text-danger"><?php echo formatCurrency($sale['remaining_amount']); ?></td>
    <td><?php echo $sale['reference_no'] ? htmlspecialchars($sale['reference_no']) : '-'; ?></td>
    <td><?php echo $sale['remarks'] ? htmlspecialchars($sale['remarks']) : '-'; ?></td>
    <td class="text-center"><?php
        $pt = strtolower($sale['payment_type'] ?? 'cash');
        if(($sale['refund_status'] ?? '') == 'full') {
            echo '<span class="badge badge-secondary" style="padding:5px 12px;border-radius:20px;font-size:12px;"><i class="fas fa-undo mr-1"></i> Refund</span>';
        } elseif(($sale['refund_status'] ?? '') == 'partial') {
            echo '<span class="badge badge-secondary" style="padding:5px 12px;border-radius:20px;font-size:12px;"><i class="fas fa-undo mr-1"></i> Refund (Part)</span>';
        } elseif(($sale['status'] ?? 1) == 0) {
            echo '<span class="badge badge-dark" style="padding:5px 12px;border-radius:20px;font-size:12px;"><i class="fas fa-ban mr-1"></i> Cancelled</span>';
        } elseif($pt == 'cash') {
            echo '<span class="badge badge-success" style="padding:5px 12px;border-radius:20px;font-size:12px;"><i class="fas fa-money-bill-wave mr-1"></i> Cash</span>';
        } elseif($pt == 'bank') {
            echo '<span class="badge badge-info" style="padding:5px 12px;border-radius:20px;font-size:12px;"><i class="fas fa-university mr-1"></i> Bank</span>';
        } elseif($pt == 'credit') {
            echo '<span class="badge badge-danger" style="padding:5px 12px;border-radius:20px;font-size:12px;"><i class="fas fa-clock mr-1"></i> Credit</span>';
        } elseif($pt == 'partial') {
            echo '<span class="badge badge-warning text-dark" style="padding:5px 12px;border-radius:20px;font-size:12px;"><i class="fas fa-adjust mr-1"></i> Partial</span>';
        } else {
            echo '<span class="badge badge-primary" style="padding:5px 12px;border-radius:20px;font-size:12px;">' . ucfirst($pt) . '</span>';
        }
    ?></td>
    <td>
        <div class="action-btns">
            <?php 
            $is_walkin = (($sale['customer_code'] ?? '') == 'WALK-IN' || stripos($sale['customer_name'] ?? '', 'Walk-in') !== false || stripos($sale['customer_name'] ?? '', 'Walk in') !== false);
            if(floatval($sale['remaining_amount']) > 0 && $is_walkin): ?>
                <button class="btn btn-sm btn-success receive-payment-btn" title="Receive Walk-in Payment (Due: <?php echo formatCurrency($sale['remaining_amount']); ?>)" 
                        data-id="<?php echo $sale['id']; ?>"
                        data-invoice="<?php echo htmlspecialchars($sale['invoice_no']); ?>"
                        data-customer="<?php echo htmlspecialchars($sale['customer_name'] . ($sale['customer_code'] ? ' (' . $sale['customer_code'] . ')' : '')); ?>"
                        data-total="<?php echo $sale['grand_total']; ?>"
                        data-received="<?php echo $sale['received_amount']; ?>"
                        data-remaining="<?php echo $sale['remaining_amount']; ?>">
                    <i class="fas fa-hand-holding-usd"></i>
                </button>
            <?php endif; ?>
            <button class="btn btn-sm btn-info" title="View Sale" onclick="openViewModal(<?php echo $sale['id']; ?>)"><i class="fas fa-eye"></i></button>
            <a href="add_sale.php?edit_id=<?php echo $sale['id']; ?>" class="btn btn-sm btn-warning" title="Edit"><i class="fas fa-edit"></i></a>
            <button class="btn btn-sm btn-primary" title="Print" onclick="window.open('print_invoice.php?invoice_no=<?php echo urlencode($sale['invoice_no']); ?>', '_blank')"><i class="fas fa-print"></i></button>
            <button class="btn btn-sm btn-danger" title="Delete" onclick="confirmDelete(<?php echo $sale['id']; ?>)"><i class="fas fa-trash"></i></button>
        </div>
    </td>
</tr>
<?php endwhile; ?>
</tbody></table></div></div></div></div><footer class="sticky-footer bg-white"><div class="container my-auto"><div class="copyright text-center my-auto"><span>&copy; <?php echo date('Y'); ?> <?php echo $software_name; ?> - All Rights Reserved</span></div></div></footer></div>

<!-- Receive Payment Modal -->
<div class="modal fade" id="receivePaymentModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #1e7e34, #28a745); color: white;">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-hand-holding-usd mr-2"></i> Receive Payment</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="receivePaymentForm">
                <input type="hidden" name="sale_id" id="rec_sale_id">
                <div class="modal-body p-4">
                    <!-- Invoice Summary Card -->
                    <div class="card bg-light border-0 mb-3" style="border-left: 4px solid #1e7e34 !important;">
                        <div class="card-body py-2 px-3">
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted d-block">Invoice No</small>
                                    <strong id="rec_invoice_no" class="text-primary font-weight-bold">-</strong>
                                </div>
                                <div class="col-6 text-right">
                                    <small class="text-muted d-block">Customer</small>
                                    <strong id="rec_customer_name" class="text-dark">-</strong>
                                </div>
                            </div>
                            <hr class="my-2">
                            <div class="row text-center">
                                <div class="col-4">
                                    <small class="text-muted d-block">Total</small>
                                    <span id="rec_grand_total" class="font-weight-bold">₨ 0.00</span>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">Received</small>
                                    <span id="rec_received_amount" class="text-success font-weight-bold">₨ 0.00</span>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">Remaining</small>
                                    <span id="rec_remaining_amount" class="text-danger font-weight-bold" style="font-size: 15px;">₨ 0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold"><i class="fas fa-calendar-alt text-success mr-1"></i> Payment Date</label>
                        <input type="date" name="payment_date" id="rec_payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold"><i class="fas fa-money-bill-wave text-success mr-1"></i> Payment Method</label>
                        <select name="payment_method" id="rec_payment_method" class="form-control">
                            <option value="cash">Cash in Hand</option>
                            <option value="bank">Bank Transfer</option>
                        </select>
                    </div>

                    <div class="form-group" id="rec_bank_group" style="display: none;">
                        <label class="font-weight-bold"><i class="fas fa-university text-info mr-1"></i> Select Bank Account</label>
                        <select name="bank_account_id" id="rec_bank_account_id" class="form-control">
                            <option value="">-- Select Bank Account --</option>
                            <?php foreach($bank_accounts as $ba): ?>
                                <option value="<?php echo $ba['id']; ?>">
                                    <?php echo htmlspecialchars($ba['bank_name'] . ' (' . ($ba['account_number'] ?? $ba['account_title']) . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold"><i class="fas fa-coins text-success mr-1"></i> Amount to Receive (₨) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="rec_amount" class="form-control form-control-lg font-weight-bold text-success" placeholder="Enter amount" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-receipt text-muted mr-1"></i> Reference / Trx No (Optional)</label>
                        <input type="text" name="reference_no" id="rec_reference_no" class="form-control" placeholder="Cheque / Trx ID / Slip No">
                    </div>

                    <div class="form-group mb-0">
                        <label><i class="fas fa-comment text-muted mr-1"></i> Remarks</label>
                        <input type="text" name="remarks" id="rec_remarks" class="form-control" placeholder="Payment received note">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success font-weight-bold" id="rec_submit_btn">
                        <i class="fas fa-check-circle mr-1"></i> Confirm Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Sale Modal -->
<div class="modal fade" id="viewSaleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #1e7e34, #0066cc); color: white;">
                <h5 class="modal-title"><i class="fas fa-file-invoice"></i> Sale Invoice Details</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="viewSaleBody">
                <div class="text-center p-5">
                    <div class="spinner-border text-success" role="status"></div>
                    <p class="mt-2">Loading sale details...</p>
                </div>
            </div>
            <div class="modal-footer" id="viewSaleFooter">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/js/sb-admin-2.min.js"></script>

<script>
$(document).ready(function(){
    $('#salesTable').DataTable({order:[[0,"desc"]],pageLength:25});
    <?php if(isset($_GET['id']) && intval($_GET['id']) > 0): ?>
    openViewModal(<?php echo intval($_GET['id']); ?>);
    <?php endif; ?>

    // Receive Payment button click
    $(document).on('click', '.receive-payment-btn', function(){
        const saleId = $(this).data('id');
        const invoiceNo = $(this).data('invoice');
        const customerName = $(this).data('customer');
        const total = parseFloat($(this).data('total')) || 0;
        const received = parseFloat($(this).data('received')) || 0;
        const remaining = parseFloat($(this).data('remaining')) || 0;

        $('#rec_sale_id').val(saleId);
        $('#rec_invoice_no').text(invoiceNo);
        $('#rec_customer_name').text(customerName);
        $('#rec_grand_total').text('₨ ' + total.toFixed(2));
        $('#rec_received_amount').text('₨ ' + received.toFixed(2));
        $('#rec_remaining_amount').text('₨ ' + remaining.toFixed(2));
        
        $('#rec_amount').val(remaining.toFixed(2)).attr('max', remaining.toFixed(2));
        $('#rec_payment_date').val(new Date().toISOString().split('T')[0]);
        $('#rec_payment_method').val('cash').trigger('change');
        $('#rec_reference_no').val('');
        $('#rec_remarks').val('');

        $('#receivePaymentModal').modal('show');
    });

    // Payment method toggle bank dropdown
    $('#rec_payment_method').on('change', function(){
        if($(this).val() === 'bank'){
            $('#rec_bank_group').slideDown(200);
            $('#rec_bank_account_id').prop('required', true);
        } else {
            $('#rec_bank_group').slideUp(200);
            $('#rec_bank_account_id').prop('required', false);
        }
    });

    // Submit Payment receipt form
    $('#receivePaymentForm').on('submit', function(e){
        e.preventDefault();
        const btn = $('#rec_submit_btn');
        const originalHtml = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Saving...');

        $.ajax({
            url: 'save_invoice_payment.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res){
                btn.prop('disabled', false).html(originalHtml);
                if(res.success){
                    $('#receivePaymentModal').modal('hide');
                    Swal.fire({
                        title: 'Success!',
                        text: res.message,
                        icon: 'success',
                        confirmButtonColor: '#1e7e34'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: res.message,
                        icon: 'error'
                    });
                }
            },
            error: function(){
                btn.prop('disabled', false).html(originalHtml);
                Swal.fire({
                    title: 'Network Error!',
                    text: 'Unable to process payment receipt. Please check your connection.',
                    icon: 'error'
                });
            }
        });
    });
});

function confirmDelete(id){
    Swal.fire({
        title:'Are you sure?',
        text:"This sale invoice will be permanently deleted!",
        icon:'warning',
        showCancelButton:true,
        confirmButtonColor:'#dc3545',
        confirmButtonText:'Yes, delete it!'
    }).then((result)=>{
        if(result.isConfirmed) window.location.href='view_invoice.php?delete_id='+id;
    });
}

function escapeHtml(str){
    if(!str) return '';
    return $('<div>').text(str).html();
}

function openViewModal(id){
    $('#viewSaleModal').modal('show');
    $('#viewSaleBody').html('<div class="text-center p-5"><div class="spinner-border text-success" role="status"></div><p class="mt-2">Loading sale details...</p></div>');
    
    $.ajax({
        url: 'get_sale_details.php',
        type: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(response){
            if(!response.success){
                $('#viewSaleBody').html('<div class="alert alert-danger m-3"><i class="fas fa-exclamation-circle"></i> ' + (response.message || 'Failed to load sale') + '</div>');
                return;
            }
            
            var s = response.sale;
            var c = response.customer;
            
            var html = '';
            html += '<div class="d-flex justify-content-between align-items-center mb-3">';
            html += '<h5 class="mb-0"><strong>' + escapeHtml(s.invoice_no) + '</strong></h5>';
            html += '<span class="badge badge-info" style="padding:6px 14px;border-radius:20px;font-size:12px;">' + escapeHtml(s.status) + '</span>';
            html += '</div>';
            
            html += '<div class="row mb-3">';
            html += '<div class="col-md-3 mb-2"><div class="view-info-card"><div class="view-info-label">Customer</div><div class="view-info-value">' + escapeHtml(c.customer_name) + '</div><small class="text-muted">' + escapeHtml(c.customer_code) + '</small></div></div>';
            html += '<div class="col-md-3 mb-2"><div class="view-info-card"><div class="view-info-label">Mobile</div><div class="view-info-value">' + escapeHtml(c.mobile || '-') + '</div></div></div>';
            html += '<div class="col-md-3 mb-2"><div class="view-info-card"><div class="view-info-label">Sale Date</div><div class="view-info-value">' + escapeHtml(s.sale_date) + '</div></div></div>';
            html += '<div class="col-md-3 mb-2"><div class="view-info-card"><div class="view-info-label">Payment Method</div><div class="view-info-value">' + escapeHtml(s.payment_type) + '</div></div></div>';
            html += '<div class="col-md-6 mb-2"><div class="view-info-card"><div class="view-info-label">Address</div><div class="view-info-value">' + escapeHtml(c.address || '-') + '</div></div></div>';
            html += '<div class="col-md-3 mb-2"><div class="view-info-card"><div class="view-info-label">Reference No</div><div class="view-info-value">' + escapeHtml(s.reference_no || '-') + '</div></div></div>';
            html += '<div class="col-md-3 mb-2"><div class="view-info-card"><div class="view-info-label">Status</div><div class="view-info-value">' + (s.remaining_amount <= 0 ? 'Paid' : (s.received_amount > 0 ? 'Partial' : 'Pending')) + '</div></div></div>';
            html += '</div>';
            
            if(response.items.length > 0){
                // Group items by product
                var productGroups = {};
                var groupOrder = [];
                for(var i = 0; i < response.items.length; i++){
                    var item = response.items[i];
                    var pid = item.product_id ? item.product_id : (item.product_name || 'general');
                    if(!productGroups[pid]){
                        productGroups[pid] = {
                            product_name: item.product_name || 'General Product',
                            product_code: item.product_code || '',
                            items: [],
                            subtotal_qty: 0,
                            subtotal_area: 0,
                            subtotal_amount: 0
                        };
                        groupOrder.push(pid);
                    }
                    productGroups[pid].items.push(item);
                    productGroups[pid].subtotal_qty += parseFloat(item.quantity) || 0;
                    productGroups[pid].subtotal_area += parseFloat(item.area) || 0;
                    productGroups[pid].subtotal_amount += parseFloat(item.amount) || 0;
                }

                html += '<div class="table-responsive"><table class="table table-bordered view-modal-table">';
                html += '<thead><tr>';
                html += '<th rowspan="2" width="6%">SR #</th><th colspan="2">ACTUAL SIZE (INCH)</th>';
                html += '<th rowspan="2" width="8%">QTY</th><th rowspan="2" width="14%">Total Area (sq ft)</th>';
                html += '<th rowspan="2" width="12%">PRICE (₨)</th>';
                html += '<th rowspan="2" width="16%">TOTAL PRICE (₨)</th>';
                html += '</tr><tr class="size-subheader"><th width="11%">HEIGHT</th><th width="11%">WIDTH</th></tr></thead><tbody>';
                
                var sr = 1;
                var totalQty = 0;
                var totalArea = 0;
                var totalPrice = 0;

                for(var g = 0; g < groupOrder.length; g++){
                    var grp = productGroups[groupOrder[g]];
                    totalQty += grp.subtotal_qty;
                    totalArea += grp.subtotal_area;
                    totalPrice += grp.subtotal_amount;

                    // Product Header Row
                    html += '<tr class="product-group-row"><td colspan="7">';
                    html += '<div class="product-group-title">';
                    html += '<span><span class="product-group-badge">Product</span><strong>' + escapeHtml(grp.product_name) + '</strong>' + (grp.product_code ? ' <small class="text-muted">(' + escapeHtml(grp.product_code) + ')</small>' : '') + '</span>';
                    html += '<span style="font-size: 11px; font-weight: normal; color: #155724;">' + grp.items.length + (grp.items.length === 1 ? ' size' : ' sizes') + '</span>';
                    html += '</div></td></tr>';

                    // Size entries
                    for(var i = 0; i < grp.items.length; i++){
                        var itm = grp.items[i];
                        var lineArea = parseFloat(itm.area) || 0;
                        var amount = parseFloat(itm.amount) || 0;
                        var clientH = parseFloat(itm.client_height) || 0;
                        var clientW = parseFloat(itm.client_width) || 0;
                        var qty = parseFloat(itm.quantity) || 0;
                        var rate = parseFloat(itm.rate) || 0;

                        html += '<tr>';
                        html += '<td class="text-center">' + (sr++) + '</td>';
                        html += '<td class="text-center">' + (clientH > 0 ? clientH.toFixed(1) : '-') + '</td>';
                        html += '<td class="text-center">' + (clientW > 0 ? clientW.toFixed(1) : '-') + '</td>';
                        html += '<td class="text-center">' + qty + '</td>';
                        html += '<td class="text-right">' + lineArea.toFixed(2) + '</td>';
                        html += '<td class="text-right">' + formatCurrency(rate) + '</td>';
                        html += '<td class="text-right"><strong>' + formatCurrency(amount) + '</strong></td>';
                        html += '</tr>';
                    }

                    // Product Subtotal Row
                    html += '<tr class="product-subtotal-row">';
                    html += '<td colspan="3" class="text-right"><strong>Total (' + escapeHtml(grp.product_name) + '):</strong></td>';
                    html += '<td class="text-center"><strong>' + grp.subtotal_qty + '</strong></td>';
                    html += '<td class="text-right"><strong>' + grp.subtotal_area.toFixed(2) + ' sq ft</strong></td>';
                    html += '<td></td>';
                    html += '<td class="text-right"><strong>' + formatCurrency(grp.subtotal_amount) + '</strong></td>';
                    html += '</tr>';
                }

                html += '</tbody>';
                html += '<tfoot><tr class="table-footer">';
                html += '<td colspan="3" class="text-right"><strong>Grand Totals:</strong></td>';
                html += '<td class="text-center"><strong>' + totalQty + '</strong></td>';
                html += '<td class="text-right"><strong>' + totalArea.toFixed(2) + ' sq ft</strong></td>';
                html += '<td></td>';
                html += '<td class="text-right"><strong>' + formatCurrency(totalPrice) + '</strong></td>';
                html += '</tr></tfoot></table></div>';
                
                html += '<div class="row justify-content-end">';
                html += '<div class="col-md-5">';
                html += '<div class="view-info-card mb-2 d-flex justify-content-between"><span class="view-total-label">Subtotal</span><span class="view-total-value">' + formatCurrency(s.subtotal) + '</span></div>';
                if(s.discount_amount > 0){
                    html += '<div class="view-info-card mb-2 d-flex justify-content-between"><span class="view-total-label">Discount (' + s.discount_percentage + '%)</span><span class="view-total-value text-danger">- ' + formatCurrency(s.discount_amount) + '</span></div>';
                }
                if(s.other_charges > 0){
                    html += '<div class="view-info-card mb-2 d-flex justify-content-between"><span class="view-total-label">Other Charges</span><span class="view-total-value">+ ' + formatCurrency(s.other_charges) + '</span></div>';
                }
                html += '<div class="view-info-card view-grand-total mb-2 d-flex justify-content-between p-3"><span class="view-total-label">Grand Total</span><span class="view-total-value">' + formatCurrency(s.grand_total) + '</span></div>';
                html += '<div class="view-info-card mb-2 d-flex justify-content-between"><span class="view-total-label">Paid Amount</span><span class="view-total-value text-success">' + formatCurrency(s.received_amount) + '</span></div>';
                html += '<div class="view-info-card mb-2 d-flex justify-content-between"><span class="view-total-label">Remaining</span><span class="view-total-value text-danger">' + formatCurrency(s.remaining_amount) + '</span></div>';
                html += '</div></div>';
            } else {
                html += '<div class="alert alert-info">No products found for this sale.</div>';
            }
            
            if(s.remarks){
                html += '<div class="alert alert-warning mb-0"><strong>Remarks:</strong> ' + escapeHtml(s.remarks) + '</div>';
            }
            
            $('#viewSaleBody').html(html);
            $('#viewSaleFooter').html(
                '<a href="add_sale.php?edit_id=' + s.id + '" class="btn btn-warning"><i class="fas fa-edit"></i> Edit</a>' +
                '<a href="print_invoice.php?invoice_no=' + encodeURIComponent(s.invoice_no) + '" class="btn btn-primary" target="_blank"><i class="fas fa-print"></i> Print</a>' +
                '<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>'
            );
        },
        error: function(){
            $('#viewSaleBody').html('<div class="alert alert-danger m-3"><i class="fas fa-exclamation-circle"></i> Failed to load sale details</div>');
        }
    });
}

function formatCurrency(amount){
    return 'Rs ' + parseFloat(amount).toFixed(2);
}
</script>
</body>
</html>
<?php mysqli_close($conn); ?>