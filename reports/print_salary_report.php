<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

// Date filters
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'month';
$employee_filter = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;

// Apply quick filters
if($filter_type == 'today') {
    $from_date = date('Y-m-d');
    $to_date = date('Y-m-d');
} elseif($filter_type == 'yesterday') {
    $from_date = date('Y-m-d', strtotime('-1 day'));
    $to_date = date('Y-m-d', strtotime('-1 day'));
} elseif($filter_type == 'week') {
    $from_date = date('Y-m-d', strtotime('monday this week'));
    $to_date = date('Y-m-d');
} elseif($filter_type == 'month') {
    $from_date = date('Y-m-01');
    $to_date = date('Y-m-d');
} elseif($filter_type == 'year') {
    $from_date = date('Y-01-01');
    $to_date = date('Y-m-d');
}

// Get selected employee name for filter display
$employee_name = 'All Employees';
if($employee_filter > 0) {
    $employee_query = "SELECT employee_name FROM employees WHERE id = $employee_filter";
    $employee_result = mysqli_query($conn, $employee_query);
    $employee_data_row = mysqli_fetch_assoc($employee_result);
    $employee_name = $employee_data_row['employee_name'] ?? 'All Employees';
}

// Get employee salary data
$employee_where = "";
if($employee_filter > 0) {
    $employee_where = " AND id = $employee_filter";
}

$employees_data_query = "SELECT id, employee_code, employee_name, designation, department, 
                         basic_salary, allowances, deductions, net_salary, 
                         opening_balance, balance_type, current_balance, status, joining_date
                         FROM employees 
                         WHERE status = 1 $employee_where
                         ORDER BY employee_name ASC";
$employees_result_data = mysqli_query($conn, $employees_data_query);

// Calculate summaries
$total_employees = 0;
$total_salary_expense = 0;
$total_paid_salary = 0;
$total_outstanding = 0;
$employee_data = [];

