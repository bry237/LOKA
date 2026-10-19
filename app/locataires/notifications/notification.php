<?php

declare(strict_types=1);

require_once __DIR__ . '/NotificationController.php';
require_once dirname(__DIR__, 3) . '/core/Authorization.php';

$controller = new NotificationController();

/*
 * Utilisateur connecté
 */
$user = Authorization::requireRole('Locataire');

/*
 * Notifications
 */
$notifications = $controller->getMyNotifications();
$unreadCount = $controller->getUnreadCount();

/*
 * Informations utilisateur pour l'en-tête
 */
$prenom = (string) ($user['prenom'] ?? '');
$nom = (string) ($user['nom'] ?? '');

$initiales = '';

if ($prenom !== '') {
    $initiales .= mb_strtoupper(mb_substr($prenom, 0, 1));
}

if ($nom !== '') {
    $initiales .= mb_strtoupper(mb_substr($nom, 0, 1));
}

if ($initiales === '') {
    $initiales = 'L';
}


/* =========================================================
   FONCTIONS
========================================================= */

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
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

    return date('d/m/Y à H:i', $timestamp);
}


function statutLabel(string $statut): string
{
    return match ($statut) {
        'READ'    => 'Lue',
        'SENT'    => 'Envoyée',
        'PENDING' => 'En attente',
        'FAILED'  => 'Échec',
        default   => $statut,
    };
}


