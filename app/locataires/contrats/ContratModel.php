<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Model.php';

final class ContratModel extends Model
{
    public function getContratsByLocataire(int $idLocataire): array
    {
        $sql = "SELECT
                    c.id_contrat,
                    c.numero,
                    c.date_debut,
                    c.date_fin,
                    c.statut AS statut_contrat,
                    c.chemin_fichier_pdf,
                    c.loyer,
                    c.charges,
                    b.titre AS titre_bien,
                    b.reference AS reference_bien,
                    cs.statut AS statut_signature,
                    cs.date_signature,
                    cs.date_depot,
                    cs.chemin_fichier_signe,
                    cs.confirmation
                FROM contrat c
                INNER JOIN contrat_locataire cl
                    ON cl.id_contrat = c.id_contrat
                LEFT JOIN bien b
                    ON b.id_bien = c.id_bien
                LEFT JOIN contrat_signature cs
                    ON cs.id_contrat = c.id_contrat
                    AND cs.id_locataire = cl.id_locataire
                WHERE cl.id_locataire = :id_locataire
                  AND c.deleted_at IS NULL
                ORDER BY c.created_at DESC, c.id_contrat DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_locataire' => $idLocataire]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getContratForLocataire(
        int $idContrat,
        int $idLocataire
    ): ?array {
        $sql = "SELECT
                    c.id_contrat,
                    c.numero,
                    c.date_debut,
                    c.date_fin,
                    c.statut AS statut_contrat,
                    c.chemin_fichier_pdf,
                    c.loyer,
                    c.charges,
                    c.depot_garantie,
                    b.titre AS titre_bien,
                    b.reference AS reference_bien,
                    cs.id_signature,
                    cs.statut AS statut_signature,
                    cs.confirmation,
                    cs.date_signature,
                    cs.chemin_fichier_signe,
                    cs.date_depot
                FROM contrat c
                INNER JOIN contrat_locataire cl
                    ON cl.id_contrat = c.id_contrat
                LEFT JOIN bien b
                    ON b.id_bien = c.id_bien
                LEFT JOIN contrat_signature cs
                    ON cs.id_contrat = c.id_contrat
                    AND cs.id_locataire = cl.id_locataire
                WHERE c.id_contrat = :id_contrat
                  AND cl.id_locataire = :id_locataire
                  AND c.deleted_at IS NULL
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_contrat' => $idContrat,
            ':id_locataire' => $idLocataire
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function getOrCreateSignature(
        int $idContrat,
        int $idLocataire
    ): array {
        $stmt = $this->db->prepare(
            "SELECT *
             FROM contrat_signature
             WHERE id_contrat = :id_contrat
               AND id_locataire = :id_locataire
             LIMIT 1"
        );

        $stmt->execute([
            ':id_contrat' => $idContrat,
            ':id_locataire' => $idLocataire
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $insert = $this->db->prepare(
                "INSERT INTO contrat_signature
                    (id_contrat, id_locataire, statut)
                 VALUES
                    (:id_contrat, :id_locataire, 'EN_ATTENTE')"
            );

            $insert->execute([
                ':id_contrat' => $idContrat,
                ':id_locataire' => $idLocataire
            ]);

            $stmt->execute([
                ':id_contrat' => $idContrat,
                ':id_locataire' => $idLocataire
            ]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if (!$row) {
            throw new RuntimeException(
                'Impossible de créer le suivi de signature.'
            );
        }

        return $row;
    }

    public function enregistrerSignatureSimulee(
        int $idContrat,
        int $idLocataire,
        string $nom
    ): void {
        // La table actuelle ne permet pas de conserver le nom du signataire.
        // On enregistre uniquement le statut, la confirmation et la date.
        $sql = "INSERT INTO contrat_signature
                    (
                        id_contrat,
                        id_locataire,
                        statut,
                        confirmation,
                        date_signature
                    )
                VALUES
                    (
                        :id_contrat,
                        :id_locataire,
                        'SIGNE_SIMULATION',
                        1,
                        NOW()
                    )
                ON DUPLICATE KEY UPDATE
                    statut = IF(
                        statut IN ('VALIDE', 'REFUSE'),
                        statut,
                        'SIGNE_SIMULATION'
                    ),
                    confirmation = IF(
                        statut = 'VALIDE',
                        confirmation,
                        1
                    ),
                    date_signature = IF(
                        statut = 'VALIDE',
                        date_signature,
                        NOW()
                    )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_contrat' => $idContrat,
            ':id_locataire' => $idLocataire
        ]);
    }

    public function enregistrerDepot(
        int $idContrat,
        int $idLocataire,
        string $chemin,
        string $nomOriginal,
        string $mime,
        int $taille
    ): void {
        // La table actuelle permet de conserver le chemin et la date,
        // mais pas le nom original, le type MIME ni la taille.
        $sql = "INSERT INTO contrat_signature
                    (
                        id_contrat,
                        id_locataire,
                        statut,
                        chemin_fichier_signe,
                        date_depot
                    )
                VALUES
                    (
                        :id_contrat,
                        :id_locataire,
                        'DEPOSE',
                        :chemin,
                        NOW()
                    )
                ON DUPLICATE KEY UPDATE
                    statut = IF(
                        statut = 'VALIDE',
                        statut,
                        'DEPOSE'
                    ),
                    chemin_fichier_signe = IF(
                        statut = 'VALIDE',
                        chemin_fichier_signe,
                        VALUES(chemin_fichier_signe)
                    ),
                    date_depot = IF(
                        statut = 'VALIDE',
                        date_depot,
                        NOW()
                    )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_contrat' => $idContrat,
            ':id_locataire' => $idLocataire,
            ':chemin' => $chemin
        ]);
    }
}
