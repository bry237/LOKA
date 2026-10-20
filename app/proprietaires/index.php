<?php
//A activer lorsque l'authentification sera terminée

declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/core/Authorization.php';
Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier', 'Comptable', 'Proprietaire');

//session_start();

/* -------------------------------
   Connexion à la base de données
-------------------------------- */

$db = require dirname(__DIR__, 2) . '/config/database.php';

try {
    $pdo = new PDO(
        "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}",
        $db['user'],
        $db['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

/* -------------------------------
   Utilisateur connecté (temporaire)
-------------------------------- */

if (!isset($_SESSION['loka_auth']['id_utilisateur'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$idUtilisateur = (int) $_SESSION['loka_auth']['id_utilisateur'];

/* -------------------------------
   Récupération du propriétaire
-------------------------------- */

$sql = "
SELECT
    u.id_utilisateur,
    u.nom,
    u.prenom,
    u.email,
    u.telephone,
    u.id_agence,
    p.id_proprietaire
FROM utilisateur u
LEFT JOIN proprietaire p
    ON p.email = u.email
WHERE u.id_utilisateur = ?
AND u.deleted_at IS NULL
LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$idUtilisateur]);

$utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$utilisateur) {
    die('Utilisateur introuvable.');
}

$prenom = $utilisateur['prenom'];
$nom = $utilisateur['nom'];

$initiales =
    strtoupper(substr($prenom, 0, 1)) .
    strtoupper(substr($nom, 0, 1));

/* -------------------------------
   Récupération de l'identifiant du propriétaire
-------------------------------- */

$sql = "
SELECT COUNT(*) AS total_biens
FROM bien_proprietaire bp
JOIN bien b ON b.id_bien = bp.id_bien
WHERE bp.id_proprietaire = ?
AND b.deleted_at IS NULL
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$utilisateur['id_proprietaire']]);

$totalBiens = $stmt->fetch()['total_biens'];

/* -------------------------------
   Nombre de contrats actifs 
-------------------------------- */

$sql = "
SELECT COUNT(*) AS contrats_actifs
FROM contrat c
JOIN bien_proprietaire bp
    ON bp.id_bien = c.id_bien
WHERE bp.id_proprietaire = ?
AND c.statut = 'ACTIF'
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$utilisateur['id_proprietaire']]);

$resultat = $stmt->fetch();

$contratsActifs = $resultat['contrats_actifs'];

/* -------------------------------
   Nombre de paiements
-------------------------------- */

$sql = "
SELECT COUNT(*) AS total_paiements
FROM paiement p
JOIN contrat c
    ON c.id_contrat = p.id_contrat
JOIN bien_proprietaire bp
    ON bp.id_bien = c.id_bien
WHERE bp.id_proprietaire = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$utilisateur['id_proprietaire']]);

$resultat = $stmt->fetch(PDO::FETCH_ASSOC);

$totalPaiements = (int) $resultat['total_paiements'];

/* -------------------------------
   Revenus encaissés du propriétaire
-------------------------------- */

$sql = "
SELECT COALESCE(SUM(p.montant), 0) AS revenus
FROM paiement p
JOIN contrat c
    ON c.id_contrat = p.id_contrat
JOIN bien_proprietaire bp
    ON bp.id_bien = c.id_bien
WHERE bp.id_proprietaire = ?
AND p.statut = 'PAID'
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$utilisateur['id_proprietaire']]);

$resultat = $stmt->fetch(PDO::FETCH_ASSOC);

$revenus = (float) $resultat['revenus'];



/* -------------------------------
   5 derniers paiements
-------------------------------- */

$sql = "
SELECT
    b.titre,
    p.date_paiement,
    p.montant,
    p.statut
FROM paiement p
JOIN contrat c ON c.id_contrat = p.id_contrat
JOIN bien b ON b.id_bien = c.id_bien
JOIN bien_proprietaire bp ON bp.id_bien = b.id_bien
WHERE bp.id_proprietaire = ?
ORDER BY p.date_paiement DESC
LIMIT 5
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$utilisateur['id_proprietaire']]);

$derniersPaiements = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* -------------------------------
   Liste des biens du propriétaire
-------------------------------- */

$sql = "
SELECT
    b.id_bien,
    b.titre,
    a.ville,
    b.statut,
    tb.nom AS type_bien
FROM bien b
JOIN bien_proprietaire bp
    ON bp.id_bien = b.id_bien
JOIN adresse a
    ON a.id_adresse = b.id_adresse
JOIN type_bien tb
    ON tb.id_type_bien = b.id_type_bien
WHERE bp.id_proprietaire = ?
AND b.deleted_at IS NULL
ORDER BY b.titre
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$utilisateur['id_proprietaire']]);

$mesBiens = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* -------------------------------
   Revenus mensuels
-------------------------------- */

$sql = "
SELECT
    MONTH(p.date_paiement) AS mois,
    SUM(p.montant) AS total
FROM paiement p
JOIN echeance e
    ON e.id_echeance = p.id_echeance
JOIN contrat c
    ON c.id_contrat = e.id_contrat
