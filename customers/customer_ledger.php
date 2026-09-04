<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');

if($customer_id == 0) {
    header("Location: view_customer.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? 0;
$user_name = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'staff';

$query = "SELECT * FROM customers WHERE id = $customer_id";
$result = mysqli_query($conn, $query);
$customer = mysqli_fetch_assoc($result);

if(!$customer) {
    header("Location: view_customer.php");
    exit();
}

// Get opening balance (balance before from_date)
$opening_query = "SELECT COALESCE(SUM(debit) - SUM(credit), 0) as balance 
                  FROM customer_ledger 
                  WHERE customer_id = $customer_id 
                  AND date < '$from_date'";
$opening_result = mysqli_query($conn, $opening_query);
$opening_balance = 0;
if($opening_result && mysqli_num_rows($opening_result) > 0) {
    $opening_data = mysqli_fetch_assoc($opening_result);
    $opening_balance = floatval($opening_data['balance']);
} else {
    $opening_balance = floatval($customer['opening_balance'] ?? 0);
    if(($customer['balance_type'] ?? '') == 'payable') {
        $opening_balance = -$opening_balance;
    }
}

// Ledger query - simple, joins only sale_master for invoice_no
$ledger_query = "SELECT cl.*, s.invoice_no
                 FROM customer_ledger cl
                 LEFT JOIN sale_master s ON cl.reference_type = 'SALE' AND cl.reference_id = s.id
                 WHERE cl.customer_id = $customer_id 
                 AND cl.date BETWEEN '$from_date' AND '$to_date'
                 ORDER BY cl.date ASC, cl.id ASC";
$ledger_result = mysqli_query($conn, $ledger_query);
$has_entries = ($ledger_result && mysqli_num_rows($ledger_result) > 0);

// Get active bank accounts for modal
$bank_query = "SELECT * FROM bank_accounts WHERE status = 1 ORDER BY bank_name";
$bank_result = mysqli_query($conn, $bank_query);

// Summary
$summary_query = "SELECT 
                    COALESCE(SUM(debit), 0) as total_debit,
                    COALESCE(SUM(credit), 0) as total_credit
                  FROM customer_ledger 
                  WHERE customer_id = $customer_id 
                  AND DATE(date) BETWEEN '$from_date' AND '$to_date'";
$summary_result = mysqli_query($conn, $summary_query);
$summary = mysqli_fetch_assoc($summary_result);

$total_debit = floatval($summary['total_debit']);
$total_credit = floatval($summary['total_credit']);
$closing_balance = $opening_balance + $total_debit - $total_credit;
$current_balance = floatval($customer['current_balance']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Ledger - <?php echo htmlspecialchars($customer['customer_name']); ?> | <?php echo $software_name; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
    <?php if($has_entries): ?>
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <?php endif; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .btn-green { background-color: #1e7e34; border-color: #1e7e34; color: white; }
        .btn-green:hover { background-color: #155724; border-color: #155724; color: white; }
        .card-header-custom { background: linear-gradient(135deg, #1e7e34, #0066cc); color: white; border-radius: 10px 10px 0 0; padding: 15px 20px; }
        .form-card { border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 30px; }
        .customer-info { background: #f8f9fc; border-radius: 10px; padding: 20px; margin-bottom: 20px; border-left: 4px solid #1e7e34; }
        .info-label { font-size: 12px; text-transform: uppercase; color: #6c757d; font-weight: 600; }
        .info-value { font-size: 18px; font-weight: 600; color: #1a1a1a; }
        .summary-card { text-align: center; padding: 15px; border-radius: 10px; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.08); height: 100%; }
        .summary-number { font-size: 24px; font-weight: bold; }
        .balance-positive { color: #dc3545; }
        .balance-negative { color: #28a745; }
        .table thead th { background-color: #1e7e34; color: white; font-weight: 600; }
        .filter-section { background: #f8f9fc; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .topbar { height: 60px; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.08); padding: 0 20px; display: flex; align-items: center; justify-content: space-between; }
        @media print {
            body { background: #fff !important; }
            #wrapper { margin: 0 !important; }
            #accordionSidebar, .topbar, .sticky-footer, .scroll-to-top,
            .no-print, .modal, .modal-backdrop, .dataTables_length,
            .dataTables_filter, .dataTables_info, .dataTables_paginate,
            .dataTables_wrapper > .row:first-child, .dataTables_wrapper > .row:last-child {
                display: none !important;
            }
            .container-fluid { padding: 0 !important; }
            .card-header, .table thead th {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <div class="topbar no-print">
                    <div class="welcome-text" style="color: #1e7e34;"><i class="fas fa-store"></i> <?php echo $software_name; ?></div>
                    <div class="user-info">
                        <span style="color: #4e73df;"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user_name); ?></span>
                        <a href="../logout.php" style="color: #dc3545; text-decoration: none;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
                <div class="container-fluid">
                    <!-- Page Header -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4 mt-3 no-print">
                        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-book text-success mr-2"></i> Customer Ledger</h1>
                        <div>
                            <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#manualEntryModal"><i class="fas fa-plus-circle"></i> Manual Entry</button>
                            <button onclick="window.open('print_customer_ledger.php?id=<?php echo $customer_id; ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>', '_blank', 'width=1000,height=750')" class="btn btn-primary btn-sm"><i class="fas fa-print"></i> Print</button>
                            <button id="exportBtn" class="btn btn-success btn-sm"><i class="fas fa-file-excel"></i> Export</button>
                            <a href="receiving_amount.php?id=<?php echo $customer_id; ?>" class="btn btn-info btn-sm"><i class="fas fa-money-bill-wave"></i> Receive Payment</a>
                            <a href="customer_detail.php?id=<?php echo $customer_id; ?>" class="btn btn-primary btn-sm"><i class="fas fa-user"></i> Detail</a>
                            <a href="view_customer.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
                        </div>
                    </div>
                    <!-- Customer Info -->
                    <div class="customer-info">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="info-label"><i class="fas fa-barcode"></i> Customer Code</div>
                                <div class="info-value"><?php echo htmlspecialchars($customer['customer_code']); ?></div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-label"><i class="fas fa-user"></i> Customer Name</div>
                                <div class="info-value"><?php echo htmlspecialchars($customer['customer_name']); ?></div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-label"><i class="fas fa-phone"></i> Mobile</div>
                                <div class="info-value"><?php echo htmlspecialchars($customer['mobile']); ?></div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-label"><i class="fas fa-chart-line"></i> Current Balance</div>
                                <div class="info-value <?php echo $current_balance > 0 ? 'balance-positive' : ($current_balance < 0 ? 'balance-negative' : ''); ?>">
                                    <?php
                                    if($current_balance > 0) echo formatCurrency($current_balance) . ' (Receivable)';
                                    elseif($current_balance < 0) echo formatCurrency(abs($current_balance)) . ' (Payable)';
                                    else echo formatCurrency(0);
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Filter -->
                    <div class="card form-card no-print">
                        <div class="card-header-custom"><i class="fas fa-filter mr-2"></i> Filter Ledger</div>
                        <div class="card-body">
                            <form method="GET" action="">
                                <input type="hidden" name="id" value="<?php echo $customer_id; ?>">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label><i class="fas fa-calendar-alt text-success mr-1"></i> From Date</label>
                                            <input type="date" name="from_date" class="form-control" value="<?php echo $from_date; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label><i class="fas fa-calendar-alt text-success mr-1"></i> To Date</label>
                                            <input type="date" name="to_date" class="form-control" value="<?php echo $to_date; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>&nbsp;</label>
                                            <button type="submit" class="btn btn-green form-control"><i class="fas fa-search mr-1"></i> Filter</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="summary-card">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Opening Balance</div>
                                <div class="summary-number <?php echo $opening_balance > 0 ? 'balance-positive' : ($opening_balance < 0 ? 'balance-negative' : ''); ?>">
                                    <?php echo formatCurrency(abs($opening_balance)); ?>
                                    <br><small><?php echo $opening_balance > 0 ? '(Receivable)' : ($opening_balance < 0 ? '(Payable)' : '(Zero)'); ?></small>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="summary-card">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Debits (Sales / DR)</div>
                                <div class="summary-number text-danger"><?php echo formatCurrency($total_debit); ?></div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="summary-card">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Credits (Payments / CR)</div>
                                <div class="summary-number text-info"><?php echo formatCurrency($total_credit); ?></div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="summary-card">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Closing Balance</div>
                                <div class="summary-number <?php echo $closing_balance > 0 ? 'balance-positive' : ($closing_balance < 0 ? 'balance-negative' : ''); ?>">
                                    <?php echo formatCurrency(abs($closing_balance)); ?>
                                    <br><small><?php echo $closing_balance > 0 ? '(Receivable)' : ($closing_balance < 0 ? '(Payable)' : '(Zero)'); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Ledger Table -->
                    <div class="card form-card">
                        <div class="card-header-custom">
                            <i class="fas fa-list mr-2"></i> Ledger Statement
                            <span class="float-right">Period: <?php echo date('d-m-Y', strtotime($from_date)); ?> to <?php echo date('d-m-Y', strtotime($to_date)); ?></span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="ledgerTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Reference Type</th>
                                            <th>Invoice #</th>
                                            <th>Description</th>
                                            <th class="text-right">Debit (Sale / DR)</th>
                                            <th class="text-right">Credit (Payment / CR)</th>
                                            <th class="text-right">Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $running_balance = $opening_balance;
                                        ?>
                                        <tr style="background:#f8f9fc;font-weight:600;">
                                            <td><?php echo date('d-m-Y', strtotime($from_date)); ?></td>
                                            <td><span class="badge badge-info">Opening</span></td>
                                            <td>-</td>
                                            <td><strong>Opening Balance</strong></td>
                                            <td class="text-right"><?php echo $opening_balance > 0 ? formatCurrency($opening_balance) : '-'; ?></td>
                                            <td class="text-right"><?php echo $opening_balance < 0 ? formatCurrency(abs($opening_balance)) : '-'; ?></td>
                                            <td class="text-right"><strong><?php echo formatCurrency(abs($running_balance)); ?> <?php echo $running_balance >= 0 ? 'DR' : 'CR'; ?></strong></td>
                                        </tr>
                                        <?php if(mysqli_num_rows($ledger_result) > 0): while($row = mysqli_fetch_assoc($ledger_result)):
                                            $debit = floatval($row['debit']);
                                            $credit = floatval($row['credit']);
                                            $running_balance += ($debit - $credit);

                                            $badge_class = '';
                                            $type_label = '';
                                            switch($row['reference_type']) {
                                                case 'OPENING': $badge_class = 'badge-info'; $type_label = 'Opening'; break;
                                                case 'SALE': $badge_class = 'badge-primary'; $type_label = 'Sale'; break;
                                                case 'PAYMENT': $badge_class = 'badge-success'; $type_label = 'Payment'; break;
                                                case 'QUOTATION': $badge_class = 'badge-secondary'; $type_label = 'Quotation'; break;
                                                case 'ADJUSTMENT': $badge_class = 'badge-warning'; $type_label = 'Adjustment'; break;
                                                case 'MANUAL': $badge_class = 'badge-dark'; $type_label = 'Manual'; break;
                                                default: $badge_class = 'badge-secondary'; $type_label = $row['reference_type']; break;
                                            }
                                        ?>
                                        <tr>
                                            <td><?php echo date('d-m-Y', strtotime($row['date'])); ?></td>
                                            <td><span class="badge <?php echo $badge_class; ?>"><?php echo $type_label; ?></span></td>
                                            <td>
                                                <?php if($row['reference_type'] == 'SALE' && !empty($row['invoice_no'])): ?>
                                                    <a href="../sales/print_invoice.php?invoice_no=<?php echo urlencode($row['invoice_no']); ?>" target="_blank" class="font-weight-bold">
                                                        <?php echo htmlspecialchars($row['invoice_no']); ?>
                                                    </a>
                                                <?php elseif($row['reference_type'] == 'PAYMENT'): ?>
                                                    <a href="receiving_amount.php?receipt_id=<?php echo $row['reference_id']; ?>" class="font-weight-bold">
                                                        RCP-<?php echo str_pad($row['reference_id'], 4, '0', STR_PAD_LEFT); ?>
                                                    </a>
                                                <?php elseif($row['reference_type'] == 'QUOTATION'): ?>
                                                    <a href="../quotations/print_quotation.php?id=<?php echo $row['reference_id']; ?>" target="_blank" class="font-weight-bold">
                                                        QTN-<?php echo str_pad($row['reference_id'], 4, '0', STR_PAD_LEFT); ?>
                                                    </a>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo nl2br(htmlspecialchars($row['description'])); ?></td>
                                            <td class="text-right text-success"><?php echo $debit > 0 ? formatCurrency($debit) : '-'; ?></td>
                                            <td class="text-right text-danger"><?php echo $credit > 0 ? formatCurrency($credit) : '-'; ?></td>
                                            <td class="text-right">
                                                <strong>
                                                    <?php
                                                    if($running_balance > 0) echo '<span class="text-danger">' . formatCurrency($running_balance) . ' (Receivable)</span>';
                                                    elseif($running_balance < 0) echo '<span class="text-success">' . formatCurrency(abs($running_balance)) . ' (Payable)</span>';
                                                    else echo formatCurrency(0);
                                                    ?>
                                                </strong>
                                            </td>
                                        </tr>
                                        <?php endwhile; else: ?>
                                        <tr><td colspan="7" class="text-center py-5">No ledger entries found for the selected period</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr style="background:#f8f9fc;font-weight:bold;">
                                            <td colspan="4" class="text-right"><strong>Totals:</strong></td>
                                            <td class="text-right text-success"><strong><?php echo formatCurrency($total_debit); ?></strong></td>
                                            <td class="text-right text-danger"><strong><?php echo formatCurrency($total_credit); ?></strong></td>
                                            <td class="text-right">
                                                <strong>
                                                    <?php
                                                    if($closing_balance > 0) echo '<span class="text-danger">' . formatCurrency($closing_balance) . ' (Receivable)</span>';
                                                    elseif($closing_balance < 0) echo '<span class="text-success">' . formatCurrency(abs($closing_balance)) . ' (Payable)</span>';
                                                    else echo formatCurrency(0);
                                                    ?>
                                                </strong>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include('../includes/footer.php'); ?>
        </div>
    </div>

    <!-- Modal for Manual Customer Entry -->
    <div class="modal fade" id="manualEntryModal" tabindex="-1" role="dialog" aria-labelledby="manualEntryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="manualEntryForm">
                    <input type="hidden" name="customer_id" value="<?php echo $customer_id; ?>">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title font-weight-bold" id="manualEntryModalLabel">
                            <i class="fas fa-edit mr-2"></i> Customer Manual Entry (Debit / Credit)
                        </h5>
                        <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info py-2 mb-3" style="font-size: 13px;">
                            <i class="fas fa-info-circle mr-1"></i>
                            <strong>Debit (DR):</strong> Increases customer balance (Customer owes more).<br>
                            <strong>Credit (CR):</strong> Decreases customer balance (Customer owes less / paid).
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold">Entry Date <span class="text-danger">*</span></label>
                            <input type="date" name="entry_date" id="entryDate" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold">Entry Type <span class="text-danger">*</span></label>
                            <div class="d-flex">
                                <div class="custom-control custom-radio mr-4">
                                    <input type="radio" id="typeDebit" name="entry_type" value="debit" class="custom-control-input" checked>
                                    <label class="custom-control-label text-danger font-weight-bold" for="typeDebit">
                                        <i class="fas fa-plus-circle text-danger mr-1"></i> Debit (DR - Increase Due)
                                    </label>
                                </div>
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="typeCredit" name="entry_type" value="credit" class="custom-control-input">
                                    <label class="custom-control-label text-success font-weight-bold" for="typeCredit">
                                        <i class="fas fa-minus-circle text-success mr-1"></i> Credit (CR - Decrease Due)
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold">Amount (Rs) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text font-weight-bold">Rs</span>
                                </div>
                                <input type="number" step="0.01" min="0.01" name="amount" id="entryAmount" class="form-control font-weight-bold" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold">Payment / Settlement Method</label>
                            <select name="payment_method" id="entryPaymentMethod" class="form-control">
                                <option value="adjustment">Direct Ledger Adjustment (No Cash/Bank impact)</option>
                                <option value="cash">Cash (Affect Cash Book)</option>
                                <option value="bank">Bank Account (Affect Bank Book)</option>
                            </select>
                        </div>
                        <div class="form-group" id="bankAccountGroup" style="display:none;">
                            <label class="font-weight-bold">Bank Account <span class="text-danger">*</span></label>
                            <select name="bank_account_id" id="entryBankAccountId" class="form-control">
                                <option value="">-- Select Bank Account --</option>
                                <?php 
                                if($bank_result && mysqli_num_rows($bank_result) > 0) {
                                    mysqli_data_seek($bank_result, 0);
                                    while($bank = mysqli_fetch_assoc($bank_result)) {
                                        echo '<option value="' . $bank['id'] . '">' . htmlspecialchars($bank['bank_name'] . ' - ' . $bank['account_title'] . ' (' . $bank['account_number'] . ')') . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold">Reference / Voucher # (Optional)</label>
                            <input type="text" name="reference_no" id="entryReferenceNo" class="form-control" placeholder="e.g. ADJ-001, Voucher #, Slip #">
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold">Description / Remarks <span class="text-danger">*</span></label>
                            <textarea name="remarks" id="entryRemarks" class="form-control" rows="2" placeholder="Reason for debit/credit entry..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" id="saveManualEntryBtn" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Save Entry
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <a class="scroll-to-top rounded no-print" href="#page-top"><i class="fas fa-angle-up"></i></a>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if($has_entries): ?>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<?php endif; ?>
    <script src="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/js/sb-admin-2.min.js"></script>
    <script>
    $(document).ready(function() {
        // Toggle bank account select
        $('#entryPaymentMethod').on('change', function() {
            if ($(this).val() === 'bank') {
                $('#bankAccountGroup').slideDown();
                $('#entryBankAccountId').prop('required', true);
            } else {
                $('#bankAccountGroup').slideUp();
                $('#entryBankAccountId').prop('required', false).val('');
            }
        });

        // Manual entry form submission
        $('#manualEntryForm').on('submit', function(e) {
            e.preventDefault();

            var amount = parseFloat($('#entryAmount').val());
            if (isNaN(amount) || amount <= 0) {
                Swal.fire('Error', 'Please enter a valid amount greater than zero.', 'error');
                return;
            }

            var remarks = $('#entryRemarks').val().trim();
            if (!remarks) {
                Swal.fire('Error', 'Please enter a description / remarks.', 'error');
                return;
            }

            var paymentMethod = $('#entryPaymentMethod').val();
            if (paymentMethod === 'bank' && !$('#entryBankAccountId').val()) {
                Swal.fire('Error', 'Please select a bank account.', 'error');
                return;
            }

            var entryType = $('input[name="entry_type"]:checked').val();
            var typeText = entryType === 'debit' ? 'Debit (DR - Increase Due)' : 'Credit (CR - Decrease Due)';

            Swal.fire({
                title: 'Confirm Manual Entry',
                html: `Are you sure you want to record this <b>${typeText}</b> entry of <b>Rs. ${amount.toLocaleString('en-US', {minimumFractionDigits: 2})}</b>?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1e7e34',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Save Entry'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#saveManualEntryBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Saving...');
                    
                    $.ajax({
                        url: 'save_manual_entry.php',
                        type: 'POST',
                        data: $('#manualEntryForm').serialize(),
                        dataType: 'json',
                        success: function(res) {
                            $('#saveManualEntryBtn').prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Save Entry');
                            if (res.success) {
                                $('#manualEntryModal').modal('hide');
                                Swal.fire({
                                    title: 'Success!',
                                    text: res.message,
                                    icon: 'success',
                                    confirmButtonColor: '#1e7e34'
                                }).then(() => {
                                    var entryDate = $('#entryDate').val();
                                    var curFrom = '<?php echo $from_date; ?>';
                                    var curTo = '<?php echo $to_date; ?>';
                                    var newFrom = (entryDate && entryDate < curFrom) ? entryDate : curFrom;
                                    var newTo = (entryDate && entryDate > curTo) ? entryDate : curTo;
                                    window.location.href = 'customer_ledger.php?id=<?php echo $customer_id; ?>&from_date=' + newFrom + '&to_date=' + newTo;
                                });
                            } else {
                                Swal.fire('Error', res.message, 'error');
                            }
                        },
                        error: function(xhr, status, error) {
                            $('#saveManualEntryBtn').prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Save Entry');
                            Swal.fire('Error', 'An error occurred while saving entry: ' + error, 'error');
                        }
                    });
                }
            });
        });

        $('#exportBtn').on('click', function() {
            var data = [];
            data.push(['Date', 'Reference Type', 'Invoice #', 'Description', 'Debit (Sale)', 'Credit (Payment)', 'Balance']);
            $('#ledgerTable tbody tr').each(function() {
                var row = [];
                $(this).find('td').each(function() { row.push($(this).text().trim()); });
                if(row.length) data.push(row);
            });
            var csv = data.map(r => r.join(',')).join('\n');
            var blob = new Blob([csv], {type:'text/csv'});
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url; a.download = 'customer_ledger_<?php echo $customer['customer_code']; ?>.csv';
            a.click(); URL.revokeObjectURL(url);
            Swal.fire({title:'Success!',text:'Export completed!',icon:'success',confirmButtonColor:'#1e7e34',timer:2000});
        });

        // Initialize DataTable last so a rendering error never blocks the
        // bank-toggle / manual-entry handlers above. Only loads when the ledger
        // has rows for the period (empty ledgers skip the CDN files entirely).
        <?php if($has_entries): ?>
        try {
            $('#ledgerTable').DataTable({
                "ordering": false,
                "pageLength": 25,
                "language": {
                    "search": "Search:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "infoEmpty": "Showing 0 to 0 of 0 entries",
                    "infoFiltered": "(filtered from _MAX_ total entries)",
                    "zeroRecords": "No entries found"
                }
            });
        } catch(e) {
            console.warn('DataTable init skipped:', e);
        }
        <?php endif; ?>
    });
    </script>
</body>
</html>

