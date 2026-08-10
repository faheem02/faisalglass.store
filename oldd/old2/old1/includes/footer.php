</div>
<!-- End of Content Wrapper -->

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<!-- Bootstrap 4 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- SB Admin 2 JS -->
<script src="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/js/sb-admin-2.min.js"></script>

<!-- Custom JavaScript -->
<script>
    $(document).ready(function() {
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
        
        // Add active class to current nav item
        var currentUrl = window.location.pathname;
        $('.sidebar .nav-link').each(function() {
            if (currentUrl.indexOf($(this).attr('href')) !== -1) {
                $(this).addClass('active');
            }
        });
    });
    
    // Function to show loading overlay
    function showLoading() {
        $('#loading-overlay').fadeIn();
    }
    
    // Function to hide loading overlay
    function hideLoading() {
        $('#loading-overlay').fadeOut();
    }
    
    // Function to show toast notification
    function showToast(message, type = 'success') {
        var bgColor = type === 'success' ? '#28a745' : '#dc3545';
        var icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        
        var toast = $(`
            <div style="position: fixed; bottom: 20px; right: 20px; z-index: 9999; 
                        background: ${bgColor}; color: white; padding: 12px 20px; 
                        border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.2);">
                <i class="fas ${icon} mr-2"></i> ${message}
            </div>
        `);
        
        $('body').append(toast);
        setTimeout(function() {
            toast.fadeOut('slow', function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // Function to format currency
    function formatCurrency(amount) {
        return '₨ ' + parseFloat(amount).toFixed(2);
    }
</script>

<!-- Loading Overlay -->
<div id="loading-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
     background: rgba(0,0,0,0.5); z-index: 9998; text-align: center; padding-top: 20%;">
    <div class="spinner-border text-light" style="width: 3rem; height: 3rem;" role="status">
        <span class="sr-only">Loading...</span>
    </div>
    <div style="color: white; margin-top: 10px; font-weight: 500;">Please wait...</div>
</div>

<!-- Footer -->
<div class="footer">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <span>&copy; <?php echo date('Y'); ?> <?php echo $software_name; ?> - All Rights Reserved</span>
                <span class="float-right">Version <?php echo $software_version; ?></span>
            </div>
        </div>
    </div>
</div>

</body>
</html>

<?php
// Close database connection
closeConnection();
?>