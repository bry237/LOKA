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
   FONCTIONS
========================================================= */

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

function statutLabel(?string $statut): string
{
    return match (strtoupper((string) $statut)) {
        'DRAFT'      => 'Brouillon',
        'ACTIVE'     => 'Actif',
        'EXPIRED'    => 'Expiré',
        'RENEWED'    => 'Renouvelé',
        'TERMINATED' => 'Terminé',
        'ARCHIVED'   => 'Archivé',
        default      => $statut ?: 'Non renseigné',
    };
}

function statutClass(?string $statut): string
{
    return match (strtoupper((string) $statut)) {
        'ACTIVE'     => 'status-active',
        'DRAFT'      => 'status-draft',
        'EXPIRED'    => 'status-expired',
        'RENEWED'    => 'status-renewed',
        'TERMINATED' => 'status-terminated',
        'ARCHIVED'   => 'status-archived',
        default      => 'status-default',
    };
}

function frequenceLabel(?string $frequence): string
{
    return match (strtoupper((string) $frequence)) {
        'MONTHLY'   => 'Mensuel',
        'QUARTERLY' => 'Trimestriel',
        'YEARLY'    => 'Annuel',
        default     => $frequence ?: '—',
    };
}


/* =========================================================
   ID DU CONTRAT
========================================================= */

$idContrat = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$idContrat || $idContrat <= 0) {
    http_response_code(400);
    exit('Contrat invalide.');
}


/* =========================================================
   UTILISATEUR CONNECTE
========================================================= */

$idUtilisateur = (int) ($auth['id_utilisateur'] ?? 0);

if ($idUtilisateur <= 0) {
    http_response_code(403);
    exit('Utilisateur non authentifié.');
}


/* =========================================================
   PROPRIETAIRE
========================================================= */

