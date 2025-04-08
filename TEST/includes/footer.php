<footer class="footer mt-auto py-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="text-white mb-0"><?php echo APP_NAME; ?></h5>
                <small class="text-muted">Version <?php echo APP_VERSION; ?></small>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-0 text-white">&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
            </div>
        </div>
    </div>
</footer>

<style>
.footer {
    background: linear-gradient(135deg, #1a1a1a 0%, #000000 100%);
    color: white;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.footer a {
    color: #6B73FF;
    text-decoration: none;
    transition: color 0.3s ease;
}

.footer a:hover {
    color: #000DFF;
}

.footer .text-muted {
    color: rgba(255,255,255,0.6) !important;
}

/* Asegurar que el footer siempre esté al final */
html, body {
    height: 100%;
}

body {
    display: flex;
    flex-direction: column;
}

main {
    flex: 1 0 auto;
}

.footer {
    flex-shrink: 0;
}
</style> 