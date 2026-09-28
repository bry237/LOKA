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

<script src="/LOKA/layouts/admin.js"></script>
<?= $extraScripts ?>
</body>
</html>
