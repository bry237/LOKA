<?php declare(strict_types=1);

require_once __DIR__ . '/PaiementModel.php';
require_once __DIR__ . '/PaiementValidator.php';

class PaiementController {
    private PaiementModel $model;
    private PaiementValidator $validator;

    public function __construct() {
        $this->model = new PaiementModel();
        $this->validator = new PaiementValidator();
    }

    public function create(int $agencyId, array $input): array {
        $errors = $this->validator->validate($input);

        if (empty($errors)) {
            try {
                $id = $this->model->create($agencyId, $input);
                return [[], $id];
            } catch (Exception $e) {
                $errors['general'] = "Une erreur est survenue lors de l'enregistrement du paiement.";
            }
        }

        return [$errors, null];
    }

    public function update(int $agencyId, int $paymentId, array $input): array {
        $errors = $this->validator->validate($input);

        if (empty($errors)) {
            try {
                $success = $this->model->update($agencyId, $paymentId, $input);
                if ($success) {
                    return [[], $paymentId];
                }
                $errors['general'] = "Paiement introuvable ou erreur de mise à jour.";
            } catch (Exception $e) {
                $errors['general'] = "Une erreur est survenue lors de la mise à jour.";
            }
        }

        return [$errors, null];
    }
}
