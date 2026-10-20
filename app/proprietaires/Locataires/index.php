<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/ProprietaireController.php';

$controller = new ProprietaireController();
$proprietaire = $controller->getCurrentProprietaire();
$locataires = $controller->getLocataires();

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function formatDate(?string $date): string
{
    if (empty($date)) {
        return '—';
    }
    $timestamp = strtotime($date);

    return $timestamp === false ? '—' : date('d/m/Y', $timestamp);
}

function contratStatutBadge(?string $statut): array
{
    return match ($statut) {
        'ACTIVE' => ['Actif', 'actif'],
        'DRAFT' => ['Brouillon', 'attente'],
        'RENEWED' => ['Renouvelé', 'actif'],
        'EXPIRED' => ['Expiré', 'inactif'],
        'TERMINATED' => ['Résilié', 'suspendu'],
        default => ['Non renseigné', 'inactif'],
    };
}

$recherche = trim((string) ($_GET['q'] ?? ''));
if ($recherche !== '') {
    $locataires = array_values(array_filter($locataires, static function (array $locataire) use ($recherche): bool {
        $contenu = ($locataire['prenom'] ?? '') . ' ' . ($locataire['nom'] ?? '') . ' ' . ($locataire['email'] ?? '') . ' ' . ($locataire['titre_bien'] ?? '');

        return mb_stripos($contenu, $recherche) !== false;
    }));
}

$totalLocataires = count($locataires);
$locatairesActifs = 0;
foreach ($locataires as $locataire) {
    if (($locataire['statut_contrat'] ?? null) === 'ACTIVE') {
        $locatairesActifs++;
    }
}

$prenom = (string) ($proprietaire['prenom'] ?? '');
$nom = (string) ($proprietaire['nom'] ?? '');

$pageTitle = 'Mes locataires';
$activeNav = 'Mes locataires';
$sidebarFile = 'sidebar-proprietaire.php';
$greetingTitle = 'Mes locataires';
$greetingSubtitle = 'Les locataires associés à vos biens.';
$profileName = trim($prenom . ' ' . $nom) ?: 'Propriétaire';
$profileRole = 'Propriétaire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1)) ?: 'P';
$extraHead = '<link rel="stylesheet" href="/LOKA/app/administration/mon-agence/dashboard.css">';

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon teal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div></div>
        <div class="kpi-value"><?= $totalLocataires ?></div>
        <div class="kpi-label">Total locataires</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div></div>
        <div class="kpi-value"><?= $locatairesActifs ?></div>
        <div class="kpi-label">Avec contrat actif</div>
      </div>
    </div>

    <div class="panel table-panel">
      <div class="panel-header" style="gap:16px;flex-wrap:wrap;">
        <div class="panel-title">Mes locataires</div>
        <form method="get" class="search-box" style="margin-left:auto;max-width:280px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" name="q" placeholder="Rechercher un locataire…" value="<?= e($recherche) ?>" autocomplete="off">
        </form>
      </div>
      <?php if (empty($locataires)): ?>
        <div class="empty-state" style="padding:48px 20px;">
          <div class="empty-state-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
          <h4>Aucun locataire trouvé</h4>
          <p>Les locataires de vos biens apparaîtront ici dès qu'un contrat sera signé.</p>
        </div>
      <?php else: ?>
        <div class="table-scroll">
          <table>
            <thead><tr><th>LOCATAIRE</th><th>CONTACT</th><th>BIEN OCCUPÉ</th><th>CONTRAT</th><th>LOYER</th><th>STATUT</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($locataires as $locataire): ?>
                <?php
                $initiales = mb_strtoupper(mb_substr((string) $locataire['prenom'], 0, 1) . mb_substr((string) $locataire['nom'], 0, 1)) ?: '?';
                [$label, $tone] = contratStatutBadge($locataire['statut_contrat'] ?? null);
                ?>
                <tr>
                  <td>
                    <div class="cell">
                      <div class="cell-avatar"><?= e($initiales) ?></div>
                      <div>
                        <div class="cell-title"><?= e(trim(($locataire['prenom'] ?? '') . ' ' . ($locataire['nom'] ?? ''))) ?></div>
                        <div class="cell-sub"><?= e($locataire['email'] ?? '—') ?></div>
                      </div>
                    </div>
                  </td>
                  <td class="cell-sub"><?= e($locataire['telephone'] ?? '—') ?></td>
                  <td class="cell-sub"><?= e($locataire['titre_bien'] ?? $locataire['reference_bien'] ?? '—') ?></td>
                  <td class="cell-sub"><?= e($locataire['numero_contrat'] ?? '—') ?></td>
                  <td class="cell-sub"><?= $locataire['loyer'] !== null ? number_format((float) $locataire['loyer'], 0, ',', ' ') . ' €' : '—' ?></td>
                  <td><span class="status-badge <?= $tone ?>"><?= e($label) ?></span></td>
                  <td>
                    <a class="consult-link" href="detail.php?id=<?= (int) $locataire['id_locataire'] ?>">
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
require dirname(__DIR__, 3) . '/layouts/footer.php';
