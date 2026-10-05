<?php
declare(strict_types=1);

/**
 * Coquille HTML commune aux pages admin (doctype, head, sidebar, ouverture du topbar).
 * Variables attendues avant require :
 *   $pageTitle        (string)              — utilisé dans <title>
 *   $activeNav        (string)              — transmis à la sidebar
 *   $greetingTitle    (string)              — transmis à la topbar
 *   $greetingSubtitle (string)              — transmis à la topbar
 *   $extraHead        (string, optionnel)   — balises supplémentaires (CSS/JS de la page)
 */
$pageTitle ??= 'Administration';
$extraHead ??= '';
$sidebarFile ??= 'sidebar.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>LOKA — <?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/LOKA/layouts/admin.css">
<?= $extraHead ?>
</head>
<body>
<div class="app">
  <div class="sidebar-overlay" id="overlay"></div>

  <?php require __DIR__ . '/' . $sidebarFile; ?>

  <main class="main">
    <?php require __DIR__ . '/navbar.php'; ?>