JOIN bien_proprietaire bp
    ON bp.id_bien = c.id_bien
WHERE bp.id_proprietaire = ?
AND p.statut='PAID'
GROUP BY MONTH(p.date_paiement)
ORDER BY MONTH(p.date_paiement)
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$utilisateur['id_proprietaire']]);

$resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$labels = [];
$datas = [];

$nomsMois = [
    1 => "Jan",
    2 => "Fév",
    3 => "Mar",
    4 => "Avr",
    5 => "Mai",
    6 => "Juin",
    7 => "Juil",
    8 => "Août",
    9 => "Sep",
    10 => "Oct",
    11 => "Nov",
    12 => "Déc"
];

foreach ($resultats as $ligne) {
    $labels[] = $nomsMois[(int)$ligne['mois']];
    $datas[] = (float)$ligne['total'];
}

?>


<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard Propriétaire | LOKA</title>

    <link rel="stylesheet" href="proprietaire.css">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

    <div class="dashboard">

        <?php $pageActive = 'dashboard'; require __DIR__ . '/sidebar.php'; ?>

        <main class="content">

            <header class="topbar">

                <div>

                    <h1>
                        Bonjour,
                        <?= htmlspecialchars($prenom) ?>
                        <?= htmlspecialchars($nom) ?>
                    </h1>

                    <p>Bienvenue sur votre espace propriétaire.</p>

                </div>

                <div class="user">

                    <button class="bell">

                        <i class="fa-regular fa-bell"></i>

                    </button>

                    <div class="avatar">
                        <?= htmlspecialchars($initiales) ?>
                    </div>

                </div>

            </header>

            <section class="cards">

                <div class="card">

                    <div class="icon"><i class="fa-solid fa-building"></i></div>

                    <h2><?= $totalBiens ?></h2>

                    <p>Mes biens</p>

                </div>

                <div class="card">

                    <div class="icon"><i class="fa-solid fa-euro-sign"></i></div>

                    <h2><?= number_format($revenus, 0, ',', ' ') ?> £</h2>
                    <p>Revenus encaissés</p>

                </div>

                <div class="card">

                    <div class="icon"><i class="fa-solid fa-file-contract"></i></div>

                    <h2><?= $contratsActifs ?></h2>

                    <p>Contrats Actifs</p>

                </div>

                <div class="card">

                    <div class="icon">
                        <i class="fa-solid fa-credit-card"></i>
                    </div>

                    <h2><?= $totalPaiements ?></h2>

                    <p>Paiements</p>

                </div>

            </section>

            <section class="grid">

                <div class="panel">

                    <h3>Revenus mensuels</h3>

                    <canvas id="revenueChart"></canvas>

                </div>

                <div class="panel">

                    <h3>Derniers paiements</h3>

                    <table>

                        <thead>

                            <tr>

                                <th>Bien</th>

                                <th>Date</th>

                                <th>Montant</th>

                                <th>Statut</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php if (empty($derniersPaiements)): ?>

                                <tr>
                                    <td colspan="4">Aucun paiement enregistré.</td>
                                </tr>

                            <?php else: ?>

                                <?php foreach ($derniersPaiements as $paiement): ?>

                                    <tr>

                                        <td><?= htmlspecialchars($paiement['titre']) ?></td>

                                        <td><?= date('d/m/Y', strtotime($paiement['date_paiement'])) ?></td>

                                        <td><?= number_format((float)$paiement['montant'], 0, ',', ' ') ?> £</td>

                                        <td>

                                            <?php
                                            $classe = match ($paiement['statut']) {
                                                'PAYE' => 'success',
                                                'EN_ATTENTE' => 'warning',
                                                'RETARD' => 'danger',
                                                default => 'info'
                                            };
                                            ?>

                                            <span class="<?= $classe ?>">
                                                <?= htmlspecialchars($paiement['statut']) ?>
                                            </span>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </section>

            <section class="panel">

                <h3>Mes biens</h3>

                <div class="properties">

                    <?php if (empty($mesBiens)): ?>

                        <p>Aucun bien enregistré.</p>

                    <?php else: ?>

                        <?php foreach ($mesBiens as $bien): ?>

                            <div class="property">

                                <h4><?= htmlspecialchars($bien['titre']) ?></h4>

                                <p><?= htmlspecialchars($bien['ville']) ?></p>

                                <?php
                                $badge = match ($bien['statut']) {
                                    'LOUE' => 'badge',
                                    'DISPONIBLE' => 'badge success',
                                    'TRAVAUX' => 'badge maintenance',
                                    default => 'badge'
                                };
                                ?>

                                <span class="<?= $badge ?>">
                                    <?= htmlspecialchars($bien['statut']) ?>
                                </span>

                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </section>

        </main>

    </div>

    <script>
        window.chartLabels = <?= json_encode($labels) ?>;
        window.chartDatas = <?= json_encode($datas) ?>;

        console.log("Labels :", window.chartLabels);
        console.log("Données :", window.chartDatas);
    </script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="proprietaire.js"></script>

</body>

</html>