<footer class="footer mt-auto py-4">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-12">
                <h5 class="text-white mb-2">Real Estate Management System</h5>
                <p class="text-muted mb-1">Version 1.0.0</p>
                <p class="text-white mb-0">&copy; 2025 Real Estate Management System. All rights reserved.</p>
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
    margin-top: auto;
}

.footer .container {
    max-width: auto;
    margin: 0 auto;
    padding: 0 1rem;
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

.footer p {
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

/* Asegurar que el footer siempre esté al final */
html, body {
    height: 100%;
    margin: 0;
    padding: 0;
}

body {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

main {
    flex: 1 0 auto;
}

.footer {
    flex-shrink: 0;
}
</style> 