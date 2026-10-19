<?php

declare(strict_types=1);

require_once __DIR__ . '/MaintenanceController.php';

$controller = new MaintenanceController();

$idIntervention = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

if ($idIntervention <= 0) {
    header('Location: index.php');
    exit;
}

/*
 * Récupération de l'intervention.
 *
 * Le controller vérifie que l'intervention appartient
 * bien au locataire connecté.
 */
$intervention = $controller->getRequest($idIntervention);

if (!$intervention) {
    header('Location: index.php');
    exit;
}

/*
 * Informations du locataire connecté
 */
$locataire = $controller->getCurrentLocataire();

if (!$locataire) {
    header('Location: index.php');
    exit;
}

/*
 * Commentaires de l'intervention
 */
$comments = $controller->getComments($idIntervention);


/*
 * Helpers d'affichage
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function dateFr(?string $date): string
{
    if (!$date) {
        return '—';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return '—';
    }

    return date('d/m/Y', $timestamp);
}


/*
 * Nom et initiales
 */
$prenom = $locataire['prenom'] ?? '';
$nom = $locataire['nom'] ?? '';

$initiales = strtoupper(
    substr($prenom, 0, 1) .
    substr($nom, 0, 1)
);


/*
 * Libellés
 */
$statuts = [
    'OPEN' => 'Ouverte',
    'IN_PROGRESS' => 'En cours',
    'DONE' => 'Terminée',
    'CLOSED' => 'Clôturée',
    'CANCELLED' => 'Annulée'
];

$priorites = [
    'LOW' => 'Faible',
    'MEDIUM' => 'Moyenne',
    'HIGH' => 'Haute',
    'URGENT' => 'Urgente'
];

$statut = $statuts[$intervention['statut']] ?? $intervention['statut'];
$priorite = $priorites[$intervention['priorite']] ?? $intervention['priorite'];

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Détail de la demande | LOKA
    </title>

    <link
        rel="stylesheet"
        href="../locataires/locataire.css"
    >

    <link
        rel="stylesheet"
        href="maintenance.css"
    >

</head>


<body>

