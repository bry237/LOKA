<?php

declare(strict_types=1);

require_once __DIR__ . '/MaintenanceController.php';

$controller = new MaintenanceController();

$locataire = $controller->getCurrentLocataire();
$requests = $controller->getMyRequests();

if (!$locataire) {
    http_response_code(404);
    exit('Locataire introuvable.');
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function statutMaintenance(string $statut): string
{
    return match ($statut) {
        'OPEN' => 'Ouverte',
        'ASSIGNED' => 'Assignée',
        'IN_PROGRESS' => 'En cours',
        'WAITING' => 'En attente',
        'RESOLVED' => 'Résolue',
        'CLOSED' => 'Clôturée',
        'CANCELLED' => 'Annulée',
        default => $statut
    };
}

function prioriteMaintenance(string $priorite): string
{
    return match ($priorite) {
        'LOW' => 'Faible',
        'MEDIUM' => 'Moyenne',
        'HIGH' => 'Élevée',
        'URGENT' => 'Urgente',
        default => $priorite
    };
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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Maintenance | LOKA</title>

    <link rel="stylesheet" href="../locataires/locataire.css">

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
                <span class="nav-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg></span>
                <span>Accueil</span>
            </a>

            <a href="../locataires/detail.php">
                <span class="nav-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/></svg></span>
                <span>Mon profil</span>
            </a>

            <a href="../locataires/logement.php">
                <span class="nav-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></svg></span>
                <span>Mon logement</span>
            </a>

            <a href="../locataires/recherche.php">
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

            <a href="/LOKA/app/locataires/paiements/paiement.php">
                <span class="nav-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                <span>Mes paiements</span>
            </a>

            <a href="index.php" class="active">
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

            <a href="../locataires/notifications/notification.php">
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


    <!-- =========================
         CONTENU PRINCIPAL
    ========================== -->

    <main class="main-content">

        <!-- HEADER IDENTIQUE À MON LOGEMENT -->

        <header class="topbar">

            <div class="topbar-left">

                <h2>
                    Maintenance
                </h2>

                <p>
                    Suivez vos demandes de maintenance.
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


        <!-- =========================
             PAGE MAINTENANCE
        ========================== -->

        <section class="housing-page">


            <?php if (empty($requests)): ?>

                <!-- =========================
                     AUCUNE DEMANDE
                     MÊME STRUCTURE QUE
                     "AUCUN LOGEMENT"
                ========================== -->

                <div class="housing-card-empty">

                    <div class="housing-card-header">

                        <span class="section-label">
                            MAINTENANCE
                        </span>

                        <h2>
                            Mes demandes
                        </h2>

                        <p>
                            Retrouvez ici toutes les informations
                            concernant vos demandes de maintenance.
                        </p>

                    </div>


                    <div class="housing-card-divider"></div>


                    <div class="housing-empty-content">

                        <div class="housing-empty-icon">

                            <svg
                                viewBox="0 0 64 64"
                                aria-hidden="true"
                            >

                                <!-- Icône clé / maintenance -->

                                <path
                                    d="M39 12
                                       C31 12 25 18 25 26
                                       C25 29 26 32 28 34
                                       L12 50
                                       C10 52 10 55 12 57
                                       C14 59 17 59 19 57
                                       L35 41
                                       C37 43 40 44 43 44
                                       C51 44 57 38 57 30
                                       C57 22 51 16 43 16"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="3"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />

                                <circle
                                    cx="43"
                                    cy="28"
                                    r="5"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="3"
                                />

                            </svg>

                        </div>


                        <h3>
                            Aucune demande de maintenance
                        </h3>


                        <p>
                            Vous n'avez actuellement aucune demande
                            de maintenance.
                            Signalez un problème dans votre logement
                            pour créer une demande.
                        </p>


                        <a
                            href="ajouter.php"
                            class="btn-primary"
                        >
                            Signaler un problème
                        </a>

                    </div>

                </div>


            <?php else: ?>


                <!-- =========================
                     DEMANDES EXISTANTES
                ========================== -->

                <div class="housing-card">

                    <div class="housing-card-header">

                        <span class="section-label">
                            MAINTENANCE
                        </span>

                        <h2>
                            Mes demandes
                        </h2>

                        <p>
                            Retrouvez ici toutes vos demandes
                            de maintenance.
                        </p>

                    </div>


                    <div class="housing-card-divider"></div>


                    <?php foreach ($requests as $request): ?>

                        <div
                            style="
                                padding: 24px 0;
                                border-bottom: 1px solid #e5e7eb;
                            "
                        >

                            <div
                                style="
                                    display: flex;
                                    justify-content: space-between;
                                    align-items: flex-start;
                                    gap: 20px;
                                "
                            >

                                <div>

                                    <h3
                                        style="
                                            margin: 0 0 8px;
                                            color: #003f46;
                                            font-size: 21px;
                                        "
                                    >

                                        <?= e($request['titre']) ?>

                                    </h3>


                                    <p
                                        style="
                                            margin: 0 0 12px;
                                            color: #64748b;
                                            line-height: 1.6;
                                        "
                                    >

                                        <?= e($request['description']) ?>

                                    </p>


                                    <div
                                        style="
                                            display: flex;
                                            flex-wrap: wrap;
                                            gap: 18px;
                                            color: #64748b;
                                            font-size: 14px;
                                        "
                                    >

                                        <span>
                                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></svg>

                                            <?= e(
                                                $request['logement_titre']
                                                ?? 'Logement'
                                            ) ?>
                                        </span>


                                        <span>
                                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-6-5.686-6-10a6 6 0 0 1 12 0c0 4.314-6 10-6 10Z"/><circle cx="12" cy="11" r="2.5"/></svg>

                                            <?= e(
                                                $request['ville']
                                                ?? ''
                                            ) ?>
                                        </span>


                                        <span>
                                            Statut :

                                            <strong>
                                                <?= e(
                                                    statutMaintenance(
                                                        $request['statut']
                                                    )
                                                ) ?>
                                            </strong>
                                        </span>


                                        <span>
                                            Priorité :

                                            <strong>
                                                <?= e(
                                                    prioriteMaintenance(
                                                        $request['priorite']
                                                    )
                                                ) ?>
                                            </strong>
                                        </span>

                                    </div>

                                </div>


                                <a
                                    href="supprimer.php?id=<?= (int) $request['id_intervention'] ?>"
                                    class="btn-primary"
                                >
                                    Supprimer
                                </a>

                                <a
                                    href="modifier.php?id=<?= (int) $request['id_intervention'] ?>"
                                    class="btn-primary"
                                >
                                    Modifier
                                </a>

                                <a
                                    href="detail.php?id=<?= (int) $request['id_intervention'] ?>"
                                    class="btn-primary"
                                >
                                    Voir le détail
                                </a>

                            </div>

                        </div>

                    <?php endforeach; ?>


                    <!-- DERNIÈRE LIGNE -->

                    <div
                        style="
                            padding-top: 25px;
                        "
                    >

                        <a
                            href="ajouter.php"
                            class="btn-primary"
                        >
                            + Signaler un problème
                        </a>

                    </div>

                </div>

            <?php endif; ?>


        </section>

    </main>

</div>

</body>

</html>