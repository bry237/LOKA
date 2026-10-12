<?php declare(strict_types=1);

require_once __DIR__ . '/MaintenanceModel.php';
require_once __DIR__ . '/MaintenanceValidator.php';

class MaintenanceController
{
    private MaintenanceModel $model;
    private MaintenanceValidator $validator;

    public function __construct()
    {
        $this->model = new MaintenanceModel();
        $this->validator = new MaintenanceValidator();
    }

    public function list(int $agencyId, array $filters): array
    {
        return $this->model->list($agencyId, $filters);
    }

    public function find(int $agencyId, int $interventionId): ?array
    {
        return $this->model->find($agencyId, $interventionId);
    }

    public function create(int $agencyId, array $data, int $userId): array
    {
        $errors = $this->validator->validate($data);
        if (empty($errors)) {
            $data['id_utilisateur_createur'] = $userId;
            $id = $this->model->create($agencyId, $data);
            return [[], $id];
        }
        return [$errors, null];
    }

    public function update(int $agencyId, int $interventionId, array $data): array
    {
        $errors = $this->validator->validate($data);
        if (empty($errors)) {
            $this->model->update($agencyId, $interventionId, $data);
            return [[], $interventionId];
        }
        return [$errors, null];
    }

    public function updateStatus(int $agencyId, int $interventionId, string $status): bool
    {
        if (in_array($status, ['OPEN', 'ASSIGNED', 'IN_PROGRESS', 'WAITING', 'RESOLVED', 'CLOSED', 'CANCELLED'])) {
            return $this->model->updateStatus($agencyId, $interventionId, $status);
        }
        return false;
    }

    public function assign(int $agencyId, int $interventionId, int $userId): bool
    {
        return $this->model->assign($agencyId, $interventionId, $userId);
    }

    public function close(int $agencyId, int $interventionId, array $data): array
    {
        $errors = $this->validator->validateClose($data);
        if (empty($errors)) {
            $this->model->close($agencyId, $interventionId, $data);
            return [[], $interventionId];
        }
        return [$errors, null];
    }

    public function comments(int $agencyId, int $interventionId): array
    {
        return $this->model->comments($agencyId, $interventionId);
    }

    public function addComment(int $agencyId, int $interventionId, int $userId, string $content): bool
    {
        if (trim($content) !== '') {
            $this->model->addComment($agencyId, $interventionId, $userId, $content);
            return true;
        }
        return false;
    }

    public function stats(int $agencyId): array
    {
        return $this->model->stats($agencyId);
    }
}
