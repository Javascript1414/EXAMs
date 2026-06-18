<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>NSTI Howrah</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Custom CSS -->
<link rel="stylesheet" href="<?= isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http' ?>://localhost/exams/assets/css/main_index.css">
<link rel="stylesheet" href="<?= isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http' ?>://localhost/exams/assets/css/index-sections.css">

</head>

<body>

<?php
// Include all sections
include __DIR__ . '/sections/navbar.php';
include __DIR__ . '/sections/hero.php';
include __DIR__ . '/sections/carousel.php';
include __DIR__ . '/sections/features.php';
include __DIR__ . '/sections/statistics.php';
include __DIR__ . '/sections/why-choose.php';
include __DIR__ . '/sections/featured-courses.php';
include __DIR__ . '/sections/testimonials.php';
include __DIR__ . '/sections/cta-section.php';
include __DIR__ . '/sections/main-content.php';
include __DIR__ . '/sections/footer.php';
?>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/script.js?v=2"></script>
<script src="scripts/carousel.js?v=2"></script>
<script src="scripts/index-animations.js?v=3"></script>

</body>
</html>