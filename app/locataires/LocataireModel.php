<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Model.php';

final class LocataireModel extends Model
{
    public function findByUserEmail(string $email): ?array
    {
        $sql = "
            SELECT
                id_locataire,
                id_agence,
                nom,
                prenom,
                email,
                telephone,
                adresse,
                code_postal,
                ville,
                pays,
                date_naissance,
                profession,
                revenu_mensuel,
                statut
            FROM locataire
            WHERE LOWER(email) = LOWER(:email)
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'email' => $email
        ]);

        $locataire = $stmt->fetch();

        return $locataire ?: null;
    }

    public function updateByEmail(string $email, array $data): bool
    {
        $fields = [];
        $params = [
            'current_email' => $email
        ];

        foreach ($data as $field => $value) {
            $fields[] = "$field = :$field";
            $params[$field] = $value;
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "
            UPDATE locataire
            SET " . implode(', ', $fields) . "
            WHERE LOWER(email) = LOWER(:current_email)
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    public function findCurrentHousing(int $idLocataire): ?array
    {
        $sql = "
            SELECT
                b.id_bien,
                b.reference,
                b.titre,
                b.surface,
                b.nombre_pieces,
                b.etage,
                b.loyer AS bien_loyer,
                b.charges AS bien_charges,
                b.caution,
                b.description,
                b.statut AS statut_bien,

                tb.nom AS type_bien,

                a.ligne1,
                a.ligne2,
                a.code_postal,
                a.ville,
                a.region,
                a.pays,

                c.id_contrat,
                c.numero AS numero_contrat,
                c.date_debut,
                c.date_fin,
                c.loyer AS contrat_loyer,
                c.charges AS contrat_charges,
                c.depot_garantie,
                c.frequence_paiement,
                c.jour_echeance,
                c.statut AS statut_contrat,

                cl.titulaire_principal,
                cl.date_entree,
                cl.date_sortie

            FROM contrat_locataire cl

            INNER JOIN contrat c
                ON c.id_contrat = cl.id_contrat

            INNER JOIN bien b
                ON b.id_bien = c.id_bien

            INNER JOIN type_bien tb
                ON tb.id_type_bien = b.id_type_bien

            INNER JOIN adresse a
                ON a.id_adresse = b.id_adresse

            WHERE cl.id_locataire = :id_locataire
            AND c.deleted_at IS NULL
            AND b.deleted_at IS NULL
            AND c.statut = 'ACTIVE'
            AND (
                cl.date_sortie IS NULL
                OR cl.date_sortie >= CURDATE()
            )

            ORDER BY cl.titulaire_principal DESC, c.date_debut DESC

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id_locataire' => $idLocataire
        ]);

        $housing = $stmt->fetch();

        return $housing ?: null;
    }

    public function searchAvailableHousing(array $filters = []): array
    {
        $sql = "
        SELECT
            b.id_bien,
            b.reference,
            b.titre,
            b.surface,
            b.nombre_pieces,
            b.etage,
            b.loyer,
            b.charges,
            b.caution,
            b.description,
            tb.nom AS type_bien,
            a.ligne1,
            a.code_postal,
            a.ville,
            a.region,
            a.pays,
            bi.image_url
        FROM bien b
        INNER JOIN type_bien tb
            ON tb.id_type_bien = b.id_type_bien
        INNER JOIN adresse a
            ON a.id_adresse = b.id_adresse
        LEFT JOIN bien_image bi
            ON bi.id_bien = b.id_bien
            AND bi.est_principale = 1
        ";

        $params = [];

        if (!empty($filters['ville'])) {
            $sql .= " AND LOWER(a.ville) LIKE LOWER(:ville)";
            $params['ville'] = '%' . trim($filters['ville']) . '%';
        }

        if (!empty($filters['type_bien'])) {
            $sql .= " AND b.id_type_bien = :type_bien";
            $params['type_bien'] = (int) $filters['type_bien'];
        }

        if ($filters['loyer_max'] !== '') {
            $sql .= " AND b.loyer <= :loyer_max";
            $params['loyer_max'] = (float) $filters['loyer_max'];
        }

        if ($filters['loyer_min'] !== '') {
            $sql .= " AND b.loyer >= :loyer_min";
            $params['loyer_min'] = (float) $filters['loyer_min'];
        }

        if ($filters['pieces'] !== '') {
            $sql .= " AND b.nombre_pieces >= :pieces";
            $params['pieces'] = (int) $filters['pieces'];
        }

        if ($filters['surface_min'] !== '') {
            $sql .= " AND b.surface >= :surface_min";
            $params['surface_min'] = (float) $filters['surface_min'];
        }

        $sql .= "
            ORDER BY b.created_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function findDonneesQuittance(int $idPaiement, int $idLocataire): ?array
{
    $sql = "
        SELECT
            p.id_paiement, p.montant, p.date_paiement, p.mode_paiement,
            p.reference_transaction,
            e.periode_debut, e.periode_fin,
            e.montant_loyer, e.montant_charges, e.montant_total,
            c.numero AS numero_contrat,
            b.titre AS bien_titre,
            ad.ligne1 AS adresse_ligne1, ad.ligne2 AS adresse_ligne2,
            ad.code_postal AS adresse_cp, ad.ville AS adresse_ville,
            ag.nom AS agence_nom, ag.adresse AS agence_adresse,
            ag.code_postal AS agence_code_postal, ag.ville AS agence_ville,
            l.nom AS locataire_nom, l.prenom AS locataire_prenom
        FROM paiement p
        INNER JOIN echeance e ON e.id_echeance = p.id_echeance
        INNER JOIN contrat c ON c.id_contrat = p.id_contrat
        INNER JOIN contrat_locataire cl ON cl.id_contrat = c.id_contrat
        INNER JOIN locataire l ON l.id_locataire = cl.id_locataire
        LEFT JOIN bien b ON b.id_bien = c.id_bien
        LEFT JOIN adresse ad ON ad.id_adresse = b.id_adresse
        LEFT JOIN agence ag ON ag.id_agence = c.id_agence
        WHERE p.id_paiement = :id_paiement
        AND p.statut = 'PAID'
        AND cl.id_locataire = :id_locataire
        LIMIT 1
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([
        'id_paiement' => $idPaiement,
        'id_locataire' => $idLocataire,
    ]);

    return $stmt->fetch() ?: null;
}
}