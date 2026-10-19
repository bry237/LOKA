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
        Détail de la demande - LOKA
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
                    ⌂
                </span>

                <span>
                    Accueil
                </span>

            </a>


            <a href="../locataires/detail.php">

                <span class="nav-icon">
                    👤
                </span>

                <span>
                    Mon profil
                </span>

            </a>


            <a href="../locataires/logement.php">

                <span class="nav-icon">
                    🏠
                </span>

                <span>
                    Mon logement
                </span>

            </a>


            <a href="../locataires/recherche.php">

                <span class="nav-icon">
                    🔎
                </span>

                <span>
                    Recherche
                </span>

            </a>


            <a href="/LOKA/app/locataires/contrats/index.php">

                <span class="nav-icon">
                    📄
                </span>

                <span>
                    Mon contrat
                </span>

            </a>


            <a href="/LOKA/app/locataires/paiements/paiement.php">

                <span class="nav-icon">
                    💳
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
                    🔧
                </span>

                <span>
                    Maintenance
                </span>

            </a>


            <a href="../documents/index.php">

                <span class="nav-icon">
                    📁
                </span>

                <span>
                    Documents
                </span>

            </a>

            <a href="/LOKA/app/locataires/quittances/index.php"
               >
                <span class="menu-icon">🧾</span>
                <span>Quittances</span>
            </a>


            <a href="../locataires/notifications/notification.php">
                <span class="nav-icon">🔔</span>
                <span>Notifications</span>
            </a>

            </a>

        </nav>

        <div class="sidebar-bottom">

            <a href="/LOKA/app/authentification/deconnexion.php">
                <span class="menu-icon">↪</span>
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
                ← Retour à mes demandes
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
                            💬
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
                    ← Retour à mes demandes
                </a>

            </div>


        </section>

    </main>

</div>

</body>

</html>