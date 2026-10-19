<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';

$auth = Authorization::requireRole('Proprietaire');

$config = require dirname(__DIR__, 3) . '/config/database.php';

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $config['host'],
    $config['port'],
    $config['name'],
    $config['charset']
);

$pdo = new PDO(
    $dsn,
    $config['user'],
    $config['password'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);

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

$idContrat = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$idContrat) {
    http_response_code(400);
    exit('Contrat invalide.');
}

/*
|--------------------------------------------------------------------------
| Identifier le propriétaire connecté
|--------------------------------------------------------------------------
*/

$idUtilisateur = (int) $auth['id_utilisateur'];

$stmt = $pdo->prepare(
    'SELECT p.id_proprietaire
     FROM proprietaire p
     INNER JOIN utilisateur u
         ON u.email = p.email
     WHERE u.id_utilisateur = ?
     LIMIT 1'
);

$stmt->execute([$idUtilisateur]);

$idProprietaire = $stmt->fetchColumn();

if (!$idProprietaire) {
    http_response_code(403);
    exit('Propriétaire introuvable.');
}

$idProprietaire = (int) $idProprietaire;

/*
|--------------------------------------------------------------------------
| Charger le contrat
|--------------------------------------------------------------------------
|
| On vérifie en même temps que le contrat appartient
| bien à un bien du propriétaire connecté.
|
*/

$stmt = $pdo->prepare(
    'SELECT
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

     LIMIT 1'
);

$stmt->execute([
    $idContrat,
    $idProprietaire
]);

$contrat = $stmt->fetch();

if (!$contrat) {
    http_response_code(404);
    exit('Contrat introuvable ou inaccessible.');
}

/*
|--------------------------------------------------------------------------
| Vérification du statut
|--------------------------------------------------------------------------
*/

if (!in_array(
    $contrat['statut'],
    ['ACTIVE', 'EXPIRED'],
    true
)) {
    http_response_code(400);
    exit(
        'Ce contrat ne peut pas être renouvelé dans son état actuel.'
    );
}

/*
|--------------------------------------------------------------------------
| Charger les locataires du contrat
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT
        cl.id_locataire,
        cl.titulaire_principal,
        cl.date_entree,
        cl.date_sortie,

        l.nom,
        l.prenom,
        l.email,
        l.telephone

     FROM contrat_locataire cl

     INNER JOIN locataire l
         ON l.id_locataire = cl.id_locataire

     WHERE cl.id_contrat = ?
       AND l.deleted_at IS NULL

     ORDER BY
        cl.titulaire_principal DESC,
        l.nom ASC,
        l.prenom ASC'
);

$stmt->execute([$idContrat]);

$locataires = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Traitement du formulaire
|--------------------------------------------------------------------------
*/

$errors = [];

$nouvelleDateDebut = $_POST['date_debut'] ?? '';

$nouvelleDateFin = $_POST['date_fin'] ?? '';

$nouveauLoyer = $_POST['loyer'] ?? (string) $contrat['loyer'];

$nouvellesCharges = $_POST['charges'] ?? (string) $contrat['charges'];

$nouveauDepot = $_POST['depot_garantie'] ?? (string) $contrat['depot_garantie'];

$nouvelleFrequence =
    $_POST['frequence_paiement']
    ?? $contrat['frequence_paiement'];

