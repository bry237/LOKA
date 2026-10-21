<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once dirname(__DIR__, 2) . '/core/Database.php';

$user = Authorization::requireRole('Proprietaire');

$pdo = Database::connection();

$idUtilisateur = (int) $user['id_utilisateur'];


function e(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}


function imageUrl(?string $image): string
{
    if (!$image) {
        return '';
    }

    if (
        str_starts_with($image, 'http://')
        || str_starts_with($image, 'https://')
        || str_starts_with($image, 'data:')
    ) {
        return $image;
    }

    return '/LOKA/' . ltrim($image, '/');
}


/*
|--------------------------------------------------------------------------
| Récupération du propriétaire connecté
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT p.id_proprietaire, p.prenom, p.nom
    FROM proprietaire p
    INNER JOIN utilisateur u
        ON u.email = p.email
    WHERE u.id_utilisateur = ?
    LIMIT 1
");

$stmt->execute([$idUtilisateur]);

$proprietaireRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$proprietaireRow) {
    http_response_code(403);
    exit('Propriétaire introuvable.');
}

$idProprietaire = (int) $proprietaireRow['id_proprietaire'];


/*
|--------------------------------------------------------------------------
| Récupération de l'identifiant du bien
|--------------------------------------------------------------------------
*/

$idBien = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idBien) {
    http_response_code(400);
    exit('Bien invalide.');
}


