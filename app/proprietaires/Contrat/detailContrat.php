<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/ProprietaireController.php';

$controller = new ProprietaireController();
$proprietaire = $controller->getCurrentProprietaire();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$contrat = $controller->getContratDetail($id);

if (!$contrat) {
    http_response_code(404);
    exit('Contrat introuvable ou accès non autorisé.');
}

$quittances = $controller->getQuittancesByContrat($id);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function dateFr(?string $date): string
{
    if (!$date) {
        return '—';
    }
    $timestamp = strtotime($date);

    return $timestamp ? date('d/m/Y à H:i', $timestamp) : '—';
}

function statutContrat(?string $statut): array
{
    return match ($statut) {
        'ACTIVE' => ['Actif', 'actif'],
        'DRAFT' => ['Brouillon', 'attente'],
        'RENEWED' => ['Renouvelé', 'actif'],
        'EXPIRED' => ['Expiré', 'inactif'],
        'TERMINATED' => ['Résilié', 'suspendu'],
        'ARCHIVED' => ['Archivé', 'inactif'],
        default => ['Non renseigné', 'inactif'],
    };
}

$statutSignatureBrut = (string) ($contrat['statut_signature'] ?? 'EN_ATTENTE');
[$labelSignature, $toneSignature] = match ($statutSignatureBrut) {
    'SIGNE_SIMULATION' => ['Signé (simulation)', 'attente'],
    'DEPOSE' => ['PDF déposé · à vérifier', 'attente'],
    'VALIDE' => ['Contrat signé', 'actif'],
    'REFUSE' => ['Non signé / erreur', 'suspendu'],
    default => ['En attente de signature', 'attente'],
};

[$labelContrat, $toneContrat] = statutContrat($contrat['statut_contrat'] ?? null);

$prenom = (string) ($proprietaire['prenom'] ?? '');
$nom = (string) ($proprietaire['nom'] ?? '');

