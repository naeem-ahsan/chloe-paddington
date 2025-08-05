<?php
// index.php
?>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Join the waiting list for the Chloe Paddington bag.">
    <meta name="author" content="Chloe Paddington">
    <meta name="generator" content="Hugo 0.101.0">
    <meta name="keywords" content="Chloe, Paddington, bag, waiting list, fashion">
    <title>Chloe Paddington</title>
    <!-- Bootstrap 5 CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-…"
        crossorigin="anonymous">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="./assets/css/style.css">
</head>

<body>
    <!-- Header Section -->
    <section id="chloe-header">
        <div class="container">
            <header class="d-flex justify-content-center py-3">
                <img src="./assets/img/chloe-logo.png" alt="Chloe">
            </header>
        </div>
    </section>
    <!-- Body Section: Form -->
    <section class="chloe-body py-3">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <div class="hero-img">
                        <img class="img-fluid" width="800" height="200" src="./assets/img/chloe-img1.webp" alt="Chloe Paddington Bag">
                    </div>
                    <div class="hero-title py-5">
                        <h1 class="text-center text-uppercase display-3">The <br>Paddington Bag <br>is back</h1>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-4 offset-xl-4 col-lg-6 offset-lg-3 col-md-8 offset-md-2 col-10 offset-1">
                    <div class="chloe-form-wrapper">
                        <form class="chloe-form" action="submit.php" method="post">
                            <div class="mb-4 d-grid gap-3 text-left">
                                <label for="title" class="text-muted">Title</label>
                                <select name="title" id="title" required>
                                    <option value=""></option>
                                    <option>Mrs.</option>
                                    <option>Mr.</option>
                                    <option>Miss</option>
                                </select>
                            </div>
                            <div class="mb-4 d-grid gap-3 text-left">
                                <label for="first_name" class="text-muted">First Name</label>
                                <input type="text" name="first_name" id="first_name" required>
                            </div>
                            <div class="mb-4 d-grid gap-3 text-left">
                                <label for="last_name" class="text-muted">Last Name</label>
                                <input type="text" name="last_name" id="last_name" required>
                            </div>
                            <div class="mb-4 d-grid gap-3 text-left">
                                <label for="phone" class="text-muted">Phone Number</label>
                                <input type="tel" name="phone" id="phone" required>
                            </div>
                            <div class="mb-4 d-grid gap-3 text-left">
                                <label for="email" class="text-muted">Email Address</label>
                                <input type="email" name="email" id="email" required>
                            </div>
                            <div class="mb-4 d-grid gap-3 text-left">
                                <h5 class="text-muted">Select your preferred color</h5>
                                <fieldset>
                                    <?php foreach (['Black', 'Brown', 'Cream', 'Beige'] as $color): ?>
                                        <label class="text-muted">
                                            <input class="me-1" type="checkbox" name="preferred_colors[]" value="<?= htmlspecialchars($color) ?>">
                                            <?= htmlspecialchars($color) ?>
                                        </label>
                                    <?php endforeach; ?>
                                </fieldset>
                            </div>
                            <div class="text-center my-5">
                                <button class="btn btn-dark" type="submit">Join the Waiting List</button>
                            </div>
                            <div class="text-center">
                                <small class="terms text-muted">
                                    I agree that Chloé may collect my personal data to inform me about product deliveries and send Chloé newsletters.
                                </small>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- 2up Image Section -->
    <section class="chloe-bottom py-5">
        <div class="container-fluid">
            <div class="row g-2">
                <div class="col-6 h-100 overflow-hidden">
                    <img src="./assets/img/chloe-img2.webp" class="w-100 h-100 object-fit-cover" alt="Chloe Paddington Bag 1">
                </div>
                <div class="col-6 h-100 overflow-hidden">
                    <img src="./assets/img/chloe-img3.webp" class="w-100 h-100 object-fit-cover" alt="Chloe Paddington Bag 2">
                </div>
            </div>
        </div>
    </section>

    <!-- Bootstrap JS Bundle -->
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-…"
        crossorigin="anonymous">
    </script>
    <!-- Custom JS -->
    <script src="./assets/js/app.js" defer></script>
</body>

</html>