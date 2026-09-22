<?php
declare(strict_types=1);

session_start();

$db = require dirname(__DIR__,2).'/config/database.php';

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}",
    $db['user'],
    $db['password'],
    [
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
    ]
);

$idUtilisateur=1;
$idBien=(int)($_GET['id']??1);

$sql="
SELECT
b.id_bien,
b.reference,
b.titre,
b.surface,
b.nombre_pieces AS nb_pieces,
b.loyer,
b.charges,
b.statut,

tb.nom AS type_bien,

 a.ligne1,
 a.ligne2,
 a.ville,
 a.code_postal,
 a.region,
 a.pays,

p.nom AS proprietaire_nom,
p.prenom AS proprietaire_prenom

FROM bien b

JOIN bien_proprietaire bp
ON bp.id_bien=b.id_bien

JOIN proprietaire p
ON p.id_proprietaire=bp.id_proprietaire

JOIN adresse a
ON a.id_adresse=b.id_adresse

JOIN type_bien tb
ON tb.id_type_bien=b.id_type_bien

WHERE b.id_bien=?
LIMIT 1
";

$stmt=$pdo->prepare($sql);
$stmt->execute([$idBien]);

$bien=$stmt->fetch();

if(!$bien){
die("Bien introuvable.");
}

$sql="
SELECT
l.nom,
l.prenom,
c.date_debut,
c.date_fin,
c.statut

FROM contrat c

JOIN locataire l
ON l.id_locataire=c.id_locataire

WHERE c.id_bien=?
AND c.statut='ACTIF'
LIMIT 1
";

$stmt=$pdo->prepare($sql);
$stmt->execute([$idBien]);

$contrat=$stmt->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?= htmlspecialchars($bien['titre']) ?> | LOKA</title>

<link rel="stylesheet" href="proprietaire.css">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

</head>

<body>

<div class="dashboard">

<aside class="sidebar">

<div class="logo">
<h2>LOKA</h2>
<p>Gestion immobilière</p>
</div>

<nav>

<a href="index.php">
<i class="fa-solid fa-chart-line"></i>
Dashboard
</a>

<a class="active">
<i class="fa-solid fa-building"></i>
Mes biens
</a>

<a>
<i class="fa-solid fa-file-contract"></i>
Mes contrats
</a>

<a>
<i class="fa-solid fa-wallet"></i>
Mes revenus
</a>

</nav>

</aside>

<main class="content">

<a href="index.php" class="back-button">
<i class="fa-solid fa-arrow-left"></i>
Retour
</a>

<div class="hero-image">

<AsyncImage query="modern apartment building exterior" aspectRatio="16:9" radius=none/>

</div>

<div class="property-header">

<div>

<h1><?= htmlspecialchars($bien['titre']) ?></h1>

<p><?= htmlspecialchars($bien['type_bien']) ?></p>

</div>

<span class="badge">
<?= htmlspecialchars($bien['statut']) ?>
</span>

</div>

<section class="detail-grid">

<div class="panel">

<h3>Informations générales</h3>

<div class="info-grid">

<div>

<label>Référence</label>

<strong><?= htmlspecialchars($bien['reference']) ?></strong>

</div>

<div>

<label>Surface</label>

<strong><?= htmlspecialchars($bien['surface']) ?> m²</strong>

</div>

<div>

<label>Pièces</label>

<strong><?= htmlspecialchars($bien['nb_pieces']) ?></strong>

</div>

<div>

<label>Loyer</label>

<strong><?= number_format($bien['loyer'],0,',',' ') ?> FCFA</strong>

</div>

<div>

<label>Charges</label>

<strong><?= number_format($bien['charges'],0,',',' ') ?> FCFA</strong>

</div>

</div>

</div>

<div class="panel">

<h3>Adresse</h3>

<p><?= htmlspecialchars($bien['ligne1']) ?></p>

<?php if(!empty($bien['ligne2'])): ?>
<p><?= htmlspecialchars($bien['ligne2']) ?></p>
<?php endif; ?>

<p>
    <?= htmlspecialchars($bien['code_postal']) ?>
    <?= htmlspecialchars($bien['ville']) ?>
</p>

<p><?= htmlspecialchars($bien['region']) ?></p>

<p><?= htmlspecialchars($bien['pays']) ?></p></div>

</section>

<section class="panel">

<h3>Contrat actuel</h3>

<?php if($contrat): ?>

<div class="contract-card">

<h4><?= htmlspecialchars($contrat['prenom'].' '.$contrat['nom']) ?></h4>

<p>Début : <?= htmlspecialchars($contrat['date_debut']) ?></p>

<p>Fin : <?= htmlspecialchars($contrat['date_fin']) ?></p>

<p>Statut : <?= htmlspecialchars($contrat['statut']) ?></p>

</div>

<?php else: ?>

<p>Aucun contrat actif.</p>

<?php endif; ?>

</section>

<section class="panel">

<h3>Derniers paiements</h3>

<p>Cette section sera reliée au module Paiements.</p>

</section>

<section class="panel">

<h3>Interventions</h3>

<p>Cette section affichera les interventions liées au bien.</p>

</section>

</main>

</div>

</body>
</html>