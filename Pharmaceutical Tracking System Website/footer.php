<?php
/**
 * footer.php
 * Reusable Footer Layout
 * Closes structural tags, includes library CDNs (Bootstrap, Chart.js),
 * and links the custom scripts in assets/js/app.js.
 */

$current_page = basename($_SERVER['PHP_SELF']);
?>

<?php if ($current_page !== 'index.php' && $current_page !== 'setup.php'): ?>
        <!-- Footer Branding Bar -->
        <footer class="footer mt-auto py-3 bg-white border-top no-print">
            <div class="container-fluid px-4">
                <div class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-2">
                    <span class="text-muted small">
                        &copy; <?php echo date('Y'); ?> Connaught Government Hospital (Freetown, Sierra Leone). All Rights Reserved.
                    </span>
                    <span class="text-muted small fw-bold">
                        University Database Management System Project
                    </span>
                </div>
            </div>
        </footer>
        
    </div> <!-- Close .main-content -->
</div> <!-- Close .app-wrapper -->
<?php endif; ?>

<!-- Bootstrap 5 JS Bundle (with Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Chart.js for Data Visualizations in Reports -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Custom Main Logic Script -->
<script src="assets/js/app.js"></script>

</body>
</html>
