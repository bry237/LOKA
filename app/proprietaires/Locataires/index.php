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

    INNER JOIN utilisateur u
        ON u.email = p.email

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
| Fonctions utilitaires
|--------------------------------------------------------------------------
*/

function e(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}


function formatMoney($value): string
{
    if ($value === null || $value === '') {
        return '—';
    }

    return number_format(
        (float) $value,
        2,
        ',',
        ' '
    ) . ' €';
}


function formatDateFr(?string $date): string
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


function statutContratLabel(?string $statut): string
{
    return match (strtoupper((string) $statut)) {

        'DRAFT' => 'Brouillon',

        'ACTIVE' => 'Actif',

        'EXPIRED' => 'Expiré',

        'RENEWED' => 'Renouvelé',

        'TERMINATED' => 'Terminé',

        'TERMINATED_EARLY' => 'Résilié',

        default => $statut ?: '—',
    };
}


function statutContratClass(?string $statut): string
{
    return match (strtoupper((string) $statut)) {

        'ACTIVE' => 'status-active',

        'DRAFT' => 'status-draft',

        'EXPIRED' => 'status-expired',

        'RENEWED' => 'status-renewed',

        'TERMINATED',
        'TERMINATED_EARLY' => 'status-terminated',

        default => 'status-default',
    };
}


/*
|--------------------------------------------------------------------------
| Récupération des locataires du propriétaire
|--------------------------------------------------------------------------
|
| Le propriétaire n'est PAS directement relié à locataire.
|
| Le lien est :
|
| propriétaire
|      ↓
| bien_proprietaire
|      ↓
| bien
|      ↓
| contrat
|      ↓
| contrat_locataire
|      ↓
| locataire
|
|--------------------------------------------------------------------------
*/

$sql = "

    SELECT

        l.id_locataire,

        l.nom,
        l.prenom,
        l.email,
        l.telephone,

        l.adresse,
        l.code_postal,
        l.ville,
        l.pays,

        l.date_naissance,
        l.profession,

        l.revenu_mensuel,
        l.revenu_mensuel_fourchette,

        l.statut AS statut_locataire,

        cl.titulaire_principal,
        cl.date_entree,
        cl.date_sortie,

        c.id_contrat,
        c.numero AS numero_contrat,

        c.date_debut,
        c.date_fin,

        c.loyer,
        c.charges,

        c.statut AS statut_contrat,

        b.id_bien,
        b.reference AS reference_bien,
        b.titre AS titre_bien

    FROM locataire l

    INNER JOIN contrat_locataire cl
        ON cl.id_locataire = l.id_locataire

    INNER JOIN contrat c
        ON c.id_contrat = cl.id_contrat

    INNER JOIN bien b
        ON b.id_bien = c.id_bien

    INNER JOIN bien_proprietaire bp
        ON bp.id_bien = b.id_bien

    WHERE bp.id_proprietaire = ?

      AND l.deleted_at IS NULL

      AND c.deleted_at IS NULL

      AND b.deleted_at IS NULL

    ORDER BY

        CASE

            WHEN c.statut = 'ACTIVE'
                THEN 0

            WHEN c.statut = 'DRAFT'
                THEN 1

            WHEN c.statut = 'RENEWED'
                THEN 2

            WHEN c.statut = 'EXPIRED'
                THEN 3

            ELSE 4

        END,

        l.nom ASC,
        l.prenom ASC,

        c.date_debut DESC
";


$stmt = $pdo->prepare($sql);

