<?php

declare(strict_types=1);

require_once __DIR__ . '/NotificationController.php';

$controller = new NotificationController();

/*
 * Vérification de l'identifiant de la notification.
 */
$idNotification = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$idNotification || $idNotification <= 0) {
    http_response_code(400);
    exit('Notification invalide.');
}


/*
 * Récupération de la notification.
 */
$notification = $controller->getNotification($idNotification);

if (!$notification) {
    http_response_code(404);
    exit('Notification introuvable.');
}


/*
 * Marque automatiquement la notification comme lue.
 */
if (($notification['statut'] ?? '') !== 'READ') {

    $controller->markAsRead($idNotification);

    /*
     * On recharge la notification afin d'avoir
     * le statut et la date de lecture actualisés.
     */
    $notification = $controller->getNotification($idNotification);

    if (!$notification) {
        http_response_code(404);
        exit('Notification introuvable.');
    }
}


function e(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
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
        <?= e($notification['titre'] ?? 'Notification') ?> - LOKA
    </title>

    <link
        rel="stylesheet"
        href="locataire.css"
    >

    <style>

        .notification-detail-container {
            max-width: 900px;
            margin: 0 auto;
        }

        .notification-detail-header {
            margin-bottom: 30px;
        }

        .notification-detail-header h1 {
            margin-bottom: 8px;
        }

        .notification-detail-header p {
            color: #666;
            margin: 0;
        }

        .notification-detail-card {
            background: #ffffff;
            border: 1px solid #e5e5e5;
            border-radius: 12px;
            padding: 30px;
        }

        .notification-detail-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eeeeee;
        }

        .notification-detail-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .notification-detail-title .icon {
            font-size: 28px;
        }

        .notification-detail-title h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }

        .notification-read-badge {
            padding: 6px 12px;
            border-radius: 20px;
            background: #eeeeee;
            color: #555;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .notification-detail-message {
            margin-top: 25px;
            color: #333;
            font-size: 16px;
            line-height: 1.8;
            white-space: normal;
        }

        .notification-detail-meta {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eeeeee;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .notification-detail-meta p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }

        .notification-detail-meta strong {
            color: #222;
        }

        .notification-actions {
            display: flex;
            gap: 12px;
            margin-top: 25px;
        }

        .btn-notification {
            display: inline-block;
            padding: 11px 18px;
            border-radius: 7px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.2s ease;
        }

        .btn-back {
            background: #111;
            color: white;
        }

        .btn-back:hover {
            background: #333;
        }

        @media (max-width: 700px) {

            .notification-detail-top {
                flex-direction: column;
            }

            .notification-actions {
                flex-direction: column;
            }

            .btn-notification {
                text-align: center;
            }

        }

    </style>

</head>

<body>

<div class="page-container">

    <!-- SIDEBAR -->

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

            <a href="logement.php">
                <span class="nav-icon">🏠</span>
                <span>Mon logement</span>
            </a>

            <a href="recherche.php">
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


    <!-- CONTENU -->

    <main class="main-content">

        <div class="notification-detail-container">

            <header class="notification-detail-header">

                <h1>
                    Notification
                </h1>

                <p>
                    Consultez les détails de cette notification.
                </p>

            </header>


            <section class="notification-detail-card">

                <div class="notification-detail-top">

                    <div class="notification-detail-title">

                        <span class="icon">
                            🔔
                        </span>

                        <h2>
                            <?= e($notification['titre'] ?? 'Notification') ?>
                        </h2>

                    </div>

                    <span class="notification-read-badge">
                        Lue
                    </span>

                </div>


                <div class="notification-detail-message">

                    <?= nl2br(
                        e($notification['message'] ?? '')
                    ) ?>

                </div>


                <div class="notification-detail-meta">

                    <p>
                        <strong>Date de création :</strong>
                        <?= dateFr($notification['created_at'] ?? null) ?>
                    </p>

                    <p>
                        <strong>Date de lecture :</strong>
                        <?= dateFr($notification['date_lecture'] ?? null) ?>
                    </p>

                    <p>
                        <strong>Type :</strong>
                        <?= e($notification['type'] ?? 'INTERNAL') ?>
                    </p>

                </div>


                <div class="notification-actions">

                    <a
                        href="notifications.php"
                        class="btn-notification btn-back"
                    >
                        ← Retour aux notifications
                    </a>

                </div>

            </section>

        </div>

    </main>

</div>

</body>

</html>