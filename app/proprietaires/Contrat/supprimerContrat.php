<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once dirname(__DIR__, 3) . '/core/Session.php';

$config = require dirname(__DIR__, 3) . '/config/database.php';

$user = Authorization::requireRole('Proprietaire');

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['host'],
        $config['port'],
        $config['name'],
        $config['charset']
    );

    $pdo = new PDO(
        $dsn,
        $config['user'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    $idContrat = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$idContrat) {
        exit('Contrat invalide.');
    }

    /*
     * Vérification que le contrat appartient bien
     * à une propriété du propriétaire connecté.
     */
    $sql = "
        SELECT
            c.id_contrat,
            c.chemin_fichier_pdf
        FROM contrat c
        INNER JOIN bien b
            ON b.id_bien = c.id_bien
        INNER JOIN bien_proprietaire bp
            ON bp.id_bien = b.id_bien
        INNER JOIN proprietaire p
            ON p.id_proprietaire = bp.id_proprietaire
        INNER JOIN utilisateur u
            ON u.email = p.email
        WHERE c.id_contrat = :id_contrat
          AND u.id_utilisateur = :id_utilisateur
          AND c.deleted_at IS NULL
          AND b.deleted_at IS NULL
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id_contrat' => $idContrat,
        ':id_utilisateur' => (int) $user['id_utilisateur'],
    ]);

    $contrat = $stmt->fetch();

    if (!$contrat) {
        exit('Contrat introuvable ou accès non autorisé.');
    }

    $cheminPdf = trim((string) ($contrat['chemin_fichier_pdf'] ?? ''));

    /*
     * Suppression du fichier physique.
     */
    if ($cheminPdf !== '') {

        // On accepte uniquement les fichiers situés dans uploads/contrats/
        $cheminRelatif = ltrim(str_replace('\\', '/', $cheminPdf), '/');

        if (str_starts_with($cheminRelatif, 'uploads/contrats/')) {

            $racineProjet = dirname(__DIR__, 3);

            $fichierPdf = $racineProjet . '/' . $cheminRelatif;

            if (is_file($fichierPdf)) {
                unlink($fichierPdf);
            }
        }
    }

    /*
     * Suppression du chemin PDF dans la base.
     */
    $stmt = $pdo->prepare("
        UPDATE contrat
        SET chemin_fichier_pdf = NULL,
            updated_at = NOW()
        WHERE id_contrat = :id_contrat
    ");

    $stmt->execute([
        ':id_contrat' => $idContrat,
    ]);

    /*
     * Retour vers la fiche du contrat.
     */
    header(
        'Location: /LOKA/app/proprietaires/Contrat/detailContrat.php?id='
        . $idContrat
        . '&pdf_deleted=1'
    );

    exit;

} catch (Throwable $e) {
    http_response_code(500);

    echo '<h2>Erreur lors de la suppression du PDF</h2>';
    echo '<p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
    exit;
}