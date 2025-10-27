    </main>
    
    <?php if (isLoggedIn()): ?>
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>FacturePro</h3>
                    <p>Solution de facturation professionnelle</p>
                </div>
                <div class="footer-section">
                    <h3>Contact</h3>
                    <p>support@facturepro.fr</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> FacturePro. Tous droits réservés.</p>
            </div>
        </div>
    </footer>
    <?php endif; ?>
    
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
</body>
</html>