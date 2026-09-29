<?php declare(strict_types=1);

require_once __DIR__ . '/ContratValidator.php';
require_once __DIR__ . '/ContratModel.php';

class ContratController {
    private ContratModel $model;
    private ContratValidator $validator;

    public function __construct() {
        $this->model = new ContratModel();
        $this->validator = new ContratValidator();
    }

    public function create(int $agencyId, array $input): array {
        $errors = $this->validator->validate($input);
        if (!empty($errors)) {
            return [$errors, null];
        }

        try {
            $id = $this->model->create($agencyId, $input);
            return [[], $id];
        } catch (\Exception $e) {
            return [['general' => 'Erreur lors de la création du contrat: ' . $e->getMessage()], null];
        }
    }

    public function update(int $agencyId, int $contractId, array $input): array {
        $errors = $this->validator->validate($input);
        if (!empty($errors)) {
            return [$errors, null];
        }

        try {
            $success = $this->model->update($agencyId, $contractId, $input);
            if (!$success) {
                return [['general' => 'Contrat introuvable ou erreur de mise à jour.'], null];
            }
            return [[], $contractId];
        } catch (\Exception $e) {
            return [['general' => 'Erreur lors de la mise à jour: ' . $e->getMessage()], null];
        }
    }

    public function terminate(int $agencyId, int $contractId, string $reason): array {
        if (empty(trim($reason))) {
            return [['reason' => 'Le motif de résiliation est obligatoire.'], null];
        }

        try {
            $success = $this->model->terminate($agencyId, $contractId, $reason);
            if (!$success) {
                return [['general' => 'Contrat introuvable ou erreur de résiliation.'], null];
            }
            return [[], $contractId];
        } catch (\Exception $e) {
            return [['general' => 'Erreur lors de la résiliation: ' . $e->getMessage()], null];
        }
    }

    public function attachTenant(int $agencyId, int $contractId, array $input): array {
        $errors = $this->validator->validateTenantAttachment($input);
        if (!empty($errors)) {
            return [$errors, null];
        }

        try {
            $this->model->attachTenant($agencyId, $contractId, $input);
            return [[], $contractId];
        } catch (\Exception $e) {
            return [['general' => 'Erreur lors de l\'association du locataire: ' . $e->getMessage()], null];
        }
    }
}
