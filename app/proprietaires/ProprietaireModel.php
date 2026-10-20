<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Model.php';

final class ProprietaireModel extends Model
{
    public function findByUserId(int $idUtilisateur): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT p.id_proprietaire, p.id_agence, p.nom, p.prenom, p.email, p.telephone,
                    p.adresse, p.code_postal, p.ville, p.pays, p.date_naissance, p.projet
             FROM proprietaire p
             WHERE p.id_utilisateur = :id_utilisateur
               AND p.deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute(['id_utilisateur' => $idUtilisateur]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function getStats(int $idProprietaire): array
    {
        $totalBiens = $this->db->prepare(
            'SELECT COUNT(*) FROM bien_proprietaire bp
             INNER JOIN bien b ON b.id_bien = bp.id_bien
             WHERE bp.id_proprietaire = :id AND b.deleted_at IS NULL'
        );
        $totalBiens->execute(['id' => $idProprietaire]);

        $contratsActifs = $this->db->prepare(
            "SELECT COUNT(*) FROM contrat c
             INNER JOIN bien_proprietaire bp ON bp.id_bien = c.id_bien
             WHERE bp.id_proprietaire = :id AND c.statut = 'ACTIVE' AND c.deleted_at IS NULL"
        );
        $contratsActifs->execute(['id' => $idProprietaire]);

        $totalPaiements = $this->db->prepare(
            'SELECT COUNT(*) FROM paiement p
             INNER JOIN contrat c ON c.id_contrat = p.id_contrat
             INNER JOIN bien_proprietaire bp ON bp.id_bien = c.id_bien
             WHERE bp.id_proprietaire = :id'
        );
        $totalPaiements->execute(['id' => $idProprietaire]);

        $revenus = $this->db->prepare(
            "SELECT COALESCE(SUM(p.montant), 0) FROM paiement p
             INNER JOIN contrat c ON c.id_contrat = p.id_contrat
             INNER JOIN bien_proprietaire bp ON bp.id_bien = c.id_bien
             WHERE bp.id_proprietaire = :id AND p.statut = 'PAID'"
        );
        $revenus->execute(['id' => $idProprietaire]);

        $impayes = $this->db->prepare(
            "SELECT COUNT(*) FROM echeance e
             INNER JOIN contrat c ON c.id_contrat = e.id_contrat
             INNER JOIN bien_proprietaire bp ON bp.id_bien = c.id_bien
             WHERE bp.id_proprietaire = :id AND e.statut IN ('PENDING', 'LATE') AND e.date_echeance < CURDATE()"
        );
        $impayes->execute(['id' => $idProprietaire]);

        return [
            'total_biens' => (int) $totalBiens->fetchColumn(),
            'contrats_actifs' => (int) $contratsActifs->fetchColumn(),
            'total_paiements' => (int) $totalPaiements->fetchColumn(),
            'revenus' => (float) $revenus->fetchColumn(),
            'echeances_en_retard' => (int) $impayes->fetchColumn(),
        ];
    }

    public function getDerniersPaiements(int $idProprietaire, int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.id_paiement, b.titre, p.date_paiement, p.montant, p.statut
             FROM paiement p
             INNER JOIN contrat c ON c.id_contrat = p.id_contrat
             INNER JOIN bien b ON b.id_bien = c.id_bien
             INNER JOIN bien_proprietaire bp ON bp.id_bien = b.id_bien
             WHERE bp.id_proprietaire = :id
             ORDER BY p.date_paiement IS NULL, p.date_paiement DESC, p.id_paiement DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':id', $idProprietaire, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getMesBiens(int $idProprietaire): array
    {
        $stmt = $this->db->prepare(
            'SELECT b.id_bien, b.titre, b.reference, a.ville, b.statut, b.loyer, b.surface, tb.nom AS type_bien
             FROM bien b
             INNER JOIN bien_proprietaire bp ON bp.id_bien = b.id_bien
             LEFT JOIN adresse a ON a.id_adresse = b.id_adresse
             LEFT JOIN type_bien tb ON tb.id_type_bien = b.id_type_bien
             WHERE bp.id_proprietaire = :id AND b.deleted_at IS NULL
             ORDER BY b.titre'
        );
        $stmt->execute(['id' => $idProprietaire]);

        return $stmt->fetchAll();
    }

    public function getRevenusMensuels(int $idProprietaire): array
    {
        $stmt = $this->db->prepare(
            "SELECT MONTH(p.date_paiement) AS mois, SUM(p.montant) AS total
             FROM paiement p
             INNER JOIN echeance e ON e.id_echeance = p.id_echeance
             INNER JOIN contrat c ON c.id_contrat = e.id_contrat
             INNER JOIN bien_proprietaire bp ON bp.id_bien = c.id_bien
             WHERE bp.id_proprietaire = :id AND p.statut = 'PAID' AND p.date_paiement IS NOT NULL
               AND p.date_paiement >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
             GROUP BY MONTH(p.date_paiement)
             ORDER BY MONTH(p.date_paiement)"
        );
        $stmt->execute(['id' => $idProprietaire]);

        return $stmt->fetchAll();
    }

    /**
     * Vue d'ensemble du dossier pour le Centre de vérification : statut des contrats,
     * réception des paiements, validation des pièces, disponibilité des quittances.
     */
    public function getDossierVerification(int $idProprietaire): array
    {
        $contrats = $this->db->prepare(
            "SELECT c.statut, COUNT(*) AS total
             FROM contrat c
             INNER JOIN bien_proprietaire bp ON bp.id_bien = c.id_bien
             WHERE bp.id_proprietaire = :id AND c.deleted_at IS NULL
             GROUP BY c.statut"
        );
        $contrats->execute(['id' => $idProprietaire]);
        $parStatut = array_column($contrats->fetchAll(), 'total', 'statut');

        $echeancesRetard = $this->db->prepare(
            "SELECT COUNT(*) FROM echeance e
             INNER JOIN contrat c ON c.id_contrat = e.id_contrat
             INNER JOIN bien_proprietaire bp ON bp.id_bien = c.id_bien
             WHERE bp.id_proprietaire = :id AND e.statut IN ('PENDING', 'LATE') AND e.date_echeance < CURDATE()"
        );
        $echeancesRetard->execute(['id' => $idProprietaire]);

        $quittancesDisponibles = $this->db->prepare(
            'SELECT COUNT(*) FROM quittance q
             INNER JOIN paiement p ON p.id_paiement = q.id_paiement
             INNER JOIN contrat c ON c.id_contrat = p.id_contrat
             INNER JOIN bien_proprietaire bp ON bp.id_bien = c.id_bien
             WHERE bp.id_proprietaire = :id'
        );
        $quittancesDisponibles->execute(['id' => $idProprietaire]);

        $documentsEnAttente = $this->db->prepare(
            "SELECT COUNT(*) FROM document_utilisateur du
             INNER JOIN proprietaire p ON p.id_utilisateur = du.id_utilisateur
             WHERE p.id_proprietaire = :id AND du.statut_verification = 'EN_ATTENTE'"
        );
        $documentsEnAttente->execute(['id' => $idProprietaire]);

        $interventionsOuvertes = $this->db->prepare(
            "SELECT COUNT(*) FROM intervention i
             INNER JOIN bien_proprietaire bp ON bp.id_bien = i.id_bien
             WHERE bp.id_proprietaire = :id AND i.statut NOT IN ('RESOLVED', 'CLOSED', 'CANCELLED')"
        );
        $interventionsOuvertes->execute(['id' => $idProprietaire]);

        return [
            'contrats_actifs' => (int) ($parStatut['ACTIVE'] ?? 0),
            'contrats_brouillon' => (int) ($parStatut['DRAFT'] ?? 0),
            'contrats_expires' => (int) (($parStatut['EXPIRED'] ?? 0) + ($parStatut['TERMINATED'] ?? 0)),
            'echeances_en_retard' => (int) $echeancesRetard->fetchColumn(),
            'quittances_disponibles' => (int) $quittancesDisponibles->fetchColumn(),
            'documents_en_attente' => (int) $documentsEnAttente->fetchColumn(),
            'interventions_ouvertes' => (int) $interventionsOuvertes->fetchColumn(),
        ];
    }

    /**
     * Interventions (incidents) ouvertes sur les biens du propriétaire, les plus récentes d'abord.
     */
    public function getInterventions(int $idProprietaire): array
    {
        $stmt = $this->db->prepare(
            'SELECT i.id_intervention, i.titre, i.description, i.priorite, i.statut, i.created_at,
                    b.titre AS bien_titre
             FROM intervention i
             INNER JOIN bien_proprietaire bp ON bp.id_bien = i.id_bien
             INNER JOIN bien b ON b.id_bien = i.id_bien
             WHERE bp.id_proprietaire = :id
             ORDER BY i.created_at DESC'
        );
        $stmt->execute(['id' => $idProprietaire]);

        return $stmt->fetchAll();
    }

    /**
     * Biens du propriétaire éligibles pour un signalement d'incident (pour le <select> du formulaire).
     */
    public function getBiensPourSignalement(int $idProprietaire): array
    {
        $stmt = $this->db->prepare(
            'SELECT b.id_bien, b.titre, b.id_agence
             FROM bien b
             INNER JOIN bien_proprietaire bp ON bp.id_bien = b.id_bien
             WHERE bp.id_proprietaire = :id AND b.deleted_at IS NULL
             ORDER BY b.titre'
        );
        $stmt->execute(['id' => $idProprietaire]);

        return $stmt->fetchAll();
    }

    /**
     * Contrats portant sur les biens du propriétaire, avec le statut de signature du
     * locataire titulaire principal (lecture seule : le propriétaire ne signe pas lui-même).
     */
    public function getContrats(int $idProprietaire): array
    {
        $sql = "
            SELECT
                c.id_contrat, c.numero, c.date_debut, c.date_fin, c.loyer, c.charges,
                c.depot_garantie, c.frequence_paiement, c.statut AS statut_contrat,
                c.chemin_fichier_pdf,
                b.titre AS titre_bien, b.reference AS reference_bien,
                l.nom AS locataire_nom, l.prenom AS locataire_prenom,
                cs.statut AS statut_signature
            FROM contrat c
            INNER JOIN bien b ON b.id_bien = c.id_bien
            INNER JOIN bien_proprietaire bp ON bp.id_bien = b.id_bien
            LEFT JOIN contrat_locataire cl ON cl.id_contrat = c.id_contrat AND cl.titulaire_principal = 1
            LEFT JOIN locataire l ON l.id_locataire = cl.id_locataire
            LEFT JOIN contrat_signature cs ON cs.id_contrat = c.id_contrat AND cs.id_locataire = cl.id_locataire
            WHERE bp.id_proprietaire = :id AND c.deleted_at IS NULL
            ORDER BY c.created_at DESC, c.id_contrat DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $idProprietaire]);

        return $stmt->fetchAll();
    }

