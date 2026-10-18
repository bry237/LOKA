<?php
declare(strict_types=1);

require_once dirname(__DIR__,2).'/config/database.php';
require_once dirname(__DIR__,2).'/core/Authorization.php';

Authorization::requireRole('Proprietaire');

// session_start();

$idUtilisateur = (int) $_SESSION['loka_auth']['id_utilisateur'];

$config = require dirname(__DIR__,2).'/config/database.php';

$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']}",
    $config['user'],
    $config['password']
);

$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

/* Propriétaire connecté */

$sql="
SELECT
u.nom,
u.prenom,
p.id_proprietaire
FROM utilisateur u
JOIN proprietaire p
ON p.email=u.email
WHERE u.id_utilisateur=?
LIMIT 1";

$stmt=$pdo->prepare($sql);
$stmt->execute([$idUtilisateur]);

$utilisateur=$stmt->fetch(PDO::FETCH_ASSOC);

$idProprietaire=(int)$utilisateur['id_proprietaire'];

$sql = "
    SELECT
        b.id_bien,
        b.reference,
        b.titre,
        b.image_url AS bien_image_url,

        bi.image_url AS photo_principale,

        tb.nom AS type_bien,
        a.ville,
        b.surface,
        b.loyer,
        b.statut

    FROM bien b

    JOIN bien_proprietaire bp
        ON bp.id_bien = b.id_bien

    LEFT JOIN adresse a
        ON a.id_adresse = b.id_adresse

    LEFT JOIN type_bien tb
        ON tb.id_type_bien = b.id_type_bien

    LEFT JOIN bien_image bi
        ON bi.id_bien = b.id_bien
        AND bi.est_principale = 1

    WHERE bp.id_proprietaire = ?
      AND b.deleted_at IS NULL

    ORDER BY b.created_at DESC
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $idProprietaire
]);


$biens = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| CONSTRUIRE L'URL DE L'IMAGE
|--------------------------------------------------------------------------
*/

function getImageUrl(?string $url): ?string
{
    if (!$url) {
        return null;
    }

    $url = trim($url);

    // Image externe : Unsplash, etc.
    if (
        str_starts_with($url, 'http://')
        ||
        str_starts_with($url, 'https://')
    ) {
        return $url;
    }

    // Image locale
    return '/LOKA/' . ltrim($url, '/');
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">

<title>Mes biens</title>

<link rel="stylesheet" href="proprietaire.css">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

  
        <aside class="sidebar">

    <div class="logo">
        <h2>LOKA</h2>
        <p>Gestion immobilière</p>
    </div>

    <nav>

        <a href="/LOKA/app/proprietaires/index.php" >
            <i class="fa-solid fa-chart-line"></i>
            Dashboard
        </a>

        <a href="/LOKA/app/proprietaires/biens.php" class="active">
            <i class="fa-solid fa-building"></i>
            Mes biens
        </a>

        <a href="/LOKA/app/proprietaires/Locataires/index.php" >
            <i class="fa-solid fa-users"></i>
            Mes locataires
        </a>

        <a
                href="/LOKA/app/proprietaires/Contrat/contrat.php"
            >
            <i class="fa-solid fa-file-contract"></i>
            Mes contrats
        </a>

        <a href="/LOKA/app/proprietaires/Revenus/revenus.php">
            <i class="fa-solid fa-wallet"></i>
            Mes revenus
        </a>

        <a href="/LOKA/app/proprietaires/Paiements/index.php">
            <i class="fa-solid fa-credit-card"></i>
            Paiements
        </a>

        <a href="/LOKA/app/proprietaires/Quittances/quittances.php">
            <i class="fa-solid fa-receipt"></i>
            Quittances
        </a>

         <a
                href="/LOKA/app/proprietaires/Documents/documents.php"
            >

                <i class="fa-solid fa-folder-open"></i>

                Documents

            </a>

        <a href="/LOKA/app/proprietaires/Parametres/parametres.php">
            <i class="fa-solid fa-gear"></i>
            Paramètres
        </a>

    </nav>

</aside> 

<div class="content">

<header class="page-header">

<div>

<h1>Mes biens</h1>

<p><?= count($biens) ?> bien(s)</p>

</div>

<a href="ajouter_biens.php" class="btn-add">

<i class="fa-solid fa-plus"></i>

Ajouter un bien

</a>

</header>

<div class="search-box">

<i class="fa-solid fa-magnifying-glass"></i>

<input
type="text"
id="searchInput"
placeholder="Rechercher un bien..."
>

</div>

<div class="property-grid" id="propertyGrid">

<?php foreach($biens as $bien): ?>

<div class="property-card">

    <div class="property-image">
       <?php
$imageUrl = getImageUrl(
    $bien['photo_principale']
    ?: $bien['bien_image_url']
);
?>

<?php if ($imageUrl): ?>

    <img
        src="<?= htmlspecialchars(
            $imageUrl,
            ENT_QUOTES,
            'UTF-8'
        ) ?>"
        alt="<?= htmlspecialchars(
            $bien['titre'],
            ENT_QUOTES,
            'UTF-8'
        ) ?>"
    >

<?php else: ?>

    <div class="image-fallback">

        <i class="fa-solid fa-building"></i>

    </div>

<?php endif; ?>
    </div>

    <div class="property-body">

        <h3><?= htmlspecialchars($bien['titre']) ?></h3>

        <p class="location">
            <i class="fa-solid fa-location-dot"></i>
            <?= htmlspecialchars($bien['ville'] ?? 'Ville inconnue') ?>
        </p>

        <p class="infos">
            <?= htmlspecialchars($bien['type_bien']) ?>
            •
            <?= htmlspecialchars($bien['surface']) ?> m²
        </p>

        <h4 class="price">
            <?= number_format((float)$bien['loyer'],0,',',' ') ?> £
        </h4>

        <span class="status">
            <?= htmlspecialchars($bien['statut']) ?>
        </span>

        <div class="actions">
            <a href="detail.php?id=<?= $bien['id_bien'] ?>" class="btn-view">
                Voir les détails
            </a>

            <a href="modifier.php?id=<?= $bien['id_bien'] ?>" class="btn-edit">
                Modifier
            </a>
        </div>

    </div>

</div>

<?php endforeach; ?>

</div>

</div>

<script src="proprietaire.js"></script>

</body>

</html>