<div class="page-container">


    <!-- =========================
         SIDEBAR
    ========================== -->

    <aside class="sidebar">

        <div class="logo">
            LOKA
        </div>


        <nav>

            <a href="../locataires/index.php">

                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
                </span>

                <span>
                    Accueil
                </span>

            </a>


            <a href="../locataires/detail.php">

                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                </span>

                <span>
                    Mon profil
                </span>

            </a>


            <a href="../locataires/logement.php">

                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></svg>
                </span>

                <span>
                    Mon logement
                </span>

            </a>


            <a href="../locataires/recherche.php">

                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                </span>

                <span>
                    Recherche
                </span>

            </a>


            <a href="/LOKA/app/locataires/contrats/index.php">

                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg>
                </span>

                <span>
                    Mon contrat
                </span>
            </a>

            </a>


            <a href="/LOKA/app/locataires/paiements/paiement.php">

                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </span>

                <span>
                    Mes paiements
                </span>

            </a>


            <a
                href="index.php"
                class="active"
            >

                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94Z"/></svg>
                </span>

                <span>
                    Maintenance
                </span>

            </a>


            <a href="../documents/index.php">

                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/></svg>
                </span>

                <span>
                    Documents
                </span>

            </a>

            <a href="/LOKA/app/locataires/quittances/index.php"
               >
                <span class="menu-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/><path d="M9 7h7M9 11h5M9 15h3"/></svg></span>
                <span>Quittances</span>
            </a>


            <a href="../locataires/notifications/notification.php">
                <span class="nav-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></span>
                <span>Notifications</span>
            </a>

            </a>

        </nav>

        <div class="sidebar-bottom">

            <a href="/LOKA/app/authentification/deconnexion.php">
                <span class="menu-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg></span>
                <span>Déconnexion</span>
            </a>

        </div>

    </aside>


    <!-- =========================
         CONTENU PRINCIPAL
    ========================== -->

    <main class="main-content">


        <!-- TOPBAR -->

        <header class="topbar">

            <div class="topbar-left">

                <h2>
                    Maintenance
                </h2>

                <p>
                    Consultez le détail de votre demande d'intervention.
                </p>

            </div>


            <div class="user-info">

                <div class="avatar">
                    <?= e($initiales) ?>
                </div>

                <div class="user-details">

                    <strong>
                        <?= e($prenom . ' ' . $nom) ?>
                    </strong>

                    <span>
                        Locataire
                    </span>

                </div>

            </div>

        </header>


        <!-- =========================
             PAGE DETAIL
        ========================== -->

        <section class="housing-page">


            <!-- RETOUR -->

            <a
                href="index.php"
                class="maintenance-back-link"
            >
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg> Retour à mes demandes
            </a>


            <!-- =========================
                 CARTE PRINCIPALE
            ========================== -->

            <div class="housing-card maintenance-detail-card">


                <div class="housing-card-header">

                    <span class="section-label">
                        DEMANDE DE MAINTENANCE
                    </span>

                    <h2>
                        <?= e($intervention['titre']) ?>
                    </h2>

                    <p>
                        Demande n°
                        <?= (int) $intervention['id_intervention'] ?>
                    </p>

                </div>


                <div class="housing-card-divider"></div>


                <!-- INFORMATIONS -->

                <div class="maintenance-detail-grid">


                    <div class="maintenance-detail-item">

                        <span>
                            Statut
                        </span>

                        <strong
                            class="maintenance-status maintenance-status-<?= strtolower($intervention['statut']) ?>"
                        >
                            <?= e($statut) ?>
                        </strong>

                    </div>


                    <div class="maintenance-detail-item">

                        <span>
                            Priorité
                        </span>

                        <strong
                            class="maintenance-priority maintenance-priority-<?= strtolower($intervention['priorite']) ?>"
                        >
                            <?= e($priorite) ?>
                        </strong>

                    </div>


                    <div class="maintenance-detail-item">

                        <span>
                            Date de création
                        </span>

                        <strong>
                            <?= dateFr($intervention['created_at']) ?>
                        </strong>

                    </div>


                    <div class="maintenance-detail-item">

                        <span>
                            Date de début
                        </span>

                        <strong>
                            <?= dateFr($intervention['date_debut']) ?>
                        </strong>

                    </div>


                    <div class="maintenance-detail-item">

                        <span>
                            Date de fin
                        </span>

                        <strong>
                            <?= dateFr($intervention['date_fin']) ?>
                        </strong>

                    </div>

                </div>


                <!-- =========================
                     LOGEMENT
                ========================== -->

                <div class="maintenance-section">

                    <span class="section-label">
                        LOGEMENT CONCERNÉ
                    </span>

                    <div class="maintenance-housing-info">

                        <strong>
                            <?= e($intervention['logement_titre']) ?>
                        </strong>

                        <span>
                            Référence :
                            <?= e($intervention['reference']) ?>
                        </span>

                        <span>
                            <?= e($intervention['ligne1']) ?>

                            <?php if (!empty($intervention['ligne2'])): ?>

                                <br>

                                <?= e($intervention['ligne2']) ?>

                            <?php endif; ?>

                        </span>

                        <span>
                            <?= e($intervention['code_postal']) ?>
                            <?= e($intervention['ville']) ?>
                        </span>

                    </div>

                </div>


                <!-- =========================
                     DESCRIPTION
                ========================== -->

                <div class="maintenance-section">

                    <span class="section-label">
                        DESCRIPTION
                    </span>

                    <div class="maintenance-description">

                        <?= nl2br(
                            e($intervention['description'])
                        ) ?>

                    </div>

                </div>


                <!-- =========================
                     COMPTE RENDU
                ========================== -->

                <?php if (!empty($intervention['compte_rendu'])): ?>

                    <div class="maintenance-section">

                        <span class="section-label">
                            COMPTE RENDU
                        </span>

                        <div class="maintenance-description">

                            <?= nl2br(
                                e($intervention['compte_rendu'])
                            ) ?>

                        </div>

                    </div>

                <?php endif; ?>


            </div>


            <!-- =========================
                 COMMENTAIRES
            ========================== -->

            <div class="housing-card maintenance-comments-card">


                <div class="housing-card-header">

                    <span class="section-label">
                        ÉCHANGES
                    </span>

                    <h2>
                        Commentaires
                    </h2>

                    <p>
                        Retrouvez ici les informations échangées concernant cette intervention.
                    </p>

                </div>


                <div class="housing-card-divider"></div>


                <?php if (empty($comments)): ?>

                    <div class="housing-empty-content">

                        <div class="housing-empty-icon">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.4 8.4 0 0 1-8.9 8.4c-1.1 0-2.1-.2-3-.6L3 21l1.8-5.4a8.4 8.4 0 1 1 16.2-4.1Z"/></svg>
                        </div>

                        <h3>
                            Aucun commentaire
                        </h3>

                        <p>
                            Aucun échange n'a encore été ajouté à cette demande.
                        </p>

                    </div>

                <?php else: ?>


                    <div class="maintenance-comments-list">

                        <?php foreach ($comments as $comment): ?>

                            <div class="maintenance-comment">

                                <div class="maintenance-comment-header">

                                    <strong>

                                        <?= e(
                                            trim(
                                                ($comment['prenom'] ?? '') .
                                                ' ' .
                                                ($comment['nom'] ?? '')
                                            )
                                        ) ?>

                                    </strong>

                                    <span>
                                        <?= dateFr($comment['created_at']) ?>
                                    </span>

                                </div>


                                <div class="maintenance-comment-content">

                                    <?= nl2br(
                                        e($comment['contenu'])
                                    ) ?>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>


            </div>


            <!-- BOUTON RETOUR -->

            <div class="maintenance-detail-actions">

                <a
                    href="index.php"
                    class="btn-primary"
                >
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg> Retour à mes demandes
                </a>

            </div>


        </section>

    </main>

</div>

</body>

</html>