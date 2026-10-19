<?php

declare(strict_types=1);
require_once __DIR__ . '/ContratController.php';
$controller = new ContratController();
$locataire = $controller->getLocataire();
$prenom = (string) ($locataire['prenom'] ?? '');
$nom = (string) ($locataire['nom'] ?? '');
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$contrat = $controller->getContrat($id);
if (!$contrat) { http_response_code(404); exit('Contrat introuvable ou accès non autorisé.'); }
function h(?string $value): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function dateFr(?string $date): string { if (!$date) return '—'; $t = strtotime($date); return $t ? date('d/m/Y à H:i', $t) : '—'; }
$statut = (string) ($contrat['statut_signature'] ?? 'EN_ATTENTE');
$messages = [
    'signature' => 'La signature simulée a été enregistrée.',
    'depot' => 'Votre PDF a été déposé. Il est en attente de vérification par l’agence.',
];
$success = $messages[(string) ($_GET['success'] ?? '')] ?? null;
$error = isset($_GET['error']) ? (string) $_GET['error'] : null;
$bloque = in_array($statut, ['VALIDE'], true);
$statutTone = ($statut === 'VALIDE' || $statut === 'SIGNE_SIMULATION') ? 'actif' : 'attente';
$statutLabel = match ($statut) {
    'SIGNE_SIMULATION' => 'Signé (simulation)',
    'DEPOSE' => 'PDF déposé · à vérifier',
    'VALIDE' => 'Validé par l’agence',
    'REFUSE' => 'À corriger',
    default => 'En attente de signature',
};

