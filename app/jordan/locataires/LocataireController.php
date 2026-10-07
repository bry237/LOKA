<?php declare(strict_types=1);

require_once __DIR__ . '/LocataireModel.php';
require_once __DIR__ . '/LocataireValidator.php';

class LocataireController {
    private LocataireModel $model;
    private LocataireValidator $validator;

    public function __construct() {
        $this->model = new LocataireModel();
        $this->validator = new LocataireValidator();
    }

    public function save(int $agencyId, array $input, ?int $tenantId = null): array {
        $errors = $this->validator->validate($input);

        if (!empty($errors)) {
            return [$errors, null];
        }

        if ($tenantId !== null) {
            $success = $this->model->update($agencyId, $tenantId, $input);
            return [$success ? [] : ['global' => 'Erreur lors de la mise à jour'], $tenantId];
        } else {
            $id = $this->model->create($agencyId, $input);
            return [$id > 0 ? [] : ['global' => 'Erreur lors de la création'], $id];
        }
    }
}
