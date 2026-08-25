<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include('../includes/database.php');
include('../includes/txt.php');

$user_name = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'User';

$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'month';

if($filter_type == 'today') {
    $from_date = date('Y-m-d');
    $to_date = date('Y-m-d');
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

$profit_query = "SELECT 
                    sd.product_id,
                    p.product_name,
                    p.product_code,
                    p.purchase_price,
                    SUM(sd.quantity) as total_quantity_sold,
                    AVG(sd.rate) as avg_sale_price,
                    SUM(sd.amount) as total_sale_amount
                 FROM sale_details sd
                 LEFT JOIN sale_master sm ON sd.sale_id = sm.id
                 LEFT JOIN products p ON sd.product_id = p.id
                 WHERE sm.sale_date BETWEEN '$from_date' AND '$to_date'
                 AND sm.status = 1
                 GROUP BY sd.product_id, p.product_name, p.product_code, p.purchase_price";

$profit_result = mysqli_query($conn, $profit_query);

$total_sales_revenue = 0;
$total_cost_of_goods = 0;
$total_profit = 0;
$profit_details = [];

if($profit_result && mysqli_num_rows($profit_result) > 0) {
    while($row = mysqli_fetch_assoc($profit_result)) {
        $quantity = floatval($row['total_quantity_sold']);
        $sale_price = floatval($row['avg_sale_price']);
        $purchase_price = floatval($row['purchase_price']);
        $sale_amount = floatval($row['total_sale_amount']);

        $cost_of_goods = $quantity * $purchase_price;
        $profit_per_product = $sale_amount - $cost_of_goods;

        $profit_details[] = [
            'product_id' => $row['product_id'],
            'product_code' => $row['product_code'],
            'product_name' => $row['product_name'],
            'quantity' => $quantity,
            'sale_price' => $sale_price,
            'purchase_price' => $purchase_price,
            'sale_amount' => $sale_amount,
            'cost_of_goods' => $cost_of_goods,
            'profit' => $profit_per_product
        ];

        $total_sales_revenue += $sale_amount;
        $total_cost_of_goods += $cost_of_goods;
        $total_profit += $profit_per_product;
    }
}

$other_income_query = "SELECT COALESCE(SUM(debit), 0) as other_income 
                       FROM cash_book 
                       WHERE reference_type = 'ADJUSTMENT' 
                       AND debit > 0
                       AND date BETWEEN '$from_date' AND '$to_date'";
$other_income_result = mysqli_query($conn, $other_income_query);
if($other_income_result && mysqli_num_rows($other_income_result) > 0) {
    $other_income_row = mysqli_fetch_assoc($other_income_result);
    $other_income = floatval($other_income_row['other_income']);
} else {
    $other_income = 0;
}

$total_income = $total_sales_revenue + $other_income;

$salary_expense_query = "SELECT COALESCE(SUM(credit), 0) as salary_expense 
                         FROM cash_book 
                         WHERE reference_type = 'SALARY' 
                         AND credit > 0
                         AND date BETWEEN '$from_date' AND '$to_date'";
$salary_expense_result = mysqli_query($conn, $salary_expense_query);
if($salary_expense_result && mysqli_num_rows($salary_expense_result) > 0) {
    $salary_row = mysqli_fetch_assoc($salary_expense_result);
    $salary_expense = floatval($salary_row['salary_expense']);
} else {
    $salary_expense = 0;
}

$employee_salary_query = "SELECT COALESCE(SUM(amount), 0) as employee_salary 
                          FROM employee_payments 
                          WHERE payment_date BETWEEN '$from_date' AND '$to_date'";
$employee_salary_result = mysqli_query($conn, $employee_salary_query);
if($employee_salary_result && mysqli_num_rows($employee_salary_result) > 0) {
    $emp_salary_row = mysqli_fetch_assoc($employee_salary_result);
    $employee_salary = floatval($emp_salary_row['employee_salary']);
} else {
    $employee_salary = 0;
}

$total_salary_expense = $salary_expense + $employee_salary;

$expenses_query = "SELECT COALESCE(SUM(amount), 0) as total_expenses 
                   FROM expenses 
                   WHERE expense_date BETWEEN '$from_date' AND '$to_date'";
$expenses_result = mysqli_query($conn, $expenses_query);
if($expenses_result && mysqli_num_rows($expenses_result) > 0) {
    $expenses_row = mysqli_fetch_assoc($expenses_result);
    $total_expenses = floatval($expenses_row['total_expenses']);
} else {
    $total_expenses = 0;
}

$other_expenses_query = "SELECT COALESCE(SUM(credit), 0) as other_expenses 
                         FROM cash_book 
                         WHERE reference_type NOT IN ('SALARY', 'EXPENSE', 'PURCHASE', 'SALE', 'CUSTOMER_RECEIPT', 'OPENING')
                         AND credit > 0
                         AND date BETWEEN '$from_date' AND '$to_date'";
$other_expenses_result = mysqli_query($conn, $other_expenses_query);
if($other_expenses_result && mysqli_num_rows($other_expenses_result) > 0) {
    $other_row = mysqli_fetch_assoc($other_expenses_result);
    $other_expenses = floatval($other_row['other_expenses']);
} else {
    $other_expenses = 0;
}

$total_expenses_amount = $total_salary_expense + $total_expenses + $other_expenses;

$gross_profit = $total_profit;
$net_profit = $gross_profit + $other_income - $total_expenses_amount;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profit &amp; Loss Report</title>
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

        /* ===== Statement Table ===== */
        .stmt-section td {
            background: #1e7e34;
            color: #fff;
            font-weight: 700;
            letter-spacing: 1px;
            border: 1px solid #166d2e;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .stmt-subtotal td {
            background: #e8f5e9;
            font-weight: 700;
            border-top: 2px solid #1e7e34;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .stmt-net td {
            background: #14532d;
            color: #fff;
            font-weight: 800;
            font-size: 13px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .neg { color: #dc3545; font-weight: 700; }
        .pos { color: #28a745; font-weight: 700; }
        .sub-heading {
            margin-top: 18px;
            margin-bottom: 6px;
            font-size: 12px;
            font-weight: 700;
            color: #14532d;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
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
        PROFIT &amp; LOSS REPORT
        <span class="title-bar title-bar-right"></span>
    </div>

    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Period From</span>
            <span class="info-value"><?php echo date('d-m-Y', strtotime($from_date)); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Period To</span>
            <span class="info-value"><?php echo date('d-m-Y', strtotime($to_date)); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Report Filter</span>
            <span class="info-value"><?php echo ucfirst($filter_type); ?></span>
        </div>
    </div>

    <div class="balance-row">
        <div class="balance-item">
            <div class="bal-label">Total Sales</div>
            <div class="bal-value bal-neutral"><?php echo formatCurrency($total_sales_revenue); ?></div>
        </div>
        <div class="balance-item">
            <div class="bal-label">Total Expenses</div>
            <div class="bal-value bal-payable"><?php echo formatCurrency($total_expenses_amount); ?></div>
        </div>
        <div class="balance-item">
            <div class="bal-label">Gross Profit</div>
            <div class="bal-value <?php echo $total_profit >= 0 ? 'bal-receivable' : 'bal-payable'; ?>"><?php echo formatCurrency($total_profit); ?></div>
        </div>
        <div class="balance-item">
            <div class="bal-label">Net <?php echo $net_profit >= 0 ? 'Profit' : 'Loss'; ?></div>
            <div class="bal-value <?php echo $net_profit >= 0 ? 'bal-receivable' : 'bal-payable'; ?>"><?php echo formatCurrency(abs($net_profit)); ?></div>
        </div>
    </div>

    <div class="sub-heading">Profit &amp; Loss Statement</div>
    <table class="list-table">
        <thead>
            <tr>
                <th width="70%">Particulars</th>
                <th width="30%" class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr class="stmt-section">
                <td>INCOME</td>
                <td class="text-right"></td>
            </tr>
            <tr>
                <td>Sales Revenue</td>
                <td class="text-right"><?php echo formatCurrency($total_sales_revenue); ?></td>
            </tr>
            <tr>
                <td>Other Income</td>
                <td class="text-right"><?php echo formatCurrency($other_income); ?></td>
            </tr>
            <tr class="stmt-subtotal">
                <td>TOTAL INCOME</td>
                <td class="text-right"><?php echo formatCurrency($total_income); ?></td>
            </tr>

            <tr class="stmt-section">
                <td>COST OF GOODS SOLD</td>
                <td class="text-right"></td>
            </tr>
            <tr>
                <td>Opening Inventory</td>
                <td class="text-right">—</td>
            </tr>
            <tr>
                <td>+ Purchases</td>
                <td class="text-right">—</td>
            </tr>
            <tr>
                <td>- Closing Inventory</td>
                <td class="text-right">—</td>
            </tr>
            <tr class="stmt-subtotal">
                <td>COST OF GOODS SOLD</td>
                <td class="text-right"><?php echo formatCurrency($total_cost_of_goods); ?></td>
            </tr>

            <tr class="stmt-section">
                <td>GROSS PROFIT</td>
                <td class="text-right"></td>
            </tr>
            <tr>
                <td>Sales Revenue</td>
                <td class="text-right"><?php echo formatCurrency($total_sales_revenue); ?></td>
            </tr>
            <tr>
                <td>Less: COGS</td>
                <td class="text-right neg"><?php echo formatCurrency($total_cost_of_goods); ?></td>
            </tr>
            <tr class="stmt-subtotal">
                <td>GROSS PROFIT</td>
                <td class="text-right"><?php echo formatCurrency($total_profit); ?></td>
            </tr>

            <tr class="stmt-section">
                <td>EXPENSES</td>
                <td class="text-right"></td>
            </tr>
            <tr>
                <td>Salary Expense</td>
                <td class="text-right"><?php echo formatCurrency($total_salary_expense); ?></td>
            </tr>
            <tr>
                <td>General Expenses</td>
                <td class="text-right"><?php echo formatCurrency($total_expenses); ?></td>
            </tr>
            <tr>
                <td>Other Expenses</td>
                <td class="text-right"><?php echo formatCurrency($other_expenses); ?></td>
            </tr>
            <tr class="stmt-subtotal">
                <td>TOTAL EXPENSES</td>
                <td class="text-right neg"><?php echo formatCurrency($total_expenses_amount); ?></td>
            </tr>

            <tr class="stmt-section">
                <td>NET PROFIT / LOSS</td>
                <td class="text-right"></td>
            </tr>
            <tr>
                <td>Gross Profit</td>
                <td class="text-right"><?php echo formatCurrency($total_profit); ?></td>
            </tr>
            <tr>
                <td>+ Other Income</td>
                <td class="text-right"><?php echo formatCurrency($other_income); ?></td>
            </tr>
            <tr>
                <td>- Total Expenses</td>
                <td class="text-right neg"><?php echo formatCurrency($total_expenses_amount); ?></td>
            </tr>
            <tr class="stmt-net">
                <td>NET <?php echo $net_profit >= 0 ? 'PROFIT' : 'LOSS'; ?></td>
                <td class="text-right"><?php echo formatCurrency(abs($net_profit)); ?></td>
            </tr>
        </tbody>
    </table>

    <div class="sub-heading">Product-wise Profit Analysis</div>
    <table class="list-table">
        <thead>
            <tr>
                <th width="12%">Code</th>
                <th>Product Name</th>
                <th width="8%" class="text-right">Qty</th>
                <th width="12%" class="text-right">Sale Price</th>
                <th width="12%" class="text-right">Purchase Price</th>
                <th width="12%" class="text-right">Total Sale</th>
                <th width="12%" class="text-right">COGS</th>
                <th width="12%" class="text-right">Profit</th>
            </tr>
        </thead>
        <tbody>
            <?php if(!empty($profit_details)): ?>
                <?php foreach($profit_details as $item): ?>
                    <tr>
                        <td class="text-center"><span class="type-badge tb-adjustment"><?php echo htmlspecialchars($item['product_code']); ?></span></td>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td class="text-right"><?php echo number_format($item['quantity'], 2); ?></td>
                        <td class="text-right"><?php echo number_format($item['sale_price'], 2); ?></td>
                        <td class="text-right"><?php echo number_format($item['purchase_price'], 2); ?></td>
                        <td class="text-right"><?php echo number_format($item['sale_amount'], 2); ?></td>
                        <td class="text-right"><?php echo number_format($item['cost_of_goods'], 2); ?></td>
                        <td class="text-right <?php echo $item['profit'] >= 0 ? 'pos' : 'neg'; ?>"><?php echo number_format($item['profit'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8" class="text-center" style="padding:20px;">No sales found for the selected period</td></tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="table-footer">
                <td colspan="5" class="text-right">Totals:</td>
                <td class="text-right"><?php echo number_format($total_sales_revenue, 2); ?></td>
                <td class="text-right"><?php echo number_format($total_cost_of_goods, 2); ?></td>
                <td class="text-right"><?php echo number_format($total_profit, 2); ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="list-footer">
        <div class="generated-info">
            Generated on: <strong><?php echo date('d-m-Y h:i A'); ?></strong><br>
            Generated by: <strong><?php echo htmlspecialchars($user_name); ?></strong><br>
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
