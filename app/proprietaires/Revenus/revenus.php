<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once dirname(__DIR__, 3) . '/core/Database.php';

$user = Authorization::requireRole('Proprietaire');
$pdo = Database::connection();

$idUtilisateur = (int) $user['id_utilisateur'];

/*
|--------------------------------------------------------------------------
| PROPRIETAIRE CONNECTE
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
| FONCTIONS
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


function formatMoney(float $amount): string
{
    return number_format(
        $amount,
        2,
        ',',
        ' '
    ) . ' €';
}


function statutLabel(string $statut): string
{
    return match ($statut) {

        'PAID' => 'Payé',

        'PENDING' => 'En attente',

        'LATE' => 'En retard',

        'CANCELLED' => 'Annulé',

        'REFUND' => 'Remboursé',

        default => $statut,
    };
}


function statutClass(string $statut): string
{
    return match ($statut) {

        'PAID' => 'status-paid',

        'PENDING' => 'status-pending',

        'LATE' => 'status-late',

        'CANCELLED' => 'status-cancelled',

        'REFUND' => 'status-refund',

        default => 'status-default',
    };
}


function modePaiementLabel(?string $mode): string
{
    return match ($mode) {

        'CASH' => 'Espèces',

        'BANK_TRANSFER' => 'Virement',

        'CARD' => 'Carte',

        'CHEQUE' => 'Chèque',

        default => $mode ?: '—',
    };
}


/*
|--------------------------------------------------------------------------
| FILTRES
|--------------------------------------------------------------------------
|
| 0 = aucun filtre d'année
| 0 = aucun filtre de mois
|
*/

$anneeActuelle = (int) date('Y');


$annee = filter_input(
    INPUT_GET,
    'annee',
    FILTER_VALIDATE_INT
);

if (
    $annee === false ||
    $annee === null ||
    $annee < 2000 ||
    $annee > 2100
) {
    $annee = 0;
}


$mois = filter_input(
    INPUT_GET,
    'mois',
    FILTER_VALIDATE_INT
);

if (
    $mois === false ||
    $mois === null ||
    $mois < 0 ||
    $mois > 12
) {
    $mois = 0;
}


$moisNoms = [

    1 => 'Janvier',

    2 => 'Février',

    3 => 'Mars',

    4 => 'Avril',

    5 => 'Mai',

    6 => 'Juin',

    7 => 'Juillet',

    8 => 'Août',

    9 => 'Septembre',

    10 => 'Octobre',

    11 => 'Novembre',

    12 => 'Décembre',
];


/*
|--------------------------------------------------------------------------
| PERIODE
|--------------------------------------------------------------------------
*/

$dateDebutSql = null;
$dateFinSql = null;


/*
| Si une année est sélectionnée
*/

if ($annee > 0) {

    $dateDebut = sprintf(
        '%04d-01-01',
        $annee
    );

    $dateFin = sprintf(
        '%04d-12-31',
        $annee
    );


    /*
    | Si un mois est également sélectionné
    */

    if ($mois > 0) {

        $dateDebut = sprintf(
            '%04d-%02d-01',
            $annee,
            $mois
        );

        $dateFin = date(
            'Y-m-t',
            strtotime($dateDebut)
        );
    }


    $dateDebutSql =
        $dateDebut . ' 00:00:00';

    $dateFinSql =
        $dateFin . ' 23:59:59';
}


/*
|--------------------------------------------------------------------------
| LIBELLE DE LA PERIODE
|--------------------------------------------------------------------------
*/

if ($annee === 0) {

    $periodeLabel = 'Toutes les années';

} elseif ($mois > 0) {

    $periodeLabel =
        $moisNoms[$mois]
        . ' '
        . $annee;

} else {

    $periodeLabel =
        'Année '
        . $annee;
}


/*
|--------------------------------------------------------------------------
| NOMBRE DE BIENS
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

$stmt->execute([
    $idProprietaire
]);

$nombreBiens = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| TOTAL GENERAL DES REVENUS
|--------------------------------------------------------------------------
|
| IMPORTANT :
| Ce total n'est PAS soumis au filtre.
|
| Il représente l'ensemble des revenus encaissés
| par le propriétaire.
|
*/