$stmt->execute([
    $idProprietaire
]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Déduplication des locataires
|--------------------------------------------------------------------------
|
| Un locataire peut avoir plusieurs contrats.
|
| Sur la page principale, on affiche une seule carte
| par locataire.
|
| La première ligne conservée est celle correspondant
| au contrat le plus important grâce au ORDER BY ci-dessus.
|--------------------------------------------------------------------------
*/

$locataires = [];

foreach ($rows as $row) {

    $idLocataire = (int) $row['id_locataire'];

    if (!isset($locataires[$idLocataire])) {

        $locataires[$idLocataire] = $row;
    }
}


/*
|--------------------------------------------------------------------------
| Statistiques
|--------------------------------------------------------------------------
*/

$totalLocataires = count($locataires);

$locatairesActifs = 0;

foreach ($locataires as $locataire) {

    if (
        strtoupper((string) $locataire['statut_contrat'])
        === 'ACTIVE'
    ) {
        $locatairesActifs++;
    }
}


/*
|--------------------------------------------------------------------------
| Initiales d'un locataire
|--------------------------------------------------------------------------
*/

function initialesLocataire(
    ?string $prenom,
    ?string $nom
): string {

    $initiales = '';

    if ($prenom) {
        $initiales .= strtoupper(
            substr(trim($prenom), 0, 1)
        );
    }

    if ($nom) {
        $initiales .= strtoupper(
            substr(trim($nom), 0, 1)
        );
    }

    return $initiales ?: '?';
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

    <title>LOKA | Mes locataires</title>


    <!-- CSS PRINCIPAL DU PROPRIETAIRE -->

    <link
        rel="stylesheet"
        href="/LOKA/app/proprietaires/proprietaire.css"
    >


    <!-- CSS SPECIFIQUE AUX LOCATAIRES -->

    <link
        rel="stylesheet"
        href="/LOKA/app/proprietaires/Locataires/locataire.css"
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


    <!-- =====================================================
         SIDEBAR
         ===================================================== -->

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
                class="active"
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
            >

                <i class="fa-solid fa-wallet"></i>

                Mes revenus

            </a>


            <a
                href="/LOKA/app/proprietaires/paiements.php"
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


    <!-- =====================================================
         CONTENU
         ===================================================== -->

    <main class="content">


        <!-- =================================================
             HEADER
             ================================================= -->

        <header class="topbar">

            <div>

        
                <h1>
                    Mes locataires
                </h1>


                <p>
                    Les locataires associés à vos biens
                </p>

            </div>


            <div class="user">

                <button
                    type="button"
                    class="bell"
                >

                    <i class="fa-regular fa-bell"></i>

                </button>


                <div class="avatar">

                    <?= e(
                        strtoupper(
                            substr(
                                $proprietaire['prenom'] ?? '',
                                0,
                                1
                            )
                            .
                            substr(
                                $proprietaire['nom'] ?? '',
                                0,
                                1
                            )
                        )
                    ) ?>

                </div>

            </div>

        </header>


        <!-- =================================================
             STATISTIQUES
             ================================================= -->

        <section class="locataire-stats">


            <div class="locataire-stat-card">

                <div class="locataire-stat-icon">

                    <i class="fa-solid fa-users"></i>

                </div>


                <div>

                    <p>
                        Total locataires
                    </p>

                    <strong>
                        <?= $totalLocataires ?>
                    </strong>

                </div>

            </div>


            <div class="locataire-stat-card">

                <div class="locataire-stat-icon active">

                    <i class="fa-solid fa-user-check"></i>

                </div>


                <div>

                    <p>
                        Locataires actifs
                    </p>

                    <strong>
                        <?= $locatairesActifs ?>
                    </strong>

                </div>

            </div>


        </section>


        <!-- =================================================
             RECHERCHE
             ================================================= -->

        <section class="locataire-search">

            <div class="search-wrapper">

                <i class="fa-solid fa-magnifying-glass"></i>

                <input
                    type="search"
                    id="searchLocataire"
                    placeholder="Rechercher un locataire..."
                    autocomplete="off"
                >

            </div>

        </section>


        <!-- =================================================
             LISTE
             ================================================= -->

        <section class="locataire-section">


            <?php if (empty($locataires)): ?>


                <div class="locataire-empty">

                    <div class="empty-icon">

                        <i class="fa-solid fa-users"></i>

                    </div>


                    <h2>
                        Aucun locataire
                    </h2>


                    <p>
                        Aucun locataire n'est actuellement
                        associé à vos biens.
                    </p>

                </div>


            <?php else: ?>


                <div
                    class="locataire-grid"
                    id="locataireGrid"
                >


                    <?php foreach ($locataires as $locataire): ?>


                        <?php

                        $nomComplet = trim(
                            ($locataire['prenom'] ?? '')
                            . ' '
                            . ($locataire['nom'] ?? '')
                        );

                        $searchContent = strtolower(
                            $nomComplet
                            . ' '
                            . ($locataire['email'] ?? '')
                            . ' '
                            . ($locataire['telephone'] ?? '')
                            . ' '
                            . ($locataire['ville'] ?? '')
                            . ' '
                            . ($locataire['titre_bien'] ?? '')
                            . ' '
                            . ($locataire['reference_bien'] ?? '')
                            . ' '
                            . ($locataire['numero_contrat'] ?? '')
                        );

                        ?>


                        <article
                            class="locataire-card"
                            data-search="<?= e($searchContent) ?>"
                        >


                            <!-- ==========================
                                 IDENTITE
                                 ========================== -->

                            <div class="locataire-card-header">


                                <div class="locataire-avatar">

                                    <?= e(
                                        initialesLocataire(
                                            $locataire['prenom'],
                                            $locataire['nom']
                                        )
                                    ) ?>

                                </div>


                                <div class="locataire-name">

                                    <h2>
                                        <?= e($nomComplet) ?>
                                    </h2>


                                    <span>

                                        <?= e(
                                            $locataire['email']
                                        ) ?>

                                    </span>

                                </div>


                            </div>


                            <!-- ==========================
                                 CONTACT
                                 ========================== -->

                            <div class="locataire-contact">


                                <?php if (!empty($locataire['telephone'])): ?>

                                    <div>

                                        <i class="fa-solid fa-phone"></i>

                                        <span>
                                            <?= e(
                                                $locataire['telephone']
                                            ) ?>
                                        </span>

                                    </div>

                                <?php endif; ?>


                                <?php if (!empty($locataire['ville'])): ?>

                                    <div>

                                        <i class="fa-solid fa-location-dot"></i>

                                        <span>

                                            <?= e(
                                                $locataire['ville']
                                            ) ?>

                                            <?php if (!empty($locataire['pays'])): ?>

                                                ,
                                                <?= e(
                                                    $locataire['pays']
                                                ) ?>

                                            <?php endif; ?>

                                        </span>

                                    </div>

                                <?php endif; ?>


                            </div>


                            <!-- ==========================
                                 BIEN
                                 ========================== -->

                            <div class="locataire-info-block">


                                <div class="block-title">

                                    <i class="fa-solid fa-building"></i>

                                    Bien occupé

                                </div>


                                <h3>

                                    <?= e(
                                        $locataire['titre_bien']
                                        ?: 'Bien sans titre'
                                    ) ?>

                                </h3>


                                <?php if (!empty($locataire['reference_bien'])): ?>

                                    <p>

                                        Référence :
                                        <strong>
                                            <?= e(
                                                $locataire['reference_bien']
                                            ) ?>
                                        </strong>

                                    </p>

                                <?php endif; ?>


                            </div>


                            <!-- ==========================
                                 CONTRAT
                                 ========================== -->

                            <div class="locataire-contract">


                                <div class="contract-top">


                                    <div>

                                        <span class="contract-label">
                                            Contrat
                                        </span>


                                        <strong>

                                            <?= e(
                                                $locataire['numero_contrat']
                                                ?: 'Sans numéro'
                                            ) ?>

                                        </strong>

                                    </div>


                                    <span
                                        class="contract-status <?= e(
                                            statutContratClass(
                                                $locataire['statut_contrat']
                                            )
                                        ) ?>"
                                    >

                                        <?= e(
                                            statutContratLabel(
                                                $locataire['statut_contrat']
                                            )
                                        ) ?>

                                    </span>


                                </div>


                                <div class="contract-dates">


                                    <div>

                                        <span>
                                            Début
                                        </span>

                                        <strong>
                                            <?= e(
                                                formatDateFr(
                                                    $locataire['date_debut']
                                                )
                                            ) ?>
                                        </strong>

                                    </div>


                                    <div>

                                        <span>
                                            Fin
                                        </span>

                                        <strong>
                                            <?= e(
                                                formatDateFr(
                                                    $locataire['date_fin']
                                                )
                                            ) ?>
                                        </strong>

                                    </div>


                                </div>


                                <div class="contract-rent">


                                    <div>

                                        <span>
                                            Loyer
                                        </span>

                                        <strong>
                                            <?= e(
                                                formatMoney(
                                                    $locataire['loyer']
                                                )
                                            ) ?>
                                        </strong>

                                    </div>


                                    <?php if ($locataire['charges'] !== null): ?>

                                        <div>

                                            <span>
                                                Charges
                                            </span>

                                            <strong>
                                                <?= e(
                                                    formatMoney(
                                                        $locataire['charges']
                                                    )
                                                ) ?>
                                            </strong>

                                        </div>

                                    <?php endif; ?>


                                </div>


                            </div>


                            <!-- ==========================
                                 ACTION
                                 ========================== -->

                            <div class="locataire-card-footer">


                                <a
                                    href="/LOKA/app/proprietaires/Locataires/detail.php?id=<?= (int) $locataire['id_locataire'] ?>"
                                    class="locataire-btn"
                                >

                                    <i class="fa-solid fa-eye"></i>

                                    Voir le locataire

                                </a>


                            </div>


                        </article>


                    <?php endforeach; ?>


                </div>


                <div
                    id="noSearchResult"
                    class="locataire-empty search-empty"
                    hidden
                >

                    <div class="empty-icon">

                        <i class="fa-solid fa-magnifying-glass"></i>

                    </div>


                    <h2>
                        Aucun résultat
                    </h2>


                    <p>
                        Aucun locataire ne correspond à votre recherche.
                    </p>

                </div>


            <?php endif; ?>


        </section>


    </main>

</div>


<script src="/LOKA/app/proprietaires/Locataires/locataire.js"></script>

</body>

</html>