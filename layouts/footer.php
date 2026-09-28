<?php
declare(strict_types=1);

/**
 * Fermeture de la coquille ouverte par header.php.
 * Variable attendue (optionnelle) : $extraScripts (string) — scripts JS de la page.
 */
$extraScripts ??= '';
?>
  </main>
</div>

<div class="modal-overlay" id="modalOverlay" hidden>
  <div class="modal-panel" id="modalPanel" role="dialog" aria-modal="true">
    <button type="button" class="modal-close" id="modalClose" aria-label="Fermer">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
    <div class="modal-body" id="modalBody"></div>
  </div>
</div>

<script src="/LOKA/layouts/admin.js"></script>
<?= $extraScripts ?>
</body>
</html>
