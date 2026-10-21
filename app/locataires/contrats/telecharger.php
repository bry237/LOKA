<?php

declare(strict_types=1);

require_once __DIR__ . '/ContratController.php';
require_once dirname(__DIR__, 3) . '/core/ContratPdf.php';

function afficherErreur(string $message, int $code = 404): void
{
    http_response_code($code);

    $pageTitle = 'Document indisponible';
    $activeNav = 'Mon contrat';
    $sidebarFile = 'sidebar-locataire.php';
    $greetingTitle = 'Document indisponible';
    $greetingSubtitle = '';
    $profileName = '';
    $profileRole = 'Locataire';
    $profileInitials = '';
    $extraHead = '<link rel="stylesheet" href="/LOKA/app/administration/mon-agence/dashboard.css">';

    require dirname(__DIR__, 3) . '/layouts/header.php';
    ?>
    <div class="panel">
      <div class="empty-state" style="padding:48px 20px;">
        <div class="empty-state-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg></div>
        <h4>Document indisponible</h4>
        <p><?= htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
        <a href="index.php" class="btn btn-primary" style="margin-top:16px;">Retour à mes contrats</a>
      </div>
    </div>
    <?php
    require dirname(__DIR__, 3) . '/layouts/footer.php';
    exit;
}

try {
    $controller = new ContratController();

    $idContrat = filter_input(
        INPUT_GET,
        'id',
        FILTER_VALIDATE_INT
    );

    if (!$idContrat || $idContrat < 1) {
        afficherErreur('Aucun contrat valide n’a été sélectionné.');
    }

    $contrat = $controller->getContrat($idContrat);

    if (!$contrat) {
        afficherErreur(
            'Ce contrat est introuvable ou vous n’êtes pas autorisé à le consulter.'
        );
    }

    $chemin = trim((string) ($contrat['chemin_fichier_pdf'] ?? ''));

    if ($chemin === '') {
        // Aucun PDF déposé (contrat créé en ligne par l'agence ou le propriétaire) :
        // on génère un résumé du contrat à la volée, comme pour les quittances.
        $locataire = $controller->getLocataire();
        $contrat['locataire_nom'] = $locataire['nom'] ?? '';
        $contrat['locataire_prenom'] = $locataire['prenom'] ?? '';
        $contrat['locataire_email'] = $locataire['email'] ?? '';

        $pdf = ContratPdf::generate($contrat);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="contrat-' . (int) $contrat['id_contrat'] . '.pdf"');
        header('Content-Length: ' . strlen($pdf));
        header('X-Content-Type-Options: nosniff');

        echo $pdf;
        exit;
    }

    $racine = dirname(__DIR__, 3);

    // Accepte un chemin relatif au projet LOKA.
    $cheminFichier = $racine . DIRECTORY_SEPARATOR
        . str_replace(
            ['/', '\\'],
            DIRECTORY_SEPARATOR,
            ltrim($chemin, '/\\')
        );

    if (!is_file($cheminFichier) || !is_readable($cheminFichier)) {
        afficherErreur(
            'Le fichier PDF du contrat est momentanément indisponible. Veuillez contacter votre agence.'
        );
    }

    if (strtolower(pathinfo($cheminFichier, PATHINFO_EXTENSION)) !== 'pdf') {
        afficherErreur('Le document associé à ce contrat n’est pas un PDF valide.');
    }

    // Aucun affichage HTML ne doit précéder l'envoi du fichier.
    header('Content-Type: application/pdf');
    header(
        'Content-Disposition: attachment; filename="contrat-'
        . (int) $contrat['id_contrat']
        . '.pdf"'
    );
    header('Content-Length: ' . filesize($cheminFichier));
    header('X-Content-Type-Options: nosniff');

    readfile($cheminFichier);
    exit;

} catch (Throwable $e) {
    error_log('Téléchargement contrat LOKA : ' . $e->getMessage());

    afficherErreur(
        'Une erreur est survenue lors du téléchargement. Veuillez réessayer.',
        500
    );
}