$stmt = $pdo->prepare("
    SELECT
        p.id_proprietaire,
        p.nom,
        p.prenom,
        p.email
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
   CONTRAT
   IMPORTANT :
   le contrat doit appartenir à un bien du propriétaire.
========================================================= */

$stmt = $pdo->prepare("
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
        c.motif_resiliation,
        c.date_resiliation,
        c.chemin_fichier_pdf,
        c.created_at,
        c.updated_at,

        b.reference AS reference_bien,
        b.titre AS titre_bien,
        b.surface,
        b.nombre_pieces,
        b.etage,
        b.description AS description_bien

    FROM contrat c

    INNER JOIN bien b
        ON b.id_bien = c.id_bien

    INNER JOIN bien_proprietaire bp
        ON bp.id_bien = b.id_bien

    WHERE c.id_contrat = ?
      AND bp.id_proprietaire = ?
      AND c.deleted_at IS NULL
      AND b.deleted_at IS NULL

    LIMIT 1
");

$stmt->execute([
    $idContrat,
    $idProprietaire
]);

$contrat = $stmt->fetch();

if (!$contrat) {
    http_response_code(404);
    exit('Contrat introuvable ou non accessible.');
}


/* =========================================================
   LOCATAIRES DU CONTRAT
========================================================= */

$stmt = $pdo->prepare("
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
        l.profession,
        l.statut AS statut_locataire,

        cl.titulaire_principal,
        cl.date_entree,
        cl.date_sortie

    FROM contrat_locataire cl

    INNER JOIN locataire l
        ON l.id_locataire = cl.id_locataire

    WHERE cl.id_contrat = ?
      AND l.deleted_at IS NULL

    ORDER BY
        cl.titulaire_principal DESC,
        l.nom ASC,
        l.prenom ASC
");

$stmt->execute([$idContrat]);

$locataires = $stmt->fetchAll();


/* =========================================================
   CALCULS
========================================================= */

$totalMensuel =
    (float) ($contrat['loyer'] ?? 0)
    +
    (float) ($contrat['charges'] ?? 0);

$statut = strtoupper(
    (string) ($contrat['statut'] ?? '')
);

$nomBien = trim(
    ($contrat['reference_bien'] ?? '')
    . ' — '
    . ($contrat['titre_bien'] ?? '')
);

if ($nomBien === '—') {
    $nomBien = 'Bien immobilier';
}


/* =========================================================
   PDF
========================================================= */

$pdfUrl = null;

if (!empty($contrat['chemin_fichier_pdf'])) {

    $pdf = trim(
        (string) $contrat['chemin_fichier_pdf']
    );

    if (
        str_starts_with($pdf, 'http://')
        ||
        str_starts_with($pdf, 'https://')
    ) {
        $pdfUrl = $pdf;
    } else {
        $pdfUrl = '/LOKA/' . ltrim($pdf, '/');
    }
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
        <?= e($contrat['numero']) ?> - LOKA
    </title>

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
           PAGE
        ===================================================== */

        .detail-contrat-page {
            padding: 30px;
        }

        .detail-contrat-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;

            margin-bottom: 20px;

            color: #374151;
            text-decoration: none;
            font-weight: 600;
        }

        .detail-contrat-back:hover {
            color: #111827;
        }


        /* =====================================================
           HEADER
        ===================================================== */

        .detail-contrat-header {
            background: #fff;

            border: 1px solid #e5e7eb;
            border-radius: 18px;

            padding: 25px;

            margin-bottom: 22px;

            display: flex;
            justify-content: space-between;
            align-items: flex-start;

            gap: 20px;

            box-shadow:
                0 3px 12px rgba(0,0,0,.04);
        }

        .detail-contrat-title {
            margin: 0 0 8px;

            font-size: 26px;
            color: #111827;
        }

        .detail-contrat-property {
            color: #6b7280;
            font-size: 15px;
        }

        .detail-contrat-property strong {
            color: #374151;
        }

        .detail-contrat-status {
            display: inline-flex;

            padding: 8px 14px;

            border-radius: 999px;

            font-size: 13px;
            font-weight: 700;

            white-space: nowrap;
        }


        /* =====================================================
           STATUS
        ===================================================== */

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
           SECTIONS
        ===================================================== */

        .detail-contrat-section {
            background: #fff;

            border: 1px solid #e5e7eb;
            border-radius: 18px;

            padding: 24px;

            margin-bottom: 22px;

            box-shadow:
                0 3px 12px rgba(0,0,0,.04);
        }

        .detail-contrat-section-title {
            margin: 0 0 20px;

            font-size: 19px;
            color: #111827;
        }


        /* =====================================================
           GRILLE
        ===================================================== */

        .detail-contrat-grid {
            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap: 15px;
        }

        .detail-contrat-field {
            background: #f9fafb;

            border-radius: 11px;

            padding: 15px;
        }

        .detail-contrat-field-label {
            display: block;

            color: #6b7280;

            font-size: 12px;

            margin-bottom: 6px;
        }

        .detail-contrat-field-value {
            color: #111827;

            font-size: 15px;
            font-weight: 600;
        }

        .detail-contrat-field-value.price {
            font-size: 18px;
        }


        /* =====================================================
           LOCATAIRES
        ===================================================== */

        .contrat-locataires-list {
            display: grid;
            gap: 15px;
        }

        .contrat-locataire-card {
            border: 1px solid #e5e7eb;
            border-radius: 13px;

            padding: 18px;
        }

        .contrat-locataire-header {
            display: flex;
            justify-content: space-between;
            align-items: center;

            gap: 15px;

            margin-bottom: 15px;
        }

        .contrat-locataire-name {
            margin: 0;

            font-size: 17px;
            color: #111827;
        }

        .contrat-principal {
            display: inline-flex;

            padding: 5px 9px;

            border-radius: 999px;

            background: #dbeafe;
            color: #1d4ed8;

            font-size: 11px;
            font-weight: 700;
        }

        .contrat-locataire-grid {
            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 12px;
        }

        .contrat-locataire-info {
            color: #4b5563;
            font-size: 14px;
        }

        .contrat-locataire-info strong {
            color: #111827;
        }


        /* =====================================================
           BIEN
        ===================================================== */

        .bien-contrat-description {
            margin-top: 18px;

            color: #4b5563;

            line-height: 1.6;
        }


        /* =====================================================
           PDF / RESILIATION
        ===================================================== */

        .contrat-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .contrat-action-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            padding: 11px 16px;

            border-radius: 9px;

            text-decoration: none;

            font-size: 14px;
            font-weight: 600;
        }

        .contrat-action-primary {
            background: #111827;
            color: #fff;
        }

        .contrat-action-primary:hover {
            background: #1f2937;
        }

        .contrat-action-secondary {
            background: #f3f4f6;
            color: #374151;
        }

        .contrat-action-secondary:hover {
            background: #e5e7eb;
        }

        .contrat-resiliation {
            background: #fff7ed;

            border: 1px solid #fed7aa;

            border-radius: 12px;

            padding: 16px;

            color: #9a3412;
        }

        .contrat-resiliation strong {
            display: block;
            margin-bottom: 5px;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 1100px) {

            .detail-contrat-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .contrat-locataire-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {

            .detail-contrat-page {
                padding: 20px;
            }

            .detail-contrat-header {
                flex-direction: column;
            }

            .detail-contrat-grid,
            .contrat-locataire-grid {
                grid-template-columns: 1fr;
            }

            .detail-contrat-status {
                align-self: flex-start;
            }
        }

        .contrat-document {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 20px;
    border: 1px solid #e5e5e5;
    border-radius: 12px;
    background: #fafafa;
}

.contrat-document-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.contrat-document-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border-radius: 10px;
    background: #f0f0f0;
    font-size: 24px;
}

.contrat-document-info strong {
    display: block;
    margin-bottom: 5px;
}

.contrat-document-info p {
    margin: 0;
    color: #777;
    font-size: 14px;
}

.contrat-document-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.contrat-document-empty {
    background: #fcfcfc;
}

@media (max-width: 700px) {

    .contrat-document {
        flex-direction: column;
        align-items: flex-start;
    }

    .contrat-document-actions {
        width: 100%;
    }

    .contrat-document-actions .contrat-action-button {
        width: 100%;
        box-sizing: border-box;
        justify-content: center;
    }
}


.contrat-action-danger {
    background: #fff1f1;
    color: #b42318;
    border: 1px solid #f3c7c3;
}

.contrat-action-danger:hover {
    background: #b42318;
    color: #fff;
}

    </style>

</head>

<body>

<div class="dashboard">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <?php $pageActive = 'contrats'; require dirname(__DIR__) . '/sidebar.php'; ?>


    <!-- =====================================================
         CONTENU
    ====================================================== -->

    <main class="content">

        <div class="detail-contrat-page">


            <!-- RETOUR -->

            <a
                href="/LOKA/app/proprietaires/Contrat/contrat.php"
                class="detail-contrat-back"
            >
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;margin-right:4px;"><polyline points="15 18 9 12 15 6"/></svg>Retour à mes contrats
            </a>


            <!-- =================================================
                 HEADER
            ================================================== -->

            <header class="detail-contrat-header">

                <div>

                    <h1 class="detail-contrat-title">
                        Contrat
                        <?= e($contrat['numero']) ?>
                    </h1>

                    <div class="detail-contrat-property">

                        <strong>
                            <?= e($contrat['reference_bien']) ?>
                        </strong>

                        —

                        <?= e($contrat['titre_bien']) ?>

                    </div>

                </div>


                <span
                    class="detail-contrat-status <?= e(statutClass($contrat['statut'])) ?>"
                >
                    <?= e(statutLabel($contrat['statut'])) ?>
                </span>

            </header>


            <!-- =================================================
                 INFORMATIONS DU CONTRAT
            ================================================== -->

            <section class="detail-contrat-section">

                <h2 class="detail-contrat-section-title">
                    Informations du contrat
                </h2>


                <div class="detail-contrat-grid">

                    <div class="detail-contrat-field">

                        <span class="detail-contrat-field-label">
                            Numéro du contrat
                        </span>

                        <div class="detail-contrat-field-value">
                            <?= e($contrat['numero']) ?>
                        </div>

                    </div>


                    <div class="detail-contrat-field">

                        <span class="detail-contrat-field-label">
                            Date de début
                        </span>

                        <div class="detail-contrat-field-value">
                            <?= formatDateFr($contrat['date_debut']) ?>
                        </div>

                    </div>


                    <div class="detail-contrat-field">

                        <span class="detail-contrat-field-label">
                            Date de fin
                        </span>

                        <div class="detail-contrat-field-value">
                            <?= formatDateFr($contrat['date_fin']) ?>
                        </div>

                    </div>


                    <div class="detail-contrat-field">

                        <span class="detail-contrat-field-label">
                            Fréquence
                        </span>

                        <div class="detail-contrat-field-value">
                            <?= e(
                                frequenceLabel(
                                    $contrat['frequence_paiement']
                                )
                            ) ?>
                        </div>

                    </div>


                    <div class="detail-contrat-field">

                        <span class="detail-contrat-field-label">
                            Loyer
                        </span>

                        <div class="detail-contrat-field-value price">
                            <?= formatMoney($contrat['loyer']) ?>
                        </div>

                    </div>


                    <div class="detail-contrat-field">

                        <span class="detail-contrat-field-label">
                            Charges
                        </span>

                        <div class="detail-contrat-field-value price">
                            <?= formatMoney($contrat['charges']) ?>
                        </div>

                    </div>


                    <div class="detail-contrat-field">

                        <span class="detail-contrat-field-label">
                            Total mensuel
                        </span>

                        <div class="detail-contrat-field-value price">
                            <?= formatMoney($totalMensuel) ?>
                        </div>

                    </div>


                    <div class="detail-contrat-field">

                        <span class="detail-contrat-field-label">
                            Dépôt de garantie
                        </span>

                        <div class="detail-contrat-field-value price">
                            <?= formatMoney($contrat['depot_garantie']) ?>
                        </div>

                    </div>


                    <div class="detail-contrat-field">

                        <span class="detail-contrat-field-label">
                            Jour d'échéance
                        </span>

                        <div class="detail-contrat-field-value">
                            <?= e(
                                $contrat['jour_echeance']
                                    ? (string) $contrat['jour_echeance']
                                    : '—'
                            ) ?>
                        </div>

                    </div>


                    <div class="detail-contrat-field">

                        <span class="detail-contrat-field-label">
                            Créé le
                        </span>

                        <div class="detail-contrat-field-value">
                            <?= formatDateFr(
                                $contrat['created_at']
                            ) ?>
                        </div>

                    </div>


                    <div class="detail-contrat-field">

                        <span class="detail-contrat-field-label">
                            Dernière modification
                        </span>

                        <div class="detail-contrat-field-value">
                            <?= formatDateFr(
                                $contrat['updated_at']
                            ) ?>
                        </div>

                    </div>

                </div>

            </section>


            <!-- =================================================
                 LOCATAIRES
            ================================================== -->

            <section class="detail-contrat-section">

                <h2 class="detail-contrat-section-title">
                    Locataire(s)
                </h2>


                <?php if (!$locataires): ?>

                    <p>
                        Aucun locataire n'est associé à ce contrat.
                    </p>

                <?php else: ?>

                    <div class="contrat-locataires-list">

                        <?php foreach ($locataires as $locataire): ?>

                            <?php
                            $nomLocataire = trim(
                                ($locataire['prenom'] ?? '')
                                . ' '
                                . ($locataire['nom'] ?? '')
                            );

                            if ($nomLocataire === '') {
                                $nomLocataire = 'Locataire';
                            }
                            ?>

                            <div class="contrat-locataire-card">

                                <div class="contrat-locataire-header">

                                    <h3 class="contrat-locataire-name">
                                        <?= e($nomLocataire) ?>
                                    </h3>

                                    <?php if (
                                        (int) $locataire['titulaire_principal'] === 1
                                    ): ?>

                                        <span class="contrat-principal">
                                            Titulaire principal
                                        </span>

                                    <?php endif; ?>

                                </div>


                                <div class="contrat-locataire-grid">

                                    <div class="contrat-locataire-info">
                                        <strong>Email :</strong>
                                        <?= e(
                                            $locataire['email'] ?: '—'
                                        ) ?>
                                    </div>

                                    <div class="contrat-locataire-info">
                                        <strong>Téléphone :</strong>
                                        <?= e(
                                            $locataire['telephone'] ?: '—'
                                        ) ?>
                                    </div>

                                    <div class="contrat-locataire-info">
                                        <strong>Profession :</strong>
                                        <?= e(
                                            $locataire['profession'] ?: '—'
                                        ) ?>
                                    </div>

                                    <div class="contrat-locataire-info">
                                        <strong>Entrée :</strong>
                                        <?= formatDateFr(
                                            $locataire['date_entree']
                                        ) ?>
                                    </div>

                                    <div class="contrat-locataire-info">
                                        <strong>Sortie :</strong>
                                        <?= formatDateFr(
                                            $locataire['date_sortie']
                                        ) ?>
                                    </div>

                                    <div class="contrat-locataire-info">
                                        <strong>Ville :</strong>
                                        <?= e(
                                            $locataire['ville'] ?: '—'
                                        ) ?>
                                    </div>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </section>


            <!-- =================================================
                 BIEN
            ================================================== -->

            <section class="detail-contrat-section">

                <h2 class="detail-contrat-section-title">
                    Bien concerné
                </h2>


                <div class="detail-contrat-grid">

                    <div class="detail-contrat-field">

                        <span class="detail-contrat-field-label">
                            Référence
                        </span>

                        <div class="detail-contrat-field-value">
                            <?= e($contrat['reference_bien']) ?>
                        </div>

                    </div>


                    <div class="detail-contrat-field">

                        <span class="detail-contrat-field-label">
                            Titre
                        </span>

                        <div class="detail-contrat-field-value">
                            <?= e($contrat['titre_bien']) ?>
                        </div>

                    </div>


                    <div class="detail-contrat-field">

                        <span class="detail-contrat-field-label">
                            Surface
                        </span>

                        <div class="detail-contrat-field-value">
                            <?= $contrat['surface'] !== null
                                ? e((string) $contrat['surface']) . ' m²'
                                : '—'
                            ?>
                        </div>

                    </div>


                    <div class="detail-contrat-field">

                        <span class="detail-contrat-field-label">
                            Nombre de pièces
                        </span>

                        <div class="detail-contrat-field-value">
                            <?= e(
                                $contrat['nombre_pieces']
                                    ? (string) $contrat['nombre_pieces']
                                    : '—'
                            ) ?>
                        </div>

                    </div>


                    <div class="detail-contrat-field">

                        <span class="detail-contrat-field-label">
                            Étage
                        </span>

                        <div class="detail-contrat-field-value">
                            <?= e(
                                $contrat['etage']
                                    ? (string) $contrat['etage']
                                    : '—'
                            ) ?>
                        </div>

                    </div>

                </div>


                <?php if (!empty($contrat['description_bien'])): ?>

                    <div class="bien-contrat-description">

                        <?= nl2br(
                            e($contrat['description_bien'])
                        ) ?>

                    </div>

                <?php endif; ?>

            </section>


            <!-- =================================================
                 RESILIATION
            ================================================== -->

            <?php if (
                !empty($contrat['motif_resiliation'])
                ||
                !empty($contrat['date_resiliation'])
            ): ?>

                <section class="detail-contrat-section">

                    <h2 class="detail-contrat-section-title">
                        Résiliation
                    </h2>

                    <div class="contrat-resiliation">

                        <?php if (
                            !empty($contrat['date_resiliation'])
                        ): ?>

                            <strong>
                                Date de résiliation
                            </strong>

                            <?= formatDateFr(
                                $contrat['date_resiliation']
                            ) ?>

                        <?php endif; ?>


                        <?php if (
                            !empty($contrat['motif_resiliation'])
                        ): ?>

                            <br>

                            <strong>
                                Motif
                            </strong>

                            <?= nl2br(
                                e($contrat['motif_resiliation'])
                            ) ?>

                        <?php endif; ?>

                    </div>

                </section>

            <?php endif; ?>


             <section class="detail-contrat-section">

    <h2 class="detail-contrat-section-title">
        Gestion du contrat
    </h2>

    <div class="contrat-actions">

     <a
    href="/LOKA/app/proprietaires/Contrat/modifierContrat.php?id=<?= (int) $contrat['id_contrat'] ?>"
    class="contrat-action-button contrat-action-primary"
>
    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;margin-right:4px;"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>Modifier le contrat
</a>

<?php if (in_array($contrat['statut'], ['ACTIVE', 'EXPIRED'], true)): ?> <a
                href="/LOKA/app/proprietaires/Contrat/renouvelerContrat.php?id=<?= (int) $contrat['id_contrat'] ?>"
                class="contrat-action-button contrat-action-secondary"
            >
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;margin-right:4px;"><path d="M21 12a9 9 0 1 1-2.6-6.4"/><polyline points="21 3 21 9 15 9"/></svg>Renouveler le contrat
            </a>

        <?php endif; ?>

    </div>

</section>

            <!-- =================================================
                 DOCUMENT
            ================================================== -->

           <section class="detail-contrat-section">

    <h2 class="detail-contrat-section-title">
        Document du contrat
    </h2>

    <?php if (!empty($contrat['chemin_fichier_pdf'])): ?>

    <div class="contrat-document-actions">

        <a
            href="/LOKA/<?= e(ltrim($contrat['chemin_fichier_pdf'], '/')) ?>"
            target="_blank"
            rel="noopener noreferrer"
            class="contrat-action-button contrat-action-secondary"
        >
            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;margin-right:4px;"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg>Voir le PDF
        </a>

        <a
            href="/LOKA/app/proprietaires/gerer_pdf_contrat.php?id=<?= (int) $contrat['id_contrat'] ?>"
            class="contrat-action-button contrat-action-secondary"
        >
            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;margin-right:4px;"><path d="M21 12a9 9 0 1 1-2.6-6.4"/><polyline points="21 3 21 9 15 9"/></svg>Remplacer le PDF
        </a>

        <a
            href="/LOKA/app/proprietaires/Contrat/supprimerContrat.php?id=<?= (int) $contrat['id_contrat'] ?>"
            class="contrat-action-button contrat-action-danger"
            onclick="return confirm('Voulez-vous vraiment supprimer le PDF de ce contrat ?');"
        >
            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;margin-right:4px;"><path d="M4 7h16"/><path d="M10 11v6M14 11v6"/><path d="M6 7l1 13a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-13"/><path d="M9 7V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v3"/></svg>Supprimer le PDF
        </a>

    </div>

<?php else: ?>

    <div class="contrat-document-empty">
        <span class="contrat-document-icon"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg></span>

        <div>
            <strong>Aucun PDF</strong>
            <p>Le contrat ne possède pas encore de document PDF.</p>
        </div>
    </div>

    <div class="contrat-document-actions">

        <a
            href="/LOKA/app/proprietaires/Contrat/gererPdfContrat.php?id=<?= (int) $contrat['id_contrat'] ?>"
            class="contrat-action-button contrat-action-primary"
        >
            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;margin-right:4px;"><path d="M12 21V9"/><path d="m7 14 5-5 5 5"/><path d="M4 5h16"/></svg>Ajouter le PDF
        </a>

    </div>

<?php endif; ?>

</section>


        </div>

    </main>

</div>

</body>
</html>