function statutClass(string $statut): string
{
    return match ($statut) {
        'READ'    => 'read',
        'SENT'    => 'sent',
        'PENDING' => 'pending',
        'FAILED'  => 'failed',
        default   => '',
    };
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Notifications | LOKA</title>

    <link rel="stylesheet" href="notification.css">

    <style>

        /* =====================================================
           CONTENU NOTIFICATIONS
        ===================================================== */

        .notifications-page {
            width: 100%;
            padding: 40px;
        }


        /* =====================================================
           COMPTEUR
        ===================================================== */

        .notification-counter {
            display: inline-block;
            margin-top: 15px;
            padding: 8px 15px;
            background: #111;
            color: #fff;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
        }


        /* =====================================================
           LISTE
        ===================================================== */

        .notification-list {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }


        /* =====================================================
           CARTE
        ===================================================== */

        .notification-card {
            width: 100%;
            padding: 22px;
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 14px;
            transition: 0.2s ease;
        }

        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 18px rgba(0, 0, 0, 0.07);
        }

        .notification-card.unread {
            border-left: 5px solid #0F8B8D;
            background: #fafafa;
        }


        /* =====================================================
           HAUT DE LA CARTE
        ===================================================== */

        .notification-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
        }

        .notification-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .notification-title h3 {
            margin: 0;
            color: #043F42;
            font-size: 18px;
            font-weight: 700;
        }

        .unread-dot {
            width: 9px;
            height: 9px;
            flex-shrink: 0;
            border-radius: 50%;
            background: #0F8B8D;
            display: inline-block;
        }


        /* =====================================================
           MESSAGE
        ===================================================== */

        .notification-message {
            margin: 15px 0;
            color: #444;
            line-height: 1.6;
            font-size: 15px;
        }


        /* =====================================================
           PIED
        ===================================================== */

        .notification-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            padding-top: 15px;
            border-top: 1px solid #eeeeee;
        }

        .notification-date {
            color: #777;
            font-size: 13px;
        }


        /* =====================================================
           STATUT
        ===================================================== */

        .notification-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 700;
        }

        .notification-status.read {
            background: #eeeeee;
            color: #555;
        }

        .notification-status.sent {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .notification-status.pending {
            background: #fff3cd;
            color: #856404;
        }

        .notification-status.failed {
            background: #f8d7da;
            color: #842029;
        }


        /* =====================================================
           BOUTON
        ===================================================== */

        .notification-action {
            display: inline-block;
            margin-top: 15px;
            padding: 9px 16px;
            border: 1px solid #0F8B8D;
            border-radius: 7px;
            background: #0F8B8D;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: 0.2s ease;
        }

        .notification-action:hover {
            background: #075E63;
            border-color: #075E63;
        }


        /* =====================================================
           AUCUNE NOTIFICATION
        ===================================================== */

        .empty-notifications {
            width: 100%;
            padding: 50px 30px;
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 14px;
            text-align: center;
        }

        .empty-notifications .empty-icon {
            margin-bottom: 15px;
            font-size: 45px;
        }

        .empty-notifications h2 {
            margin-bottom: 10px;
            color: #043F42;
        }

        .empty-notifications p {
            margin: 0;
            color: #777;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 650px) {

            .notifications-page {
                padding: 20px;
            }

            .notification-top,
            .notification-footer {
                flex-direction: column;
                align-items: flex-start;
            }

        }

    </style>

</head>


<body>

<div class="page-container">


    <!-- =====================================================
         SIDEBAR
    ===================================================== -->

    <aside class="sidebar">

        <div class="logo">
            LOKA
        </div>

        <nav>

            <a href="../index.php">
                <span class="nav-icon">⌂</span>
                <span>Accueil</span>
            </a>

            <a href="../detail.php">
                <span class="nav-icon">👤</span>
                <span>Mon profil</span>
            </a>

            <a href="../logement.php">
                <span class="nav-icon">🏠</span>
                <span>Mon logement</span>
            </a>

            <a href="../recherche.php">
                <span class="nav-icon">🔎</span>
                <span>Recherche</span>
            </a>

            <a href="/LOKA/app/locataires/contrats/index.php">

                <span class="nav-icon">
                    📄
                </span>

                <span>
                    Mon contrat
                </span>

            <a href="/LOKA/app/locataires/paiements/paiement.php">
                <span class="nav-icon">💳</span>
                <span>Mes paiements</span>
            </a>

            <a href="../../maintenance/index.php">
                <span class="nav-icon">🔧</span>
                <span>Maintenance</span>
            </a>

            <a href="../../documents/index.php">
                <span class="nav-icon">📁</span>
                <span>Documents</span>
            </a>

            <a href="/LOKA/app/locataires/quittances/index.php"
               >
                <span class="menu-icon">🧾</span>
                <span>Quittances</span>
            </a>

            <a href="notifications.php" class="active">
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


    <!-- =====================================================
         CONTENU PRINCIPAL
    ===================================================== -->

    <main class="main-content">


        <!-- =================================================
             EN-TÊTE IDENTIQUE AUX AUTRES PAGES
        ================================================= -->

        <header class="topbar">

            <div class="topbar-left">

                <h2>
                    Notifications
                </h2>

                <p>
                    Consultez les messages et informations qui vous sont destinés.
                </p>

                <?php if ($unreadCount > 0): ?>

                    <div class="notification-counter">

                        <?= $unreadCount ?>

                        notification<?= $unreadCount > 1 ? 's' : '' ?>

                        non lue<?= $unreadCount > 1 ? 's' : '' ?>

                    </div>

                <?php endif; ?>

            </div>


            <!-- =============================================
                 UTILISATEUR
            ============================================== -->

            <div class="user-info">

                <div class="avatar">
                    <?= e($initiales) ?>
                </div>

                <div class="user-details">

                    <strong>
                        <?= e(trim($prenom . ' ' . $nom)) ?>
                    </strong>

                    <span>
                        Locataire
                    </span>

                </div>

            </div>

        </header>


        <!-- =================================================
             CONTENU
        ================================================= -->

        <section class="notifications-page">


            <?php if (empty($notifications)): ?>

                <div class="empty-notifications">

                    <div class="empty-icon">
                        🔔
                    </div>

                    <h2>
                        Aucune notification
                    </h2>

                    <p>
                        Vous n'avez actuellement aucune notification.
                    </p>

                </div>


            <?php else: ?>


                <section class="notification-list">


                    <?php foreach ($notifications as $notification): ?>

                        <?php

                        $isUnread =
                            ($notification['statut'] ?? '') !== 'READ';

                        $statut =
                            (string) ($notification['statut'] ?? '');

                        ?>


                        <article
                            class="notification-card <?= $isUnread ? 'unread' : '' ?>"
                        >


                            <!-- TITRE -->

                            <div class="notification-top">

                                <div class="notification-title">

                                    <?php if ($isUnread): ?>

                                        <span
                                            class="unread-dot"
                                            title="Notification non lue"
                                        ></span>

                                    <?php endif; ?>


                                    <h3>
                                        <?= e($notification['titre'] ?? 'Notification') ?>
                                    </h3>

                                </div>


                                <span
                                    class="notification-status <?= e(statutClass($statut)) ?>"
                                >
                                    <?= e(statutLabel($statut)) ?>
                                </span>

                            </div>


                            <!-- MESSAGE -->

                            <div class="notification-message">

                                <?= nl2br(
                                    e($notification['message'] ?? '')
                                ) ?>

                            </div>


                            <!-- DATE -->

                            <div class="notification-footer">

                                <span class="notification-date">

                                    <?= dateFr(
                                        $notification['created_at'] ?? null
                                    ) ?>

                                </span>

                            </div>


                            <!-- BOUTON -->

                            <?php if ($isUnread): ?>

                                <a
                                    href="lire-notification.php?id=<?= (int) $notification['id_notification'] ?>"
                                    class="notification-action"
                                >
                                    Lire la notification
                                </a>

                            <?php endif; ?>


                        </article>


                    <?php endforeach; ?>


                </section>


            <?php endif; ?>


        </section>

    </main>

</div>

</body>

</html>