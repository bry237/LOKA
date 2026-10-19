<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';

$auth = Authorization::requireRole('Proprietaire');

$config = require dirname(__DIR__, 3) . '/config/database.php';

try {
    $pdo = new PDO(
        sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['name'],
            $config['charset']
        ),
        $config['user'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('Erreur de connexion à la base de données.');
}


/* =========================================================
   OUTILS
========================================================= */

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
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

function getContractStatus(
    ?string $status,
    ?string $dateFin
): string {
    $status = strtoupper(trim((string) $status));

    /*
     * Si le statut est renseigné en base,
     * on le conserve.
     */
    if ($status !== '') {
        return $status;
    }

    /*
     * Pour les anciens contrats de test dont le statut
     * est vide, on déduit uniquement l'affichage
     * à partir de la date de fin.
     */
    if ($dateFin) {
        $fin = strtotime($dateFin);

        if ($fin !== false && $fin < strtotime('today')) {
            return 'EXPIRED';
        }
    }

    return 'UNKNOWN';
}

function statutContratLabel(
    ?string $status,
    ?string $dateFin = null
): string {
    $status = getContractStatus($status, $dateFin);

    return match ($status) {
        'DRAFT'      => 'Brouillon',
        'ACTIVE'     => 'Actif',
        'EXPIRED'    => 'Expiré',
        'RENEWED'    => 'Renouvelé',
        'TERMINATED' => 'Terminé',
        'ARCHIVED'   => 'Archivé',
        'UNKNOWN'    => 'Non renseigné',
        default      => $status !== '' ? $status : 'Non renseigné',
    };
}

function statutContratClass(
    ?string $status,
    ?string $dateFin = null
): string {
    $status = getContractStatus($status, $dateFin);

    return match ($status) {
        'ACTIVE'     => 'status-active',
        'DRAFT'      => 'status-draft',
        'EXPIRED'    => 'status-expired',
        'RENEWED'    => 'status-renewed',
        'TERMINATED' => 'status-terminated',
        'ARCHIVED'   => 'status-archived',
        default      => 'status-default',
    };
}

function frequencePaiementLabel(?string $frequence): string
{
    return match (strtoupper((string) $frequence)) {
        'MONTHLY'   => 'Mensuel',
        'QUARTERLY' => 'Trimestriel',
        'YEARLY'    => 'Annuel',
        default     => $frequence ?: '—',
    };
}


/* =========================================================
   PROPRIETAIRE CONNECTE
========================================================= */

$idUtilisateur = (int) ($auth['id_utilisateur'] ?? 0);

if ($idUtilisateur <= 0) {
    http_response_code(403);
    exit('Utilisateur non authentifié.');
}


/*
 * On retrouve le propriétaire à partir de l'utilisateur
 * actuellement connecté.
 */
$stmt = $pdo->prepare("
    SELECT
        p.id_proprietaire,
        p.nom,
        p.prenom
    FROM utilisateur u
    INNER JOIN proprietaire p
        ON p.email = u.email
    WHERE u.id_utilisateur = ?
    LIMIT 1
");

$stmt->execute([$idUtilisateur]);

$proprietaire = $stmt->fetch();

if (!$proprietaire) {
    http_response_code(403);
    exit('Profil propriétaire introuvable.');
}

$idProprietaire = (int) $proprietaire['id_proprietaire'];


/* =========================================================
   RECUPERATION DES CONTRATS
========================================================= */

$sql = "
    SELECT
        c.id_contrat,
        c.id_bien,
        c.numero,
        c.date_debut,
        c.date_fin,
        c.loyer,
        c.charges,
        c.depot_garantie,
        c.frequence_paiement,
        c.jour_echeance,
        c.statut,

        b.reference AS reference_bien,
        b.titre AS titre_bien,

        (
            SELECT GROUP_CONCAT(
                DISTINCT CONCAT(
                    COALESCE(l.prenom, ''),
                    ' ',
                    COALESCE(l.nom, '')
                )
                ORDER BY cl2.titulaire_principal DESC, l.nom ASC, l.prenom ASC
                SEPARATOR ', '
            )
            FROM contrat_locataire cl2
            INNER JOIN locataire l
                ON l.id_locataire = cl2.id_locataire
            WHERE cl2.id_contrat = c.id_contrat
              AND l.deleted_at IS NULL
        ) AS locataires

    FROM contrat c

    INNER JOIN bien b
        ON b.id_bien = c.id_bien

    INNER JOIN bien_proprietaire bp
        ON bp.id_bien = b.id_bien

    WHERE bp.id_proprietaire = ?
      AND c.deleted_at IS NULL
      AND b.deleted_at IS NULL

    ORDER BY
        CASE
            WHEN c.statut = 'ACTIVE' THEN 0
            WHEN c.statut = 'DRAFT' THEN 1
            WHEN c.statut = 'RENEWED' THEN 2
            WHEN c.statut = 'EXPIRED' THEN 3
            WHEN c.statut = 'TERMINATED' THEN 4
            WHEN c.statut = 'ARCHIVED' THEN 5
            ELSE 6
        END,
        c.date_debut DESC,
        c.id_contrat DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$idProprietaire]);

