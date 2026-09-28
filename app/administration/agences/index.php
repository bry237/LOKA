<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/AgenceController.php';

$currentUser = Authorization::requireRole('Administrateur plateforme');

$data = AgenceController::list($_GET);
$filters = $data['filters'];
$result = $data['result'];

$pageTitle = 'Agences';
$activeNav = 'Agences';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Gérez les agences inscrites sur la plateforme.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));

$csrfToken = Auth::csrfToken();

$currentQuery = http_build_query(array_filter([
	'q' => $filters['q'],
	'status' => $filters['status'],
	'page' => $result['page'] > 1 ? $result['page'] : null,
]));

$paginationUrl = static function (int $page) use ($filters): string {
	return '?' . http_build_query(array_filter([
		'q' => $filters['q'],
		'status' => $filters['status'],
		'page' => $page > 1 ? $page : null,
	]));
};

$statusLabels = ['ACTIVE' => 'Actif', 'PENDING' => 'En attente', 'SUSPENDED' => 'Suspendu'];
$statusTones = ['ACTIVE' => 'actif', 'PENDING' => 'attente', 'SUSPENDED' => 'suspendu'];
$hasFilters = $filters['q'] !== '' || $filters['status'] !== '';

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <div class="page-header" style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;">
      <div>
        <h1>Agences</h1>
        <p><?= $result['total'] ?> agence<?= $result['total'] > 1 ? 's' : '' ?> sur la plateforme</p>
      </div>
      <a class="btn btn-primary" href="ajouter.php" data-modal>+ Ajouter une agence</a>
    </div>

    <?php if (isset($_GET['success'])): ?>
      <div class="feedback success"><?= htmlspecialchars((string) $_GET['success'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      <div class="feedback error"><?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="panel table-panel">
      <form class="filters-form" method="get" action="index.php">
        <div class="search-box">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" name="q" placeholder="Nom, ville, email…" value="<?= htmlspecialchars($filters['q'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <select class="filter-select" name="status">
          <option value="">Tous les statuts</option>
          <option value="ACTIVE"<?= $filters['status'] === 'ACTIVE' ? ' selected' : '' ?>>Actif</option>
          <option value="PENDING"<?= $filters['status'] === 'PENDING' ? ' selected' : '' ?>>En attente</option>
          <option value="SUSPENDED"<?= $filters['status'] === 'SUSPENDED' ? ' selected' : '' ?>>Suspendu</option>
        </select>
        <button type="submit" class="btn btn-primary">Filtrer</button>
        <?php if ($hasFilters): ?><a class="btn" href="index.php">Réinitialiser</a><?php endif; ?>
      </form>

      <div class="table-scroll">
        <table>
          <thead>
            <tr>
              <th>AGENCE</th>
              <th>ADMINISTRATION</th>
              <th>ABONNEMENT</th>
              <th>DATE D'INSCRIPTION</th>
              <th>STATUT</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$result['rows']): ?>
              <tr class="empty-row"><td colspan="6">Aucune agence ne correspond à ces critères.</td></tr>
            <?php endif; ?>
            <?php foreach ($result['rows'] as $a): ?>
              <?php
                $initials = mb_strtoupper(mb_substr($a['nom'], 0, 2));
                $nextStatus = $a['statut'] === 'ACTIVE' ? 'SUSPENDED' : 'ACTIVE';
                $actionLabel = match ($a['statut']) {
                	'ACTIVE' => 'Suspendre',
                	'SUSPENDED' => 'Réactiver',
                	default => 'Activer',
                };
                $actionClass = $a['statut'] === 'ACTIVE' ? 'btn-danger' : 'btn-success';
              ?>
              <tr>
                <td>
                  <div class="cell">
                    <div class="cell-avatar"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>
                    <div>
                      <div class="cell-title"><?= htmlspecialchars($a['nom'], ENT_QUOTES, 'UTF-8') ?></div>
                      <div class="cell-sub"><?= htmlspecialchars($a['ville'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                  </div>
                </td>
                <td><?= htmlspecialchars($a['admin_nom'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($a['plan_nom'] ?? 'Aucun', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= (new DateTimeImmutable($a['created_at']))->format('d/m/Y') ?></td>
                <td><span class="status-badge <?= $statusTones[$a['statut']] ?? '' ?>"><?= htmlspecialchars($statusLabels[$a['statut']] ?? $a['statut'], ENT_QUOTES, 'UTF-8') ?></span></td>
                <td>
                  <div style="display:flex;gap:8px;align-items:center;justify-content:flex-end;">
                    <a class="consult-link" href="detail.php?id=<?= (int) $a['id_agence'] ?>" data-modal>
                      Consulter
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 6 15 12 9 18"/></svg>
                    </a>
                    <form class="inline-form" method="post" action="changer-statut.php">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                      <input type="hidden" name="id" value="<?= (int) $a['id_agence'] ?>">
                      <input type="hidden" name="statut" value="<?= $nextStatus ?>">
                      <input type="hidden" name="redirect" value="<?= htmlspecialchars('index.php' . ($currentQuery !== '' ? '?' . $currentQuery : ''), ENT_QUOTES, 'UTF-8') ?>">
                      <button type="submit" class="btn btn-sm <?= $actionClass ?>"<?= $a['statut'] === 'ACTIVE' ? ' onclick="return confirm(\'Suspendre cette agence ?\');"' : '' ?>><?= $actionLabel ?></button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="table-footer">
        <?php
          $shownFrom = $result['total'] ? ($result['page'] - 1) * $result['pageSize'] + 1 : 0;
          $shownTo = min($result['page'] * $result['pageSize'], $result['total']);
        ?>
        <div>Affichage <?= $shownFrom ?>-<?= $shownTo ?> sur <?= $result['total'] ?> agences</div>
        <div class="pagination">
          <?php if ($result['page'] > 1): ?>
            <a class="page-btn" href="<?= htmlspecialchars($paginationUrl($result['page'] - 1), ENT_QUOTES, 'UTF-8') ?>">‹</a>
          <?php else: ?>
            <span class="page-btn" aria-disabled="true">‹</span>
          <?php endif; ?>
          <?php for ($i = 1; $i <= $result['totalPages']; $i++): ?>
            <a class="page-btn<?= $i === $result['page'] ? ' active' : '' ?>" href="<?= htmlspecialchars($paginationUrl($i), ENT_QUOTES, 'UTF-8') ?>"><?= $i ?></a>
          <?php endfor; ?>
          <?php if ($result['page'] < $result['totalPages']): ?>
            <a class="page-btn" href="<?= htmlspecialchars($paginationUrl($result['page'] + 1), ENT_QUOTES, 'UTF-8') ?>">›</a>
          <?php else: ?>
            <span class="page-btn" aria-disabled="true">›</span>
          <?php endif; ?>
        </div>
      </div>
    </div>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
