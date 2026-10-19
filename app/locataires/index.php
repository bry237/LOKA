<?php

declare(strict_types=1);

require_once __DIR__ . '/LocataireController.php';

$controller = new LocataireController();
$locataire = $controller->getCurrentLocataire();

if (!$locataire) {
    http_response_code(404);
    exit('Profil locataire introuvable.');
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$prenom = e($locataire['prenom']);
$nom = e($locataire['nom']);
$email = e($locataire['email']);
$ville = e($locataire['ville']);
$adresse = e($locataire['adresse']);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace locataire | LOKA</title>

    <link rel="stylesheet" href="./locataire.css">
</head>

<body>

<div class="layout">

    <aside class="sidebar">

        <div class="logo">
            LOKA
        </div>

        <nav>

            <a href="index.php" class="active">
                <span>⌂</span>
                Accueil
            </a>

            <a href="detail.php">
                <span>👤</span>
                Mon profil
            </a>

            <a href="logement.php">
                <span>🏠</span>
                Mon logement
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
                <span>💳</span>
                Mes paiements
            </a>

            <a href="../maintenance/index.php">
                <span>🔧</span>
                Maintenance
            </a>

            <a href="../documents/index.php">
                <span>📁</span>
                Documents
            </a>

            <a href="/LOKA/app/locataires/quittances/index.php"
               >
                <span class="menu-icon">🧾</span>
                <span>Quittances</span>
            </a>

            <a href="notifications/notification.php">
                <span>🔔</span>
                Notifications
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

            <div>
                <h1>Bonjour <?= $prenom ?> 👋</h1>
                <p>Bienvenue dans votre espace locataire</p>
            </div>

            <div class="user-info">

                <div class="avatar">
                    <?= strtoupper(substr($prenom, 0, 1) . substr($nom, 0, 1)) ?>
                </div>

                <div>
                    <strong><?= $prenom . ' ' . $nom ?></strong>
                    <span>Locataire</span>
                </div>

            </div>

        </header>

        <section class="housing-summary">

            <div class="housing-summary-content">

                <div>

                    <span class="section-label">
                        MON LOGEMENT
                    </span>

                    <h2>
                        Mon appartement
                    </h2>

                    <p>
                        <?= $adresse ?>, <?= $ville ?>
                    </p>

                </div>

                <a href="logement.php" class="btn-primary">
                    Voir mon logement
                </a>

            </div>

        </section>

        <section class="profile-section">

            <div class="section-header">

                <div>
                    <span class="section-label">
                        MON ESPACE
                    </span>

                    <h2>
                        Mes services
                    </h2>
                </div>

            </div>

            <div class="services-grid">

                <a href="contrat/index.php" class="service-card">

                    <div class="service-icon">
                        📄
                    </div>

                    <h3>
                        Mon contrat
                    </h3>

                    <p>
                        Consultez votre contrat de location.
                    </p>

                </a>

                <a href="paiements/paiement.php" class="service-card">

                    <div class="service-icon">
                        💳
                    </div>

                    <h3>
                        Mes paiements
                    </h3>

                    <p>
                        Consultez vos paiements et vos reçus.
                    </p>

                </a>

                <a href="../maintenance/index.php" class="service-card">

                    <div class="service-icon">
                        🔧
                    </div>

                    <h3>
                        Maintenance
                    </h3>

                    <p>
                        Signalez et suivez vos demandes d'intervention.
                    </p>

                </a>

                <a href="../documents/index.php" class="service-card">

                    <div class="service-icon">
                        📁
                    </div>

                    <h3>
                        Mes documents
                    </h3>

                    <p>
                        Retrouvez vos documents de location.
                    </p>

                </a>

                <a href="notifications/notification.php" class="service-card">

                    <div class="service-icon">
                        🔔
                    </div>

                    <h3>
                        Notifications
                    </h3>

                    <p>
                        Consultez les informations importantes.
                    </p>

                </a>

                <a href="detail.php" class="service-card">

                    <div class="service-icon">
                        👤
                    </div>

                    <h3>
                        Mon profil
                    </h3>

                    <p>
                        Consultez et modifiez vos informations.
                    </p>

                </a>

            </div>

        </section>

    </main>

</div>

</body>

</html>