    public function getContratDetail(int $idContrat, int $idProprietaire): ?array
    {
        $sql = "
            SELECT
                c.id_contrat, c.numero, c.date_debut, c.date_fin, c.loyer, c.charges,
                c.depot_garantie, c.frequence_paiement, c.jour_echeance, c.statut AS statut_contrat,
                c.type_contrat, c.chemin_fichier_pdf,
                b.id_bien, b.titre AS titre_bien, b.reference AS reference_bien,
                l.nom AS locataire_nom, l.prenom AS locataire_prenom, l.email AS locataire_email, l.telephone AS locataire_telephone,
                cs.statut AS statut_signature, cs.date_signature, cs.date_depot, cs.confirmation
            FROM contrat c
            INNER JOIN bien b ON b.id_bien = c.id_bien
            INNER JOIN bien_proprietaire bp ON bp.id_bien = b.id_bien
            LEFT JOIN contrat_locataire cl ON cl.id_contrat = c.id_contrat AND cl.titulaire_principal = 1
            LEFT JOIN locataire l ON l.id_locataire = cl.id_locataire
            LEFT JOIN contrat_signature cs ON cs.id_contrat = c.id_contrat AND cs.id_locataire = cl.id_locataire
            WHERE c.id_contrat = :id_contrat
              AND bp.id_proprietaire = :id_proprietaire
              AND c.deleted_at IS NULL
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_contrat' => $idContrat, 'id_proprietaire' => $idProprietaire]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Quittances rattachées à un contrat précis (pour l'onglet détail du contrat).
     */
    public function getQuittancesByContrat(int $idContrat, int $idProprietaire): array
    {
        $stmt = $this->db->prepare(
            'SELECT q.id_quittance, q.id_paiement, q.numero, q.date_emission, q.periode_debut, q.periode_fin,
                    p.montant, p.statut AS statut_paiement
             FROM quittance q
             INNER JOIN paiement p ON p.id_paiement = q.id_paiement
             INNER JOIN contrat c ON c.id_contrat = p.id_contrat
             INNER JOIN bien_proprietaire bp ON bp.id_bien = c.id_bien
             WHERE c.id_contrat = :id_contrat AND bp.id_proprietaire = :id_proprietaire
             ORDER BY q.date_emission DESC'
        );
        $stmt->execute(['id_contrat' => $idContrat, 'id_proprietaire' => $idProprietaire]);

        return $stmt->fetchAll();
    }

