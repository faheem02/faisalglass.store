<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$user_id = $_SESSION['user_id'] ?? 0;
$user_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'staff';

$bank_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch bank details
$bank_query = "SELECT * FROM bank_accounts WHERE id = $bank_id";
$bank_result = mysqli_query($conn, $bank_query);
if(!$bank_result || mysqli_num_rows($bank_result) == 0) {
    header("Location: manage_banks.php");
    exit();
}
$bank = mysqli_fetch_assoc($bank_result);

$page_title = htmlspecialchars($bank['bank_name']) . " - Ledger";

// Date filters
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-d');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'today';

if($filter_type == 'today') {
    $from_date = date('Y-m-d');
    $to_date = date('Y-m-d');
} elseif($filter_type == 'week') {
    $from_date = date('Y-m-d', strtotime('monday this week'));
    $to_date = date('Y-m-d');
} elseif($filter_type == 'month') {
    $from_date = date('Y-m-01');
    $to_date = date('Y-m-d');
}

// Opening balance before from_date
$opening_query = "SELECT balance FROM bank_book
                  WHERE bank_account_id = $bank_id AND date < '$from_date'
                  ORDER BY date DESC, id DESC LIMIT 1";
$opening_result = mysqli_query($conn, $opening_query);
$opening_balance = 0;
if(mysqli_num_rows($opening_result) > 0) {
    $opening_balance = floatval(mysqli_fetch_assoc($opening_result)['balance']);
}

// Transactions for the period
$trans_query = "SELECT * FROM bank_book
                WHERE bank_account_id = $bank_id AND date BETWEEN '$from_date' AND '$to_date'
                ORDER BY date ASC, id ASC";
$trans_result = mysqli_query($conn, $trans_query);

$total_debit = 0;
$total_credit = 0;
$transactions = [];

