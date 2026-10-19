<?php

declare(strict_types=1);

require_once __DIR__ . '/ContratController.php';

$controller = new ContratController();
$id = filter_input(INPUT_POST, 'id_contrat', FILTER_VALIDATE_INT) ?: 0;
$action = (string) ($_POST['action'] ?? '');

try {
    if ($action === 'signer') {
        $controller->signerSimule(
            $id,
            (string) ($_POST['nom_signataire'] ?? ''),
            isset($_POST['consentement']) && $_POST['consentement'] === '1',
            isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null
        );
        header('Location: detail.php?id=' . $id . '&success=signature');
        exit;
    }
    if ($action === 'deposer') {
        $controller->deposerPdfSigne(
            $id,
            $_FILES['contrat_signe'] ?? [],
            isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null
        );
        header('Location: detail.php?id=' . $id . '&success=depot');
        exit;
    }
    throw new RuntimeException('Action inconnue.');
} catch (Throwable $e) {
    $message = $e->getMessage();
    header('Location: detail.php?id=' . $id . '&error=' . rawurlencode($message));
    exit;
}
