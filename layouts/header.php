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

// Chargée tôt (avant le <head>) pour pouvoir surcharger les couleurs de admin.css avec la couleur
// de marque de l'agence, si elle en a une — voir agency-identity.php et core/Color.php. La sidebar
// réutilise ensuite $sidebarAgence sans refaire la requête (voir le garde dans sidebar-agence.php).
if ($sidebarFile === 'sidebar-agence.php' && !isset($sidebarAgence)) {
	require __DIR__ . '/agency-identity.php';
}
$agenceTheme = $sidebarAgence['couleur_accent'] ?? null;
if ($agenceTheme !== null) {
	require_once __DIR__ . '/../core/Color.php';
}
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
<?php if ($agenceTheme !== null): ?>
<style>
  :root{
    --primary:<?= htmlspecialchars($agenceTheme, ENT_QUOTES, 'UTF-8') ?>;
    --dark:<?= htmlspecialchars(Color::darken($agenceTheme, 0.35), ENT_QUOTES, 'UTF-8') ?>;
    --deep:<?= htmlspecialchars(Color::darken($agenceTheme, 0.55), ENT_QUOTES, 'UTF-8') ?>;
  }
</style>
<?php endif; ?>
<?= $extraHead ?>
</head>
<body>
<div class="app">
  <div class="sidebar-overlay" id="overlay"></div>

  <?php require __DIR__ . '/' . $sidebarFile; ?>

  <main class="main">
    <?php require __DIR__ . '/navbar.php'; ?>