/*
|--------------------------------------------------------------------------
| Récupération du bien
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        b.id_bien, b.reference, b.titre, b.image_url, b.surface, b.nombre_pieces,
        b.etage, b.loyer, b.charges, b.caution, b.statut, b.description,
        b.balcon, b.terrasse, b.jardin, b.piscine, b.ascenseur, b.meuble,
        b.exposition, b.chauffage, b.climatisation, b.acces_pmr, b.etat_bien,
        b.classe_energetique,
        tb.nom AS type_bien,
        a.ligne1, a.code_postal, a.ville, a.region, a.pays
    FROM bien b
    INNER JOIN bien_proprietaire bp ON bp.id_bien = b.id_bien
    LEFT JOIN type_bien tb ON tb.id_type_bien = b.id_type_bien
    LEFT JOIN adresse a ON a.id_adresse = b.id_adresse
    WHERE b.id_bien = ?
      AND bp.id_proprietaire = ?
      AND b.deleted_at IS NULL
    LIMIT 1
");

$stmt->execute([$idBien, $idProprietaire]);

$bien = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$bien) {
    http_response_code(404);
    exit('Bien introuvable ou accès refusé.');
}


/*
|--------------------------------------------------------------------------
| Photos
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id_image, image_url, est_principale
    FROM bien_image
    WHERE id_bien = ?
    ORDER BY est_principale DESC, ordre ASC, id_image ASC
");

$stmt->execute([$idBien]);

$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($images) && !empty($bien['image_url'])) {
    $images[] = ['id_image' => 0, 'image_url' => $bien['image_url'], 'est_principale' => 1];
}


$statuts = [
    'CREATED' => ['Créé', 'inactif'],
    'AVAILABLE' => ['Disponible', 'attente'],
    'OCCUPIED' => ['Occupé', 'actif'],
    'MAINTENANCE' => ['En travaux', 'suspendu'],
    'ARCHIVED' => ['Archivé', 'inactif'],
];
[$statutLabel, $statutTone] = $statuts[$bien['statut']] ?? [$bien['statut'], 'inactif'];

$loyer = (float) $bien['loyer'];
$charges = (float) $bien['charges'];
$totalMensuel = $loyer + $charges;

$adresseComplete = trim(($bien['ligne1'] ?? '') . ', ' . ($bien['code_postal'] ?? '') . ' ' . ($bien['ville'] ?? ''));

$oui = static fn (int $value): string => $value === 1 ? 'Oui' : 'Non';

$prenom = (string) ($proprietaireRow['prenom'] ?? '');
$nom = (string) ($proprietaireRow['nom'] ?? '');

$pageTitle = $bien['titre'];
$activeNav = 'Mes biens';
$sidebarFile = 'sidebar-proprietaire.php';
$greetingTitle = $bien['titre'];
$greetingSubtitle = 'Référence ' . $bien['reference'];
$profileName = trim($prenom . ' ' . $nom) ?: 'Propriétaire';
$profileRole = 'Propriétaire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1)) ?: 'P';
$extraHead = '<link rel="stylesheet" href="/LOKA/app/administration/mon-agence/dashboard.css">';

require dirname(__DIR__, 2) . '/layouts/header.php';
?>
    <a class="detail-back" href="biens.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour à mes biens
    </a>

    <div class="detail-header">
      <div class="detail-identity">
        <div>
          <p class="panel-sub" style="margin:0 0 2px;"><?= e($bien['type_bien'] ?? 'Bien') ?></p>
          <h1 class="detail-name"><?= e($bien['titre']) ?></h1>
          <p class="detail-sub"><?= e($adresseComplete) ?></p>
        </div>
      </div>
      <span class="status-badge <?= $statutTone ?>"><?= e($statutLabel) ?></span>
    </div>

    <?php if (($_GET['success'] ?? '') === 'created'): ?>
      <div class="feedback success">Bien ajouté.</div>
    <?php endif; ?>

    <?php if (!empty($images)): ?>
      <div class="panel" style="margin-bottom:18px;">
        <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(160px, 1fr));gap:12px;padding:18px;">
          <?php foreach ($images as $image): ?>
            <div style="position:relative;border-radius:12px;overflow:hidden;aspect-ratio:4/3;background:var(--panel-muted, #f1f5f5);">
              <img src="<?= e(imageUrl($image['image_url'])) ?>" alt="<?= e($bien['titre']) ?>" style="width:100%;height:100%;object-fit:cover;display:block;">
              <?php if ((int) $image['est_principale'] === 1): ?>
                <span class="status-badge actif" style="position:absolute;top:8px;left:8px;font-size:11px;padding:3px 9px;">Principale</span>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php else: ?>
      <div class="panel">
        <div class="empty-state" style="padding:40px 20px;">
          <div class="empty-state-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8h3l1.5-2h7L17 8h3a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1Z"/><circle cx="12" cy="14" r="3.5"/></svg></div>
          <h4>Aucune photo disponible</h4>
        </div>
      </div>
    <?php endif; ?>

    <div class="detail-grid">
      <div class="detail-col">
        <div class="panel">
          <div class="panel-header"><div class="panel-title">Informations du bien</div></div>
          <div class="info-list">
            <div class="info-row"><span class="info-label">Type</span><span class="info-value"><?= e($bien['type_bien'] ?? '—') ?></span></div>
            <div class="info-row"><span class="info-label">Surface</span><span class="info-value"><?= number_format((float) $bien['surface'], 0, ',', ' ') ?> m²</span></div>
            <div class="info-row"><span class="info-label">Nombre de pièces</span><span class="info-value"><?= (int) $bien['nombre_pieces'] ?></span></div>
            <div class="info-row"><span class="info-label">Étage</span><span class="info-value"><?= $bien['etage'] !== null ? e((string) $bien['etage']) : '—' ?></span></div>
            <div class="info-row"><span class="info-label">Meublé</span><span class="info-value"><?= e($bien['meuble'] ?? '—') ?></span></div>
            <div class="info-row"><span class="info-label">Exposition</span><span class="info-value"><?= $bien['exposition'] ? e($bien['exposition']) : '—' ?></span></div>
            <div class="info-row"><span class="info-label">État</span><span class="info-value"><?= $bien['etat_bien'] ? e($bien['etat_bien']) : '—' ?></span></div>
            <div class="info-row"><span class="info-label">Classe énergétique</span><span class="info-value"><?= $bien['classe_energetique'] ? e($bien['classe_energetique']) : '—' ?></span></div>
          </div>
        </div>

        <div class="panel" style="margin-top:18px;">
          <div class="panel-header"><div class="panel-title">Localisation</div></div>
          <div class="info-list">
            <div class="info-row"><span class="info-label">Adresse</span><span class="info-value"><?= e($bien['ligne1'] ?? '—') ?></span></div>
            <div class="info-row"><span class="info-label">Ville</span><span class="info-value"><?= e($bien['ville'] ?? '—') ?></span></div>
            <div class="info-row"><span class="info-label">Code postal</span><span class="info-value"><?= e($bien['code_postal'] ?? '—') ?></span></div>
            <div class="info-row"><span class="info-label">Région</span><span class="info-value"><?= $bien['region'] ? e($bien['region']) : '—' ?></span></div>
            <div class="info-row"><span class="info-label">Pays</span><span class="info-value"><?= e($bien['pays'] ?? '—') ?></span></div>
          </div>
        </div>

        <?php if (!empty($bien['description'])): ?>
          <div class="panel" style="margin-top:18px;">
            <div class="panel-header"><div class="panel-title">Description</div></div>
            <p style="padding:0 18px 18px;font-size:13px;color:var(--text);line-height:1.6;"><?= nl2br(e($bien['description'])) ?></p>
          </div>
        <?php endif; ?>
      </div>

      <div class="detail-col">
        <div class="panel">
          <div class="panel-header"><div class="panel-title">Informations financières</div></div>
          <div class="info-list">
            <div class="info-row"><span class="info-label">Loyer</span><span class="info-value"><?= number_format($loyer, 0, ',', ' ') ?> €</span></div>
            <div class="info-row"><span class="info-label">Charges</span><span class="info-value"><?= number_format($charges, 0, ',', ' ') ?> €</span></div>
            <div class="info-row"><span class="info-label">Caution</span><span class="info-value"><?= number_format((float) $bien['caution'], 0, ',', ' ') ?> €</span></div>
          </div>
          <div style="padding:0 18px 18px;display:flex;justify-content:space-between;align-items:center;">
            <span class="panel-sub" style="margin:0;">Total mensuel</span>
            <strong style="font-size:22px;color:var(--primary, #0f8b8d);"><?= number_format($totalMensuel, 0, ',', ' ') ?> €</strong>
          </div>
        </div>

        <div class="panel" style="margin-top:18px;">
          <div class="panel-header"><div class="panel-title">Équipements</div></div>
          <div class="info-list">
            <div class="info-row"><span class="info-label">Balcon</span><span class="info-value"><?= $oui((int) $bien['balcon']) ?></span></div>
            <div class="info-row"><span class="info-label">Terrasse</span><span class="info-value"><?= $oui((int) $bien['terrasse']) ?></span></div>
            <div class="info-row"><span class="info-label">Jardin</span><span class="info-value"><?= $oui((int) $bien['jardin']) ?></span></div>
            <div class="info-row"><span class="info-label">Piscine</span><span class="info-value"><?= $oui((int) $bien['piscine']) ?></span></div>
            <div class="info-row"><span class="info-label">Ascenseur</span><span class="info-value"><?= $oui((int) $bien['ascenseur']) ?></span></div>
            <div class="info-row"><span class="info-label">Climatisation</span><span class="info-value"><?= e($bien['climatisation'] ?? '—') ?></span></div>
            <div class="info-row"><span class="info-label">Accès PMR</span><span class="info-value"><?= $oui((int) $bien['acces_pmr']) ?></span></div>
          </div>
        </div>

        <div class="panel" style="margin-top:18px;">
          <div class="panel-header"><div class="panel-title">Actions</div></div>
          <div style="padding:18px;display:flex;gap:10px;flex-wrap:wrap;">
            <a href="modifier.php?id=<?= $idBien ?>" class="btn btn-secondary">Modifier</a>
            <a href="supprimer.php?id=<?= $idBien ?>" class="btn btn-danger" onclick="return confirm('Supprimer définitivement « <?= e($bien['titre']) ?> » ?');">Supprimer</a>
          </div>
        </div>
      </div>
    </div>
<?php
require dirname(__DIR__, 2) . '/layouts/footer.php';