    /**
     * Quittances de tous les paiements perçus sur les biens du propriétaire.
     */
    public function getQuittances(int $idProprietaire): array
    {
        $stmt = $this->db->prepare(
            'SELECT q.id_quittance, q.id_paiement, q.numero, q.date_emission, q.periode_debut, q.periode_fin,
                    p.montant, p.date_paiement, p.statut AS statut_paiement,
                    b.titre AS bien_titre
             FROM quittance q
             INNER JOIN paiement p ON p.id_paiement = q.id_paiement
             INNER JOIN contrat c ON c.id_contrat = p.id_contrat
             INNER JOIN bien b ON b.id_bien = c.id_bien
             INNER JOIN bien_proprietaire bp ON bp.id_bien = b.id_bien
             WHERE bp.id_proprietaire = :id
             ORDER BY q.date_emission DESC, q.id_quittance DESC'
        );
        $stmt->execute(['id' => $idProprietaire]);

        return $stmt->fetchAll();
    }

    /**
     * Données nécessaires pour régénérer le PDF d'une quittance (même format que côté
     * locataire), avec vérification que le paiement appartient bien à un bien du propriétaire.
     */
    public function getDonneesQuittance(int $idPaiement, int $idProprietaire): ?array
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
            INNER JOIN bien b ON b.id_bien = c.id_bien
            INNER JOIN bien_proprietaire bp ON bp.id_bien = b.id_bien
            LEFT JOIN adresse ad ON ad.id_adresse = b.id_adresse
            LEFT JOIN agence ag ON ag.id_agence = c.id_agence
            LEFT JOIN contrat_locataire cl ON cl.id_contrat = c.id_contrat AND cl.titulaire_principal = 1
            LEFT JOIN locataire l ON l.id_locataire = cl.id_locataire
            WHERE p.id_paiement = :id_paiement
              AND p.statut = 'PAID'
              AND bp.id_proprietaire = :id_proprietaire
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_paiement' => $idPaiement, 'id_proprietaire' => $idProprietaire]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $row['numero_quittance'] = sprintf('QUI-%s-%06d', date('Y', strtotime((string) $row['date_paiement'])), $idPaiement);
        $row['date_emission'] = date('Y-m-d H:i:s');

