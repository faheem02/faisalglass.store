<?php
/**
 * Employee Detail Page
 * Faysal Glass And Aluminium Centre
 * 
 * Display complete employee profile with balance summary and salary history
 * Page: Employee Detail
 */

session_start();
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$page_title = "Employee Detail";

// Check if employee ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: view_ledger.php");
    exit();
}

$employee_id = intval($_GET['id']);

// Fetch employee details
$query = "SELECT * FROM employees WHERE id = $employee_id";
$result = mysqli_query($conn, $query);

if(!$result || mysqli_num_rows($result) == 0) {
    header("Location: view_ledger.php");
    exit();
}

$employee = mysqli_fetch_assoc($result);

// Calculate current balance from ledger
$balance_query = "SELECT SUM(debit) as total_debit, SUM(credit) as total_credit 
                  FROM employee_ledger WHERE employee_id = $employee_id";
$balance_result = mysqli_query($conn, $balance_query);

$total_debit = 0;
$total_credit = 0;
if($balance_result && mysqli_num_rows($balance_result) > 0) {
    $balance_data = mysqli_fetch_assoc($balance_result);
    $total_debit = floatval($balance_data['total_debit']);
    $total_credit = floatval($balance_data['total_credit']);
}
$current_balance = $total_credit - $total_debit;

// Calculate total salary earned
$salary_query = "SELECT SUM(credit) as total_salary FROM employee_ledger 
                 WHERE employee_id = $employee_id AND reference_type = 'SALARY'";
$salary_result = mysqli_query($conn, $salary_query);
$total_salary = 0;
if($salary_result && mysqli_num_rows($salary_result) > 0) {
    $salary_data = mysqli_fetch_assoc($salary_result);
    $total_salary = floatval($salary_data['total_salary']);
}

// Calculate total payments made
$payment_query = "SELECT SUM(debit) as total_payments FROM employee_ledger 
                  WHERE employee_id = $employee_id AND reference_type = 'PAYMENT'";
$payment_result = mysqli_query($conn, $payment_query);
$total_payments = 0;
if($payment_result && mysqli_num_rows($payment_result) > 0) {
    $payment_data = mysqli_fetch_assoc($payment_result);
    $total_payments = floatval($payment_data['total_payments']);
}

// Fetch recent transactions (last 10 ledger entries)
$transactions_query = "SELECT * FROM employee_ledger WHERE employee_id = $employee_id ORDER BY id DESC LIMIT 10";
$transactions_result = mysqli_query($conn, $transactions_query);

