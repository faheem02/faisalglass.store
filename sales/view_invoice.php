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
        .card-header-custom{background:linear-gradient(135deg,#1e7e34,#0066cc);color:white;border-radius:10px 10px 0 0;padding:15px 20px}
        .summary-card{text-align:center;padding:20px;border-radius:10px;background:white;box-shadow:0 2px 8px rgba(0,0,0,0.08)}
        .summary-number{font-size:28px;font-weight:bold}
        .badge-paid{background:#28a745;color:white;padding:5px 12px;border-radius:20px}
        .badge-partial{background:#ffc107;color:#1a1a1a;padding:5px 12px;border-radius:20px}
        .badge-pending{background:#dc3545;color:white;padding:5px 12px;border-radius:20px}
        .badge-refund{background:#17a2b8;color:white;padding:5px 12px;border-radius:20px}
        .table thead th{background:#1e7e34;color:white}
        .refund-item { border-bottom: 1px solid #eee; padding: 10px; }
        .refund-item:last-child { border-bottom: none; }
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
<div class="card form-card"><div class="card-header-custom"><i class="fas fa-list mr-2"></i> Sale Invoices <span class="float-right">Total: <strong><?php echo formatCurrency($summary['total_sale'] ?? 0); ?></strong></span></div><div class="card-body"><div class="table-responsive"><table class="table table-bordered" id="salesTable"><thead><tr><th>Invoice No</th><th>Date</th><th>Customer</th><th>Grand Total</th><th>Received</th><th>Remaining</th><th>Payment Type</th><th>Reference No</th><th>Remarks</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php while($sale = mysqli_fetch_assoc($sales_result)): 
    $status = $sale['remaining_amount'] <= 0 ? 'Paid' : ($sale['received_amount'] > 0 ? 'Partial' : 'Pending');
    $status_class = $sale['remaining_amount'] <= 0 ? 'badge-paid' : ($sale['received_amount'] > 0 ? 'badge-partial' : 'badge-pending');
    if($sale['refund_status'] != 'none') {
        $status = 'Refunded (' . ucfirst($sale['refund_status']) . ')';
        $status_class = 'badge-refund';
    }
?>
<tr>
    <td class="font-weight-bold text-primary"><?php echo $sale['invoice_no']; ?></td>
    <td><?php echo date('d-m-Y', strtotime($sale['sale_date'])); ?></td>
    <td><strong><?php echo $sale['customer_name']; ?></strong><br><small><?php echo $sale['customer_code']; ?></small></td>
    <td class="text-right"><?php echo formatCurrency($sale['grand_total']); ?></td>
    <td class="text-right text-success"><?php echo formatCurrency($sale['received_amount']); ?></td>
    <td class="text-right text-danger"><?php echo formatCurrency($sale['remaining_amount']); ?></td>
    <td><?php echo ucfirst($sale['payment_type']); ?></td>
    <td><?php echo $sale['reference_no'] ? htmlspecialchars($sale['reference_no']) : '-'; ?></td>
    <td><?php echo $sale['remarks'] ? htmlspecialchars($sale['remarks']) : '-'; ?></td>
    <td class="text-center"><span class="<?php echo $status_class; ?>"><?php echo $status; ?></span></td>
    <td>
        <button class="btn btn-sm btn-info" onclick="window.open('print_invoice.php?invoice_no=<?php echo $sale['invoice_no']; ?>', '_blank')"><i class="fas fa-print"></i></button>
        <?php if($sale['refund_status'] == 'none'): ?>
        <a href="add_sale.php?edit_id=<?php echo $sale['id']; ?>" class="btn btn-sm btn-warning ml-1"><i class="fas fa-edit"></i></a>
        <?php endif; ?>
        <?php if($sale['refund_status'] == 'none' && $sale['grand_total'] > 0): ?>
        <button class="btn btn-sm btn-refund ml-1" onclick="openRefundModal(<?php echo $sale['id']; ?>, '<?php echo $sale['invoice_no']; ?>')"><i class="fas fa-undo-alt"></i> Refund</button>
        <?php endif; ?>
        <button class="btn btn-sm btn-danger ml-1" onclick="confirmDelete(<?php echo $sale['id']; ?>)"><i class="fas fa-trash"></i></button>
    </td>
</tr>
<?php endwhile; ?>
</tbody></table></div></div></div></div><footer class="sticky-footer bg-white"><div class="container my-auto"><div class="copyright text-center my-auto"><span>&copy; <?php echo date('Y'); ?> <?php echo $software_name; ?> - All Rights Reserved</span></div></div></footer></div>

<!-- Refund Modal -->
<div class="modal fade" id="refundModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #ffc107, #e0a800); color: #1a1a1a;">
                <h5 class="modal-title"><i class="fas fa-undo-alt"></i> Process Refund</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="refundModalBody">
                <div class="text-center p-5">
                    <div class="spinner-border text-warning" role="status"></div>
                    <p class="mt-2">Loading sale details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-refund" id="processRefundBtn">Process Refund</button>
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

function openRefundModal(saleId, invoiceNo){
    $('#refundModal').modal('show');
    $('#refundModalBody').html('<div class="text-center p-5"><div class="spinner-border text-warning" role="status"></div><p class="mt-2">Loading sale details...</p></div>');
    
    $.ajax({
        url: 'get_sale_details_for_refund.php',
        type: 'GET',
        data: { sale_id: saleId },
        dataType: 'json',
        success: function(response){
            if(response.success){
                var html = `
                    <input type="hidden" id="refund_sale_id" value="${response.sale_id}">
                    <div class="alert alert-info">
                        <strong>Invoice: ${response.invoice_no}</strong><br>
                        Customer: ${response.customer_name}<br>
                        Sale Date: ${response.sale_date}
                    </div>
                    <div class="form-group">
                        <label>Refund Date</label>
                        <input type="date" id="refund_date" class="form-control" value="${new Date().toISOString().slice(0,10)}">
                    </div>
                    <div class="form-group">
                        <label>Refund Reason</label>
                        <textarea id="refund_reason" class="form-control" rows="2" placeholder="Enter reason for refund"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Select Items to Refund</label>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th width="5%"><input type="checkbox" id="selectAll"></th>
                                        <th>Product</th>
                                        <th>Size</th>
                                        <th>Original Qty</th>
                                        <th width="15%">Refund Qty</th>
                                        <th>Unit Price</th>
                                        <th width="15%">Refund Amount</th>
                                    </tr>
                                </thead>
                                <tbody>`;
                
                for(var i=0; i<response.items.length; i++){
                    var item = response.items[i];
                    html += `
                        <tr>
                            <td class="text-center"><input type="checkbox" class="refund-item-checkbox" data-id="${item.id}" data-max-qty="${item.quantity}" data-unit-price="${item.rate}" data-original-amount="${item.amount}"></td>
                            <td>${item.product_name}<br><small>${item.product_code}</small></td>
                            <td>${item.client_size || '-'}</td>
                            <td class="text-center">${item.quantity}</td>
                            <td><input type="number" step="0.01" class="form-control refund-qty" data-id="${item.id}" disabled min="0" max="${item.quantity}" value="0"></td>
                            <td class="text-right">${formatCurrency(item.rate)}</td>
                            <td><input type="number" step="0.01" class="form-control refund-amount" data-id="${item.id}" disabled value="0"></td>
                        </tr>`;
                }
                
                html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="alert alert-warning" id="refundTotalAlert">
                        Total Refund Amount: <strong>₨ 0.00</strong>
                    </div>
                `;
                $('#refundModalBody').html(html);
                
                // Bind events
                $('#selectAll').on('change', function(){
                    $('.refund-item-checkbox').prop('checked', $(this).is(':checked')).trigger('change');
                });
                
                $('.refund-item-checkbox').on('change', function(){
                    var row = $(this).closest('tr');
                    var qtyInput = row.find('.refund-qty');
                    var amountInput = row.find('.refund-amount');
                    if($(this).is(':checked')){
                        qtyInput.prop('disabled', false);
                        amountInput.prop('disabled', false);
                        qtyInput.val(1);
                        var unitPrice = parseFloat($(this).data('unit-price'));
                        var amount = unitPrice * 1;
                        amountInput.val(amount.toFixed(2));
                    } else {
                        qtyInput.prop('disabled', true).val(0);
                        amountInput.prop('disabled', true).val(0);
                    }
                    calculateRefundTotal();
                });
                
                $('.refund-qty').on('keyup change', function(){
                    var row = $(this).closest('tr');
                    var checkbox = row.find('.refund-item-checkbox');
                    var maxQty = parseFloat(checkbox.data('max-qty'));
                    var qty = parseFloat($(this).val()) || 0;
                    if(qty > maxQty){
                        qty = maxQty;
                        $(this).val(qty);
                    }
                    var unitPrice = parseFloat(checkbox.data('unit-price'));
                    var amount = qty * unitPrice;
                    row.find('.refund-amount').val(amount.toFixed(2));
                    calculateRefundTotal();
                });
                
                $('.refund-amount').on('keyup change', function(){
                    var row = $(this).closest('tr');
                    var checkbox = row.find('.refund-item-checkbox');
                    var amount = parseFloat($(this).val()) || 0;
                    var unitPrice = parseFloat(checkbox.data('unit-price'));
                    var qty = amount / unitPrice;
                    row.find('.refund-qty').val(qty.toFixed(2));
                    calculateRefundTotal();
                });
                
            } else {
                $('#refundModalBody').html('<div class="alert alert-danger">' + response.message + '</div>');
            }
        },
        error: function(){
            $('#refundModalBody').html('<div class="alert alert-danger">Failed to load sale details!</div>');
        }
    });
}

function calculateRefundTotal(){
    var total = 0;
    $('.refund-amount').each(function(){
        var val = parseFloat($(this).val()) || 0;
        total += val;
    });
    $('#refundTotalAlert').html('Total Refund Amount: <strong>' + formatCurrency(total) + '</strong>');
}

function formatCurrency(amount){
    return '₨ ' + parseFloat(amount).toFixed(2);
}

$('#processRefundBtn').on('click', function(){
    var saleId = $('#refund_sale_id').val();
    var refundDate = $('#refund_date').val();
    var refundReason = $('#refund_reason').val();
    var refundItems = [];
    var refundQuantities = [];
    var refundAmounts = [];
    
    $('.refund-item-checkbox:checked').each(function(){
        var row = $(this).closest('tr');
        var qty = row.find('.refund-qty').val();
        var amount = row.find('.refund-amount').val();
        if(parseFloat(qty) > 0){
            refundItems.push($(this).data('id'));
            refundQuantities.push(qty);
            refundAmounts.push(amount);
        }
    });
    
    if(refundItems.length === 0){
        Swal.fire({ title: 'Error!', text: 'Please select at least one item to refund!', icon: 'error' });
        return;
    }
    
    if(!refundDate){
        Swal.fire({ title: 'Error!', text: 'Please select refund date!', icon: 'error' });
        return;
    }
    
    Swal.fire({
        title: 'Confirm Refund',
        text: 'Are you sure you want to process this refund? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        confirmButtonText: 'Yes, Process Refund!'
    }).then((result) => {
        if(result.isConfirmed){
            Swal.fire({ title: 'Processing...', text: 'Please wait...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
            
            $.ajax({
                url: 'refund_sale.php',
                type: 'POST',
                data: {
                    refund_sale: 1,
                    sale_id: saleId,
                    refund_date: refundDate,
                    refund_reason: refundReason,
                    refund_items: refundItems,
                    refund_quantities: refundQuantities,
                    refund_amounts: refundAmounts
                },
                dataType: 'json',
                success: function(response){
                    if(response.success){
                        Swal.fire({ title: 'Success!', text: response.message, icon: 'success', confirmButtonColor: '#1e7e34' }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({ title: 'Error!', text: response.message, icon: 'error' });
                    }
                },
                error: function(){
                    Swal.fire({ title: 'Error!', text: 'Failed to process refund!', icon: 'error' });
                }
            });
        }
    });
});
</script>
</body>
</html>
<?php mysqli_close($conn); ?>