<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once dirname(__DIR__, 3) . '/core/Auth.php';
require_once dirname(__DIR__, 3) . '/core/Session.php';
require_once dirname(__DIR__, 3) . '/core/Database.php';
require_once __DIR__ . '/ContratModel.php';

final class ContratController
{
    private ContratModel $model;
    private array $user;
    private array $locataire;

    public function __construct()
    {
        $this->user = Authorization::requireRole('Locataire');
        $this->model = new ContratModel();
        $stmt = Database::connection()->prepare(
            'SELECT id_locataire, nom, prenom, email FROM locataire WHERE LOWER(email) = LOWER(:email) AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([':email' => (string) ($this->user['email'] ?? '')]);
        $locataire = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$locataire) {
            http_response_code(403);
            exit('Aucun dossier locataire n’est associé à ce compte.');
        }
        $this->locataire = $locataire;
    }

    public function getLocataire(): array { return $this->locataire; }
    public function getContrats(): array { return $this->model->getContratsByLocataire((int) $this->locataire['id_locataire']); }

    public function getContrat(int $id): ?array
    {
        if ($id < 1) return null;
        return $this->model->getContratForLocataire($id, (int) $this->locataire['id_locataire']);
    }

    public function token(): string { return Auth::csrfToken(); }

    public function signerSimule(int $idContrat, string $nom, bool $consentement, ?string $token): void
    {
        if (!Auth::verifyCsrf($token)) throw new RuntimeException('Le formulaire a expiré. Rechargez la page et réessayez.');
        $contrat = $this->getContrat($idContrat);
        if (!$contrat) throw new RuntimeException('Contrat introuvable ou accès non autorisé.');
        if (($contrat['statut_signature'] ?? '') === 'VALIDE') throw new RuntimeException('Ce contrat a déjà été validé par l’agence.');
        $nom = trim($nom);
        if ($nom === '' || mb_strlen($nom) > 255) throw new RuntimeException('Saisissez votre nom complet.');
        if (!$consentement) throw new RuntimeException('Vous devez confirmer votre intention de signer pour continuer.');
        $this->model->enregistrerSignatureSimulee($idContrat, $this->idLocataire(), $nom);
    }

    public function deposerPdfSigne(int $idContrat, array $file, ?string $token): void
    {
        if (!Auth::verifyCsrf($token)) throw new RuntimeException('Le formulaire a expiré. Rechargez la page et réessayez.');
        $contrat = $this->getContrat($idContrat);
        if (!$contrat) throw new RuntimeException('Contrat introuvable ou accès non autorisé.');
        if (($contrat['statut_signature'] ?? '') === 'VALIDE') throw new RuntimeException('Ce contrat a déjà été validé par l’agence.');
        if (!isset($file['error']) || is_array($file['error']) || $file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Le transfert du fichier a échoué. Sélectionnez de nouveau le PDF.');
        if ((int) $file['size'] < 1 || (int) $file['size'] > 10 * 1024 * 1024) throw new RuntimeException('Le PDF doit peser moins de 10 Mo.');
        if (!is_uploaded_file((string) $file['tmp_name'])) throw new RuntimeException('Fichier reçu de manière non valide.');
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file((string) $file['tmp_name']);
        $handle = fopen((string) $file['tmp_name'], 'rb');
        $signaturePdf = $handle !== false ? fread($handle, 5) : '';
        if ($handle !== false) fclose($handle);
        if ($mime !== 'application/pdf' || $signaturePdf !== '%PDF-') throw new RuntimeException('Seuls les fichiers PDF valides sont acceptés.');

        $root = dirname(__DIR__, 3);
        $storage = $root . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'contrats-signatures';
        if (!is_dir($storage) && !mkdir($storage, 0750, true) && !is_dir($storage)) throw new RuntimeException('Le dossier de stockage des contrats ne peut pas être créé.');
        $safeName = bin2hex(random_bytes(16)) . '.pdf';
        $destination = $storage . DIRECTORY_SEPARATOR . $safeName;
        if (!move_uploaded_file((string) $file['tmp_name'], $destination)) throw new RuntimeException('Impossible d’enregistrer le PDF. Vérifiez les droits du dossier var/contrats-signatures.');
        $original = basename((string) ($file['name'] ?? 'contrat-signe.pdf'));
        $relative = 'var/contrats-signatures/' . $safeName;
        try {
            $this->model->enregistrerDepot($idContrat, $this->idLocataire(), $relative, $original, 'application/pdf', (int) filesize($destination));
        } catch (Throwable $e) {
            @unlink($destination);
            throw $e;
        }
    }

    public function idLocataire(): int { return (int) $this->locataire['id_locataire']; }
}
