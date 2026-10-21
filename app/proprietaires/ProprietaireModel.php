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
                cs.statut AS statut_signature, cs.date_signature, cs.date_depot, cs.confirmation,
                ag.nom AS agence_nom, ad.ligne1 AS adresse_ligne1, ad.code_postal AS adresse_cp, ad.ville AS adresse_ville
            FROM contrat c
            INNER JOIN bien b ON b.id_bien = c.id_bien
            INNER JOIN bien_proprietaire bp ON bp.id_bien = b.id_bien
            LEFT JOIN contrat_locataire cl ON cl.id_contrat = c.id_contrat AND cl.titulaire_principal = 1
            LEFT JOIN locataire l ON l.id_locataire = cl.id_locataire
            LEFT JOIN contrat_signature cs ON cs.id_contrat = c.id_contrat AND cs.id_locataire = cl.id_locataire
            LEFT JOIN agence ag ON ag.id_agence = c.id_agence
            LEFT JOIN adresse ad ON ad.id_adresse = b.id_adresse
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

    /**
     * Locataires présents sur les contrats des biens du propriétaire (un seul par
     * locataire : le contrat le plus pertinent, par statut puis date, détermine la ligne).
     */
    public function getLocataires(int $idProprietaire): array
    {
        $stmt = $this->db->prepare(
            "SELECT l.id_locataire, l.nom, l.prenom, l.email, l.telephone, l.ville, l.statut AS statut_locataire,
                    c.id_contrat, c.numero AS numero_contrat, c.statut AS statut_contrat, c.date_debut, c.date_fin, c.loyer,
                    b.titre AS titre_bien, b.reference AS reference_bien
             FROM locataire l
             INNER JOIN contrat_locataire cl ON cl.id_locataire = l.id_locataire
             INNER JOIN contrat c ON c.id_contrat = cl.id_contrat
             INNER JOIN bien b ON b.id_bien = c.id_bien
             INNER JOIN bien_proprietaire bp ON bp.id_bien = b.id_bien
             WHERE bp.id_proprietaire = :id AND l.deleted_at IS NULL AND c.deleted_at IS NULL AND b.deleted_at IS NULL
             ORDER BY
                CASE c.statut WHEN 'ACTIVE' THEN 0 WHEN 'DRAFT' THEN 1 WHEN 'RENEWED' THEN 2 WHEN 'EXPIRED' THEN 3 ELSE 4 END,
                l.nom, l.prenom, c.date_debut DESC"
        );
        $stmt->execute(['id' => $idProprietaire]);

        $locataires = [];
        foreach ($stmt->fetchAll() as $row) {
            $id = (int) $row['id_locataire'];
            if (!isset($locataires[$id])) {
                $locataires[$id] = $row;
            }
        }

        return array_values($locataires);
    }

    /**
     * Fiche d'un locataire, uniquement s'il est lié à un bien du propriétaire connecté
     * (empêche un propriétaire de consulter le locataire d'un autre via l'URL).
     */
    public function getLocataireDetail(int $idLocataire, int $idProprietaire): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT l.id_locataire, l.nom, l.prenom, l.email, l.telephone, l.adresse, l.code_postal, l.ville, l.pays,
                    l.date_naissance, l.profession, l.revenu_mensuel, l.revenu_mensuel_fourchette, l.statut AS statut_locataire
             FROM locataire l
             WHERE l.id_locataire = :id_locataire
               AND l.deleted_at IS NULL
               AND EXISTS (
                   SELECT 1 FROM contrat_locataire cl
                   INNER JOIN contrat c ON c.id_contrat = cl.id_contrat
                   INNER JOIN bien b ON b.id_bien = c.id_bien
                   INNER JOIN bien_proprietaire bp ON bp.id_bien = b.id_bien
                   WHERE cl.id_locataire = l.id_locataire AND c.deleted_at IS NULL AND b.deleted_at IS NULL
                     AND bp.id_proprietaire = :id_proprietaire
               )
             LIMIT 1"
        );
        $stmt->execute(['id_locataire' => $idLocataire, 'id_proprietaire' => $idProprietaire]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Contrats d'un locataire portant sur les biens du propriétaire connecté.
     */
    public function getContratsLocataire(int $idLocataire, int $idProprietaire): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.id_contrat, c.numero AS numero_contrat, c.date_debut, c.date_fin, c.loyer, c.charges,
                    c.depot_garantie, c.frequence_paiement, c.jour_echeance, c.statut AS statut_contrat,
                    cl.titulaire_principal, cl.date_entree, cl.date_sortie,
                    b.id_bien, b.reference AS reference_bien, b.titre AS titre_bien
             FROM contrat_locataire cl
             INNER JOIN contrat c ON c.id_contrat = cl.id_contrat
             INNER JOIN bien b ON b.id_bien = c.id_bien
             INNER JOIN bien_proprietaire bp ON bp.id_bien = b.id_bien
             WHERE cl.id_locataire = :id_locataire AND bp.id_proprietaire = :id_proprietaire
               AND c.deleted_at IS NULL AND b.deleted_at IS NULL
             ORDER BY
                CASE c.statut WHEN 'ACTIVE' THEN 0 WHEN 'DRAFT' THEN 1 WHEN 'RENEWED' THEN 2 WHEN 'EXPIRED' THEN 3 ELSE 4 END,
                c.date_debut DESC"
        );
        $stmt->execute(['id_locataire' => $idLocataire, 'id_proprietaire' => $idProprietaire]);

        return $stmt->fetchAll();
    }

    /**
     * Historique complet des paiements liés aux biens du propriétaire (page Paiements,
     * sans limite — contrairement à getDerniersPaiements utilisé sur le dashboard).
     */
    public function getPaiements(int $idProprietaire): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.id_paiement, p.montant, p.date_paiement, p.mode_paiement, p.statut,
                    c.numero AS numero_contrat, b.reference AS reference_bien, b.titre AS titre_bien,
                    e.periode_debut, e.periode_fin, e.date_echeance,
                    l.nom AS locataire_nom, l.prenom AS locataire_prenom
             FROM paiement p
             INNER JOIN contrat c ON c.id_contrat = p.id_contrat
             INNER JOIN bien b ON b.id_bien = c.id_bien
             INNER JOIN bien_proprietaire bp ON bp.id_bien = b.id_bien
             LEFT JOIN echeance e ON e.id_echeance = p.id_echeance
             LEFT JOIN locataire l ON l.id_locataire = p.id_locataire
             WHERE bp.id_proprietaire = :id AND c.deleted_at IS NULL AND b.deleted_at IS NULL
             ORDER BY p.date_paiement IS NULL, p.date_paiement DESC, p.id_paiement DESC"
        );
        $stmt->execute(['id' => $idProprietaire]);

        return $stmt->fetchAll();
    }

    /**
     * Types de documents disponibles (table de référence, non liée au propriétaire).
     */
    public function getTypesDocuments(): array
    {
        $stmt = $this->db->prepare('SELECT id_type_document, nom FROM type_document ORDER BY nom');
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** Locataires des biens du propriétaire (pour associer un document à l'un d'eux). */
    public function getLocatairesDeMesBiens(int $idProprietaire): array
    {
        $stmt = $this->db->prepare(
            'SELECT DISTINCT l.id_locataire, l.nom, l.prenom
             FROM locataire l
             INNER JOIN contrat_locataire cl ON cl.id_locataire = l.id_locataire
             INNER JOIN contrat c ON c.id_contrat = cl.id_contrat
             INNER JOIN bien_proprietaire bp ON bp.id_bien = c.id_bien
             WHERE bp.id_proprietaire = :id AND l.deleted_at IS NULL
             ORDER BY l.nom, l.prenom'
        );
        $stmt->execute(['id' => $idProprietaire]);

        return $stmt->fetchAll();
    }

    /**
     * Enregistre un document déjà stocké sur disque (chemin + métadonnées), associé au
     * propriétaire et, éventuellement, à un de ses biens/contrats/locataires. Les identifiants
     * optionnels sont revérifiés ici : jamais fait confiance à un id posté sans contrôle
     * d'appartenance au propriétaire.
     *
     * @param array{id_type_document:int, id_bien:?int, id_contrat:?int, id_locataire:?int,
     *     nom:string, nom_original:string, mime_type:string, taille_octets:int, chemin_stockage:string} $data
     */
    public function creerDocument(int $idProprietaire, array $data): int
    {
        $idBien = $data['id_bien'];
        if ($idBien !== null) {
            $check = $this->db->prepare('SELECT 1 FROM bien_proprietaire WHERE id_bien = :id_bien AND id_proprietaire = :id LIMIT 1');
            $check->execute(['id_bien' => $idBien, 'id' => $idProprietaire]);
            if (!$check->fetchColumn()) {
                throw new RuntimeException('Ce bien ne vous appartient pas.');
            }
        }

        $idContrat = $data['id_contrat'];
        if ($idContrat !== null) {
            $check = $this->db->prepare(
                'SELECT 1 FROM contrat c INNER JOIN bien_proprietaire bp ON bp.id_bien = c.id_bien
                 WHERE c.id_contrat = :id_contrat AND bp.id_proprietaire = :id LIMIT 1'
            );
            $check->execute(['id_contrat' => $idContrat, 'id' => $idProprietaire]);
            if (!$check->fetchColumn()) {
                throw new RuntimeException('Ce contrat ne vous appartient pas.');
            }
        }

        $idLocataire = $data['id_locataire'];
        if ($idLocataire !== null) {
            $check = $this->db->prepare(
                'SELECT 1 FROM contrat_locataire cl
                 INNER JOIN contrat c ON c.id_contrat = cl.id_contrat
                 INNER JOIN bien_proprietaire bp ON bp.id_bien = c.id_bien
                 WHERE cl.id_locataire = :id_locataire AND bp.id_proprietaire = :id LIMIT 1'
            );
            $check->execute(['id_locataire' => $idLocataire, 'id' => $idProprietaire]);
            if (!$check->fetchColumn()) {
                throw new RuntimeException('Ce locataire n’est rattaché à aucun de vos biens.');
            }
        }

        $stmt = $this->db->prepare(
            'INSERT INTO document (id_type_document, id_bien, id_contrat, id_proprietaire, id_locataire,
                nom, nom_original, mime_type, taille_octets, chemin_stockage, version)
             VALUES (:id_type_document, :id_bien, :id_contrat, :id_proprietaire, :id_locataire,
                :nom, :nom_original, :mime_type, :taille_octets, :chemin_stockage, 1)'
        );
        $stmt->execute([
            'id_type_document' => $data['id_type_document'],
            'id_bien' => $idBien,
            'id_contrat' => $idContrat,
            'id_proprietaire' => $idProprietaire,
            'id_locataire' => $idLocataire,
            'nom' => $data['nom'],
            'nom_original' => $data['nom_original'],
            'mime_type' => $data['mime_type'],
            'taille_octets' => $data['taille_octets'],
            'chemin_stockage' => $data['chemin_stockage'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Compteurs pour les cartes de la page Documents : total et répartition par
     * rattachement (bien / contrat / locataire), sur le périmètre du propriétaire.
     */
    public function getDocumentsStats(int $idProprietaire): array
    {
        $base = "
            d.id_proprietaire = :p1
            OR EXISTS (SELECT 1 FROM bien_proprietaire bp WHERE bp.id_bien = d.id_bien AND bp.id_proprietaire = :p2)
            OR EXISTS (
                SELECT 1 FROM contrat c INNER JOIN bien_proprietaire bp ON bp.id_bien = c.id_bien
                WHERE c.id_contrat = d.id_contrat AND bp.id_proprietaire = :p3
            )
            OR EXISTS (
                SELECT 1 FROM contrat c INNER JOIN bien_proprietaire bp ON bp.id_bien = c.id_bien
                INNER JOIN contrat_locataire cl ON cl.id_contrat = c.id_contrat
                WHERE cl.id_locataire = d.id_locataire AND bp.id_proprietaire = :p4
            )
        ";
        $params4 = ['p1' => $idProprietaire, 'p2' => $idProprietaire, 'p3' => $idProprietaire, 'p4' => $idProprietaire];

        $total = $this->db->prepare("SELECT COUNT(*) FROM document d WHERE $base");
        $total->execute($params4);

        $biens = $this->db->prepare(
            "SELECT COUNT(*) FROM document d WHERE d.id_bien IS NOT NULL AND ($base)"
        );
        $biens->execute($params4);

        $contrats = $this->db->prepare(
            "SELECT COUNT(*) FROM document d WHERE d.id_contrat IS NOT NULL AND ($base)"
        );
        $contrats->execute($params4);

        $locataires = $this->db->prepare(
            "SELECT COUNT(*) FROM document d WHERE d.id_locataire IS NOT NULL AND ($base)"
        );
        $locataires->execute($params4);

        return [
            'total' => (int) $total->fetchColumn(),
            'biens' => (int) $biens->fetchColumn(),
            'contrats' => (int) $contrats->fetchColumn(),
            'locataires' => (int) $locataires->fetchColumn(),
        ];
    }

    /**
     * Documents accessibles au propriétaire (rattachés directement à lui, ou à l'un de
     * ses biens/contrats/locataires), avec filtres optionnels de recherche et de type/bien/contrat.
     */
    public function getDocuments(int $idProprietaire, array $filtres = []): array
    {
        $sql = "
            SELECT d.id_document, d.id_bien, d.id_contrat, d.id_locataire, d.nom, d.nom_original,
                   d.mime_type, d.taille_octets, d.chemin_stockage, d.version, d.created_at,
                   td.nom AS type_document_nom,
                   b.reference AS reference_bien, b.titre AS titre_bien,
                   c.numero AS numero_contrat,
                   l.nom AS locataire_nom, l.prenom AS locataire_prenom
            FROM document d
            INNER JOIN type_document td ON td.id_type_document = d.id_type_document
            LEFT JOIN bien b ON b.id_bien = d.id_bien
            LEFT JOIN contrat c ON c.id_contrat = d.id_contrat
            LEFT JOIN locataire l ON l.id_locataire = d.id_locataire
            WHERE (
                d.id_proprietaire = :p1
                OR EXISTS (SELECT 1 FROM bien_proprietaire bp WHERE bp.id_bien = d.id_bien AND bp.id_proprietaire = :p2)
                OR EXISTS (
                    SELECT 1 FROM contrat c2 INNER JOIN bien_proprietaire bp ON bp.id_bien = c2.id_bien
                    WHERE c2.id_contrat = d.id_contrat AND bp.id_proprietaire = :p3
                )
                OR EXISTS (
                    SELECT 1 FROM contrat c3 INNER JOIN bien_proprietaire bp ON bp.id_bien = c3.id_bien
                    INNER JOIN contrat_locataire cl ON cl.id_contrat = c3.id_contrat
                    WHERE cl.id_locataire = d.id_locataire AND bp.id_proprietaire = :p4
                )
            )
        ";
        $params = ['p1' => $idProprietaire, 'p2' => $idProprietaire, 'p3' => $idProprietaire, 'p4' => $idProprietaire];

        $recherche = trim((string) ($filtres['recherche'] ?? ''));
        if ($recherche !== '') {
            $sql .= ' AND (d.nom LIKE :recherche OR d.nom_original LIKE :recherche OR b.reference LIKE :recherche OR b.titre LIKE :recherche OR c.numero LIKE :recherche OR l.nom LIKE :recherche OR l.prenom LIKE :recherche)';
            $params['recherche'] = '%' . $recherche . '%';
        }

        $idType = (int) ($filtres['id_type_document'] ?? 0);
        if ($idType > 0) {
            $sql .= ' AND d.id_type_document = :id_type_document';
            $params['id_type_document'] = $idType;
        }

        $idBien = (int) ($filtres['id_bien'] ?? 0);
        if ($idBien > 0) {
            $sql .= ' AND d.id_bien = :id_bien';
            $params['id_bien'] = $idBien;
        }

        $idContrat = (int) ($filtres['id_contrat'] ?? 0);
        if ($idContrat > 0) {
            $sql .= ' AND d.id_contrat = :id_contrat';
            $params['id_contrat'] = $idContrat;
        }

        $sql .= ' ORDER BY d.created_at DESC, d.id_document DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Total des revenus encaissés (PAID), sans filtre de période.
     */
    public function getTotalRevenus(int $idProprietaire): float
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(p.montant), 0)
             FROM paiement p
             INNER JOIN contrat c ON c.id_contrat = p.id_contrat
             INNER JOIN bien b ON b.id_bien = c.id_bien
             INNER JOIN bien_proprietaire bp ON bp.id_bien = b.id_bien
             WHERE bp.id_proprietaire = :id AND p.statut = 'PAID' AND c.deleted_at IS NULL AND b.deleted_at IS NULL"
        );
        $stmt->execute(['id' => $idProprietaire]);

        return (float) $stmt->fetchColumn();
    }

    /**
     * Répartition encaissé / en attente / en retard sur une période optionnelle
     * (bornes au format 'Y-m-d H:i:s', ou null pour aucun filtre).
     */
    public function getRevenusPeriode(int $idProprietaire, ?string $dateDebutSql, ?string $dateFinSql): array
    {
        $sql = "
            SELECT
                COALESCE(SUM(CASE WHEN p.statut = 'PAID' THEN p.montant ELSE 0 END), 0) AS revenus_encaisses,
                COALESCE(SUM(CASE WHEN p.statut = 'PENDING' THEN p.montant ELSE 0 END), 0) AS revenus_attente,
                COALESCE(SUM(CASE WHEN p.statut = 'LATE' THEN p.montant ELSE 0 END), 0) AS revenus_retard
            FROM paiement p
            INNER JOIN contrat c ON c.id_contrat = p.id_contrat
            INNER JOIN bien b ON b.id_bien = c.id_bien
            INNER JOIN bien_proprietaire bp ON bp.id_bien = b.id_bien
            WHERE bp.id_proprietaire = :id AND c.deleted_at IS NULL AND b.deleted_at IS NULL
        ";
        $params = ['id' => $idProprietaire];
        if ($dateDebutSql !== null && $dateFinSql !== null) {
            $sql .= ' AND p.date_paiement >= :debut AND p.date_paiement <= :fin';
            $params['debut'] = $dateDebutSql;
            $params['fin'] = $dateFinSql;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return [
            'encaisses' => (float) ($row['revenus_encaisses'] ?? 0),
            'attente' => (float) ($row['revenus_attente'] ?? 0),
            'retard' => (float) ($row['revenus_retard'] ?? 0),
        ];
    }

    /**
     * Historique des paiements sur une période optionnelle, pour le tableau de la page Revenus.
     */
    public function getHistoriqueRevenus(int $idProprietaire, ?string $dateDebutSql, ?string $dateFinSql): array
    {
        $sql = "
            SELECT p.id_paiement, p.montant, p.date_paiement, p.mode_paiement, p.statut,
                   c.numero AS numero_contrat, b.reference AS reference_bien, b.titre AS titre_bien,
                   l.nom AS locataire_nom, l.prenom AS locataire_prenom
            FROM paiement p
            INNER JOIN contrat c ON c.id_contrat = p.id_contrat
            INNER JOIN bien b ON b.id_bien = c.id_bien
            INNER JOIN bien_proprietaire bp ON bp.id_bien = b.id_bien
            LEFT JOIN locataire l ON l.id_locataire = p.id_locataire
            WHERE bp.id_proprietaire = :id AND c.deleted_at IS NULL AND b.deleted_at IS NULL
        ";
        $params = ['id' => $idProprietaire];
        if ($dateDebutSql !== null && $dateFinSql !== null) {
            $sql .= ' AND p.date_paiement >= :debut AND p.date_paiement <= :fin';
            $params['debut'] = $dateDebutSql;
            $params['fin'] = $dateFinSql;
        }
        $sql .= ' ORDER BY p.date_paiement DESC, p.id_paiement DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Revenus encaissés (PAID) mois par mois, pour une année donnée (graphique de la page Revenus).
     */
    public function getRevenusMensuelsAnnee(int $idProprietaire, int $annee): array
    {
        $stmt = $this->db->prepare(
            "SELECT MONTH(p.date_paiement) AS mois, COALESCE(SUM(p.montant), 0) AS total
             FROM paiement p
             INNER JOIN contrat c ON c.id_contrat = p.id_contrat
             INNER JOIN bien b ON b.id_bien = c.id_bien
             INNER JOIN bien_proprietaire bp ON bp.id_bien = b.id_bien
             WHERE bp.id_proprietaire = :id AND c.deleted_at IS NULL AND b.deleted_at IS NULL
               AND p.statut = 'PAID' AND YEAR(p.date_paiement) = :annee
             GROUP BY MONTH(p.date_paiement)"
        );
        $stmt->execute(['id' => $idProprietaire, 'annee' => $annee]);

        return $stmt->fetchAll();
    }

    /**
     * Met à jour le profil du propriétaire ET les champs communs du compte utilisateur
     * (les deux tables doivent rester synchronisées, voir Parametres/parametres.php).
     * Retourne un message d'erreur, ou null en cas de succès.
     */
    public function updateProfil(int $idProprietaire, int $idUtilisateur, array $data): ?string
    {
        $email = trim((string) ($data['email'] ?? ''));
        $nom = trim((string) ($data['nom'] ?? ''));
        $prenom = trim((string) ($data['prenom'] ?? ''));

        if ($nom === '' || $prenom === '') {
            return 'Le nom et le prénom sont obligatoires.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Veuillez saisir une adresse email valide.';
        }

        $stmt = $this->db->prepare('SELECT id_utilisateur FROM utilisateur WHERE email = :email AND id_utilisateur <> :id LIMIT 1');
        $stmt->execute(['email' => $email, 'id' => $idUtilisateur]);
        if ($stmt->fetch()) {
            return 'Cette adresse email est déjà utilisée par un autre utilisateur.';
        }

        $telephone = trim((string) ($data['telephone'] ?? '')) ?: null;
        $adresse = trim((string) ($data['adresse'] ?? '')) ?: null;
        $codePostal = trim((string) ($data['code_postal'] ?? '')) ?: null;
        $ville = trim((string) ($data['ville'] ?? '')) ?: null;
        $pays = trim((string) ($data['pays'] ?? '')) ?: null;

        try {
            $this->db->beginTransaction();

            $this->db->prepare(
                'UPDATE proprietaire SET nom = ?, prenom = ?, email = ?, telephone = ?, adresse = ?, code_postal = ?, ville = ?, pays = ?, updated_at = CURRENT_TIMESTAMP WHERE id_proprietaire = ?'
            )->execute([$nom, $prenom, $email, $telephone, $adresse, $codePostal, $ville, $pays, $idProprietaire]);

            $this->db->prepare(
                'UPDATE utilisateur SET nom = ?, prenom = ?, email = ?, telephone = ? WHERE id_utilisateur = ?'
            )->execute([$nom, $prenom, $email, $telephone, $idUtilisateur]);

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return 'Une erreur est survenue lors de la mise à jour du profil.';
        }

        return null;
    }

    /**
     * Change le mot de passe après vérification de l'ancien. Retourne un message
     * d'erreur, ou null en cas de succès.
     */
    public function updateMotDePasse(int $idUtilisateur, string $ancien, string $nouveau, string $confirmation): ?string
    {
        if ($ancien === '' || $nouveau === '' || $confirmation === '') {
            return 'Veuillez remplir tous les champs du mot de passe.';
        }
        if (strlen($nouveau) < 8) {
            return 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
        }
        if ($nouveau !== $confirmation) {
            return 'La confirmation du nouveau mot de passe ne correspond pas.';
        }

        $stmt = $this->db->prepare('SELECT mot_de_passe FROM utilisateur WHERE id_utilisateur = :id LIMIT 1');
        $stmt->execute(['id' => $idUtilisateur]);
        $utilisateur = $stmt->fetch();

        if (!$utilisateur) {
            return 'Utilisateur introuvable.';
        }
        if (!password_verify($ancien, (string) $utilisateur['mot_de_passe'])) {
            return 'L’ancien mot de passe est incorrect.';
        }

        $this->db->prepare('UPDATE utilisateur SET mot_de_passe = ? WHERE id_utilisateur = ?')
            ->execute([password_hash($nouveau, PASSWORD_DEFAULT), $idUtilisateur]);

        return null;
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

    /** Biens du propriétaire éligibles à un nouveau contrat (pas de bien supprimé). */
    public function getMesBiensPourContrat(int $idProprietaire): array
    {
        $stmt = $this->db->prepare(
            'SELECT b.id_bien, b.titre, b.reference, b.id_agence, b.loyer, b.charges, b.caution
             FROM bien b
             INNER JOIN bien_proprietaire bp ON bp.id_bien = b.id_bien
             WHERE bp.id_proprietaire = :id AND b.deleted_at IS NULL
             ORDER BY b.titre'
        );
        $stmt->execute(['id' => $idProprietaire]);

        return $stmt->fetchAll();
    }

    /** Locataires de l'agence qui gère les biens de ce propriétaire (pour l'associer à un contrat). */
    public function getLocatairesDeLAgence(int $idAgence): array
    {
        $stmt = $this->db->prepare(
            "SELECT id_locataire, nom, prenom, email
             FROM locataire
             WHERE id_agence = :id_agence AND deleted_at IS NULL AND statut = 'ACTIVE'
             ORDER BY nom, prenom"
        );
        $stmt->execute(['id_agence' => $idAgence]);

        return $stmt->fetchAll();
    }

    /**
     * Crée un contrat pour un bien du propriétaire et y associe un locataire comme titulaire
     * principal, afin qu'il apparaisse immédiatement côté locataire (table contrat_locataire).
     *
     * @param array{id_bien:int, id_locataire:int, date_debut:string, date_fin:?string, loyer:float,
     *     charges:float, depot_garantie:float, frequence_paiement:string, jour_echeance:int,
     *     type_contrat:string} $data
     */
    public function creerContrat(int $idProprietaire, array $data): int
    {
        $stmt = $this->db->prepare(
            'SELECT b.id_agence FROM bien b
             INNER JOIN bien_proprietaire bp ON bp.id_bien = b.id_bien
             WHERE b.id_bien = :id_bien AND bp.id_proprietaire = :id_proprietaire AND b.deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute(['id_bien' => $data['id_bien'], 'id_proprietaire' => $idProprietaire]);
        $idAgence = $stmt->fetchColumn();
        if (!$idAgence) {
            throw new RuntimeException('Ce bien ne vous appartient pas.');
        }
        $idAgence = (int) $idAgence;

        $locataireCheck = $this->db->prepare(
            'SELECT 1 FROM locataire WHERE id_locataire = :id_locataire AND id_agence = :id_agence AND deleted_at IS NULL LIMIT 1'
        );
        $locataireCheck->execute(['id_locataire' => $data['id_locataire'], 'id_agence' => $idAgence]);
        if (!$locataireCheck->fetchColumn()) {
            throw new RuntimeException('Ce locataire n’appartient pas à l’agence qui gère ce bien.');
        }

        $numero = 'CT-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(3)));

        $this->db->beginTransaction();
        try {
            $insertContrat = $this->db->prepare(
                "INSERT INTO contrat (id_agence, id_bien, numero, date_debut, date_fin, loyer, charges,
                    depot_garantie, frequence_paiement, jour_echeance, statut, type_contrat)
                 VALUES (:id_agence, :id_bien, :numero, :date_debut, :date_fin, :loyer, :charges,
                    :depot_garantie, :frequence_paiement, :jour_echeance, 'ACTIVE', :type_contrat)"
            );
            $insertContrat->execute([
                'id_agence' => $idAgence,
                'id_bien' => $data['id_bien'],
                'numero' => $numero,
                'date_debut' => $data['date_debut'],
                'date_fin' => $data['date_fin'],
                'loyer' => $data['loyer'],
                'charges' => $data['charges'],
                'depot_garantie' => $data['depot_garantie'],
                'frequence_paiement' => $data['frequence_paiement'],
                'jour_echeance' => $data['jour_echeance'],
                'type_contrat' => $data['type_contrat'],
            ]);
            $idContrat = (int) $this->db->lastInsertId();

            $insertLocataire = $this->db->prepare(
                'INSERT INTO contrat_locataire (id_contrat, id_locataire, titulaire_principal, date_entree)
                 VALUES (:id_contrat, :id_locataire, 1, :date_entree)'
            );
            $insertLocataire->execute([
                'id_contrat' => $idContrat,
                'id_locataire' => $data['id_locataire'],
                'date_entree' => $data['date_debut'],
            ]);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return $idContrat;
    }
}