if(mysqli_num_rows($trans_result) > 0) {
    while($row = mysqli_fetch_assoc($trans_result)) {
        $transactions[] = $row;
        $total_debit += floatval($row['debit']);
        $total_credit += floatval($row['credit']);
    }
}
$closing_balance = $opening_balance + $total_debit - $total_credit;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | <?php echo $software_name; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .stat-card { background: white; border-radius: 12px; padding: 20px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.08); transition: transform 0.2s; height: 100%; }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-number { font-size: 28px; font-weight: bold; }
        .debit-text { color: #28a745; font-weight: 600; }
        .credit-text { color: #dc3545; font-weight: 600; }
        .topbar { height: 60px; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.08); padding: 0 20px; display: flex; align-items: center; justify-content: space-between; }
        .bank-header { background: linear-gradient(135deg, #1e7e34, #4e73df); color: white; padding: 20px; border-radius: 15px; margin-bottom: 25px; }
        .ref-badge { font-size: 11px; }
        .table thead th { background-color: #1e7e34; color: white; font-weight: 600; font-size: 13px; white-space: nowrap; }
        .btn-green { background-color: #1e7e34; border-color: #1e7e34; color: white; }
        .btn-green:hover { background-color: #155724; color: white; }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include('../includes/sidebar.php'); ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <div class="topbar no-print">
                    <div class="welcome-text" style="color: #1e7e34;">
                        <i class="fas fa-store"></i> <?php echo $software_name; ?>
                    </div>
                    <div class="user-info">
                        <span style="color: #4e73df;"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user_name); ?> (<?php echo ucfirst($user_role); ?>)</span>
                        <a href="../logout.php" style="color: #dc3545; text-decoration: none; margin-left: 15px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>

                <div class="container-fluid">
                    <div class="bank-header">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h2 class="mb-1"><i class="fas fa-university"></i> <?php echo htmlspecialchars($bank['bank_name']); ?></h2>
                                <p class="mb-0">
                                    <i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($bank['account_title'] ?? '-'); ?> |
                                    <i class="fas fa-credit-card"></i> <?php echo htmlspecialchars($bank['account_number'] ?? '-'); ?>
                                </p>
                            </div>
                            <div class="col-md-4 text-right">
                                <h5>Current Balance</h5>
                                <h2 style="font-size: 32px;"><?php echo formatCurrency($closing_balance); ?></h2>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stat-card border-left-success">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Opening Balance</div>
                                <div class="stat-number" style="color: #1e7e34;"><?php echo formatCurrency($opening_balance); ?></div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stat-card border-left-primary">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Deposits (Debit)</div>
                                <div class="stat-number" style="color: #4e73df;"><?php echo formatCurrency($total_debit); ?></div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stat-card border-left-warning">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Withdrawals (Credit)</div>
                                <div class="stat-number" style="color: #f6c23e;"><?php echo formatCurrency($total_credit); ?></div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stat-card border-left-info">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Closing Balance</div>
                                <div class="stat-number" style="color: #36b9cc;"><?php echo formatCurrency($closing_balance); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold" style="color: #1e7e34;"><i class="fas fa-filter"></i> Filters</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="" class="form-inline justify-content-between flex-wrap">
                                <input type="hidden" name="id" value="<?php echo $bank_id; ?>">
                                <div class="btn-group mb-2 mb-md-0">
                                    <a href="?id=<?php echo $bank_id; ?>&filter_type=today" class="btn btn-sm <?php echo $filter_type == 'today' ? 'btn-success' : 'btn-outline-success'; ?>">Today</a>
                                    <a href="?id=<?php echo $bank_id; ?>&filter_type=week" class="btn btn-sm <?php echo $filter_type == 'week' ? 'btn-success' : 'btn-outline-success'; ?>">This Week</a>
                                    <a href="?id=<?php echo $bank_id; ?>&filter_type=month" class="btn btn-sm <?php echo $filter_type == 'month' ? 'btn-success' : 'btn-outline-success'; ?>">This Month</a>
                                </div>
                                <div class="form-group mb-2 mb-md-0">
                                    <label class="mr-2">From:</label>
                                    <input type="date" name="from_date" class="form-control form-control-sm" value="<?php echo $from_date; ?>">
                                </div>
                                <div class="form-group mb-2 mb-md-0">
                                    <label class="mr-2 ml-md-3">To:</label>
                                    <input type="date" name="to_date" class="form-control form-control-sm" value="<?php echo $to_date; ?>">
                                </div>
                                <div>
                                    <input type="hidden" name="filter_type" value="custom">
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Apply</button>
                                    <a href="bank_ledger.php?id=<?php echo $bank_id; ?>" class="btn btn-secondary btn-sm"><i class="fas fa-sync-alt"></i> Reset</a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold" style="color: #1e7e34;">
                                <i class="fas fa-list"></i> Transaction Ledger
                                (<?php echo date('d-m-Y', strtotime($from_date)); ?> to <?php echo date('d-m-Y', strtotime($to_date)); ?>)
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="bankTable">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Reference Type</th>
                                            <th>Reference #</th>
                                            <th>Description</th>
                                            <th class="text-right">Deposit (Debit)</th>
                                            <th class="text-right">Withdrawal (Credit)</th>
                                            <th class="text-right">Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if($opening_balance != 0): ?>
                                        <tr style="background-color: #f8f9fc;">
                                            <td><?php echo date('d-m-Y', strtotime($from_date . ' -1 day')); ?></td>
                                            <td colspan="2">Opening Balance</td>
                                            <td>Balance brought forward</td>
                                            <td class="text-right">-</td>
                                            <td class="text-right">-</td>
                                            <td class="text-right"><strong><?php echo formatCurrency($opening_balance); ?></strong></td>
                                        </tr>
                                        <?php endif; ?>

                                        <?php
                                        $bal = $opening_balance;
                                        foreach($transactions as $trans):
                                            $bal = floatval($trans['balance']);
                                            $ref_display = '';
                                            $ref_type = $trans['reference_type'];
                                            $ref_id = $trans['reference_id'];
                                            if($ref_type == 'SALE') {
                                                $ref_display = '<span class="badge badge-info ref-badge">SAL-' . str_pad($ref_id, 5, '0', STR_PAD_LEFT) . '</span>';
                                            } elseif($ref_type == 'PURCHASE') {
                                                $ref_display = '<span class="badge badge-warning ref-badge">PUR-' . str_pad($ref_id, 5, '0', STR_PAD_LEFT) . '</span>';
                                            } elseif($ref_type == 'customer_receipt' || $ref_type == 'CUSTOMER_PAYMENT') {
                                                $ref_display = '<span class="badge badge-success ref-badge">PAY-' . str_pad($ref_id, 5, '0', STR_PAD_LEFT) . '</span>';
                                            } elseif($ref_type == 'SUPPLIER_PAYMENT') {
                                                $ref_display = '<span class="badge badge-secondary ref-badge">SUP-PAY-' . str_pad($ref_id, 5, '0', STR_PAD_LEFT) . '</span>';
                                            } elseif($ref_type == 'EXPENSE') {
                                                $ref_display = '<span class="badge badge-danger ref-badge">EXP-' . str_pad($ref_id, 5, '0', STR_PAD_LEFT) . '</span>';
                                            } elseif($ref_type == 'EMPLOYEE_PAYMENT') {
                                                $ref_display = '<span class="badge badge-primary ref-badge">EMP-PAY-' . str_pad($ref_id, 5, '0', STR_PAD_LEFT) . '</span>';
                                            } elseif($ref_type == 'WITHDRAW') {
                                                $ref_display = '<span class="badge badge-primary ref-badge">WTH-' . str_pad($ref_id, 5, '0', STR_PAD_LEFT) . '</span>';
                                            } elseif($ref_type == 'TRANSFER_IN') {
                                                $ref_display = '<span class="badge badge-info ref-badge">TRF-IN-' . str_pad($ref_id, 5, '0', STR_PAD_LEFT) . '</span>';
                                            } elseif($ref_type == 'TRANSFER_OUT') {
                                                $ref_display = '<span class="badge badge-secondary ref-badge">TRF-OUT-' . str_pad($ref_id, 5, '0', STR_PAD_LEFT) . '</span>';
                                            } elseif($ref_type == 'OPENING') {
                                                $ref_display = '<span class="badge badge-success ref-badge">OPENING</span>';
                                            } else {
                                                $ref_display = '<span class="badge badge-secondary ref-badge">' . ucfirst(str_replace('_', ' ', $ref_type)) . '</span>';
                                            }
                                        ?>
                                        <tr>
                                            <td><?php echo date('d-m-Y', strtotime($trans['date'])); ?></td>
                                            <td><?php echo str_replace('_', ' ', ucwords($ref_type)); ?></td>
                                            <td><?php echo $ref_display; ?></td>
                                            <td><?php echo htmlspecialchars($trans['description'] ?? ''); ?></td>
                                            <td class="text-right debit-text"><?php echo $trans['debit'] > 0 ? formatCurrency($trans['debit']) : '-'; ?></td>
                                            <td class="text-right credit-text"><?php echo $trans['credit'] > 0 ? formatCurrency($trans['credit']) : '-'; ?></td>
                                            <td class="text-right"><strong><?php echo formatCurrency($bal); ?></strong></td>
                                        </tr>
                                        <?php endforeach; ?>

                                        <?php if(empty($transactions)): ?>
                                        <tr><td colspan="7" class="text-center py-4"><i class="fas fa-inbox fa-2x text-muted mb-2 d-block"></i>No transactions found in selected period</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr style="background: #e8f5e9; font-weight: bold;">
                                            <td colspan="4" class="text-right">Totals:</td>
                                            <td class="text-right debit-text"><?php echo formatCurrency($total_debit); ?></td>
                                            <td class="text-right credit-text"><?php echo formatCurrency($total_credit); ?></td>
                                            <td class="text-right"><?php echo formatCurrency($closing_balance); ?></td>
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

    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/js/sb-admin-2.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#bankTable').DataTable({
                "pageLength": 50,
                "order": [[0, 'asc']],
                "language": { "search": "Search:", "lengthMenu": "Show _MENU_ entries", "info": "Showing _START_ to _END_ of _TOTAL_ entries" }
            });
        });
    </script>
</body>
</html>
