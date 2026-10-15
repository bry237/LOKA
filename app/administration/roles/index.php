<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/RoleController.php';

$currentUser = Authorization::requireRole('Administrateur plateforme');

$roles = RoleController::list();

$pageTitle = 'Rôles & permissions';
$activeNav = 'Roles';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Les rôles sont fixes ; vous pouvez ajuster leur description et les permissions associées.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <div class="page-header">
      <h1>Rôles & permissions</h1>
      <p><?= count($roles) ?> rôles disponibles sur la plateforme</p>
    </div>

    <?php if (isset($_GET['success'])): ?>
      <div class="feedback success"><?= htmlspecialchars((string) $_GET['success'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      <div class="feedback error"><?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="panel table-panel">
      <div class="table-scroll">
        <table>
          <thead>
            <tr>
              <th>RÔLE</th>
              <th>DESCRIPTION</th>
              <th>UTILISATEURS</th>
              <th>PERMISSIONS</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($roles as $role): ?>
              <tr>
                <td><div class="cell-title"><?= htmlspecialchars($role['nom'], ENT_QUOTES, 'UTF-8') ?></div></td>
                <td><span class="cell-sub"><?= htmlspecialchars($role['description'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span></td>
                <td><?= (int) $role['nb_utilisateurs'] ?></td>
                <td><?= (int) $role['nb_permissions'] ?> / 16</td>
                <td>
                  <div style="display:flex;gap:8px;align-items:center;justify-content:flex-end;">
                    <a class="btn btn-sm" href="modifier.php?id=<?= (int) $role['id_role'] ?>" data-modal>Description</a>
                    <a class="btn btn-sm btn-primary" href="permissions.php?id=<?= (int) $role['id_role'] ?>">Permissions</a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
