<?php
/**
 * Supplier Detail Page
 * Faysal Glass And Aluminium Centre
 * 
 * Display complete supplier profile with balance summary and transactions
 * Page: Supplier Detail
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$page_title = "Supplier Detail";

// Check if supplier ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: supplier_view.php");
    exit();
}

$supplier_id = intval($_GET['id']);

// Fetch supplier details
$query = "SELECT * FROM suppliers WHERE id = $supplier_id";
$result = mysqli_query($conn, $query);

if(!$result || mysqli_num_rows($result) == 0) {
    header("Location: supplier_view.php");
    exit();
}

$supplier = mysqli_fetch_assoc($result);

// Calculate current balance from ledger
$balance_query = "SELECT SUM(debit) as total_debit, SUM(credit) as total_credit 
                  FROM supplier_ledger WHERE supplier_id = $supplier_id";
$balance_result = mysqli_query($conn, $balance_query);

$total_debit = 0;
$total_credit = 0;

if($balance_result && mysqli_num_rows($balance_result) > 0) {
    $balance_data = mysqli_fetch_assoc($balance_result);
    $total_debit = floatval($balance_data['total_debit']);
    $total_credit = floatval($balance_data['total_credit']);
}
$current_balance = $total_credit - $total_debit;

// Calculate total purchases (check if purchases table exists)
$total_purchases = 0;
$total_paid = 0;
$outstanding = 0;

$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'purchases'");
if(mysqli_num_rows($table_check) > 0) {
    $purchases_query = "SELECT SUM(total_amount) as total_purchases, SUM(paid_amount) as total_paid 
                        FROM purchases WHERE supplier_id = $supplier_id AND status = 1";
    $purchases_result = mysqli_query($conn, $purchases_query);
    if($purchases_result && mysqli_num_rows($purchases_result) > 0) {
        $purchases_data = mysqli_fetch_assoc($purchases_result);
        $total_purchases = floatval($purchases_data['total_purchases']);
        $total_paid = floatval($purchases_data['total_paid']);
        $outstanding = $total_purchases - $total_paid;
    }
}

// Calculate total payments made to supplier
$total_payments = 0;
$payments_query = "SELECT SUM(amount) as total_payments FROM supplier_payments WHERE supplier_id = $supplier_id";
$payments_result = mysqli_query($conn, $payments_query);
if($payments_result && mysqli_num_rows($payments_result) > 0) {
    $payments_data = mysqli_fetch_assoc($payments_result);
    $total_payments = floatval($payments_data['total_payments']);
}

// Fetch recent transactions (last 10 ledger entries)
$transactions_query = "SELECT * FROM supplier_ledger WHERE supplier_id = $supplier_id ORDER BY id DESC LIMIT 10";
$transactions_result = mysqli_query($conn, $transactions_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | <?php echo $software_name; ?></title>
    
    <!-- Bootstrap 4 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    
    <!-- SB Admin 2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        .btn-green {
            background-color: #1e7e34;
            border-color: #1e7e34;
            color: white;
        }
        .btn-green:hover {
            background-color: #155724;
            border-color: #155724;
            color: white;
        }
        .card-header-custom {
            background: linear-gradient(135deg, #1e7e34, #0066cc);
            color: white;
            border-radius: 10px 10px 0 0;
            padding: 15px 20px;
        }
        .form-card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        .info-card {
            background: #f8f9fc;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #1e7e34;
        }
        .info-label {
            font-size: 12px;
            text-transform: uppercase;
            color: #6c757d;
            font-weight: 600;
        }
        .info-value {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
        }
        .badge-payable {
            background-color: #dc3545;
            color: white;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 16px;
        }
        .badge-receivable {
            background-color: #28a745;
            color: white;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 16px;
        }
        .badge-active {
            background-color: #28a745;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
        }
        .badge-inactive {
            background-color: #dc3545;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
        }
        .summary-number {
            font-size: 28px;
            font-weight: bold;
        }
        .summary-card {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            height: 100%;
        }
        .supplier-code {
            font-family: monospace;
            font-size: 20px;
            font-weight: bold;
            color: #0066cc;
        }
        .action-buttons {
            margin-top: 20px;
        }
        .action-buttons .btn {
            margin-right: 10px;
        }
    </style>
</head>
<body id="page-top">

<div id="wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <div class="container-fluid">
        
        <!-- Page Header -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-truck text-success mr-2"></i> Supplier Detail
            </h1>
            <div>
                <a href="supplier_view.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Back to List
                </a>
                <a href="credit_pay.php?id=<?php echo $supplier_id; ?>" class="btn btn-green ml-2">
                    <i class="fas fa-money-bill-wave mr-1"></i> Make Payment
                </a>
                <a href="supplier_ledger.php?id=<?php echo $supplier_id; ?>" class="btn btn-info ml-2">
                    <i class="fas fa-book mr-1"></i> View Full Ledger
                </a>
            </div>
        </div>
        
        <!-- Supplier Information Card -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-info-circle mr-2"></i> Supplier Information
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-card">
                                    <div class="info-label"><i class="fas fa-barcode"></i> Supplier Code</div>
                                    <div class="supplier-code"><?php echo $supplier['supplier_code']; ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-card">
                                    <div class="info-label"><i class="fas fa-user"></i> Supplier Name</div>
                                    <div class="info-value"><?php echo htmlspecialchars($supplier['supplier_name']); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-card">
                                    <div class="info-label"><i class="fas fa-building"></i> Company Name</div>
                                    <div class="info-value"><?php echo htmlspecialchars($supplier['company_name']) ?: '-'; ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-card">
                                    <div class="info-label"><i class="fas fa-user-tie"></i> Contact Person</div>
                                    <div class="info-value"><?php echo htmlspecialchars($supplier['contact_person']) ?: '-'; ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-card">
                                    <div class="info-label"><i class="fas fa-phone"></i> Mobile Number</div>
                                    <div class="info-value"><?php echo htmlspecialchars($supplier['mobile']); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-card">
                                    <div class="info-label"><i class="fas fa-envelope"></i> Email</div>
                                    <div class="info-value"><?php echo htmlspecialchars($supplier['email']) ?: '-'; ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-card">
                                    <div class="info-label"><i class="fas fa-id-card"></i> CNIC</div>
                                    <div class="info-value"><?php echo htmlspecialchars($supplier['cnic']) ?: '-'; ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-card">
                                    <div class="info-label"><i class="fas fa-file-invoice"></i> NTN</div>
                                    <div class="info-value"><?php echo htmlspecialchars($supplier['ntn']) ?: '-'; ?></div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="info-card">
                                    <div class="info-label"><i class="fas fa-map-marker-alt"></i> Address</div>
                                    <div class="info-value"><?php echo nl2br(htmlspecialchars($supplier['address'])) ?: '-'; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-card text-center">
                            <div class="info-label">Current Balance Status</div>
                            <div class="mt-3">
                                <?php if($current_balance > 0): ?>
                                    <div class="badge-payable">
                                        <i class="fas fa-arrow-up"></i> Payable
                                    </div>
                                    <div class="mt-2">
                                        <h3 class="text-danger"><?php echo formatCurrency($current_balance); ?></h3>
                                        <small>Company owes to supplier</small>
                                    </div>
                                <?php elseif($current_balance < 0): ?>
                                    <div class="badge-receivable">
                                        <i class="fas fa-arrow-down"></i> Receivable
                                    </div>
                                    <div class="mt-2">
                                        <h3 class="text-success"><?php echo formatCurrency(abs($current_balance)); ?></h3>
                                        <small>Supplier owes to company</small>
                                    </div>
                                <?php else: ?>
                                    <div class="badge-secondary" style="padding: 8px 20px; border-radius: 30px; background: #6c757d; color: white;">
                                        <i class="fas fa-balance-scale"></i> Zero Balance
                                    </div>
                                    <div class="mt-2">
                                        <h3><?php echo formatCurrency(0); ?></h3>
                                        <small>No outstanding amount</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="mt-3">
                                <div class="info-label">Status</div>
                                <?php if($supplier['status'] == 1): ?>
                                    <span class="badge-active"><i class="fas fa-check-circle"></i> Active</span>
                                <?php else: ?>
                                    <span class="badge-inactive"><i class="fas fa-times-circle"></i> Inactive</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Balance Summary Cards -->
        <div class="row">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="summary-card">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Opening Balance</div>
                    <div class="summary-number">
                        <?php 
                        if($supplier['opening_balance'] > 0) {
                            echo formatCurrency($supplier['opening_balance']);
                            echo '<br><small class="text-muted">(' . ucfirst($supplier['balance_type']) . ')</small>';
                        } else {
                            echo formatCurrency(0);
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="summary-card">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Purchases</div>
                    <div class="summary-number text-primary"><?php echo formatCurrency($total_purchases); ?></div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="summary-card">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Payments</div>
                    <div class="summary-number text-info"><?php echo formatCurrency($total_payments); ?></div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="summary-card">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Outstanding Amount</div>
                    <div class="summary-number <?php echo $outstanding > 0 ? 'text-danger' : 'text-success'; ?>">
                        <?php echo formatCurrency($outstanding); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Purchase Summary -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-chart-line mr-2"></i> Purchase Summary
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-center">
                        <h6>Total Purchase Amount</h6>
                        <h4 class="text-primary"><?php echo formatCurrency($total_purchases); ?></h4>
                    </div>
                    <div class="col-md-4 text-center">
                        <h6>Total Paid Amount</h6>
                        <h4 class="text-success"><?php echo formatCurrency($total_paid); ?></h4>
                    </div>
                    <div class="col-md-4 text-center">
                        <h6>Outstanding Amount</h6>
                        <h4 class="<?php echo $outstanding > 0 ? 'text-danger' : 'text-success'; ?>">
                            <?php echo formatCurrency($outstanding); ?>
                        </h4>
                    </div>
                </div>
                
                <?php if($outstanding > 0): ?>
                <div class="text-center mt-3">
                    <a href="credit_pay.php?id=<?php echo $supplier_id; ?>" class="btn btn-green">
                        <i class="fas fa-money-bill-wave mr-1"></i> Pay Outstanding Amount (<?php echo formatCurrency($outstanding); ?>)
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Transactions -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-history mr-2"></i> Recent Transactions (Last 10)
                <a href="supplier_ledger.php?id=<?php echo $supplier_id; ?>" class="btn btn-sm btn-light float-right">
                    View Full Ledger <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Reference Type</th>
                                <th>Description</th>
                                <th class="text-right">Debit (Receivable)</th>
                                <th class="text-right">Credit (Payable)</th>
                                <th class="text-right">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($transactions_result && mysqli_num_rows($transactions_result) > 0): ?>
                                <?php while($transaction = mysqli_fetch_assoc($transactions_result)): ?>
                                <tr>
                                    <td><?php echo date('d-m-Y', strtotime($transaction['date'])); ?></td>
                                    <td>
                                        <?php 
                                        $badge_class = '';
                                        switch($transaction['reference_type']) {
                                            case 'OPENING':
                                                $badge_class = 'badge-info';
                                                break;
                                            case 'PURCHASE':
                                                $badge_class = 'badge-primary';
                                                break;
                                            case 'PAYMENT':
                                                $badge_class = 'badge-success';
                                                break;
                                            case 'ADJUSTMENT':
                                                $badge_class = 'badge-warning';
                                                break;
                                            default:
                                                $badge_class = 'badge-secondary';
                                        }
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>"><?php echo $transaction['reference_type']; ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                    <td class="text-right text-success"><?php echo $transaction['debit'] > 0 ? formatCurrency($transaction['debit']) : '-'; ?></td>
                                    <td class="text-right text-danger"><?php echo $transaction['credit'] > 0 ? formatCurrency($transaction['credit']) : '-'; ?></td>
                                    <td class="text-right">
                                        <strong>
                                            <?php 
                                            if($transaction['balance'] > 0) {
                                                echo '<span class="text-danger">' . formatCurrency($transaction['balance']) . ' (Payable)</span>';
                                            } elseif($transaction['balance'] < 0) {
                                                echo '<span class="text-success">' . formatCurrency(abs($transaction['balance'])) . ' (Receivable)</span>';
                                            } else {
                                                echo formatCurrency(0);
                                            }
                                            ?>
                                        </strong>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No transactions found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Notes Section -->
        <?php if($supplier['notes']): ?>
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-sticky-note mr-2"></i> Notes
            </div>
            <div class="card-body">
                <p><?php echo nl2br(htmlspecialchars($supplier['notes'])); ?></p>
            </div>
        </div>
        <?php endif; ?>
        
    </div>
    
    <footer class="sticky-footer bg-white">
        <div class="container my-auto">
            <div class="copyright text-center my-auto">
                <span>&copy; <?php echo date('Y'); ?> <?php echo $software_name; ?> - All Rights Reserved</span>
            </div>
        </div>
    </footer>
    
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/js/sb-admin-2.min.js"></script>

</body>
</html>

<?php mysqli_close($conn); ?>