$contrats = $stmt->fetchAll();


/* =========================================================
   STATISTIQUES
========================================================= */

$totalContrats = count($contrats);
$contratsActifs = 0;
$contratsExpires = 0;
$contratsBrouillons = 0;
$totalLoyersActifs = 0;

foreach ($contrats as $contrat) {

    $status = getContractStatus(
        $contrat['statut'] ?? null,
        $contrat['date_fin'] ?? null
    );

    if ($status === 'ACTIVE') {
        $contratsActifs++;

        $totalLoyersActifs +=
            (float) ($contrat['loyer'] ?? 0);
    }

    if ($status === 'EXPIRED') {
        $contratsExpires++;
    }

    if ($status === 'DRAFT') {
        $contratsBrouillons++;
    }
}


/* =========================================================
   AFFICHAGE
========================================================= */
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Mes contrats | LOKA</title>

    <link
        rel="stylesheet"
        href="/LOKA/app/proprietaires/proprietaire.css"
    >

	   <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <style>
        /* =====================================================
           PAGE MES CONTRATS
        ===================================================== */

        .contrats-page {
            padding: 30px;
        }

        .contrats-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 28px;
        }

        .contrats-header h1 {
            margin: 0 0 8px;
        }

        .contrats-header p {
            margin: 0;
            color: #6b7280;
        }

        .contrats-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: #374151;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .contrats-back:hover {
            color: #111827;
        }

        /* =====================================================
           STATISTIQUES
        ===================================================== */

        .contrats-stats {
            display: grid;
            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap: 18px;
            margin-bottom: 28px;
        }

        .contrat-stat-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 20px;

            display: flex;
            align-items: center;
            gap: 15px;

            box-shadow:
                0 3px 12px rgba(0, 0, 0, 0.04);
        }

        .contrat-stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #f3f4f6;
            font-size: 22px;
        }

        .contrat-stat-content {
            min-width: 0;
        }

        .contrat-stat-label {
            display: block;
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 5px;
        }

        .contrat-stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #111827;
        }

        /* =====================================================
           BARRE DE RECHERCHE
        ===================================================== */

        .contrats-toolbar {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;

            padding: 18px;
            margin-bottom: 22px;
        }

        .contrat-search-wrapper {
            position: relative;
        }

        .contrat-search-wrapper span {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }

        #searchContrat {
            width: 100%;
            box-sizing: border-box;

            border: 1px solid #d1d5db;
            border-radius: 10px;

            padding: 12px 15px 12px 42px;

            font-size: 15px;
            outline: none;
        }

        #searchContrat:focus {
            border-color: #2563eb;
            box-shadow:
                0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        /* =====================================================
           LISTE DES CONTRATS
        ===================================================== */

        .contrats-list {
            display: grid;
            gap: 18px;
        }

        .contrat-card {
            background: #fff;

            border: 1px solid #e5e7eb;
            border-radius: 16px;

            padding: 22px;

            box-shadow:
                0 3px 12px rgba(0, 0, 0, 0.04);
        }

        .contrat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;

            gap: 20px;
            margin-bottom: 20px;
        }

        .contrat-number {
            margin: 0 0 6px;

            font-size: 19px;
            font-weight: 700;
            color: #111827;
        }

        .contrat-property {
            color: #6b7280;
            font-size: 14px;
        }

        .contrat-property strong {
            color: #374151;
        }

        .contrat-status {
            display: inline-flex;
            align-items: center;

            padding: 6px 11px;
            border-radius: 999px;

            font-size: 12px;
            font-weight: 700;

            white-space: nowrap;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-draft {
            background: #fef3c7;
            color: #92400e;
        }

        .status-expired {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-renewed {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-terminated {
            background: #f3f4f6;
            color: #374151;
        }

        .status-archived {
            background: #e5e7eb;
            color: #4b5563;
        }

        .status-default {
            background: #f3f4f6;
            color: #6b7280;
        }

        /* =====================================================
           INFORMATIONS
        ===================================================== */

        .contrat-grid {
            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap: 15px;

            margin-bottom: 20px;
        }

        .contrat-info {
            background: #f9fafb;
            border-radius: 10px;
            padding: 14px;
        }

        .contrat-info-label {
            display: block;

            color: #6b7280;
            font-size: 12px;

            margin-bottom: 5px;
        }

        .contrat-info-value {
            font-size: 15px;
            font-weight: 600;
            color: #111827;
        }

        .contrat-info-value.price {
            font-size: 17px;
        }

        .contrat-footer {
            border-top: 1px solid #e5e7eb;

            padding-top: 16px;

            display: flex;
            justify-content: space-between;
            align-items: center;

            gap: 20px;
        }

        .contrat-locataire {
            color: #4b5563;
            font-size: 14px;
        }

        .contrat-locataire strong {
            color: #111827;
        }

        .contrat-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            padding: 10px 15px;

            border-radius: 9px;

            background: #111827;
            color: #fff;

            text-decoration: none;
            font-size: 14px;
            font-weight: 600;

            white-space: nowrap;
        }

        .contrat-button:hover {
            background: #1f2937;
        }

        /* =====================================================
           AUCUN CONTRAT
        ===================================================== */

        .contrats-empty {
            background: #fff;
            border: 1px dashed #d1d5db;
            border-radius: 16px;

            padding: 60px 25px;

            text-align: center;
            color: #6b7280;
        }

        .contrats-empty-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }

        .contrats-empty h3 {
            margin: 0 0 8px;
            color: #111827;
        }

        .contrats-empty p {
            margin: 0;
        }

        .contrats-search-empty {
            display: none;

            background: #fff;
            border: 1px dashed #d1d5db;
            border-radius: 16px;

            padding: 40px;

            text-align: center;
            color: #6b7280;
        }

        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 1100px) {

            .contrats-stats {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .contrat-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {

            .contrats-page {
                padding: 20px;
            }

            .contrats-header {
                flex-direction: column;
            }

            .contrats-stats {
                grid-template-columns: 1fr;
            }

            .contrat-card-header {
                flex-direction: column;
            }

            .contrat-grid {
                grid-template-columns: 1fr;
            }

            .contrat-footer {
                flex-direction: column;
                align-items: stretch;
            }

            .contrat-button {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<div class="dashboard">

    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside class="sidebar">

        <div class="sidebar-logo">
             <div class="logo">
        <h2>LOKA</h2>
        <p>Gestion immobilière</p>
    </div>

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
				 class="active"
            >

                <i class="fa-solid fa-file-contract"></i>

                Mes contrats

            </a>


            <a
                href="/LOKA/app/proprietaires/Revenus/revenus.php"
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


    <!-- =====================================================
         CONTENU
    ====================================================== -->

    <main class="content">

        <div class="contrats-page">

            <a
                href="/LOKA/app/proprietaires/index.php"
                class="contrats-back"
            >
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;margin-right:4px;"><polyline points="15 18 9 12 15 6"/></svg>Retour au tableau de bord
            </a>


            <div class="contrats-header">

                <div>
                    <h1>Mes contrats</h1>

                    <p>
                        Consultez les contrats liés à vos biens.
                    </p>
                </div>

            </div>


            <!-- =================================================
                 STATISTIQUES
            ================================================== -->

            <section class="contrats-stats">

                <div class="contrat-stat-card">

                    <div class="contrat-stat-icon">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg>
                    </div>

                    <div class="contrat-stat-content">

                        <span class="contrat-stat-label">
                            Total des contrats
                        </span>

                        <div class="contrat-stat-value">
                            <?= $totalContrats ?>
                        </div>

                    </div>

                </div>


                <div class="contrat-stat-card">

                    <div class="contrat-stat-icon">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>

                    <div class="contrat-stat-content">

                        <span class="contrat-stat-label">
                            Contrats actifs
                        </span>

                        <div class="contrat-stat-value">
                            <?= $contratsActifs ?>
                        </div>

                    </div>

                </div>


                <div class="contrat-stat-card">

                    <div class="contrat-stat-icon">
                        !
                    </div>

                    <div class="contrat-stat-content">

                        <span class="contrat-stat-label">
                            Contrats expirés
                        </span>

                        <div class="contrat-stat-value">
                            <?= $contratsExpires ?>
                        </div>

                    </div>

                </div>


                <div class="contrat-stat-card">

                    <div class="contrat-stat-icon">
                        €
                    </div>

                    <div class="contrat-stat-content">

                        <span class="contrat-stat-label">
                            Loyers actifs / mois
                        </span>

                        <div class="contrat-stat-value">
                            <?= formatMoney($totalLoyersActifs) ?>
                        </div>

                    </div>

                </div>

            </section>


            <!-- =================================================
                 RECHERCHE
            ================================================== -->

            <div class="contrats-toolbar">

                <div class="contrat-search-wrapper">

                    <span><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></span>

                    <input
                        type="search"
                        id="searchContrat"
                        placeholder="Rechercher par numéro, bien ou locataire..."
                        autocomplete="off"
                    >

                </div>

            </div>


            <?php if (!$contrats): ?>

                <div class="contrats-empty">

                    <div class="contrats-empty-icon">
                        <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg>
                    </div>

                    <h3>
                        Aucun contrat
                    </h3>

                    <p>
                        Aucun contrat n'est actuellement
                        associé à vos biens.
                    </p>

                </div>

            <?php else: ?>

                <div
                    class="contrats-list"
                    id="contratsList"
                >

                    <?php foreach ($contrats as $contrat): ?>

                        <?php
                        $status = getContractStatus(
                            $contrat['statut'] ?? null,
                            $contrat['date_fin'] ?? null
                        );

                        $statusLabel = statutContratLabel(
                            $contrat['statut'] ?? null,
                            $contrat['date_fin'] ?? null
                        );

                        $statusClass = statutContratClass(
                            $contrat['statut'] ?? null,
                            $contrat['date_fin'] ?? null
                        );

                        $locataires =
                            trim((string) ($contrat['locataires'] ?? ''));

                        if ($locataires === '') {
                            $locataires = 'Aucun locataire renseigné';
                        }

                        $searchData = implode(' ', [
                            $contrat['numero'] ?? '',
                            $contrat['reference_bien'] ?? '',
                            $contrat['titre_bien'] ?? '',
                            $locataires,
                            $statusLabel,
                        ]);
                        ?>

                        <article
                            class="contrat-card"
                            data-search="<?= e($searchData) ?>"
                        >

                            <div class="contrat-card-header">

                                <div>

                                    <h2 class="contrat-number">
                                        <?= e($contrat['numero']) ?>
                                    </h2>

                                    <div class="contrat-property">

                                        <strong>
                                            <?= e($contrat['reference_bien']) ?>
                                        </strong>

                                        —

                                        <?= e($contrat['titre_bien']) ?>

                                    </div>

                                </div>


                                <span
                                    class="contrat-status <?= e($statusClass) ?>"
                                >
                                    <?= e($statusLabel) ?>
                                </span>

                            </div>


                            <div class="contrat-grid">

                                <div class="contrat-info">

                                    <span class="contrat-info-label">
                                        Début
                                    </span>

                                    <div class="contrat-info-value">
                                        <?= formatDateFr($contrat['date_debut']) ?>
                                    </div>

                                </div>


                                <div class="contrat-info">

                                    <span class="contrat-info-label">
                                        Fin
                                    </span>

                                    <div class="contrat-info-value">
                                        <?= formatDateFr($contrat['date_fin']) ?>
                                    </div>

                                </div>


                                <div class="contrat-info">

                                    <span class="contrat-info-label">
                                        Loyer
                                    </span>

                                    <div class="contrat-info-value price">
                                        <?= formatMoney($contrat['loyer']) ?>
                                    </div>

                                </div>


                                <div class="contrat-info">

                                    <span class="contrat-info-label">
                                        Charges
                                    </span>

                                    <div class="contrat-info-value price">
                                        <?= formatMoney($contrat['charges']) ?>
                                    </div>

                                </div>


                                <div class="contrat-info">

                                    <span class="contrat-info-label">
                                        Dépôt de garantie
                                    </span>

                                    <div class="contrat-info-value">
                                        <?= formatMoney($contrat['depot_garantie']) ?>
                                    </div>

                                </div>


                                <div class="contrat-info">

                                    <span class="contrat-info-label">
                                        Paiement
                                    </span>

                                    <div class="contrat-info-value">
                                        <?= e(
                                            frequencePaiementLabel(
                                                $contrat['frequence_paiement']
                                            )
                                        ) ?>
                                    </div>

                                </div>


                                <div class="contrat-info">

                                    <span class="contrat-info-label">
                                        Jour d'échéance
                                    </span>

                                    <div class="contrat-info-value">
                                        <?= e(
                                            $contrat['jour_echeance']
                                                ? (string) $contrat['jour_echeance']
                                                : '—'
                                        ) ?>
                                    </div>

                                </div>


                                <div class="contrat-info">

                                    <span class="contrat-info-label">
                                        Total mensuel
                                    </span>

                                    <div class="contrat-info-value price">
                                        <?php
                                        if (
                                            $contrat['loyer'] !== null
                                            ||
                                            $contrat['charges'] !== null
                                        ) {
                                            echo formatMoney(
                                                (float) ($contrat['loyer'] ?? 0)
                                                +
                                                (float) ($contrat['charges'] ?? 0)
                                            );
                                        } else {
                                            echo '—';
                                        }
                                        ?>
                                    </div>

                                </div>

                            </div>


                            <div class="contrat-footer">

                                <div class="contrat-locataire">

                                    <strong>
                                        Locataire(s) :
                                    </strong>

                                    <?= e($locataires) ?>

                                </div>


                                <a
                                    href="/LOKA/app/proprietaires/Contrat/detailContrat.php?id=<?= (int) $contrat['id_contrat'] ?>"
                                    class="contrat-button"
                                >
                                    Voir le contrat <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;margin-left:2px;"><polyline points="9 18 15 12 9 6"/></svg>
                                </a>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>


                <div
                    id="contratsSearchEmpty"
                    class="contrats-search-empty"
                >
                    Aucun contrat ne correspond à votre recherche.
                </div>

            <?php endif; ?>

        </div>

    </main>

</div>


<script>
document.addEventListener('DOMContentLoaded', function () {

    const searchInput =
        document.getElementById('searchContrat');

    const cards =
        document.querySelectorAll('.contrat-card');

    const emptyMessage =
        document.getElementById('contratsSearchEmpty');

    if (!searchInput) {
        return;
    }

    function rechercherContrat() {

        const recherche =
            searchInput.value
                .toLowerCase()
                .trim();

        let nombreVisible = 0;

        cards.forEach(function (card) {

            const contenu =
                (
                    card.dataset.search || ''
                ).toLowerCase();

            const visible =
                recherche === ''
                ||
                contenu.includes(recherche);

            card.style.display =
                visible ? '' : 'none';

            if (visible) {
                nombreVisible++;
            }
        });

        if (emptyMessage) {
            emptyMessage.style.display =
                nombreVisible === 0
                ? 'block'
                : 'none';
        }
    }

    searchInput.addEventListener(
        'input',
        rechercherContrat
    );

    searchInput.addEventListener(
        'keydown',
        function (event) {

            if (event.key === 'Escape') {

                searchInput.value = '';

                rechercherContrat();

                searchInput.blur();
            }
        }
    );

});
</script>

</body>
</html>