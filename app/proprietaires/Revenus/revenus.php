<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once dirname(__DIR__, 3) . '/core/Database.php';

$user = Authorization::requireRole('Proprietaire');
$pdo = Database::connection();

$idUtilisateur = (int) $user['id_utilisateur'];

/*
|--------------------------------------------------------------------------
| Récupération du propriétaire connecté
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT
        p.id_proprietaire,
        p.nom,
        p.prenom,
        p.email
    FROM proprietaire p
    INNER JOIN utilisateur u ON u.email = p.email
    WHERE u.id_utilisateur = ?
    LIMIT 1
");

$stmt->execute([$idUtilisateur]);
$proprietaire = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$proprietaire) {
    http_response_code(403);
    exit('Propriétaire introuvable.');
}

$idProprietaire = (int) $proprietaire['id_proprietaire'];

/*
|--------------------------------------------------------------------------
| Nombre de biens du propriétaire
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT b.id_bien)
    FROM bien b
    INNER JOIN bien_proprietaire bp
        ON bp.id_bien = b.id_bien
    WHERE bp.id_proprietaire = ?
      AND b.deleted_at IS NULL
");

$stmt->execute([$idProprietaire]);

$nombreBiens = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| Fonctions d'affichage
|--------------------------------------------------------------------------
*/
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function formatMoney(float $amount): string
{
    return number_format($amount, 2, ',', ' ') . ' €';
}

$revenusEncaisses = 0.0;
$revenusEnAttente = 0.0;
$revenusEnRetard = 0.0;

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mes revenus - LOKA</title>

    <!-- CSS PRINCIPAL DU PROPRIETAIRE -->

    <link
        rel="stylesheet"
        href="/LOKA/app/proprietaires/proprietaire.css"
    >


    <!-- CSS SPECIFIQUE AUX REVENUS -->

     <link
        rel="stylesheet"
        href="/LOKA/app/proprietaires/Revenus/revenus.css"
    >


    <!-- POLICE -->

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >
        
</head>

<body>

<div class="dashboard">

    <aside class="sidebar">

        <div class="logo">

            <h2>LOKA</h2>

            <p>Gestion immobilière</p>

        </div>


        <nav>


            <a
                href="/LOKA/app/proprietaires/index.php"
            >

                <i class="fa-solid fa-chart-line"></i>

                Dashboard

            </a>


            <a
                href="/LOKA/app/proprietaires/biens.php"
            >

                <i class="fa-solid fa-building"></i>

                Mes biens

            </a>


            <a
                href="/LOKA/app/proprietaires/Locataires/index.php"
                
            >

                <i class="fa-solid fa-users"></i>

                Mes locataires

            </a>


            <a
                href="/LOKA/app/proprietaires/Contrat/contrat.php"
            >

                <i class="fa-solid fa-file-contract"></i>

                Mes contrats

            </a>


            <a
                href="/LOKA/app/proprietaires/mes_revenus.php"
                class="active"
            >

                <i class="fa-solid fa-wallet"></i>

                Mes revenus

            </a>


            <a
                href="/LOKA/app/proprietaires/Paiements/index.php"
               
            >

                <i class="fa-solid fa-credit-card"></i>

                Paiements

            </a>


            <a
                href="/LOKA/app/proprietaires/quittances.php"
            >

                <i class="fa-solid fa-receipt"></i>

                Quittances

            </a>


            <a
                href="/LOKA/app/proprietaires/documents.php"
            >

                <i class="fa-solid fa-folder-open"></i>

                Documents

            </a>


            <a
                href="/LOKA/app/proprietaires/parametres.php"
            >

                <i class="fa-solid fa-gear"></i>

                Paramètres

            </a>


        </nav>

    </aside>

    <main class="main-content">

        <!-- En-tête -->
        <header class="page-header">

            <div>
                <h1>Mes revenus</h1>

                <p>
                    Consultez les revenus liés à vos biens.
                </p>
            </div>

            <div class="owner-badge">
                <i class="fa-solid fa-user"></i>

                <span>
                    <?= e(trim(($proprietaire['prenom'] ?? '') . ' ' . ($proprietaire['nom'] ?? ''))) ?>
                </span>
            </div>

        </header>


        <!-- Statistiques -->
        <section class="stats-grid">

            <article class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-wallet"></i>
                </div>

                <div class="stat-content">
                    <span class="stat-label">
                        Revenus encaissés
                    </span>

                    <strong>
                        <?= formatMoney($revenusEncaisses) ?>
                    </strong>
                </div>
            </article>


            <article class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-clock"></i>
                </div>

                <div class="stat-content">
                    <span class="stat-label">
                        En attente
                    </span>

                    <strong>
                        <?= formatMoney($revenusEnAttente) ?>
                    </strong>
                </div>
            </article>


            <article class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>

                <div class="stat-content">
                    <span class="stat-label">
                        En retard
                    </span>

                    <strong>
                        <?= formatMoney($revenusEnRetard) ?>
                    </strong>
                </div>
            </article>


            <article class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-building"></i>
                </div>

                <div class="stat-content">
                    <span class="stat-label">
                        Biens concernés
                    </span>

                    <strong>
                        <?= $nombreBiens ?>
                    </strong>
                </div>
            </article>

        </section>


        <!-- Historique -->
        <section class="revenus-card">

            <div class="section-header">

                <div>
                    <h2>Historique des revenus</h2>

                    <p>
                        Retrouvez ici les revenus générés par vos biens.
                    </p>
                </div>

            </div>


            <div class="empty-state">

                <div class="empty-icon">
                    <i class="fa-solid fa-chart-line"></i>
                </div>

                <h3>Aucun revenu affiché pour le moment</h3>

                <p>
                    Le détail des revenus sera affiché ici prochainement.
                </p>

            </div>

        </section>

    </main>

</div>

</body>
</html>