while($employee = mysqli_fetch_assoc($employees_result_data)) {
    $employee_id = $employee['id'];
    $net_salary = floatval($employee['net_salary']);
    $current_balance = floatval($employee['current_balance']);
    
    // Get paid salary for the period
    $paid_query = "SELECT COALESCE(SUM(amount), 0) as paid_amount 
                   FROM employee_payments 
                   WHERE employee_id = $employee_id 
                   AND payment_date BETWEEN '$from_date' AND '$to_date'";
    $paid_result = mysqli_query($conn, $paid_query);
    $paid_amount = floatval(mysqli_fetch_assoc($paid_result)['paid_amount']);
    
    // Get salary entries for the period
    $salary_query = "SELECT COALESCE(SUM(net_salary), 0) as salary_amount 
                     FROM employee_salary 
                     WHERE employee_id = $employee_id 
                     AND month_year BETWEEN DATE_FORMAT('$from_date', '%Y-%m') AND DATE_FORMAT('$to_date', '%Y-%m')";
    $salary_result = mysqli_query($conn, $salary_query);
    $salary_amount = floatval(mysqli_fetch_assoc($salary_result)['salary_amount']);
    
    // Calculate payable salary (net salary per month)
    $payable_salary = $net_salary;
    
    $employee_data[] = [
        'id' => $employee_id,
        'employee_code' => $employee['employee_code'],
        'employee_name' => $employee['employee_name'],
        'designation' => $employee['designation'],
        'department' => $employee['department'],
        'basic_salary' => floatval($employee['basic_salary']),
        'allowances' => floatval($employee['allowances']),
        'deductions' => floatval($employee['deductions']),
        'net_salary' => $net_salary,
        'payable_salary' => $payable_salary,
        'paid_salary' => $paid_amount,
        'current_balance' => $current_balance,
        'status' => $employee['status'],
        'joining_date' => $employee['joining_date']
    ];
    
    $total_employees++;
    $total_salary_expense += $payable_salary;
    $total_paid_salary += $paid_amount;
    $total_outstanding += $current_balance;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; margin: 0; background: white; }
            .list-container { margin: 0; box-shadow: none; padding: 0; }
            @page { size: A4 portrait; margin: 12mm; }
            .list-table thead { display: table-header-group; }
            .list-table tr { page-break-inside: avoid; }
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: #eef1f5;
            color: #212529;
            font-size: 12px;
            line-height: 1.6;
        }
        .list-container {
            max-width: 900px;
            margin: 24px auto;
            background: #fff;
            box-shadow: 0 0 24px rgba(0,0,0,0.12);
            border-radius: 6px;
            padding: 26px 30px;
        }

        /* ===== Company Header ===== */
        .company-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            border-bottom: 3px double #1e7e34;
            padding-bottom: 14px;
            margin-bottom: 16px;
        }
        .brand-left { display: flex; align-items: center; gap: 14px; }
        .brand-logo {
            width: 54px;
            height: 54px;
            background: #1e7e34;
            color: #fff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 1px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .brand-name {
            font-size: 21px;
            font-weight: 700;
            color: #14532d;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            line-height: 1.2;
        }
        .brand-tagline {
            font-size: 11px;
            color: #6b7280;
            letter-spacing: 0.5px;
        }
        .company-contact-info {
            text-align: right;
            font-size: 11px;
            color: #374151;
            line-height: 1.8;
        }
        .company-contact-info .contact-line { white-space: nowrap; }
        .company-contact-info i { color: #1e7e34; width: 16px; }

        /* ===== Title ===== */
        .list-title {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            font-size: 16px;
            font-weight: 700;
            color: #14532d;
            letter-spacing: 4px;
            margin: 8px 0 16px;
        }
        .list-title .title-bar {
            flex: 0 0 70px;
            height: 3px;
            border-radius: 2px;
        }
        .title-bar-left { background: linear-gradient(to right, transparent, #1e7e34); }
        .title-bar-right { background: linear-gradient(to left, transparent, #1e7e34); }

        /* ===== Info Grid ===== */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 18px;
        }
        .info-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
            background: #f8faf9;
            border: 1px solid #e5e7eb;
            border-left: 3px solid #1e7e34;
            border-radius: 4px;
            padding: 7px 12px;
        }
        .info-label {
            font-size: 10px;
            font-weight: 700;
            color: #6b7280;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .info-value {
            font-weight: 600;
            color: #111827;
            word-break: break-word;
        }

        /* ===== Balance Row ===== */
        .balance-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 18px;
        }
        .balance-item {
            text-align: center;
            background: #f8faf9;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 10px 8px;
        }
        .balance-item .bal-label {
            font-size: 10px;
            font-weight: 700;
            color: #6b7280;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .balance-item .bal-value {
            font-size: 15px;
            font-weight: 700;
            margin-top: 2px;
        }
        .bal-payable { color: #dc3545; }
        .bal-receivable { color: #28a745; }
        .bal-neutral { color: #111827; }

        /* ===== Table ===== */
        .list-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-top: 4px;
        }
        .list-table th {
            background: #1e7e34;
            color: #fff;
            padding: 7px 6px;
            text-align: center;
            border: 1px solid #166d2e;
            font-weight: 600;
            letter-spacing: 0.4px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .list-table td {
            padding: 5px 6px;
            border: 1px solid #e5e7eb;
            vertical-align: middle;
        }
        .list-table tbody tr:nth-child(even) { background: #f6f9f7; }
        .list-table .text-right { text-align: right; font-variant-numeric: tabular-nums; }
        .list-table .text-center { text-align: center; }
        .type-badge {
            display: inline-block;
            padding: 1px 8px;
            border-radius: 10px;
            font-size: 9.5px;
            font-weight: 600;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .tb-purchase { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
        .tb-payment { background: #d4edda; color: #155724; border: 1px solid #a3d9a5; }
        .tb-opening { background: #e3f2fd; color: #0066cc; border: 1px solid #bbdefb; }
        .tb-adjustment { background: #fff3cd; color: #856404; border: 1px solid #ffe69c; }
        .tb-other { background: #e2e8f0; color: #374151; border: 1px solid #cbd5e1; }
        .table-footer {
            background: #e8f5e9;
            font-weight: 700;
            border-top: 2px solid #1e7e34;
        }
        .table-footer td {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ===== Footer ===== */
        .list-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 22px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
        }
        .generated-info { font-size: 11px; color: #6b7280; }
        .generated-info strong { color: #374151; }
        .signature-block { text-align: center; width: 200px; }
        .sig-line { border-bottom: 1.5px solid #374151; height: 32px; margin-bottom: 4px; }
        .sig-label { font-size: 11px; color: #6b7280; letter-spacing: 0.5px; }

        .action-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            padding: 12px;
            background: rgba(255,255,255,0.96);
            box-shadow: 0 -2px 12px rgba(0,0,0,0.12);
            z-index: 1000;
        }
        .btn-action {
            padding: 10px 24px;
            margin: 0 8px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            color: #fff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }
        .btn-print { background: #1e7e34; }
        .btn-exit { background: #1a56db; }
    </style>
</head>
<body>
<div class="list-container" id="listContent">

    <div class="company-header">
        <div class="brand-left">
            <div class="brand-logo">FG</div>
            <div>
                <div class="brand-name">Faisal Glass &amp; Aluminum Centre</div>
                <div class="brand-tagline">Deals in all kind of glass local &amp; imported</div>
            </div>
        </div>
        <div class="company-contact-info">
            <div class="contact-line"><i class="fas fa-phone-alt"></i> 0321-4186775 &nbsp;&nbsp; <i class="fas fa-mobile-alt"></i> 0322-8701098</div>
            <div class="contact-line"><i class="fas fa-map-marker-alt"></i> Lajna Chowk Collage Road Township Lahore</div>
        </div>
    </div>

    <div class="list-title">
        <span class="title-bar title-bar-left"></span>
        SALARY REPORT
        <span class="title-bar title-bar-right"></span>
    </div>

    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">From Date</span>
            <span class="info-value"><?php echo date('d-m-Y', strtotime($from_date)); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">To Date</span>
            <span class="info-value"><?php echo date('d-m-Y', strtotime($to_date)); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Employee</span>
            <span class="info-value"><?php echo htmlspecialchars($employee_name); ?></span>
        </div>
    </div>

    <div class="balance-row">
        <div class="balance-item">
            <div class="bal-label">Total Employees</div>
            <div class="bal-value bal-neutral"><?php echo $total_employees; ?></div>
        </div>
        <div class="balance-item">
            <div class="bal-label">Total Salary Expense</div>
            <div class="bal-value bal-receivable"><?php echo formatCurrency($total_salary_expense); ?></div>
        </div>
        <div class="balance-item">
            <div class="bal-label">Total Paid This Period</div>
            <div class="bal-value bal-neutral"><?php echo formatCurrency($total_paid_salary); ?></div>
        </div>
        <div class="balance-item">
            <div class="bal-label">Total Outstanding</div>
            <div class="bal-value bal-payable"><?php echo formatCurrency($total_outstanding); ?></div>
        </div>
    </div>

    <table class="list-table">
        <thead>
            <tr>
                <th width="8%">Emp Code</th>
                <th width="14%">Employee Name</th>
                <th width="12%">Designation</th>
                <th width="11%">Department</th>
                <th width="9%">Basic</th>
                <th width="7%">Allow.</th>
                <th width="7%">Deduct.</th>
                <th width="10%">Net Salary</th>
                <th width="10%">Paid</th>
                <th width="12%">Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php if(!empty($employee_data)): ?>
                <?php foreach($employee_data as $employee): ?>
                    <tr>
                        <td class="text-center"><?php echo htmlspecialchars($employee['employee_code']); ?></td>
                        <td><strong><?php echo htmlspecialchars($employee['employee_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($employee['designation'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($employee['department'] ?? '-'); ?></td>
                        <td class="text-right"><?php echo formatCurrency($employee['basic_salary']); ?></td>
                        <td class="text-right"><?php echo formatCurrency($employee['allowances']); ?></td>
                        <td class="text-right"><?php echo formatCurrency($employee['deductions']); ?></td>
                        <td class="text-right"><?php echo formatCurrency($employee['net_salary']); ?></td>
                        <td class="text-right"><?php echo formatCurrency($employee['paid_salary']); ?></td>
                        <td class="text-right">
                            <?php if($employee['current_balance'] > 0): ?>
                                <?php echo formatCurrency($employee['current_balance']); ?> (Payable)
                            <?php elseif($employee['current_balance'] < 0): ?>
                                <?php echo formatCurrency(abs($employee['current_balance'])); ?> (Advance)
                            <?php else: ?>
                                <?php echo formatCurrency(0); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="10" class="text-center" style="padding:20px;">No salary data found for the selected period</td></tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="table-footer">
                <td colspan="4" class="text-right">Totals:</td>
                <td class="text-right"><?php echo formatCurrency(array_sum(array_column($employee_data, 'basic_salary'))); ?></td>
                <td class="text-right"><?php echo formatCurrency(array_sum(array_column($employee_data, 'allowances'))); ?></td>
                <td class="text-right"><?php echo formatCurrency(array_sum(array_column($employee_data, 'deductions'))); ?></td>
                <td class="text-right"><?php echo formatCurrency($total_salary_expense); ?></td>
                <td class="text-right"><?php echo formatCurrency($total_paid_salary); ?></td>
                <td class="text-right"><?php echo formatCurrency($total_outstanding); ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="list-footer">
        <div class="generated-info">
            Generated on: <strong><?php echo date('d-m-Y h:i A'); ?></strong><br>
            Period: <?php echo date('d-m-Y', strtotime($from_date)); ?> to <?php echo date('d-m-Y', strtotime($to_date)); ?>
        </div>
        <div class="signature-block">
            <div class="sig-line"></div>
            <div class="sig-label">Authorized Signature</div>
        </div>
    </div>
</div>

<div class="action-bar no-print">
    <button class="btn-action btn-print" onclick="window.print();"><i class="fas fa-print"></i> Print</button>
    <button class="btn-action btn-exit" id="exitBtn"><i class="fas fa-sign-out-alt"></i> Exit</button>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('#exitBtn').on('click', function() { window.close(); });
});
</script>
</body>
</html>
<?php mysqli_close($conn); ?>