$nouveauJourEcheance =
    $_POST['jour_echeance']
    ?? (string) $contrat['jour_echeance'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | Validation dates
    |--------------------------------------------------------------------------
    */

    if (!$nouvelleDateDebut) {
        $errors[] = 'La date de début est obligatoire.';
    }

    if (!$nouvelleDateFin) {
        $errors[] = 'La date de fin est obligatoire.';
    }

    if (
        $nouvelleDateDebut &&
        $nouvelleDateFin &&
        $nouvelleDateFin <= $nouvelleDateDebut
    ) {
        $errors[] =
            'La date de fin doit être postérieure à la date de début.';
    }

    /*
    |--------------------------------------------------------------------------
    | Validation montant
    |--------------------------------------------------------------------------
    */

    if ($nouveauLoyer === '' || !is_numeric($nouveauLoyer)) {
        $errors[] = 'Le loyer est invalide.';
    }

    if ($nouvellesCharges === '' || !is_numeric($nouvellesCharges)) {
        $errors[] = 'Les charges sont invalides.';
    }

    if ($nouveauDepot === '' || !is_numeric($nouveauDepot)) {
        $errors[] = 'Le dépôt de garantie est invalide.';
    }

    /*
    |--------------------------------------------------------------------------
    | Validation fréquence
    |--------------------------------------------------------------------------
    */

    $frequencesAutorisees = [
        'MONTHLY',
        'QUARTERLY',
        'YEARLY',
    ];

    if (!in_array(
        $nouvelleFrequence,
        $frequencesAutorisees,
        true
    )) {
        $errors[] = 'La fréquence de paiement est invalide.';
    }

    /*
    |--------------------------------------------------------------------------
    | Validation jour d'échéance
    |--------------------------------------------------------------------------
    */

    $nouveauJourEcheanceInt =
        filter_var(
            $nouveauJourEcheance,
            FILTER_VALIDATE_INT
        );

    if (
        $nouveauJourEcheanceInt === false ||
        $nouveauJourEcheanceInt < 1 ||
        $nouveauJourEcheanceInt > 31
    ) {
        $errors[] =
            'Le jour d’échéance doit être compris entre 1 et 31.';
    }

    /*
    |--------------------------------------------------------------------------
    | Création du nouveau contrat
    |--------------------------------------------------------------------------
    */

    if (!$errors) {

        try {

            $pdo->beginTransaction();

            /*
            |--------------------------------------------------------------------------
            | Sécurité :
            | on recontrôle que l'ancien contrat est toujours renouvelable.
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare(
                'SELECT
                    id_contrat,
                    id_bien,
                    numero,
                    date_debut,
                    date_fin,
                    loyer,
                    charges,
                    depot_garantie,
                    frequence_paiement,
                    jour_echeance,
                    statut

                 FROM contrat

                 WHERE id_contrat = ?
                   AND deleted_at IS NULL

                 FOR UPDATE'
            );

            $stmt->execute([$idContrat]);

            $ancienContrat = $stmt->fetch();

            if (!$ancienContrat) {
                throw new RuntimeException(
                    'Le contrat n’existe plus.'
                );
            }

            if (!in_array(
                $ancienContrat['statut'],
                ['ACTIVE', 'EXPIRED'],
                true
            )) {
                throw new RuntimeException(
                    'Le contrat ne peut plus être renouvelé.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Génération du numéro du nouveau contrat
            |--------------------------------------------------------------------------
            */

            $nouveauNumero =
                $ancienContrat['numero']
                . '-RENOUV-'
                . date('YmdHis');

            /*
            |--------------------------------------------------------------------------
            | Passage de l'ancien contrat à RENEWED
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare(
                'UPDATE contrat
                 SET
                    statut = ?,
                    updated_at = NOW()
                 WHERE id_contrat = ?'
            );

            $stmt->execute([
                'RENEWED',
                $idContrat
            ]);

            /*
            |--------------------------------------------------------------------------
            | Création du nouveau contrat
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare(
                'INSERT INTO contrat (
                    id_agence,
                    id_bien,
                    numero,
                    date_debut,
                    date_fin,
                    loyer,
                    charges,
                    depot_garantie,
                    frequence_paiement,
                    jour_echeance,
                    statut
                )
                SELECT
                    id_agence,
                    id_bien,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                FROM contrat
                WHERE id_contrat = ?'
            );

            $stmt->execute([
                $nouveauNumero,
                $nouvelleDateDebut,
                $nouvelleDateFin,
                (float) $nouveauLoyer,
                (float) $nouvellesCharges,
                (float) $nouveauDepot,
                $nouvelleFrequence,
                $nouveauJourEcheanceInt,
                'ACTIVE',
                $idContrat,
            ]);

            $nouveauIdContrat =
                (int) $pdo->lastInsertId();

            /*
            |--------------------------------------------------------------------------
            | Copier les locataires
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare(
                'INSERT INTO contrat_locataire (
                    id_contrat,
                    id_locataire,
                    titulaire_principal,
                    date_entree,
                    date_sortie
                )
                SELECT
                    ?,
                    id_locataire,
                    titulaire_principal,
                    ?,
                    NULL
                FROM contrat_locataire
                WHERE id_contrat = ?'
            );

            $stmt->execute([
                $nouveauIdContrat,
                $nouvelleDateDebut,
                $idContrat,
            ]);

            $pdo->commit();

            header(
                'Location: /LOKA/app/proprietaires/Contrat/detailContrat.php?id='
                . $nouveauIdContrat
                . '&renewed=1'
            );

            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] =
                'Impossible de renouveler le contrat : '
                . $e->getMessage();
        }
    }
}

/*
|--------------------------------------------------------------------------
| Valeurs par défaut
|--------------------------------------------------------------------------
*/

if (!$nouvelleDateDebut) {

    if (!empty($contrat['date_fin'])) {

        $nouvelleDateDebut =
            date(
                'Y-m-d',
                strtotime(
                    $contrat['date_fin'] . ' +1 day'
                )
            );

    } else {

        $nouvelleDateDebut =
            date('Y-m-d');

    }
}

if (!$nouvelleDateFin) {

    if ($nouvelleDateDebut) {

        $nouvelleDateFin =
            date(
                'Y-m-d',
                strtotime(
                    $nouvelleDateDebut . ' +1 year'
                )
            );

    }
}

$frequenceLabels = [
    'MONTHLY' => 'Mensuelle',
    'QUARTERLY' => 'Trimestrielle',
    'YEARLY' => 'Annuelle',
];

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
        Renouveler le contrat - LOKA
    </title>

    <link
        rel="stylesheet"
        href="/LOKA/app/proprietaires/proprietaire.css"
    >

    <style>

        .renouveler-contrat-page {
            padding: 30px;
        }

        .renouveler-contrat-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            text-decoration: none;
            color: #555;
        }

        .renouveler-contrat-header {
            margin-bottom: 25px;
        }

        .renouveler-contrat-header h1 {
            margin: 0 0 8px;
            font-size: 30px;
        }

        .renouveler-contrat-header p {
            margin: 0;
            color: #666;
        }

        .renouveler-contrat-card {
            background: #fff;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 18px rgba(0,0,0,.06);
        }

        .renouveler-contrat-card h2 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 20px;
        }

        .contrat-actuel {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }

        .contrat-actuel-item {
            padding: 15px;
            border-radius: 10px;
            background: #f7f7f7;
        }

        .contrat-actuel-label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            color: #777;
        }

        .contrat-actuel-value {
            font-weight: 600;
        }

        .renouveler-form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .form-group label {
            font-weight: 600;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            box-sizing: border-box;
            padding: 12px 14px;
            border: 1px solid #ddd;
            border-radius: 9px;
            font-size: 15px;
        }

        .form-help {
            margin-top: 5px;
            color: #777;
            font-size: 13px;
        }

        .error-box {
            padding: 15px 18px;
            margin-bottom: 20px;
            border-radius: 10px;
            background: #fff1f1;
            color: #a40000;
        }

        .error-box ul {
            margin: 0;
            padding-left: 20px;
        }

        .locataires-renouvellement {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .locataire-renouvellement {
            padding: 10px 14px;
            background: #f5f5f5;
            border-radius: 9px;
        }

        .renouveler-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 25px;
        }

        .btn-renouveler {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 20px;
            border-radius: 9px;
            text-decoration: none;
            border: 0;
            cursor: pointer;
            font-size: 15px;
        }

        .btn-cancel {
            background: #eee;
            color: #333;
        }

        .btn-save {
            background: #222;
            color: #fff;
        }

        @media (max-width: 900px) {

            .contrat-actuel {
                grid-template-columns: repeat(2, 1fr);
            }

            .renouveler-form-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {

            .renouveler-contrat-page {
                padding: 20px;
            }

            .contrat-actuel {
                grid-template-columns: 1fr;
            }

            .renouveler-actions {
                flex-direction: column;
            }

            .btn-renouveler {
                width: 100%;
            }
        }

    </style>

</head>

<body>

<div class="renouveler-contrat-page">

    <a
        href="/LOKA/app/proprietaires/Contrat/detailContrat.php?id=<?= (int) $contrat['id_contrat'] ?>"
        class="renouveler-contrat-back"
    >
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;margin-right:4px;"><polyline points="15 18 9 12 15 6"/></svg>Retour au contrat
    </a>

    <div class="renouveler-contrat-header">

        <h1>
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;margin-right:6px;"><path d="M21 12a9 9 0 1 1-2.6-6.4"/><polyline points="21 3 21 9 15 9"/></svg>Renouveler le contrat
        </h1>

        <p>
            Créez une nouvelle période de location tout en conservant
            l'historique du contrat actuel.
        </p>

    </div>

    <?php if ($errors): ?>

        <div class="error-box">

            <ul>

                <?php foreach ($errors as $error): ?>

                    <li>
                        <?= e($error) ?>
                    </li>

                <?php endforeach; ?>

            </ul>

        </div>

    <?php endif; ?>

    <section class="renouveler-contrat-card">

        <h2>
            Contrat actuel
        </h2>

        <div class="contrat-actuel">

            <div class="contrat-actuel-item">

                <span class="contrat-actuel-label">
                    Numéro
                </span>

                <span class="contrat-actuel-value">
                    <?= e($contrat['numero']) ?>
                </span>

            </div>

            <div class="contrat-actuel-item">

                <span class="contrat-actuel-label">
                    Bien
                </span>

                <span class="contrat-actuel-value">
                    <?= e($contrat['reference_bien']) ?>
                    —
                    <?= e($contrat['titre_bien']) ?>
                </span>

            </div>

            <div class="contrat-actuel-item">

                <span class="contrat-actuel-label">
                    Période actuelle
                </span>

                <span class="contrat-actuel-value">
                    <?= formatDateFr($contrat['date_debut']) ?>
                    →
                    <?= formatDateFr($contrat['date_fin']) ?>
                </span>

            </div>

            <div class="contrat-actuel-item">

                <span class="contrat-actuel-label">
                    Loyer actuel
                </span>

                <span class="contrat-actuel-value">
                    <?= formatMoney($contrat['loyer']) ?>
                </span>

            </div>

        </div>

    </section>

    <section class="renouveler-contrat-card">

        <h2>
            Nouvelle période
        </h2>

        <form method="POST">

            <div class="renouveler-form-grid">

                <div class="form-group">

                    <label for="date_debut">
                        Nouvelle date de début
                    </label>

                    <input
                        type="date"
                        id="date_debut"
                        name="date_debut"
                        value="<?= e($nouvelleDateDebut) ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="date_fin">
                        Nouvelle date de fin
                    </label>

                    <input
                        type="date"
                        id="date_fin"
                        name="date_fin"
                        value="<?= e($nouvelleDateFin) ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="loyer">
                        Nouveau loyer
                    </label>

                    <input
                        type="number"
                        id="loyer"
                        name="loyer"
                        step="0.01"
                        min="0"
                        value="<?= e((string) $nouveauLoyer) ?>"
                        required
                    >

                </div>

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
                        value="<?= e((string) $nouvellesCharges) ?>"
                        required
                    >

                </div>

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
                        value="<?= e((string) $nouveauDepot) ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="frequence_paiement">
                        Fréquence de paiement
                    </label>

                    <select
                        id="frequence_paiement"
                        name="frequence_paiement"
                        required
                    >

                        <?php foreach ($frequenceLabels as $value => $label): ?>

                            <option
                                value="<?= e($value) ?>"
                                <?= $nouvelleFrequence === $value ? 'selected' : '' ?>
                            >
                                <?= e($label) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

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
                        value="<?= e((string) $nouveauJourEcheance) ?>"
                        required
                    >

                    <span class="form-help">
                        Entre 1 et 31.
                    </span>

                </div>

            </div>

            <div class="renouveler-actions">

                <a
                    href="/LOKA/app/proprietaires/detail_contrat.php?id=<?= (int) $contrat['id_contrat'] ?>"
                    class="btn-renouveler btn-cancel"
                >
                    Annuler
                </a>

                <button
                    type="submit"
                    class="btn-renouveler btn-save"
                >
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;margin-right:4px;"><path d="M21 12a9 9 0 1 1-2.6-6.4"/><polyline points="21 3 21 9 15 9"/></svg>Créer le renouvellement
                </button>

            </div>

        </form>

    </section>

    <section class="renouveler-contrat-card">

        <h2>
            Locataires concernés
        </h2>

        <?php if ($locataires): ?>

            <div class="locataires-renouvellement">

                <?php foreach ($locataires as $locataire): ?>

                    <div class="locataire-renouvellement">

                        <?= e(
                            trim(
                                $locataire['prenom']
                                . ' '
                                . $locataire['nom']
                            )
                        ) ?>

                        <?php if ($locataire['titulaire_principal']): ?>

                            — titulaire principal

                        <?php endif; ?>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <p>
                Aucun locataire associé à ce contrat.
            </p>

        <?php endif; ?>

    </section>

</div>

</body>

</html>