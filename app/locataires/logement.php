<?php

declare(strict_types=1);

require_once __DIR__ . '/LocataireController.php';

$controller = new LocataireController();

$locataire = $controller->getCurrentLocataire();
$logement = $controller->getCurrentHousing();

if (!$locataire) {
    http_response_code(404);
    exit('Locataire introuvable.');
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function money($value): string
{
    if ($value === null || $value === '') {
        return '—';
    }

    return number_format((float) $value, 2, ',', ' ') . ' €';
}

function dateFr(?string $date): string
{
    if (!$date) {
        return '—';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return e($date);
    }

    return date('d/m/Y', $timestamp);
}

$prenom = e($locataire['prenom'] ?? '');
$nom = e($locataire['nom'] ?? '');

$initiales = '';

if ($prenom !== '') {
    $initiales .= strtoupper(substr($prenom, 0, 1));
}

if ($nom !== '') {
    $initiales .= strtoupper(substr($nom, 0, 1));
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mon logement | LOKA</title>

    <link rel="stylesheet" href="locataire.css">

</head>

<body>

<div class="page-container">

    <aside class="sidebar">

        <div class="logo">
            LOKA
        </div>

        <nav>

            <a href="index.php">
                <span class="nav-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg></span>
                <span>Accueil</span>
            </a>

            <a href="detail.php">
                <span class="nav-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/></svg></span>
                <span>Mon profil</span>
            </a>

            <a href="logement.php" class="active">
                <span class="nav-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></svg></span>
                <span>Mon logement</span>
            </a>

            <a href="recherche.php">
                <span><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></span>
                Recherche
            </a>

            <a href="/LOKA/app/locataires/contrats/index.php">

                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg>
                </span>

                <span>
                    Mon contrat
                </span>
            </a>

            <a href="paiements/paiement.php">
                <span class="nav-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                <span>Mes paiements</span>
            </a>

            <a href="../maintenance/index.php">
                <span class="nav-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94Z"/></svg></span>
                <span>Maintenance</span>
            </a>

            <a href="../documents/index.php">
                <span class="nav-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/></svg></span>
                <span>Documents</span>
            </a>

            <a href="/LOKA/app/locataires/quittances/index.php"
               >
                <span class="menu-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/><path d="M9 7h7M9 11h5M9 15h3"/></svg></span>
                <span>Quittances</span>
            </a>

            <a href="notifications/notification.php">
                <span class="nav-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></span>
                <span>Notifications</span>
            </a>

        </nav>

        <div class="sidebar-bottom">

            <a href="/LOKA/app/authentification/deconnexion.php">
                <span class="menu-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg></span>
                <span>Déconnexion</span>
            </a>

        </div>

    </aside>

    <main class="main-content">

        <header class="topbar">

            <div class="topbar-left">

                <h2>
                    Mon logement
                </h2>

                <p>
                    Consultez les informations concernant votre logement.
                </p>

            </div>

            <div class="user-info">

                <div class="avatar">
                    <?= $initiales ?>
                </div>

                <div class="user-details">

                    <strong>
                        <?= $prenom . ' ' . $nom ?>
                    </strong>

                    <span>
                        Locataire
                    </span>

                </div>

            </div>

        </header>

        <section class="housing-page">

            <?php if (!$logement): ?>

                <div class="housing-card-empty">

                    <div class="housing-card-header">

                        <span class="section-label">
                            MON LOGEMENT
                        </span>

                        <h2>
                            Mon logement
                        </h2>

                        <p>
                            Retrouvez ici les informations concernant votre logement.
                        </p>

                    </div>

                    <div class="housing-card-divider"></div>

                    <div class="housing-empty-content">

                        <div class="housing-empty-icon">

                            <svg viewBox="0 0 64 64" aria-hidden="true">

                                <path
                                    d="M8 30L32 10L56 30"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="3"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />

                                <path
                                    d="M14 27V54H50V27"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="3"
                                    stroke-linejoin="round"
                                />

                                <path
                                    d="M26 54V38H38V54"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="3"
                                    stroke-linejoin="round"
                                />

                            </svg>

                        </div>

                        <h3>
                            Aucun logement pour le moment
                        </h3>

                        <p>
                            Vous n'avez pas encore de logement associé à votre profil.
                            Les informations de votre logement apparaîtront ici
                            une fois celui-ci associé à votre contrat.
                        </p>

                        <a href="recherche.php" class="btn-primary">
                            Rechercher un logement
                        </a>

                    </div>

                </div>

            <?php else: ?>

                <div class="housing-card">

                    <div class="housing-card-header">

                        <span class="section-label">
                            MON LOGEMENT
                        </span>

                        <div class="housing-title-row">

                            <div>

                                <h2>
                                    <?= e($logement['titre']) ?>
                                </h2>

                                <p>
                                    <?= e($logement['type_bien']) ?>
                                    · Référence <?= e($logement['reference']) ?>
                                </p>

                            </div>

                            <span class="housing-status">
                                Logement occupé
                            </span>

                        </div>

                    </div>

                    <div class="housing-card-divider"></div>

                    <div class="housing-address">

                        <div class="housing-info-icon">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-6-5.686-6-10a6 6 0 0 1 12 0c0 4.314-6 10-6 10Z"/><circle cx="12" cy="11" r="2.5"/></svg>
                        </div>

                        <div>

                            <span class="housing-info-label">
                                ADRESSE
                            </span>

                            <strong>
                                <?= e($logement['ligne1']) ?>
                            </strong>

                            <?php if (!empty($logement['ligne2'])): ?>

                                <span>
                                    <?= e($logement['ligne2']) ?>
                                </span>

                            <?php endif; ?>

                            <span>
                                <?= e($logement['code_postal']) ?>
                                <?= e($logement['ville']) ?>
                                <?php if (!empty($logement['pays'])): ?>
                                    · <?= e($logement['pays']) ?>
                                <?php endif; ?>
                            </span>

                        </div>

                    </div>

                    <div class="housing-details-grid">

                        <div class="housing-detail">

                            <span>
                                TYPE DE BIEN
                            </span>

                            <strong>
                                <?= e($logement['type_bien']) ?>
                            </strong>

                        </div>

                        <div class="housing-detail">

                            <span>
                                SURFACE
                            </span>

                            <strong>
                                <?= number_format(
                                    (float) $logement['surface'],
                                    2,
                                    ',',
                                    ' '
                                ) ?> m²
                            </strong>

                        </div>

                        <div class="housing-detail">

                            <span>
                                PIÈCES
                            </span>

                            <strong>
                                <?= e((string) $logement['nombre_pieces']) ?>
                            </strong>

                        </div>

                        <div class="housing-detail">

                            <span>
                                ÉTAGE
                            </span>

                            <strong>
                                <?= $logement['etage'] !== null
                                    ? e((string) $logement['etage'])
                                    : '—'
                                ?>
                            </strong>

                        </div>

                    </div>

                </div>

                <div class="housing-columns">

                    <div class="housing-card">

                        <div class="housing-card-header">

                            <span class="section-label">
                                LOCATION
                            </span>

                            <h2>
                                Informations financières
                            </h2>

                        </div>

                        <div class="housing-card-divider"></div>

                        <div class="financial-list">

                            <div class="financial-row">

                                <span>
                                    Loyer
                                </span>

                                <strong>
                                    <?= money($logement['contrat_loyer']) ?>
                                </strong>

                            </div>

                            <div class="financial-row">

                                <span>
                                    Charges
                                </span>

                                <strong>
                                    <?= money($logement['contrat_charges']) ?>
                                </strong>

                            </div>

                            <div class="financial-row financial-total">

                                <span>
                                    Total mensuel
                                </span>

                                <strong>
                                    <?= money(
                                        (float) $logement['contrat_loyer']
                                        + (float) $logement['contrat_charges']
                                    ) ?>
                                </strong>

                            </div>

                            <div class="financial-row">

                                <span>
                                    Dépôt de garantie
                                </span>

                                <strong>
                                    <?= money($logement['depot_garantie']) ?>
                                </strong>

                            </div>

                            <div class="financial-row">

                                <span>
                                    Paiement
                                </span>

                                <strong>
                                    <?= e($logement['frequence_paiement']) ?>
                                </strong>

                            </div>

                        </div>

                    </div>

                    <div class="housing-card">

                        <div class="housing-card-header">

                            <span class="section-label">
                                CONTRAT
                            </span>

                            <h2>
                                Informations du contrat
                            </h2>

                        </div>

                        <div class="housing-card-divider"></div>

                        <div class="contract-info">

                            <div>

                                <span>
                                    Numéro du contrat
                                </span>

                                <strong>
                                    <?= e($logement['numero_contrat']) ?>
                                </strong>

                            </div>

                            <div>

                                <span>
                                    Date d'entrée
                                </span>

                                <strong>
                                    <?= dateFr($logement['date_entree'] ?? $logement['date_debut']) ?>
                                </strong>

                            </div>

                            <div>

                                <span>
                                    Date de fin
                                </span>

                                <strong>
                                    <?= dateFr($logement['date_fin']) ?>
                                </strong>

                            </div>

                            <div>

                                <span>
                                    Jour d'échéance
                                </span>

                                <strong>
                                    <?= e((string) $logement['jour_echeance']) ?>
                                </strong>

                            </div>

                        </div>

                        <a href="../contrats/detail.php?id=<?= (int) $logement['id_contrat'] ?>"
                           class="btn-primary housing-contract-button">
                            Consulter mon contrat
                        </a>

                    </div>

                </div>

                <div class="housing-card housing-description-card">

                    <div class="housing-card-header">

                        <span class="section-label">
                            LOGEMENT
                        </span>

                        <h2>
                            Description
                        </h2>

                    </div>

                    <div class="housing-card-divider"></div>

                    <p class="housing-description">

                        <?php if (!empty($logement['description'])): ?>

                            <?= nl2br(e($logement['description'])) ?>

                        <?php else: ?>

                            Aucune description n'est disponible pour ce logement.

                        <?php endif; ?>

                    </p>

                </div>

            <?php endif; ?>

        </section>

    </main>

</div>

</body>

</html>