$stmt = $pdo->prepare("
    SELECT
        COALESCE(SUM(p.montant), 0)

    FROM paiement p

    INNER JOIN contrat c
        ON c.id_contrat = p.id_contrat

    INNER JOIN bien b
        ON b.id_bien = c.id_bien

    INNER JOIN bien_proprietaire bp
        ON bp.id_bien = b.id_bien

    WHERE bp.id_proprietaire = ?

      AND p.statut = 'PAID'

      AND c.deleted_at IS NULL

      AND b.deleted_at IS NULL
");

$stmt->execute([
    $idProprietaire
]);

$totalRevenus = (float) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| STATISTIQUES FILTREES
|--------------------------------------------------------------------------
*/

$sqlRevenus = "
    SELECT

        COALESCE(
            SUM(
                CASE
                    WHEN p.statut = 'PAID'
                    THEN p.montant
                    ELSE 0
                END
            ),
            0
        ) AS revenus_encaisses,

        COALESCE(
            SUM(
                CASE
                    WHEN p.statut = 'PENDING'
                    THEN p.montant
                    ELSE 0
                END
            ),
            0
        ) AS revenus_attente,

        COALESCE(
            SUM(
                CASE
                    WHEN p.statut = 'LATE'
                    THEN p.montant
                    ELSE 0
                END
            ),
            0
        ) AS revenus_retard

    FROM paiement p

    INNER JOIN contrat c
        ON c.id_contrat = p.id_contrat

    INNER JOIN bien b
        ON b.id_bien = c.id_bien

    INNER JOIN bien_proprietaire bp
        ON bp.id_bien = b.id_bien

    WHERE bp.id_proprietaire = ?

      AND c.deleted_at IS NULL

      AND b.deleted_at IS NULL
";


$paramsRevenus = [
    $idProprietaire
];


/*
|--------------------------------------------------------------------------
| AJOUT DU FILTRE DATE UNIQUEMENT SI NECESSAIRE
|--------------------------------------------------------------------------
*/

if (
    $dateDebutSql !== null &&
    $dateFinSql !== null
) {

    $sqlRevenus .= "
        AND p.date_paiement >= ?
        AND p.date_paiement <= ?
    ";

    $paramsRevenus[] =
        $dateDebutSql;

    $paramsRevenus[] =
        $dateFinSql;
}


$stmt = $pdo->prepare(
    $sqlRevenus
);

$stmt->execute(
    $paramsRevenus
);

$revenus = $stmt->fetch(
    PDO::FETCH_ASSOC
);


$revenusEncaisses = (float) (
    $revenus['revenus_encaisses'] ?? 0
);

$revenusEnAttente = (float) (
    $revenus['revenus_attente'] ?? 0
);

$revenusEnRetard = (float) (
    $revenus['revenus_retard'] ?? 0
);


/*
|--------------------------------------------------------------------------
| HISTORIQUE
|--------------------------------------------------------------------------
*/

$sqlHistorique = "
    SELECT

        p.id_paiement,

        p.montant,

        p.date_paiement,

        p.mode_paiement,

        p.statut,

        c.numero AS numero_contrat,

        b.reference AS reference_bien,

        b.titre AS titre_bien,

        l.nom AS locataire_nom,

        l.prenom AS locataire_prenom

    FROM paiement p

    INNER JOIN contrat c
        ON c.id_contrat = p.id_contrat

    INNER JOIN bien b
        ON b.id_bien = c.id_bien

    INNER JOIN bien_proprietaire bp
        ON bp.id_bien = b.id_bien

    LEFT JOIN locataire l
        ON l.id_locataire = p.id_locataire

    WHERE bp.id_proprietaire = ?

      AND c.deleted_at IS NULL

      AND b.deleted_at IS NULL
";


$paramsHistorique = [
    $idProprietaire
];


if (
    $dateDebutSql !== null &&
    $dateFinSql !== null
) {

    $sqlHistorique .= "
        AND p.date_paiement >= ?
        AND p.date_paiement <= ?
    ";

    $paramsHistorique[] =
        $dateDebutSql;

    $paramsHistorique[] =
        $dateFinSql;
}


$sqlHistorique .= "
    ORDER BY
        p.date_paiement DESC,
        p.id_paiement DESC
";


$stmt = $pdo->prepare(
    $sqlHistorique
);

$stmt->execute(
    $paramsHistorique
);

$historiqueRevenus =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/*
|--------------------------------------------------------------------------
| GRAPHIQUE
|--------------------------------------------------------------------------
|
| Le graphique est uniquement annuel.
|
| Si aucune année n'est sélectionnée,
| on utilise l'année actuelle.
|
*/

$anneeGraphique =
    $annee > 0
        ? $annee
        : $anneeActuelle;


$graphiqueRevenus = [];


/*
| Création des 12 mois
*/

foreach ($moisNoms as $numero => $nom) {

    $graphiqueRevenus[$numero] = [

        'mois' => $nom,

        'montant' => 0,
    ];
}


/*
| Récupération des revenus PAID
*/

$stmt = $pdo->prepare("
    SELECT

        MONTH(p.date_paiement) AS mois,

        COALESCE(
            SUM(p.montant),
            0
        ) AS total

    FROM paiement p

    INNER JOIN contrat c
        ON c.id_contrat = p.id_contrat

    INNER JOIN bien b
        ON b.id_bien = c.id_bien

    INNER JOIN bien_proprietaire bp
        ON bp.id_bien = b.id_bien

    WHERE bp.id_proprietaire = ?

      AND c.deleted_at IS NULL

      AND b.deleted_at IS NULL

      AND p.statut = 'PAID'

      AND YEAR(p.date_paiement) = ?

    GROUP BY MONTH(p.date_paiement)

    ORDER BY MONTH(p.date_paiement)
");


$stmt->execute([
    $idProprietaire,
    $anneeGraphique
]);


$revenusMensuels =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/*
| Remplissage du graphique
*/

foreach ($revenusMensuels as $revenuMensuel) {

    $numeroMois =
        (int) $revenuMensuel['mois'];


    if (
        isset(
            $graphiqueRevenus[$numeroMois]
        )
    ) {

        $graphiqueRevenus[$numeroMois]['montant'] =
            (float) $revenuMensuel['total'];
    }
}


$graphiqueRevenus =
    array_values(
        $graphiqueRevenus
    );

?>

<!DOCTYPE html>

<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
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
                href="/LOKA/app/proprietaires/Revenus/revenus.php"
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
                href="/LOKA/app/proprietaires/Quittances/quittances.php"
            >

                <i class="fa-solid fa-receipt"></i>

                Quittances

            </a>


            <a
                href="/LOKA/app/proprietaires/Documents/documents.php"
            >

                <i class="fa-solid fa-folder-open"></i>

                Documents

            </a>


            <a
                href="/LOKA/app/proprietaires/Parametres/parametres.php"
            >

                <i class="fa-solid fa-gear"></i>

                Paramètres

            </a>


        </nav>

    </aside>
    <main class="main-content">


        <!-- =====================================================
             EN-TETE
        ====================================================== -->

        <header class="page-header">

            <div>

                <h1>
                    Mes revenus
                </h1>

                <p>
                    Consultez les revenus liés à vos biens.
                </p>

            </div>


            <div class="owner-badge">

                <i class="fa-solid fa-user"></i>

                <span>

                    <?= e(
                        trim(
                            ($proprietaire['prenom'] ?? '')
                            . ' '
                            . ($proprietaire['nom'] ?? '')
                        )
                    ) ?>

                </span>

            </div>

        </header>


        <!-- =====================================================
             TOTAL GENERAL
        ====================================================== -->

        <section class="total-revenus-card">


            <div class="total-revenus-content">


                <div class="total-revenus-info">


                    <div class="total-revenus-icon">

                        <i class="fa-solid fa-wallet"></i>

                    </div>


                    <div>

                        <span class="total-revenus-label">

                            Total de mes revenus

                        </span>


                        <strong class="total-revenus-value">

                            <?= formatMoney(
                                $totalRevenus
                            ) ?>

                        </strong>

                    </div>


                </div>


            </div>


        </section>


        <!-- =====================================================
             FILTRES
        ====================================================== -->

        <section class="filters-card">


            <div class="filters-header">

                <h2>
                    Filtrer les revenus
                </h2>

                <p>
                    Sélectionnez une année et éventuellement un mois.
                </p>

            </div>


            <form
                method="GET"
                action="revenus.php"
                class="revenus-filters"
            >


                <!-- ANNEE -->

                <div class="filter-group">

                    <label for="annee">

                        Année

                    </label>


                    <select
                        name="annee"
                        id="annee"
                    >


                        <option
                            value="0"
                            <?= $annee === 0
                                ? 'selected'
                                : '' ?>
                        >

                            Toutes les années

                        </option>


                        <?php

                        for (
                            $i = $anneeActuelle - 5;
                            $i <= $anneeActuelle + 1;
                            $i++
                        ):

                        ?>


                            <option
                                value="<?= $i ?>"
                                <?= $annee === $i
                                    ? 'selected'
                                    : '' ?>
                            >

                                <?= $i ?>

                            </option>


                        <?php endfor; ?>


                    </select>

                </div>


                <!-- MOIS -->

                <div class="filter-group">

                    <label for="mois">

                        Mois

                    </label>


                    <select
                        name="mois"
                        id="mois"
                    >


                        <option
                            value="0"
                            <?= $mois === 0
                                ? 'selected'
                                : '' ?>
                        >

                            Tous les mois

                        </option>


                        <?php

                        foreach (
                            $moisNoms
                            as $numero => $nom
                        ):

                        ?>


                            <option
                                value="<?= $numero ?>"
                                <?= $mois === $numero
                                    ? 'selected'
                                    : '' ?>
                            >

                                <?= e($nom) ?>

                            </option>


                        <?php endforeach; ?>


                    </select>

                </div>


                <!-- BOUTON FILTRER -->

                <button
                    type="submit"
                    class="filter-button"
                >

                    <i class="fa-solid fa-filter"></i>

                    Filtrer

                </button>


                <!-- RESET -->

                <a
                    href="revenus.php"
                    class="reset-button"
                >

                    <i class="fa-solid fa-rotate-left"></i>

                    Réinitialiser

                </a>


            </form>


        </section>


        <!-- =====================================================
             PERIODE
        ====================================================== -->

        <div class="period-info">


            <i class="fa-solid fa-calendar-days"></i>


            <span>

                Revenus affichés pour :

                <strong>

                    <?= e(
                        $periodeLabel
                    ) ?>

                </strong>

            </span>


        </div>


        <!-- =====================================================
             STATISTIQUES
        ====================================================== -->

        <section class="stats-grid">


            <!-- ENCAISSES -->

            <article class="stat-card">


                <div class="stat-icon">

                    <i class="fa-solid fa-wallet"></i>

                </div>


                <div class="stat-content">

                    <span class="stat-label">

                        Revenus encaissés

                    </span>


                    <strong>

                        <?= formatMoney(
                            $revenusEncaisses
                        ) ?>

                    </strong>

                </div>


            </article>


            <!-- ATTENTE -->

            <article class="stat-card">


                <div class="stat-icon">

                    <i class="fa-solid fa-clock"></i>

                </div>


                <div class="stat-content">

                    <span class="stat-label">

                        En attente

                    </span>


                    <strong>

                        <?= formatMoney(
                            $revenusEnAttente
                        ) ?>

                    </strong>

                </div>


            </article>


            <!-- RETARD -->

            <article class="stat-card">


                <div class="stat-icon">

                    <i class="fa-solid fa-triangle-exclamation"></i>

                </div>


                <div class="stat-content">

                    <span class="stat-label">

                        En retard

                    </span>


                    <strong>

                        <?= formatMoney(
                            $revenusEnRetard
                        ) ?>

                    </strong>

                </div>


            </article>


            <!-- BIENS -->

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


        <!-- =====================================================
             GRAPHIQUE
        ====================================================== -->

        <section class="revenus-card chart-card">


            <div class="section-header">


                <div>

                    <h2>
                        Évolution des revenus
                    </h2>


                    <p>

                        Revenus encaissés par mois en

                        <?= e(
                            (string) $anneeGraphique
                        ) ?>.

                    </p>

                </div>


            </div>


            <div class="chart-container">

                <canvas
                    id="revenusChart"
                ></canvas>

            </div>


        </section>


        <!-- =====================================================
             HISTORIQUE
        ====================================================== -->

        <section class="revenus-card">


            <div class="section-header">


                <div>

                    <h2>
                        Historique des revenus
                    </h2>


                    <p>

                        Revenus générés par vos biens
                        pour <?= e($periodeLabel) ?>.

                    </p>

                </div>


            </div>


            <?php if (
                empty($historiqueRevenus)
            ): ?>


                <!-- AUCUN RESULTAT -->

                <div class="empty-state">


                    <div class="empty-icon">

                        <i class="fa-solid fa-chart-line"></i>

                    </div>


                    <h3>

                        Aucun revenu enregistré

                    </h3>


                    <p>

                        Aucun paiement ne correspond
                        à la période sélectionnée.

                    </p>


                </div>


            <?php else: ?>


                <!-- TABLEAU -->

                <div class="table-wrapper">


                    <table class="revenus-table">


                        <thead>

                            <tr>

                                <th>
                                    Date
                                </th>

                                <th>
                                    Bien
                                </th>

                                <th>
                                    Locataire
                                </th>

                                <th>
                                    Contrat
                                </th>

                                <th>
                                    Mode
                                </th>

                                <th>
                                    Montant
                                </th>

                                <th>
                                    Statut
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php foreach (
                            $historiqueRevenus
                            as $revenu
                        ): ?>


                            <tr>


                                <!-- DATE -->

                                <td>

                                    <?php

                                    if (
                                        !empty(
                                            $revenu[
                                                'date_paiement'
                                            ]
                                        )
                                    ) {

                                        echo e(
                                            date(
                                                'd/m/Y',
                                                strtotime(
                                                    $revenu[
                                                        'date_paiement'
                                                    ]
                                                )
                                            )
                                        );

                                    } else {

                                        echo '—';

                                    }

                                    ?>

                                </td>


                                <!-- BIEN -->

                                <td>


                                    <div class="property-cell">


                                        <strong>

                                            <?= e(
                                                $revenu[
                                                    'reference_bien'
                                                ]
                                            ) ?>

                                        </strong>


                                        <?php if (
                                            !empty(
                                                $revenu[
                                                    'titre_bien'
                                                ]
                                            )
                                        ): ?>


                                            <span>

                                                <?= e(
                                                    $revenu[
                                                        'titre_bien'
                                                    ]
                                                ) ?>

                                            </span>


                                        <?php endif; ?>


                                    </div>


                                </td>


                                <!-- LOCATAIRE -->

                                <td>


                                    <?php

                                    $nomLocataire =
                                        trim(
                                            (
                                                $revenu[
                                                    'locataire_prenom'
                                                ]
                                                ?? ''
                                            )
                                            . ' '
                                            . (
                                                $revenu[
                                                    'locataire_nom'
                                                ]
                                                ?? ''
                                            )
                                        );


                                    echo e(
                                        $nomLocataire
                                        ?: '—'
                                    );

                                    ?>


                                </td>


                                <!-- CONTRAT -->

                                <td>

                                    <?= e(
                                        $revenu[
                                            'numero_contrat'
                                        ]
                                    ) ?>

                                </td>


                                <!-- MODE -->

                                <td>

                                    <?= e(
                                        modePaiementLabel(
                                            $revenu[
                                                'mode_paiement'
                                            ]
                                        )
                                    ) ?>

                                </td>


                                <!-- MONTANT -->

                                <td class="amount">

                                    <?= formatMoney(
                                        (float)
                                        $revenu[
                                            'montant'
                                        ]
                                    ) ?>

                                </td>


                                <!-- STATUT -->

                                <td>


                                    <span
                                        class="status-badge <?= e(
                                            statutClass(
                                                $revenu[
                                                    'statut'
                                                ]
                                            )
                                        ) ?>"
                                    >

                                        <?= e(
                                            statutLabel(
                                                $revenu[
                                                    'statut'
                                                ]
                                            )
                                        ) ?>

                                    </span>


                                </td>


                            </tr>


                        <?php endforeach; ?>


                        </tbody>


                    </table>


                </div>


            <?php endif; ?>


        </section>


    </main>


</div>


<!-- =========================================================
     CHART.JS
========================================================= -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<script>

const revenusMensuels =
    <?= json_encode(
        $graphiqueRevenus,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    ) ?>;


const canvas =
    document.getElementById(
        'revenusChart'
    );


if (canvas) {


    new Chart(
        canvas,
        {

            type: 'line',


            data: {

                labels:
                    revenusMensuels.map(
                        item => item.mois
                    ),


                datasets: [

                    {

                        label:
                            'Revenus encaissés',


                        data:
                            revenusMensuels.map(
                                item => item.montant
                            ),


                        tension: 0.3,


                        fill: true,


                        borderWidth: 2,


                        pointRadius: 4,


                        pointHoverRadius: 6

                    }

                ]

            },


            options: {

                responsive: true,


                maintainAspectRatio: false,


                plugins: {

                    legend: {

                        display: false

                    },


                    tooltip: {

                        callbacks: {

                            label: function(context) {

                                return new Intl.NumberFormat(
                                    'fr-FR',
                                    {
                                        style: 'currency',
                                        currency: 'EUR'
                                    }
                                ).format(
                                    context.raw
                                );

                            }

                        }

                    }

                },


                scales: {

                    y: {

                        beginAtZero: true,


                        ticks: {

                            callback:
                                function(value) {

                                    return new Intl.NumberFormat(
                                        'fr-FR',
                                        {
                                            style: 'currency',
                                            currency: 'EUR',
                                            maximumFractionDigits: 0
                                        }
                                    ).format(
                                        value
                                    );

                                }

                        }

                    }

                }

            }

        }
    );

}

</script>


</body>

</html>