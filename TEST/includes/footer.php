<footer class="footer mt-auto py-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="text-white mb-0"><?php echo APP_NAME; ?></h5>
                <small class="text-muted">Version <?php echo APP_VERSION; ?></small>
                <p class="mb-0 text-white">&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
            </div>
        </div>
    </div>
</footer>

<style>
.footer {
    width: 100%;
    background: var(--primary-color);
    color: white;
    box-shadow: 0 4px 20px var(--shadow-color);
    padding: 2rem 0;
    position: relative;
    bottom: 0;
}

.footer .container {
    max-width: auto;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 1rem;
    flex-wrap: wrap;
}

.footer a {
    color: var(--secondary-color);
    text-decoration: none;
    transition: color 0.3s ease;
}

.footer a:hover {
    color: var(--accent-color);
}

.footer .text-muted {
    color: rgba(255,255,255,0.7) !important;
}

.footer h5 {
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.footer p, .footer small {
    margin-bottom: 0;
    font-size: 0.9rem;
}

.footer .col-md-6:last-child {
    text-align: right;
    flex: 1;
}

@media (max-width: 768px) {
    .footer .container {
        flex-direction: column;
        text-align: center;
    }

    .footer .col-md-6:last-child {
        text-align: center;
        flex: none;
    }

    .footer h5, .footer p {
        margin-bottom: 1rem;
    }
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