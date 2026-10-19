<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Model.php';

final class QuittanceModel extends Model
{
    /**
     * Récupère toutes les quittances d'un locataire.
     *
     * @param int $idLocataire
     * @return array
     */
    public function getQuittancesByLocataire(int $idLocataire): array
    {
        $sql = "
            SELECT
                q.id_quittance,
                q.id_paiement,
                q.numero,
                q.date_emission,
                q.periode_debut,
                q.periode_fin,
                q.chemin_fichier_pdf,

                p.montant,
                p.date_paiement,
                p.mode_paiement,
                p.reference_transaction,
                p.statut AS statut_paiement,

                e.id_echeance,
                e.montant_loyer,
                e.montant_charges,
                e.montant_total

            FROM quittance q

            INNER JOIN paiement p
                ON p.id_paiement = q.id_paiement

            LEFT JOIN echeance e
                ON e.id_echeance = p.id_echeance

            WHERE p.id_locataire = :id_locataire

            ORDER BY q.date_emission DESC, q.id_quittance DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id_locataire' => $idLocataire
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère une quittance précise appartenant au locataire.
     *
     * @param int $idQuittance
     * @param int $idLocataire
     * @return array|null
     */
    public function getQuittanceByIdForLocataire(
        int $idQuittance,
        int $idLocataire
    ): ?array {
        $sql = "
            SELECT
                q.id_quittance,
                q.id_paiement,
                q.numero,
                q.date_emission,
                q.periode_debut,
                q.periode_fin,
                q.chemin_fichier_pdf,

                p.montant,
                p.date_paiement,
                p.mode_paiement,
                p.reference_transaction,
                p.statut AS statut_paiement,

                e.id_echeance,
                e.montant_loyer,
                e.montant_charges,
                e.montant_total

            FROM quittance q

            INNER JOIN paiement p
                ON p.id_paiement = q.id_paiement

            LEFT JOIN echeance e
                ON e.id_echeance = p.id_echeance

            WHERE q.id_quittance = :id_quittance
              AND p.id_locataire = :id_locataire

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id_quittance' => $idQuittance,
            'id_locataire' => $idLocataire
        ]);

        $quittance = $stmt->fetch(PDO::FETCH_ASSOC);

        return $quittance !== false ? $quittance : null;
    }

    /**
     * Récupère uniquement le chemin du fichier PDF
     * d'une quittance appartenant au locataire.
     *
     * @param int $idQuittance
     * @param int $idLocataire
     * @return string|null
     */
    public function getPdfPathForLocataire(
        int $idQuittance,
        int $idLocataire
    ): ?string {
        $sql = "
            SELECT q.chemin_fichier_pdf

            FROM quittance q

            INNER JOIN paiement p
                ON p.id_paiement = q.id_paiement

            WHERE q.id_quittance = :id_quittance
              AND p.id_locataire = :id_locataire

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id_quittance' => $idQuittance,
            'id_locataire' => $idLocataire
        ]);

        $path = $stmt->fetchColumn();

        return $path !== false && $path !== null
            ? (string) $path
            : null;
    }

    /**
     * Compte le nombre de quittances du locataire.
     *
     * @param int $idLocataire
     * @return int
     */
    public function countQuittancesByLocataire(int $idLocataire): int
    {
        $sql = "
            SELECT COUNT(*)

            FROM quittance q

            INNER JOIN paiement p
                ON p.id_paiement = q.id_paiement

            WHERE p.id_locataire = :id_locataire
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id_locataire' => $idLocataire
        ]);

        return (int) $stmt->fetchColumn();
    }

    /**
 * Recherche un locataire à partir de son adresse e-mail.
 */
public function findLocataireByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            "
            SELECT *
            FROM locataire
            WHERE LOWER(email) = LOWER(:email)
            LIMIT 1
            "
        );

        $stmt->execute([
            'email' => $email
        ]);

        $locataire = $stmt->fetch(PDO::FETCH_ASSOC);

        return $locataire !== false ? $locataire : null;
    }
}