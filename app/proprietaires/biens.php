<?php

declare(strict_types=1);

require_once __DIR__ . '/ProprietaireController.php';

$controller = new ProprietaireController();
$mesBiens = $controller->getMesBiens();

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function bienStatutBadge(string $statut): array
{
    return match ($statut) {
        'OCCUPIED' => ['Loué', 'actif'],
        'AVAILABLE' => ['Disponible', 'attente'],
        'MAINTENANCE' => ['En travaux', 'suspendu'],
        'ARCHIVED' => ['Archivé', 'inactif'],
        default => ['Créé', 'inactif'],
    };
}

$recherche = trim((string) ($_GET['q'] ?? ''));
if ($recherche !== '') {
    $mesBiens = array_values(array_filter($mesBiens, static function (array $bien) use ($recherche): bool {
        return mb_stripos($bien['titre'], $recherche) !== false || mb_stripos((string) ($bien['ville'] ?? ''), $recherche) !== false;
    }));
}

$proprietaire = $controller->getCurrentProprietaire();
$prenom = (string) ($proprietaire['prenom'] ?? '');
$nom = (string) ($proprietaire['nom'] ?? '');

$pageTitle = 'Mes biens';
$activeNav = 'Mes biens';
$sidebarFile = 'sidebar-proprietaire.php';
$greetingTitle = 'Mes biens';
$greetingSubtitle = 'Consultez et gérez votre patrimoine immobilier.';
$profileName = trim($prenom . ' ' . $nom) ?: 'Propriétaire';
$profileRole = 'Propriétaire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1)) ?: 'P';
$extraHead = '<link rel="stylesheet" href="/LOKA/app/administration/mon-agence/dashboard.css">';

require dirname(__DIR__, 2) . '/layouts/header.php';
?>
    <div class="panel table-panel">
      <div class="panel-header" style="gap:16px;flex-wrap:wrap;">
        <div class="panel-title">Mes biens</div>
        <form method="get" class="search-box" style="margin-left:auto;max-width:280px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" name="q" placeholder="Rechercher un bien…" value="<?= e($recherche) ?>" autocomplete="off">
        </form>
        <a class="btn btn-primary" href="ajouter_biens.php">+ Ajouter un bien</a>
      </div>
      <?php if (empty($mesBiens)): ?>
        <div class="empty-state" style="padding:48px 20px;">
          <div class="empty-state-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/></svg></div>
          <h4>Aucun bien trouvé</h4>
          <p>Vos biens apparaîtront ici dès qu'ils seront enregistrés par votre agence.</p>
        </div>
      <?php else: ?>
        <div class="table-scroll">
          <table>
            <thead><tr><th>BIEN</th><th>TYPE</th><th>VILLE</th><th>SURFACE</th><th>LOYER</th><th>STATUT</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($mesBiens as $bien): ?>
                <?php [$label, $tone] = bienStatutBadge((string) $bien['statut']); ?>
                <tr>
                  <td class="cell-title"><?= e($bien['titre']) ?><div class="cell-sub"><?= e($bien['reference']) ?></div></td>
                  <td class="cell-sub"><?= e($bien['type_bien'] ?? '—') ?></td>
                  <td class="cell-sub"><?= e($bien['ville'] ?? '—') ?></td>
                  <td class="cell-sub"><?= number_format((float) $bien['surface'], 0) ?> m²</td>
                  <td class="cell-sub"><?= number_format((float) $bien['loyer'], 0, ',', ' ') ?> €</td>
                  <td><span class="status-badge <?= $tone ?>"><?= e($label) ?></span></td>
                  <td>
                    <a class="consult-link" href="detail.php?id=<?= (int) $bien['id_bien'] ?>">
                      Voir
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 6 15 12 9 18"/></svg>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
<?php
require dirname(__DIR__, 2) . '/layouts/footer.php';
