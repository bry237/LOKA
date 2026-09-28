<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/UtilisateurController.php';

$currentUser = Authorization::requireRole('Administrateur plateforme');

$data = UtilisateurController::list($_GET);
$filters = $data['filters'];
$roles = $data['roles'];
$result = $data['result'];

$pageTitle = 'Utilisateurs';
$activeNav = 'Utilisateurs';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Gérez les comptes de la plateforme.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));

$csrfToken = Auth::csrfToken();

$currentQuery = http_build_query(array_filter([
	'q' => $filters['q'],
	'role' => $filters['role'],
	'status' => $filters['status'],
	'page' => $result['page'] > 1 ? $result['page'] : null,
]));

$paginationUrl = static function (int $page) use ($filters): string {
	return '?' . http_build_query(array_filter([
		'q' => $filters['q'],
		'role' => $filters['role'],
		'status' => $filters['status'],
		'page' => $page > 1 ? $page : null,
	]));
};

$statusLabels = ['ACTIVE' => 'Actif', 'INACTIVE' => 'Inactif', 'LOCKED' => 'Verrouillé'];
$statusTones = ['ACTIVE' => 'actif', 'INACTIVE' => 'inactif', 'LOCKED' => 'suspendu'];
$hasFilters = $filters['q'] !== '' || $filters['role'] !== '' || $filters['status'] !== '';

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <div class="page-header">
      <h1>Utilisateurs</h1>
      <p><?= $result['total'] ?> compte<?= $result['total'] > 1 ? 's' : '' ?> sur la plateforme</p>
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
          <input type="text" name="q" placeholder="Nom, prénom, email…" value="<?= htmlspecialchars($filters['q'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <select class="filter-select" name="role">
          <option value="">Tous les rôles</option>
          <?php foreach ($roles as $role): ?>
            <option value="<?= (int) $role['id_role'] ?>"<?= $filters['role'] === (string) $role['id_role'] ? ' selected' : '' ?>><?= htmlspecialchars($role['nom'], ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>
        <select class="filter-select" name="status">
          <option value="">Tous les statuts</option>
          <option value="ACTIVE"<?= $filters['status'] === 'ACTIVE' ? ' selected' : '' ?>>Actif</option>
          <option value="INACTIVE"<?= $filters['status'] === 'INACTIVE' ? ' selected' : '' ?>>Inactif</option>
          <option value="LOCKED"<?= $filters['status'] === 'LOCKED' ? ' selected' : '' ?>>Verrouillé</option>
        </select>
        <button type="submit" class="btn btn-primary">Filtrer</button>
        <?php if ($hasFilters): ?><a class="btn" href="index.php">Réinitialiser</a><?php endif; ?>
      </form>

      <div class="table-scroll">
        <table>
          <thead>
            <tr>
              <th>UTILISATEUR</th>
              <th>RÔLE</th>
              <th>AGENCE</th>
              <th>DERNIÈRE CONNEXION</th>
              <th>STATUT</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$result['rows']): ?>
              <tr class="empty-row"><td colspan="6">Aucun utilisateur ne correspond à ces critères.</td></tr>
            <?php endif; ?>
            <?php foreach ($result['rows'] as $u): ?>
              <?php
                $initials = mb_strtoupper(mb_substr($u['prenom'], 0, 1) . mb_substr($u['nom'], 0, 1));
                $lastLogin = $u['derniere_connexion']
                	? (new DateTimeImmutable($u['derniere_connexion']))->format('d/m/Y H:i')
                	: 'Jamais connecté';
                $isSelf = (int) $u['id_utilisateur'] === (int) $currentUser['id_utilisateur'];
              ?>
              <tr>
                <td>
                  <div class="cell">
                    <div class="cell-avatar"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>
                    <div>
                      <div class="cell-title"><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom'], ENT_QUOTES, 'UTF-8') ?></div>
                      <div class="cell-sub"><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                  </div>
                </td>
                <td><?= htmlspecialchars($u['role_nom'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($u['agence_nom'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($lastLogin, ENT_QUOTES, 'UTF-8') ?></td>
                <td><span class="status-badge <?= $statusTones[$u['statut']] ?? '' ?>"><?= htmlspecialchars($statusLabels[$u['statut']] ?? $u['statut'], ENT_QUOTES, 'UTF-8') ?></span></td>
                <td>
                  <div style="display:flex;gap:8px;align-items:center;justify-content:flex-end;">
                    <a class="consult-link" href="detail.php?id=<?= (int) $u['id_utilisateur'] ?>" data-modal>
                      Consulter
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 6 15 12 9 18"/></svg>
                    </a>
                    <?php if ($isSelf): ?>
                      <span class="cell-sub">Vous</span>
                    <?php elseif ($u['statut'] === 'ACTIVE'): ?>
                      <form class="inline-form" method="post" action="desactiver.php">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="id" value="<?= (int) $u['id_utilisateur'] ?>">
                        <input type="hidden" name="redirect" value="<?= htmlspecialchars('index.php' . ($currentQuery !== '' ? '?' . $currentQuery : ''), ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Désactiver ce compte ?');">Désactiver</button>
                      </form>
                    <?php else: ?>
                      <form class="inline-form" method="post" action="activer.php">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="id" value="<?= (int) $u['id_utilisateur'] ?>">
                        <input type="hidden" name="redirect" value="<?= htmlspecialchars('index.php' . ($currentQuery !== '' ? '?' . $currentQuery : ''), ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="btn btn-sm btn-success">Activer</button>
                      </form>
                    <?php endif; ?>
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
        <div>Affichage <?= $shownFrom ?>-<?= $shownTo ?> sur <?= $result['total'] ?> utilisateurs</div>
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