// Fetch monthly salary records
$salary_records_query = "SELECT * FROM employee_salary WHERE employee_id = $employee_id ORDER BY month_year DESC LIMIT 12";
$salary_records_result = mysqli_query($conn, $salary_records_query);
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
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    
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
        .badge-advance {
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
        .employee-code {
            font-family: monospace;
            font-size: 20px;
            font-weight: bold;
            color: #0066cc;
        }
        .action-buttons {
            margin-top: 20px;
        }
        .salary-status-paid {
            background-color: #28a745;
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 11px;
        }
        .salary-status-pending {
            background-color: #dc3545;
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 11px;
        }
        .salary-status-partial {
            background-color: #ffc107;
            color: #1a1a1a;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 11px;
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
                <i class="fas fa-user-circle text-success mr-2"></i> Employee Detail
            </h1>
            <div>
                <a href="view_ledger.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Back to List
                </a>
                <a href="paid_amount.php?id=<?php echo $employee_id; ?>" class="btn btn-green ml-2">
                    <i class="fas fa-money-bill-wave mr-1"></i> Make Payment
                </a>
                <a href="employee_ledger.php?id=<?php echo $employee_id; ?>" class="btn btn-info ml-2">
                    <i class="fas fa-book mr-1"></i> View Full Ledger
                </a>
            </div>
        </div>
        
        <!-- Employee Information Card -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-info-circle mr-2"></i> Employee Information
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-card">
                                    <div class="info-label"><i class="fas fa-barcode"></i> Employee Code</div>
                                    <div class="employee-code"><?php echo $employee['employee_code']; ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-card">
                                    <div class="info-label"><i class="fas fa-user"></i> Employee Name</div>
                                    <div class="info-value"><?php echo htmlspecialchars($employee['employee_name']); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-card">
                                    <div class="info-label"><i class="fas fa-user-friends"></i> Father Name</div>
                                    <div class="info-value"><?php echo htmlspecialchars($employee['father_name']) ?: '-'; ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-card">
                                    <div class="info-label"><i class="fas fa-briefcase"></i> Designation</div>
                                    <div class="info-value"><?php echo htmlspecialchars($employee['designation']) ?: '-'; ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-card">
                                    <div class="info-label"><i class="fas fa-building"></i> Department</div>
                                    <div class="info-value"><?php echo htmlspecialchars($employee['department']) ?: '-'; ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-card">
                                    <div class="info-label"><i class="fas fa-clock"></i> Employee Type</div>
                                    <div class="info-value"><?php echo ucfirst(str_replace('_', ' ', $employee['employee_type'])); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-card">
                                    <div class="info-label"><i class="fas fa-id-card"></i> CNIC</div>
                                    <div class="info-value"><?php echo htmlspecialchars($employee['cnic']) ?: '-'; ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-card">
                                    <div class="info-label"><i class="fas fa-phone"></i> Mobile Number</div>
                                    <div class="info-value"><?php echo htmlspecialchars($employee['mobile']); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-card">
                                    <div class="info-label"><i class="fas fa-envelope"></i> Email</div>
                                    <div class="info-value"><?php echo htmlspecialchars($employee['email']) ?: '-'; ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-card">
                                    <div class="info-label"><i class="fas fa-calendar-alt"></i> Joining Date</div>
                                    <div class="info-value"><?php echo $employee['joining_date'] ? date('d-m-Y', strtotime($employee['joining_date'])) : '-'; ?></div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="info-card">
                                    <div class="info-label"><i class="fas fa-map-marker-alt"></i> Address</div>
                                    <div class="info-value"><?php echo nl2br(htmlspecialchars($employee['address'])) ?: '-'; ?></div>
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
                                        <small>Company owes to employee</small>
                                    </div>
                                <?php elseif($current_balance < 0): ?>
                                    <div class="badge-advance">
                                        <i class="fas fa-arrow-down"></i> Advance
                                    </div>
                                    <div class="mt-2">
                                        <h3 class="text-success"><?php echo formatCurrency(abs($current_balance)); ?></h3>
                                        <small>Employee owes to company</small>
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
                                <?php if($employee['status'] == 1): ?>
                                    <span class="badge-active"><i class="fas fa-check-circle"></i> Active</span>
                                <?php else: ?>
                                    <span class="badge-inactive"><i class="fas fa-times-circle"></i> Inactive</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if($employee['bank_name'] || $employee['bank_account_no']): ?>
                        <div class="info-card">
                            <div class="info-label"><i class="fas fa-university"></i> Bank Details</div>
                            <div class="info-value"><?php echo htmlspecialchars($employee['bank_name']) ?: '-'; ?></div>
                            <div class="info-value small"><?php echo htmlspecialchars($employee['bank_account_no']) ?: '-'; ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Salary Summary Cards -->
        <div class="row">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="summary-card">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Monthly Net Salary</div>
                    <div class="summary-number text-success"><?php echo formatCurrency($employee['net_salary']); ?></div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="summary-card">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Salary Earned</div>
                    <div class="summary-number text-primary"><?php echo formatCurrency($total_salary); ?></div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="summary-card">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Payments Made</div>
                    <div class="summary-number text-info"><?php echo formatCurrency($total_payments); ?></div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="summary-card">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Outstanding Balance</div>
                    <div class="summary-number <?php echo $current_balance > 0 ? 'text-danger' : ($current_balance < 0 ? 'text-success' : 'text-secondary'); ?>">
                        <?php echo formatCurrency(abs($current_balance)); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Salary Breakdown -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-chart-line mr-2"></i> Salary Breakdown
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <h6>Basic Salary</h6>
                        <h4 class="text-primary"><?php echo formatCurrency($employee['basic_salary']); ?></h4>
                    </div>
                    <div class="col-md-3 text-center">
                        <h6>Allowances</h6>
                        <h4 class="text-success"><?php echo formatCurrency($employee['allowances']); ?></h4>
                    </div>
                    <div class="col-md-3 text-center">
                        <h6>Deductions</h6>
                        <h4 class="text-danger"><?php echo formatCurrency($employee['deductions']); ?></h4>
                    </div>
                    <div class="col-md-3 text-center">
                        <h6>Net Salary</h6>
                        <h4 class="text-info"><?php echo formatCurrency($employee['net_salary']); ?></h4>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Monthly Salary Records -->
        <?php if($salary_records_result && mysqli_num_rows($salary_records_result) > 0): ?>
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-calendar-alt mr-2"></i> Monthly Salary Records
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th class="text-right">Basic Salary</th>
                                <th class="text-right">Allowances</th>
                                <th class="text-right">Deductions</th>
                                <th class="text-right">Net Salary</th>
                                <th class="text-right">Paid Amount</th>
                                <th class="text-right">Remaining</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($record = mysqli_fetch_assoc($salary_records_result)): ?>
                            <tr>
                                <td><?php echo date('M Y', strtotime($record['month_year'] . '-01')); ?> (<?php echo $record['month_year']; ?>)</td>
                                <td class="text-right"><?php echo formatCurrency($record['basic_salary']); ?></td>
                                <td class="text-right"><?php echo formatCurrency($record['allowances']); ?></td>
                                <td class="text-right"><?php echo formatCurrency($record['deductions']); ?></td>
                                <td class="text-right"><?php echo formatCurrency($record['net_salary']); ?></td>
                                <td class="text-right"><?php echo formatCurrency($record['paid_amount']); ?></td>
                                <td class="text-right"><?php echo formatCurrency($record['remaining_amount']); ?></td>
                                <td class="text-center">
                                    <?php if($record['status'] == 'paid'): ?>
                                        <span class="salary-status-paid"><i class="fas fa-check-circle"></i> Paid</span>
                                    <?php elseif($record['status'] == 'partial'): ?>
                                        <span class="salary-status-partial"><i class="fas fa-hourglass-half"></i> Partial</span>
                                    <?php else: ?>
                                        <span class="salary-status-pending"><i class="fas fa-clock"></i> Pending</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Recent Transactions -->
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-history mr-2"></i> Recent Transactions (Last 10)
                <a href="employee_ledger.php?id=<?php echo $employee_id; ?>" class="btn btn-sm btn-light float-right">
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
                                <th class="text-right">Debit (Payment/Advance)</th>
                                <th class="text-right">Credit (Salary)</th>
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
                                            case 'SALARY':
                                                $badge_class = 'badge-primary';
                                                break;
                                            case 'PAYMENT':
                                                $badge_class = 'badge-success';
                                                break;
                                            case 'ADVANCE':
                                                $badge_class = 'badge-warning';
                                                break;
                                            case 'ADJUSTMENT':
                                                $badge_class = 'badge-secondary';
                                                break;
                                            default:
                                                $badge_class = 'badge-secondary';
                                        }
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>"><?php echo $transaction['reference_type']; ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                    <td class="text-right text-warning"><?php echo $transaction['debit'] > 0 ? formatCurrency($transaction['debit']) : '-'; ?></td>
                                    <td class="text-right text-success"><?php echo $transaction['credit'] > 0 ? formatCurrency($transaction['credit']) : '-'; ?></td>
                                    <td class="text-right">
                                        <strong>
                                            <?php 
                                            if($transaction['balance'] > 0) {
                                                echo '<span class="text-danger">' . formatCurrency($transaction['balance']) . ' (Payable)</span>';
                                            } elseif($transaction['balance'] < 0) {
                                                echo '<span class="text-success">' . formatCurrency(abs($transaction['balance'])) . ' (Advance)</span>';
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
        <?php if($employee['notes']): ?>
        <div class="card form-card">
            <div class="card-header-custom">
                <i class="fas fa-sticky-note mr-2"></i> Notes
            </div>
            <div class="card-body">
                <p><?php echo nl2br(htmlspecialchars($employee['notes'])); ?></p>
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
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/js/sb-admin-2.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable for monthly records if exists
    if($('#monthlyTable tbody tr').length > 0) {
        $('#monthlyTable').DataTable({
            "order": [[0, "desc"]],
            "pageLength": 10,
            "language": {
                "search": "Search:",
                "lengthMenu": "Show _MENU_ entries",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries"
            }
        });
    }
});
</script>

</body>
</html>

<?php mysqli_close($conn); ?>