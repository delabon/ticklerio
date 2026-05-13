<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= $_ENV['APP_NAME'] ?></title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="<?= asset('app.min.css') ?>">
    <meta name="csrf-token" content="<?= csrf() ?>">
</head>
<body>
    <div class="page-wrapper">
    <nav class="navbar navbar-expand-lg app-navbar" data-bs-theme="dark">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="fa-solid fa-ticket-simple me-2"></i><?= ucfirst($_ENV['APP_ID']) ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Home</a>
                    </li>
                    <?php if (isLoggedIn()) : ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/account"><i class="fa-regular fa-user me-1"></i>Account</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/tickets"><i class="fa-solid fa-list me-1"></i>Tickets</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/logout"><i class="fa-solid fa-arrow-right-from-bracket me-1"></i>Logout</a>
                        </li>
                    <?php else : ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/login">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/register">Register</a>
                        </li>
                    <?php endif ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="page-content">