        return $row;
    }

    public function creerSignalement(int $idProprietaire, int $idUtilisateur, int $idBien, string $titre, string $description, string $priorite): int
    {
        $stmt = $this->db->prepare(
            'SELECT bp.id_bien FROM bien_proprietaire bp WHERE bp.id_bien = :id_bien AND bp.id_proprietaire = :id_proprietaire LIMIT 1'
        );
        $stmt->execute(['id_bien' => $idBien, 'id_proprietaire' => $idProprietaire]);
        if (!$stmt->fetchColumn()) {
            throw new RuntimeException('Ce bien ne vous appartient pas.');
        }

        $agence = $this->db->prepare('SELECT id_agence FROM bien WHERE id_bien = :id_bien LIMIT 1');
        $agence->execute(['id_bien' => $idBien]);
        $idAgence = (int) $agence->fetchColumn();

        $insert = $this->db->prepare(
            "INSERT INTO intervention (id_agence, id_bien, id_utilisateur_createur, titre, description, priorite, statut)
             VALUES (:id_agence, :id_bien, :id_utilisateur, :titre, :description, :priorite, 'OPEN')"
        );
        $insert->execute([
            'id_agence' => $idAgence,
            'id_bien' => $idBien,
            'id_utilisateur' => $idUtilisateur,
            'titre' => $titre,
            'description' => $description,
            'priorite' => $priorite,
        ]);

        return (int) $this->db->lastInsertId();
    }
}
