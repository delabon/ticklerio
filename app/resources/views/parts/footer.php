    </main>

    <footer class="app-footer">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <span>&copy; <?= date('Y') ?> <?= ucfirst($_ENV['APP_ID']) ?>. All rights reserved.</span>
                <span><i class="fa-solid fa-ticket-simple me-1"></i>Customer Support System</span>
            </div>
        </div>
    </footer>
    </div>
    <script src="<?= asset('app.min.js') ?>"></script>
</body>
</html>
