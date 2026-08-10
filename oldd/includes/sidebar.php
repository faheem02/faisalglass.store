<?php
/**
 * Sidebar Navigation
 * Faysal Glass And Aluminium Centre
 * 
 * This file contains the complete sidebar menu structure
 * DO NOT CHANGE THE MENU HIERARCHY
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

// Include txt.php for software name if not already included
if (!isset($software_short_name)) {
    include_once('txt.php');
}

// Get user info from session
$user_full_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'User';
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'staff';
?>

<!-- Sidebar -->
<ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar" style="background: linear-gradient(180deg, #0d3b1f 0%, #0a2e18 100%);">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="../dashboard/dashboard.php">
        <div class="sidebar-brand-icon">
            <i class="fas fa-glass-cheers"></i>
        </div>
        <div class="sidebar-brand-text mx-2"><?php echo isset($software_short_name) ? $software_short_name : 'Faisal Glass'; ?></div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Nav Item - Dashboard -->
    <li class="nav-item">
        <a class="nav-link" href="../dashboard/dashboard.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <!-- ===================================================== -->
    <!-- QUOTATION MODULE - NEW (Added before Sale) -->
    <!-- ===================================================== -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseQuotation" aria-expanded="true" aria-controls="collapseQuotation">
            <i class="fas fa-fw fa-file-alt"></i>
            <span>Quotation</span>
        </a>
        <div id="collapseQuotation" class="collapse" aria-labelledby="headingQuotation" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="../quotations/add_quotation.php">Add Quotation</a>
                <a class="collapse-item" href="../quotations/view_quotation.php">View Quotations</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - Sale -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseSale" aria-expanded="true" aria-controls="collapseSale">
            <i class="fas fa-fw fa-shopping-cart"></i>
            <span>Sale</span>
        </a>
        <div id="collapseSale" class="collapse" aria-labelledby="headingSale" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="../sales/add_sale.php">Add Sale</a>
                <a class="collapse-item" href="../sales/view_invoice.php">View Sale Invoice</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - Purchase -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePurchase" aria-expanded="true" aria-controls="collapsePurchase">
            <i class="fas fa-fw fa-boxes"></i>
            <span>Purchase</span>
        </a>
        <div id="collapsePurchase" class="collapse" aria-labelledby="headingPurchase" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="../purchases/add_purchase.php">Add Purchase</a>
                <a class="collapse-item" href="../purchases/view_invoice.php">View Purchase Invoice</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - Product -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseProduct" aria-expanded="true" aria-controls="collapseProduct">
            <i class="fas fa-fw fa-box"></i>
            <span>Product</span>
        </a>
        <div id="collapseProduct" class="collapse" aria-labelledby="headingProduct" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="../products/addproducts.php">Add Opening Stock</a>
                <a class="collapse-item" href="../products/addproduct.php">View Product List</a>
                <a class="collapse-item" href="../products/category.php">Add Category</a>
                <a class="collapse-item" href="../products/company.php">Add Company</a>
                <a class="collapse-item" href="../products/units.php">Add Units</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - Customer Ledger -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseCustomer" aria-expanded="true" aria-controls="collapseCustomer">
            <i class="fas fa-fw fa-users"></i>
            <span>Customer Ledger</span>
        </a>
        <div id="collapseCustomer" class="collapse" aria-labelledby="headingCustomer" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="../customers/add_customer.php">Add Customer Ledgers</a>
                <a class="collapse-item" href="../customers/view_customer.php">View Customer Ledgers</a>
                <a class="collapse-item" href="../customers/receiving_amount.php">Receiving Amount</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - Supplier Ledger -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseSupplier" aria-expanded="true" aria-controls="collapseSupplier">
            <i class="fas fa-fw fa-truck"></i>
            <span>Supplier Ledger</span>
        </a>
        <div id="collapseSupplier" class="collapse" aria-labelledby="headingSupplier" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="../suppliers/supplier.php">Add Supplier Ledgers</a>
                <a class="collapse-item" href="../suppliers/supplier_view.php">View Supplier Ledgers</a>
                <a class="collapse-item" href="../suppliers/paid_amount.php">Paid Amount</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - Salary Ledger -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseSalary" aria-expanded="true" aria-controls="collapseSalary">
            <i class="fas fa-fw fa-money-bill-wave"></i>
            <span>Salary Ledger</span>
        </a>
        <div id="collapseSalary" class="collapse" aria-labelledby="headingSalary" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="../employees/add_employee.php">Add Employee</a>
                <a class="collapse-item" href="../employees/view_ledger.php">View Employee Ledger</a>
                <a class="collapse-item" href="../employees/paid_amount.php">Paid Amount</a>
                <a class="collapse-item" href="../employees/payable_amount.php">Payable Amount</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - Expense -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseExpense" aria-expanded="true" aria-controls="collapseExpense">
            <i class="fas fa-fw fa-chart-line"></i>
            <span>Expense</span>
        </a>
        <div id="collapseExpense" class="collapse" aria-labelledby="headingExpense" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="../expenses/add_expense_head.php">Add Expense Head</a>
                <a class="collapse-item" href="../expenses/view_by_head.php">View Expense By Head</a>
            </div>
        </div>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading - Cash & Bank -->
    <div class="sidebar-heading">Cash & Bank</div>

    <!-- Nav Item - Cash / Bank -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseCashBank" aria-expanded="true" aria-controls="collapseCashBank">
            <i class="fas fa-fw fa-university"></i>
            <span>Cash / Bank</span>
        </a>
        <div id="collapseCashBank" class="collapse" aria-labelledby="headingCashBank" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="../accounts/cashbook.php">Cash Book</a>
                <?php
                $bank_sidebar_query = "SELECT id, bank_name, account_number FROM bank_accounts WHERE status = 1 ORDER BY bank_name ASC";
                $bank_sidebar_result = @mysqli_query($conn, $bank_sidebar_query);
                if($bank_sidebar_result && mysqli_num_rows($bank_sidebar_result) > 0):
                    while($b = mysqli_fetch_assoc($bank_sidebar_result)):
                ?>
                <a class="collapse-item" href="../accounts/bank_ledger.php?id=<?php echo $b['id']; ?>">
                    <i class="fas fa-university fa-fw mr-1"></i> <?php echo htmlspecialchars($b['bank_name']); ?>
                </a>
                <?php
                    endwhile;
                endif;
                ?>
                <a class="collapse-item" href="../accounts/manage_banks.php"><i class="fas fa-cog fa-fw mr-1"></i> Manage Banks</a>
                <a class="collapse-item" href="../accounts/withdraw.php">Withdraw</a>
                <a class="collapse-item" href="../accounts/transfer.php">Transfer</a>
            </div>
        </div>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading - Reports -->
    <div class="sidebar-heading">Reports</div>

    <!-- Nav Item - Reports -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseReports" aria-expanded="true" aria-controls="collapseReports">
            <i class="fas fa-fw fa-chart-pie"></i>
            <span>Reports</span>
        </a>
        <div id="collapseReports" class="collapse" aria-labelledby="headingReports" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="../reports/sale_report.php">Sale Report</a>
                <a class="collapse-item" href="../reports/refund_report.php">Refund Report</a>
                <a class="collapse-item" href="../reports/purchase_report.php">Purchase Report</a>
                <a class="collapse-item" href="../reports/invoice_report.php">Invoice Report</a>
                <a class="collapse-item" href="../reports/inventory_report.php">Inventory Report</a>
                <a class="collapse-item" href="../reports/customer_report.php">Customer Report</a>
                <a class="collapse-item" href="../reports/supplier_report.php">Supplier Report</a>
                <a class="collapse-item" href="../reports/salary_report.php">Salary Report</a>
                <a class="collapse-item" href="../reports/profit_loss.php">Profit & Loss</a>
            </div>
        </div>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">

    <!-- Sidebar Toggler (Sidebar) -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>
</ul>
<!-- End of Sidebar -->

<!-- Content Wrapper -->
<div id="content-wrapper" class="d-flex flex-column">

    <!-- Main Content -->
    <div id="content">

        <!-- Topbar -->
        <nav class="navbar navbar-expand navbar-light topbar mb-4 static-top shadow navbar-top">
            <!-- Sidebar Toggle Button -->
            <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3" style="color: white;">
                <i class="fa fa-bars"></i>
            </button>

            <!-- Topbar Search -->
            <form class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search">
                <div class="input-group">
                    <input type="text" class="form-control bg-light border-0 small" placeholder="Search for products, customers..." aria-label="Search" aria-describedby="basic-addon2" style="color: #1a1a1a; font-weight: 500;">
                    <div class="input-group-append">
                        <button class="btn btn-light" type="button">
                            <i class="fas fa-search fa-sm"></i>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Topbar Navbar -->
            <ul class="navbar-nav ml-auto">
                <!-- Nav Item - User Information -->
                <li class="nav-item dropdown no-arrow">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="mr-2 d-none d-lg-inline text-white text-large font-weight-bold">
                            <i class="fas fa-user-circle mr-1"></i> <?php echo htmlspecialchars($user_full_name); ?>
                        </span>
                        <i class="fas fa-chevron-down text-white small"></i>
                    </a>
                    <!-- Dropdown - User Information -->
                    <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                        <div class="dropdown-header bg-gradient-success text-white rounded-top" style="background: linear-gradient(135deg, #1e7e34, #0066cc);">
                            <i class="fas fa-user-circle fa-2x d-block mb-2"></i>
                            <strong><?php echo htmlspecialchars($user_full_name); ?></strong>
                            <br>
                            <small><i class="fas fa-tag"></i> <?php echo ucfirst($user_role); ?></small>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#">
                            <i class="fas fa-user fa-sm fa-fw mr-2 text-success"></i>
                            My Profile
                        </a>
                        <a class="dropdown-item" href="#">
                            <i class="fas fa-cogs fa-sm fa-fw mr-2 text-primary"></i>
                            Settings
                        </a>
                        <a class="dropdown-item" href="#">
                            <i class="fas fa-clock fa-sm fa-fw mr-2 text-info"></i>
                            Activity Log
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="#" data-toggle="modal" data-target="#logoutModal">
                            <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2"></i>
                            Logout
                        </a>
                    </div>
                </li>
            </ul>
        </nav>

        <!-- Logout Modal -->
        <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Ready to Leave?</h5>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <i class="fas fa-question-circle text-warning mr-2"></i> 
                        Are you sure you want to logout? Any unsaved progress will be lost.
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <a class="btn btn-danger" href="../logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>