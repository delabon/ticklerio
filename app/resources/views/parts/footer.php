    <footer class="bg-body-tertiary text-white py-4 mt-5" data-bs-theme="dark">
        <div class="container">
            <div>
                © <?= date('Y') ?> Copyright <?= ucfirst($_ENV['APP_ID']) ?>, All rights reserved.
            </div>
        </div>
    </footer>
    <script src="<?= asset('app.min.js') ?>"></script>
</body>
</html>