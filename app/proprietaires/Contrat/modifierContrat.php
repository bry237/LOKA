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

function formatDateInput(?string $date): string
{
    if (!$date) {
        return '';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return '';
    }

    return date('Y-m-d', $timestamp);
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
   RECUPERATION DU CONTRAT
   Vérification propriétaire + bien
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

        b.reference AS reference_bien,
        b.titre AS titre_bien

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
   TRAITEMENT DU FORMULAIRE
========================================================= */

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $numero = trim(
        (string) ($_POST['numero'] ?? '')
    );

    $dateDebut = trim(
        (string) ($_POST['date_debut'] ?? '')
    );

    $dateFin = trim(
        (string) ($_POST['date_fin'] ?? '')
    );

    $loyer = trim(
        (string) ($_POST['loyer'] ?? '')
    );

    $charges = trim(
        (string) ($_POST['charges'] ?? '')
    );

    $depotGarantie = trim(
        (string) ($_POST['depot_garantie'] ?? '')
    );

    $frequencePaiement = strtoupper(
        trim(
            (string) ($_POST['frequence_paiement'] ?? '')
        )
    );

    $jourEcheance = trim(
        (string) ($_POST['jour_echeance'] ?? '')
    );

    $statut = strtoupper(
        trim(
            (string) ($_POST['statut'] ?? '')
        )
    );

    $motifResiliation = trim(
        (string) ($_POST['motif_resiliation'] ?? '')
    );

    $dateResiliation = trim(
        (string) ($_POST['date_resiliation'] ?? '')
    );


    /* =====================================================
       VALIDATION
    ====================================================== */

    if ($numero === '') {
        $errors[] = 'Le numéro du contrat est obligatoire.';
    }

    if ($dateDebut === '') {
        $errors[] = 'La date de début est obligatoire.';
    }

    if ($dateFin === '') {
        $errors[] = 'La date de fin est obligatoire.';
    }

    if (
        $dateDebut !== ''
        &&
        $dateFin !== ''
        &&
        strtotime($dateFin) < strtotime($dateDebut)
    ) {
        $errors[] =
            'La date de fin doit être postérieure ou égale à la date de début.';
    }


    if ($loyer === '' || !is_numeric($loyer)) {
        $errors[] = 'Le loyer doit être un montant valide.';
    } elseif ((float) $loyer < 0) {
        $errors[] = 'Le loyer ne peut pas être négatif.';
    }


    if ($charges !== '' && !is_numeric($charges)) {
        $errors[] = 'Les charges doivent être un montant valide.';
    } elseif ($charges !== '' && (float) $charges < 0) {
        $errors[] = 'Les charges ne peuvent pas être négatives.';
    }


    if (
        $depotGarantie !== ''
        &&
        !is_numeric($depotGarantie)
    ) {
        $errors[] =
            'Le dépôt de garantie doit être un montant valide.';
    } elseif (
        $depotGarantie !== ''
        &&
        (float) $depotGarantie < 0
    ) {
        $errors[] =
            'Le dépôt de garantie ne peut pas être négatif.';
    }


    $frequencesAutorisees = [
        'MONTHLY',
        'QUARTERLY',
        'YEARLY',
    ];

    if (!in_array(
        $frequencePaiement,
        $frequencesAutorisees,
        true
    )) {
        $errors[] =
            'La fréquence de paiement sélectionnée est invalide.';
    }


    if ($jourEcheance !== '') {

        if (
            !ctype_digit($jourEcheance)
            ||
            (int) $jourEcheance < 1
            ||
            (int) $jourEcheance > 31
        ) {
            $errors[] =
                'Le jour d’échéance doit être compris entre 1 et 31.';
        }

    }


    $statutsAutorises = [
        'DRAFT',
        'ACTIVE',
        'EXPIRED',
        'RENEWED',
        'TERMINATED',
        'ARCHIVED',
    ];

    if (!in_array(
        $statut,
        $statutsAutorises,
        true
    )) {
        $errors[] =
            'Le statut sélectionné est invalide.';
    }


    if ($statut === 'TERMINATED') {

        if ($dateResiliation === '') {
            $errors[] =
                'La date de résiliation est obligatoire pour un contrat terminé.';
        }

    }


    /* =====================================================
       ENREGISTREMENT
    ====================================================== */

    if (!$errors) {

        try {

            $stmt = $pdo->prepare("
                UPDATE contrat
                SET
                    numero = ?,
                    date_debut = ?,
                    date_fin = ?,
                    loyer = ?,
                    charges = ?,
                    depot_garantie = ?,
                    frequence_paiement = ?,
                    jour_echeance = ?,
                    statut = ?,
                    motif_resiliation = ?,
                    date_resiliation = ?,
                    updated_at = NOW()
                WHERE id_contrat = ?
                  AND deleted_at IS NULL
            ");

            $stmt->execute([
                $numero,
                $dateDebut,
                $dateFin,
                (float) $loyer,
                $charges === ''
                    ? null
                    : (float) $charges,
                $depotGarantie === ''
                    ? null
                    : (float) $depotGarantie,
                $frequencePaiement,
                $jourEcheance === ''
                    ? null
                    : (int) $jourEcheance,
                $statut,
                $motifResiliation !== ''
                    ? $motifResiliation
                    : null,
                $dateResiliation !== ''
                    ? $dateResiliation
                    : null,
                $idContrat,
            ]);


            header(
                'Location: /LOKA/app/proprietaires/Contrat/detailContrat.php?id='
                . $idContrat
                . '&updated=1'
            );

            exit;

        } catch (PDOException $e) {

            $errors[] =
                'Impossible d’enregistrer les modifications. '
                . 'Vérifie notamment que le numéro du contrat est unique.';
        }
    }


    /*
     * On conserve les valeurs saisies
     * en cas d'erreur.
     */
    $contrat['numero'] =
        $numero;

    $contrat['date_debut'] =
        $dateDebut;

    $contrat['date_fin'] =
        $dateFin;

    $contrat['loyer'] =
        $loyer;

    $contrat['charges'] =
        $charges;

    $contrat['depot_garantie'] =
        $depotGarantie;

    $contrat['frequence_paiement'] =
        $frequencePaiement;

    $contrat['jour_echeance'] =
        $jourEcheance;

    $contrat['statut'] =
        $statut;

    $contrat['motif_resiliation'] =
        $motifResiliation;

    $contrat['date_resiliation'] =
        $dateResiliation;
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
        Modifier <?= e($contrat['numero']) ?> - LOKA
    </title>

    <link
        rel="stylesheet"
        href="/LOKA/app/proprietaires/proprietaire.css"
    >

    <style>

        .modifier-contrat-page {
            padding: 30px;
            max-width: 1100px;
        }

        .modifier-contrat-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;

            margin-bottom: 20px;

            color: #374151;
            text-decoration: none;
            font-weight: 600;
        }

        .modifier-contrat-header {
            margin-bottom: 22px;
        }

        .modifier-contrat-header h1 {
            margin: 0 0 8px;
            color: #111827;
        }

        .modifier-contrat-header p {
            margin: 0;
            color: #6b7280;
        }

        .modifier-contrat-card {
            background: #fff;

            border: 1px solid #e5e7eb;
            border-radius: 18px;

            padding: 25px;

            margin-bottom: 22px;

            box-shadow:
                0 3px 12px rgba(0,0,0,.04);
        }

        .modifier-contrat-section-title {
            margin: 0 0 20px;

            font-size: 19px;
            color: #111827;
        }

        .modifier-contrat-grid {
            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            box-sizing: border-box;

            border: 1px solid #d1d5db;
            border-radius: 9px;

            padding: 11px 12px;

            font-size: 14px;
            color: #111827;

            background: #fff;

            outline: none;
        }

        .form-group textarea {
            min-height: 110px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #2563eb;

            box-shadow:
                0 0 0 3px rgba(37,99,235,.10);
        }

        .bien-info {
            background: #f9fafb;

            border-radius: 12px;

            padding: 16px;

            color: #4b5563;
        }

        .bien-info strong {
            color: #111827;
        }

        .error-box {
            background: #fef2f2;

            border: 1px solid #fecaca;

            border-radius: 12px;

            padding: 15px 18px;

            margin-bottom: 22px;

            color: #991b1b;
        }

        .error-box ul {
            margin: 8px 0 0;
            padding-left: 20px;
        }

        .modifier-contrat-actions {
            display: flex;
            justify-content: flex-end;

            gap: 12px;

            margin-top: 22px;
        }

        .btn-contrat {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            padding: 11px 17px;

            border-radius: 9px;

            border: none;

            text-decoration: none;

            font-size: 14px;
            font-weight: 600;

            cursor: pointer;
        }

        .btn-cancel {
            background: #f3f4f6;
            color: #374151;
        }

        .btn-cancel:hover {
            background: #e5e7eb;
        }

        .btn-save {
            background: #111827;
            color: #fff;
        }

        .btn-save:hover {
            background: #1f2937;
        }

        .resiliation-note {
            grid-column: 1 / -1;

            background: #fff7ed;

            border: 1px solid #fed7aa;

            border-radius: 10px;

            padding: 13px;

            color: #9a3412;

            font-size: 13px;
        }

        @media (max-width: 768px) {

            .modifier-contrat-page {
                padding: 20px;
            }

            .modifier-contrat-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full,
            .resiliation-note {
                grid-column: auto;
            }

            .modifier-contrat-actions {
                flex-direction: column;
            }

            .btn-contrat {
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
            <h2>LOKA</h2>
        </div>

        <nav class="sidebar-nav">

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

        </nav>

    </aside>


    <!-- =====================================================
         CONTENU
    ====================================================== -->

    <main class="content">

        <div class="modifier-contrat-page">

            <a
                href="/LOKA/app/proprietaires/Contrat/detailContrat.php?id=<?= $idContrat ?>"
                class="modifier-contrat-back"
            >
                ← Retour au contrat
            </a>


            <div class="modifier-contrat-header">

                <h1>
                    Modifier le contrat
                    <?= e($contrat['numero']) ?>
                </h1>

                <p>
                    Modifiez les informations administratives
                    et financières du contrat.
                </p>

            </div>


            <!-- =================================================
                 ERREURS
            ================================================== -->

            <?php if ($errors): ?>

                <div class="error-box">

                    <strong>
                        Impossible d'enregistrer les modifications.
                    </strong>

                    <ul>

                        <?php foreach ($errors as $error): ?>

                            <li>
                                <?= e($error) ?>
                            </li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 BIEN
            ================================================== -->

            <section class="modifier-contrat-card">

                <h2 class="modifier-contrat-section-title">
                    Bien concerné
                </h2>

                <div class="bien-info">

                    <strong>
                        <?= e($contrat['reference_bien']) ?>
                    </strong>

                    —

                    <?= e($contrat['titre_bien']) ?>

                </div>

            </section>


            <!-- =================================================
                 FORMULAIRE
            ================================================== -->

            <form
                method="post"
                action=""
            >

                <section class="modifier-contrat-card">

                    <h2 class="modifier-contrat-section-title">
                        Informations du contrat
                    </h2>


                    <div class="modifier-contrat-grid">


                        <!-- NUMERO -->

                        <div class="form-group">

                            <label for="numero">
                                Numéro du contrat *
                            </label>

                            <input
                                type="text"
                                id="numero"
                                name="numero"
                                maxlength="100"
                                required
                                value="<?= e(
                                    $contrat['numero']
                                ) ?>"
                            >

                        </div>


                        <!-- STATUT -->

                        <div class="form-group">

                            <label for="statut">
                                Statut *
                            </label>

                            <select
                                id="statut"
                                name="statut"
                                required
                            >

                                <option
                                    value="DRAFT"
                                    <?= $contrat['statut'] === 'DRAFT'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Brouillon
                                </option>

                                <option
                                    value="ACTIVE"
                                    <?= $contrat['statut'] === 'ACTIVE'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Actif
                                </option>

                                <option
                                    value="EXPIRED"
                                    <?= $contrat['statut'] === 'EXPIRED'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Expiré
                                </option>

                                <option
                                    value="RENEWED"
                                    <?= $contrat['statut'] === 'RENEWED'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Renouvelé
                                </option>

                                <option
                                    value="TERMINATED"
                                    <?= $contrat['statut'] === 'TERMINATED'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Terminé
                                </option>

                                <option
                                    value="ARCHIVED"
                                    <?= $contrat['statut'] === 'ARCHIVED'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Archivé
                                </option>

                            </select>

                        </div>


                        <!-- DATE DEBUT -->

                        <div class="form-group">

                            <label for="date_debut">
                                Date de début *
                            </label>

                            <input
                                type="date"
                                id="date_debut"
                                name="date_debut"
                                required
                                value="<?= e(
                                    formatDateInput(
                                        $contrat['date_debut']
                                    )
                                ) ?>"
                            >

                        </div>


                        <!-- DATE FIN -->

                        <div class="form-group">

                            <label for="date_fin">
                                Date de fin *
                            </label>

                            <input
                                type="date"
                                id="date_fin"
                                name="date_fin"
                                required
                                value="<?= e(
                                    formatDateInput(
                                        $contrat['date_fin']
                                    )
                                ) ?>"
                            >

                        </div>


                        <!-- LOYER -->

                        <div class="form-group">

                            <label for="loyer">
                                Loyer *
                            </label>

                            <input
                                type="number"
                                id="loyer"
                                name="loyer"
                                step="0.01"
                                min="0"
                                required
                                value="<?= e(
                                    (string) $contrat['loyer']
                                ) ?>"
                            >

                        </div>


                        <!-- CHARGES -->

                        <div class="form-group">

                            <label for="charges">
                                Charges
                            </label>

                            <input
                                type="number"
                                id="charges"
                                name="charges"
                                step="0.01"
                                min="0"
                                value="<?= e(
                                    (string) $contrat['charges']
                                ) ?>"
                            >

                        </div>


                        <!-- DEPOT -->

                        <div class="form-group">

                            <label for="depot_garantie">
                                Dépôt de garantie
                            </label>

                            <input
                                type="number"
                                id="depot_garantie"
                                name="depot_garantie"
                                step="0.01"
                                min="0"
                                value="<?= e(
                                    (string) $contrat['depot_garantie']
                                ) ?>"
                            >

                        </div>


                        <!-- FREQUENCE -->

                        <div class="form-group">

                            <label for="frequence_paiement">
                                Fréquence de paiement *
                            </label>

                            <select
                                id="frequence_paiement"
                                name="frequence_paiement"
                                required
                            >

                                <option
                                    value="MONTHLY"
                                    <?= $contrat['frequence_paiement'] === 'MONTHLY'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Mensuel
                                </option>

                                <option
                                    value="QUARTERLY"
                                    <?= $contrat['frequence_paiement'] === 'QUARTERLY'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Trimestriel
                                </option>

                                <option
                                    value="YEARLY"
                                    <?= $contrat['frequence_paiement'] === 'YEARLY'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Annuel
                                </option>

                            </select>

                        </div>


                        <!-- JOUR ECHEANCE -->

                        <div class="form-group">

                            <label for="jour_echeance">
                                Jour d'échéance
                            </label>

                            <input
                                type="number"
                                id="jour_echeance"
                                name="jour_echeance"
                                min="1"
                                max="31"
                                value="<?= e(
                                    (string) $contrat['jour_echeance']
                                ) ?>"
                            >

                        </div>


                        <!-- DATE RESILIATION -->

                        <div class="form-group">

                            <label for="date_resiliation">
                                Date de résiliation
                            </label>

                            <input
                                type="date"
                                id="date_resiliation"
                                name="date_resiliation"
                                value="<?= e(
                                    formatDateInput(
                                        $contrat['date_resiliation']
                                    )
                                ) ?>"
                            >

                        </div>


                        <!-- MOTIF -->

                        <div class="form-group full">

                            <label for="motif_resiliation">
                                Motif de résiliation
                            </label>

                            <textarea
                                id="motif_resiliation"
                                name="motif_resiliation"
                                placeholder="Motif éventuel de résiliation..."
                            ><?= e(
                                $contrat['motif_resiliation']
                            ) ?></textarea>

                        </div>


                        <div class="resiliation-note">

                            <strong>
                                Information
                            </strong>

                            Si le statut est
                            <strong>Terminé</strong>,
                            une date de résiliation doit être
                            renseignée.

                        </div>

                    </div>


                    <!-- ACTIONS -->

                    <div class="modifier-contrat-actions">

                        <a
                            href="/LOKA/app/proprietaires/Contrat/detailContrat.php?id=<?= $idContrat ?>"
                            class="btn-contrat btn-cancel"
                        >
                            Annuler
                        </a>

                        <button
                            type="submit"
                            class="btn-contrat btn-save"
                        >
                            Enregistrer les modifications
                        </button>

                    </div>

                </section>

            </form>

        </div>

    </main>

</div>

</body>
</html>