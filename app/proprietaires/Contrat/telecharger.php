<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/ProprietaireController.php';

function afficherErreur(string $message, int $code = 404): void
{
    http_response_code($code);

    $pageTitle = 'Document indisponible';
    $activeNav = 'Mes contrats';
    $sidebarFile = 'sidebar-proprietaire.php';
    $greetingTitle = 'Document indisponible';
    $greetingSubtitle = '';
    $profileName = '';
    $profileRole = 'Propriétaire';
    $profileInitials = '';
    $extraHead = '<link rel="stylesheet" href="/LOKA/app/administration/mon-agence/dashboard.css">';

    require dirname(__DIR__, 3) . '/layouts/header.php';
    ?>
    <div class="panel">
      <div class="empty-state" style="padding:48px 20px;">
        <div class="empty-state-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg></div>
        <h4>Document indisponible</h4>
        <p><?= htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
        <a href="contrat.php" class="btn btn-primary" style="margin-top:16px;">Retour à mes contrats</a>
      </div>
    </div>
    <?php
    require dirname(__DIR__, 3) . '/layouts/footer.php';
    exit;
}

try {
    $controller = new ProprietaireController();

    $idContrat = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$idContrat || $idContrat < 1) {
        afficherErreur('Aucun contrat valide n’a été sélectionné.');
    }

    $contrat = $controller->getContratDetail($idContrat);

    if (!$contrat) {
        afficherErreur('Ce contrat est introuvable ou vous n’êtes pas autorisé à le consulter.');
    }

    $chemin = trim((string) ($contrat['chemin_fichier_pdf'] ?? ''));

    if ($chemin === '') {
        afficherErreur('Le document du contrat n’a pas encore été ajouté par l’agence.');
    }

    $racine = dirname(__DIR__, 3);
    $cheminFichier = $racine . DIRECTORY_SEPARATOR
        . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($chemin, '/\\'));

    if (!is_file($cheminFichier) || !is_readable($cheminFichier)) {
        afficherErreur('Le fichier PDF du contrat est momentanément indisponible. Veuillez contacter votre agence.');
    }

    if (strtolower(pathinfo($cheminFichier, PATHINFO_EXTENSION)) !== 'pdf') {
        afficherErreur('Le document associé à ce contrat n’est pas un PDF valide.');
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="contrat-' . (int) $contrat['id_contrat'] . '.pdf"');
    header('Content-Length: ' . filesize($cheminFichier));
    header('X-Content-Type-Options: nosniff');

    readfile($cheminFichier);
    exit;
} catch (Throwable $e) {
    error_log('Téléchargement contrat propriétaire LOKA : ' . $e->getMessage());
    afficherErreur('Une erreur est survenue lors du téléchargement. Veuillez réessayer.', 500);
}