$pageTitle = 'Contrat ' . $contrat['numero'];
$activeNav = 'Mon contrat';
$sidebarFile = 'sidebar-locataire.php';
$greetingTitle = 'Détail du contrat';
$greetingSubtitle = 'Gérez votre contrat de location.';
$profileName = trim($prenom . ' ' . $nom);
$profileRole = 'Locataire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1)) ?: 'L';
$extraHead = '<link rel="stylesheet" href="/LOKA/app/locataires/ops.css?v=' . filemtime(dirname(__DIR__) . '/ops.css') . '">';

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <a class="detail-back" href="index.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour à mes contrats
    </a>

    <div class="detail-header">
      <div class="detail-identity">
        <div>
          <p class="panel-sub" style="margin:0 0 2px;">CONTRAT DE LOCATION</p>
          <h1 class="detail-name">Contrat <?= h($contrat['numero']) ?></h1>
          <p class="detail-sub"><?= h($contrat['titre_bien'] ?: $contrat['reference_bien'] ?: 'Logement') ?></p>
        </div>
      </div>
      <span class="status-badge <?= $statutTone ?>"><?= h($statutLabel) ?></span>
    </div>

    <?php if ($success): ?><div class="feedback success"><?= h($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="feedback error"><?= h($error) ?></div><?php endif; ?>

    <div class="detail-grid">
      <div class="detail-col">
        <div class="panel">
          <div class="panel-header"><div class="panel-title">Informations du contrat</div></div>
          <div class="info-list">
            <div class="info-row"><span class="info-label">Numéro</span><span class="info-value"><?= h($contrat['numero']) ?></span></div>
            <div class="info-row"><span class="info-label">Date de début</span><span class="info-value"><?= h(dateFr($contrat['date_debut'] ?? null)) ?></span></div>
            <div class="info-row"><span class="info-label">Date de fin</span><span class="info-value"><?= h(dateFr($contrat['date_fin'] ?? null)) ?></span></div>
            <div class="info-row"><span class="info-label">Loyer mensuel</span><span class="info-value"><?= number_format((float) $contrat['loyer'], 2, ',', ' ') ?> €</span></div>
            <div class="info-row"><span class="info-label">Charges mensuelles</span><span class="info-value"><?= number_format((float) $contrat['charges'], 2, ',', ' ') ?> €</span></div>
            <div class="info-row"><span class="info-label">Dépôt de garantie</span><span class="info-value"><?= number_format((float) $contrat['depot_garantie'], 2, ',', ' ') ?> €</span></div>
          </div>
          <div class="form-actions">
            <a class="btn btn-primary" href="telecharger.php?id=<?= $id ?>&type=original">Télécharger le contrat original</a>
            <?php if (!empty($contrat['chemin_pdf_signe'])): ?>
              <a class="btn" href="telecharger.php?id=<?= $id ?>&type=signe">Télécharger mon PDF déposé</a>
            <?php endif; ?>
          </div>
        </div>

        <div class="panel" style="margin-top:18px;">
          <div class="panel-header"><div class="panel-title">Déposer un contrat signé</div></div>
          <p style="padding:0 18px;font-size:12.5px;color:var(--text);">Si vous avez signé le document hors de LOKA, vous pouvez transmettre ici le PDF signé. Format PDF uniquement, 10 Mo maximum.</p>
          <?php if (!$bloque): ?>
            <form method="post" action="action.php" enctype="multipart/form-data">
              <input type="hidden" name="csrf_token" value="<?= h($controller->token()) ?>">
              <input type="hidden" name="id_contrat" value="<?= $id ?>">
              <input type="hidden" name="action" value="deposer">
              <input type="hidden" name="MAX_FILE_SIZE" value="10485760">
              <div class="form-grid full">
                <div class="form-field">
                  <label for="contrat_signe">Fichier PDF signé</label>
                  <input id="contrat_signe" name="contrat_signe" type="file" accept="application/pdf,.pdf" required>
                </div>
              </div>
              <div class="form-actions"><button class="btn btn-primary" type="submit">Envoyer le PDF signé</button></div>
            </form>
          <?php else: ?>
            <p style="padding:0 18px 18px;font-size:13px;color:var(--text);">Le contrat est validé. Le dépôt est fermé.</p>
          <?php endif; ?>
          <?php if (!empty($contrat['nom_fichier_signe'])): ?>
            <div class="info-list">
              <div class="info-row"><span class="info-label">Dernier fichier envoyé</span><span class="info-value"><?= h($contrat['nom_fichier_signe']) ?></span></div>
              <div class="info-row"><span class="info-label">Déposé le</span><span class="info-value"><?= h(dateFr($contrat['date_depot'] ?? null)) ?></span></div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="detail-col">
        <div class="panel">
          <div class="panel-header"><div class="panel-title">Signature en ligne</div></div>
          <p style="padding:0 18px;font-size:12.5px;color:var(--text);">Cette fonction sert à démontrer le parcours utilisateur dans le projet scolaire. Elle n’utilise aucun prestataire et ne constitue pas une signature électronique juridiquement garantie.</p>
          <?php if (!empty($contrat['nom_signataire'])): ?>
            <div class="info-list">
              <div class="info-row"><span class="info-label">Nom saisi</span><span class="info-value"><?= h($contrat['nom_signataire']) ?></span></div>
              <div class="info-row"><span class="info-label">Date d’enregistrement</span><span class="info-value"><?= h(dateFr($contrat['date_signature'] ?? null)) ?></span></div>
              <div class="info-row"><span class="info-label">Statut</span><span class="info-value"><?= h($statut === 'VALIDE' ? 'Validé par l’agence' : 'Signé (simulation)') ?></span></div>
            </div>
          <?php endif; ?>
          <?php if (!$bloque && $statut !== 'DEPOSE'): ?>
            <form method="post" action="action.php">
              <input type="hidden" name="csrf_token" value="<?= h($controller->token()) ?>">
              <input type="hidden" name="id_contrat" value="<?= $id ?>">
              <input type="hidden" name="action" value="signer">
              <div class="form-grid full">
                <div class="form-field">
                  <label for="nom_signataire">Nom complet du signataire</label>
                  <input id="nom_signataire" name="nom_signataire" type="text" maxlength="255" required value="<?= h($contrat['nom_signataire'] ?? ($prenom . ' ' . $nom)) ?>">
                </div>
                <label class="filter-checkbox" style="align-items:flex-start;">
                  <input type="checkbox" name="consentement" value="1" required style="margin-top:3px;">
                  <span>Je confirme avoir pris connaissance du contrat et souhaite enregistrer cette signature de démonstration.</span>
                </label>
              </div>
              <div class="form-actions"><button class="btn btn-primary" type="submit">Confirmer la signature simulée</button></div>
            </form>
          <?php elseif ($statut === 'DEPOSE'): ?>
            <p style="padding:0 18px 18px;font-size:13px;color:var(--text);">Votre PDF a été déposé. Attendez la vérification de l’agence avant de recommencer.</p>
          <?php else: ?>
            <p style="padding:0 18px 18px;font-size:13px;color:var(--text);">Le contrat a été validé par l’agence. La signature est verrouillée.</p>
          <?php endif; ?>
        </div>

        <div class="panel" style="margin-top:18px;">
          <p style="padding:14px 18px;font-size:12px;color:var(--text);line-height:1.5;">
            <strong>À savoir :</strong> « Signé (simulation) » signifie que LOKA a enregistré une confirmation. « PDF déposé » signifie qu’un fichier a été transmis. Seule une vérification effectuée par l’agence peut faire passer le dossier à « Validé par l’agence » dans la gestion administrative.
          </p>
        </div>
      </div>
    </div>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