$pageTitle = 'Contrat ' . $contrat['numero'];
$activeNav = 'Mes contrats';
$sidebarFile = 'sidebar-proprietaire.php';
$greetingTitle = 'Détail du contrat';
$greetingSubtitle = 'Consultez le contrat, son statut de signature et ses quittances.';
$profileName = trim($prenom . ' ' . $nom) ?: 'Propriétaire';
$profileRole = 'Propriétaire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1)) ?: 'P';
$extraHead = '<link rel="stylesheet" href="/LOKA/app/administration/mon-agence/dashboard.css">';

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <a class="detail-back" href="contrat.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour à mes contrats
    </a>

    <?php if (($_GET['success'] ?? '') === 'created'): ?>
      <div class="feedback success">Contrat créé. Votre locataire peut maintenant le consulter depuis son espace.</div>
    <?php endif; ?>

    <div class="detail-header">
      <div class="detail-identity">
        <div>
          <p class="panel-sub" style="margin:0 0 2px;">CONTRAT DE LOCATION</p>
          <h1 class="detail-name">Contrat <?= e($contrat['numero']) ?></h1>
          <p class="detail-sub"><?= e($contrat['titre_bien'] ?: $contrat['reference_bien'] ?: 'Bien') ?></p>
        </div>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <span class="status-badge <?= $toneContrat ?>"><?= e($labelContrat) ?></span>
        <span class="status-badge <?= $toneSignature ?>"><?= e($labelSignature) ?></span>
      </div>
    </div>

    <div class="detail-grid">
      <div class="detail-col">
        <div class="panel">
          <div class="panel-header"><div class="panel-title">Informations du contrat</div></div>
          <div class="info-list">
            <div class="info-row"><span class="info-label">Numéro</span><span class="info-value"><?= e($contrat['numero']) ?></span></div>
            <div class="info-row"><span class="info-label">Date de début</span><span class="info-value"><?= e(dateFr($contrat['date_debut'] ?? null)) ?></span></div>
            <div class="info-row"><span class="info-label">Date de fin</span><span class="info-value"><?= e(dateFr($contrat['date_fin'] ?? null)) ?></span></div>
            <div class="info-row"><span class="info-label">Loyer mensuel</span><span class="info-value"><?= number_format((float) $contrat['loyer'], 2, ',', ' ') ?> €</span></div>
            <div class="info-row"><span class="info-label">Charges mensuelles</span><span class="info-value"><?= number_format((float) $contrat['charges'], 2, ',', ' ') ?> €</span></div>
            <div class="info-row"><span class="info-label">Dépôt de garantie</span><span class="info-value"><?= number_format((float) $contrat['depot_garantie'], 2, ',', ' ') ?> €</span></div>
          </div>
          <div class="form-actions">
            <a class="btn btn-primary" href="telecharger.php?id=<?= $id ?>">
              <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;margin-right:3px;"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M4 19h16"/></svg>
              Télécharger le contrat
            </a>
          </div>
        </div>

        <div class="panel" style="margin-top:18px;">
          <div class="panel-header"><div class="panel-title">Locataire</div></div>
          <?php if (!empty($contrat['locataire_nom'])): ?>
            <div class="info-list">
              <div class="info-row"><span class="info-label">Nom</span><span class="info-value"><?= e(trim(($contrat['locataire_prenom'] ?? '') . ' ' . $contrat['locataire_nom'])) ?></span></div>
              <div class="info-row"><span class="info-label">Email</span><span class="info-value"><?= e($contrat['locataire_email'] ?? '—') ?></span></div>
              <div class="info-row"><span class="info-label">Téléphone</span><span class="info-value"><?= e($contrat['locataire_telephone'] ?? '—') ?></span></div>
            </div>
          <?php else: ?>
            <p style="padding:0 18px 18px;font-size:13px;color:var(--text);">Aucun locataire n'est actuellement rattaché à ce contrat.</p>
          <?php endif; ?>
        </div>
      </div>

      <div class="detail-col">
        <div class="panel">
          <div class="panel-header"><div class="panel-title">Statut de signature</div></div>
          <div style="padding:0 18px 18px;">
            <span class="status-badge <?= $toneSignature ?>" style="font-size:13px;padding:7px 14px;"><?= e($labelSignature) ?></span>
          </div>
          <?php if (!empty($contrat['date_signature']) || !empty($contrat['date_depot'])): ?>
            <div class="info-list">
              <?php if (!empty($contrat['date_signature'])): ?>
                <div class="info-row"><span class="info-label">Signature enregistrée le</span><span class="info-value"><?= e(dateFr($contrat['date_signature'])) ?></span></div>
              <?php endif; ?>
              <?php if (!empty($contrat['date_depot'])): ?>
                <div class="info-row"><span class="info-label">PDF déposé le</span><span class="info-value"><?= e(dateFr($contrat['date_depot'])) ?></span></div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
          <p style="padding:0 18px 18px;font-size:12px;color:var(--text);line-height:1.5;">
            La signature est effectuée par le locataire ; vous pouvez uniquement consulter son avancement ici. Seule une vérification de l'agence fait passer le statut à « Contrat signé ».
          </p>
        </div>

        <div class="panel table-panel" style="margin-top:18px;">
          <div class="panel-header"><div class="panel-title">Quittances de ce contrat</div></div>
          <?php if (empty($quittances)): ?>
            <p style="padding:0 18px 18px;font-size:13px;color:var(--text);">Aucune quittance disponible pour ce contrat pour le moment.</p>
          <?php else: ?>
            <div class="document-list">
              <?php foreach ($quittances as $q): ?>
                <div class="document-item">
                  <div style="display:flex;gap:14px;min-width:0;">
                    <div class="cell-avatar"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/><path d="M9 7h7M9 11h5M9 15h3"/></svg></div>
                    <div class="document-main">
                      <div class="document-title"><?= e($q['numero'] ?? 'Quittance') ?></div>
                      <div class="document-sub">Montant : <strong style="color:var(--heading);"><?= number_format((float) $q['montant'], 2, ',', ' ') ?> €</strong></div>
                    </div>
                  </div>
                  <div class="document-actions">
                    <a href="../Quittances/quittance-telecharger.php?id=<?= (int) $q['id_paiement'] ?>" class="btn btn-sm">Télécharger</a>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
