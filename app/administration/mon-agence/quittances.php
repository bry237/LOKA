<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/MonAgenceController.php';
require_once __DIR__ . '/team-presentation.php';

$currentUser = Authorization::requireRole('Administrateur agence', 'Comptable');
$idAgence = (int) $currentUser['id_agence'];

$data = MonAgenceController::quittances($idAgence);
$quittances = $data['list'];
$icons = teamIcons();

$pageTitle = 'Quittances';
$activeNav = 'Quittances';
$sidebarFile = 'sidebar-agence.php';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Les quittances générées automatiquement lors des encaissements.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));
$extraHead = '<link rel="stylesheet" href="dashboard.css"><link rel="stylesheet" href="ops.css">';

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <div class="page-header">
      <h1>Quittances</h1>
      <p>Générées automatiquement à chaque paiement enregistré comme payé.</p>
    </div>

    <div class="panel table-panel">
      <div class="panel-header"><div class="panel-title">Quittances</div></div>
      <?php if (!$quittances): ?>
        <div class="empty-state">
          <div class="empty-state-icon"><?= $icons['folder'] ?></div>
          <h4>Aucune quittance</h4>
          <p>Une quittance est générée automatiquement dès qu'un paiement est enregistré comme payé depuis la page Paiements.</p>
        </div>
      <?php else: ?>
        <div class="table-scroll">
          <table>
            <thead>
              <tr><th>NUMÉRO</th><th>CONTRAT</th><th>LOCATAIRE</th><th>PÉRIODE</th><th>MONTANT</th><th>ÉMISE LE</th><th></th></tr>
            </thead>
            <tbody>
              <?php foreach ($quittances as $q): ?>
                <tr>
                  <td class="cell-title"><?= htmlspecialchars($q['numero'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="cell-sub"><?= htmlspecialchars($q['contrat_numero'] . ' — ' . $q['bien_titre'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="cell-sub"><?= $q['locataire_nom'] ? htmlspecialchars($q['locataire_nom'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
                  <td class="cell-sub"><?= $q['periode_debut'] ? (new DateTimeImmutable($q['periode_debut']))->format('d/m/Y') . ' — ' . (new DateTimeImmutable($q['periode_fin']))->format('d/m/Y') : '—' ?></td>
                  <td class="cell-sub"><?= number_format((float) $q['montant'], 2, ',', ' ') ?> €</td>
                  <td class="cell-sub"><?= (new DateTimeImmutable($q['date_emission']))->format('d/m/Y') ?></td>
                  <td style="display:flex;gap:14px;align-items:center;">
                    <?php if ($q['chemin_fichier_pdf']): ?>
                      <a class="consult-link" href="quittance-document.php?id=<?= (int) $q['id_quittance'] ?>" target="_blank" rel="noopener">
                        Voir le document
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 6 15 12 9 18"/></svg>
                      </a>
                      <a class="consult-link" href="quittance-document.php?id=<?= (int) $q['id_quittance'] ?>&download=1">
                        Télécharger
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><polyline points="7 11 12 16 17 11"/><path d="M5 21h14"/></svg>
                      </a>
                    <?php endif; ?>
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
