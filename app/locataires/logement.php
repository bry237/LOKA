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

    <title>Mon logement - LOKA</title>

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
                <span class="nav-icon">⌂</span>
                <span>Accueil</span>
            </a>

            <a href="detail.php">
                <span class="nav-icon">👤</span>
                <span>Mon profil</span>
            </a>

            <a href="logement.php" class="active">
                <span class="nav-icon">🏠</span>
                <span>Mon logement</span>
            </a>

            <a href="recherche.php">
                <span>🔎</span>
                Recherche
            </a>

            <a href="/LOKA/app/locataires/contrats/index.php">

                <span class="nav-icon">
                    📄
                </span>

                <span>
                    Mon contrat
                </span>

            <a href="paiements/paiement.php">
                <span class="nav-icon">💳</span>
                <span>Mes paiements</span>
            </a>

            <a href="../maintenance/index.php">
                <span class="nav-icon">🔧</span>
                <span>Maintenance</span>
            </a>

            <a href="../documents/index.php">
                <span class="nav-icon">📁</span>
                <span>Documents</span>
            </a>

            <a href="/LOKA/app/locataires/quittances/index.php"
               >
                <span class="menu-icon">🧾</span>
                <span>Quittances</span>
            </a>

            <a href="notifications/notification.php">
                <span class="nav-icon">🔔</span>
                <span>Notifications</span>
            </a>

        </nav>

        <div class="sidebar-bottom">

            <a href="/LOKA/app/authentification/deconnexion.php">
                <span class="menu-icon">↪</span>
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
                            📍
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