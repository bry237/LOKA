<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Model.php';

final class MaintenanceModel extends Model
{
    public function getByLocataire(int $idLocataire): array
    {
        $sql = "
            SELECT
                i.id_intervention,
                i.id_bien,
                i.titre,
                i.description,
                i.priorite,
                i.statut,
                i.date_debut,
                i.date_fin,
                i.cout_estime,
                i.cout_final,
                i.compte_rendu,
                i.created_at,
                i.updated_at,

                b.reference,
                b.titre AS logement_titre,

                a.ligne1,
                a.code_postal,
                a.ville

            FROM intervention i

            INNER JOIN bien b
                ON b.id_bien = i.id_bien

            INNER JOIN adresse a
                ON a.id_adresse = b.id_adresse

            WHERE i.id_locataire = :id_locataire

            ORDER BY i.created_at DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id_locataire' => $idLocataire
        ]);

        return $stmt->fetchAll();
    }

    public function findByIdForLocataire(
        int $idIntervention,
        int $idLocataire
    ): ?array {
        $sql = "
            SELECT
                i.id_intervention,
                i.id_bien,
                i.titre,
                i.description,
                i.priorite,
                i.statut,
                i.date_debut,
                i.date_fin,
                i.cout_estime,
                i.cout_final,
                i.compte_rendu,
                i.created_at,
                i.updated_at,

                b.reference,
                b.titre AS logement_titre,

                a.ligne1,
                a.ligne2,
                a.code_postal,
                a.ville,
                a.region,
                a.pays

            FROM intervention i

            INNER JOIN bien b
                ON b.id_bien = i.id_bien

            INNER JOIN adresse a
                ON a.id_adresse = b.id_adresse

            WHERE i.id_intervention = :id_intervention
            AND i.id_locataire = :id_locataire

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id_intervention' => $idIntervention,
            'id_locataire' => $idLocataire
        ]);

        $intervention = $stmt->fetch();

        return $intervention ?: null;
    }

    public function create(
        int $idAgence,
        int $idBien,
        int $idLocataire,
        ?int $idUtilisateurCreateur,
        string $titre,
        string $description,
        string $priorite = 'MEDIUM'
    ): bool {
        $sql = "
            INSERT INTO intervention (
                id_agence,
                id_bien,
                id_locataire,
                id_utilisateur_createur,
                titre,
                description,
                priorite,
                statut
            )
            VALUES (
                :id_agence,
                :id_bien,
                :id_locataire,
                :id_utilisateur_createur,
                :titre,
                :description,
                :priorite,
                'OPEN'
            )
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'id_agence' => $idAgence,
            'id_bien' => $idBien,
            'id_locataire' => $idLocataire,
            'id_utilisateur_createur' => $idUtilisateurCreateur,
            'titre' => $titre,
            'description' => $description,
            'priorite' => $priorite
        ]);
    }

    public function getComments(int $idIntervention): array
    {
        $sql = "
            SELECT
                ci.id_commentaire,
                ci.id_intervention,
                ci.id_utilisateur,
                ci.contenu,
                ci.created_at,
                u.nom,
                u.prenom

            FROM commentaire_intervention ci

            LEFT JOIN utilisateur u
                ON u.id_utilisateur = ci.id_utilisateur

            WHERE ci.id_intervention = :id_intervention

            ORDER BY ci.created_at ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id_intervention' => $idIntervention
        ]);

        return $stmt->fetchAll();
    }

    public function updateForLocataire(
    int $idIntervention,
    int $idLocataire,
    string $titre,
    string $description,
    string $priorite
): bool {
    $sql = "
        UPDATE intervention
        SET
            titre = :titre,
            description = :description,
            priorite = :priorite
        WHERE id_intervention = :id_intervention
          AND id_locataire = :id_locataire
          AND statut = 'OPEN'
    ";

    $stmt = $this->db->prepare($sql);

    return $stmt->execute([
        'titre' => $titre,
        'description' => $description,
        'priorite' => $priorite,
        'id_intervention' => $idIntervention,
        'id_locataire' => $idLocataire
    ]);
}


public function deleteForLocataire(
    int $idIntervention,
    int $idLocataire
): bool {
        $sql = "
            DELETE FROM intervention
            WHERE id_intervention = :id_intervention
            AND id_locataire = :id_locataire
            AND statut = 'OPEN'
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'id_intervention' => $idIntervention,
            'id_locataire' => $idLocataire
        ]);
    }
}

