<?php

declare(strict_types=1);

require_once __DIR__ . '/DocumentController.php';


/* =========================================================
   ID DU DOCUMENT
   ========================================================= */

$idDocument = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$idDocument) {
    http_response_code(400);
    exit('Document invalide.');
}


/* =========================================================
   RÉCUPÉRATION DU DOCUMENT
   La méthode vérifie également le locataire propriétaire
   du document.
   ========================================================= */

$controller = new DocumentController();

$document = $controller->getDocument(
    (int) $idDocument
);

if (!$document) {
    http_response_code(404);
    exit('Document introuvable ou accès refusé.');
}


/* =========================================================
   CHEMIN DU FICHIER
   ========================================================= */

$chemin = trim(
    (string) (
        $document['chemin_stockage']
        ?? ''
    )
);

if ($chemin === '') {
    http_response_code(404);
    exit('Aucun fichier associé à ce document.');
}


/*
 * Si le chemin enregistré dans la base est déjà un chemin
 * absolu, on le conserve.
 *
 * Sinon, on le considère comme relatif à la racine
 * du projet LOKA.
 */

if (
    !preg_match(
        '#^(?:[A-Za-z]:[\\\\/]|/)#',
        $chemin
    )
) {
    $chemin = dirname(__DIR__, 2)
        . DIRECTORY_SEPARATOR
        . ltrim(
            str_replace(
                ['/', '\\'],
                DIRECTORY_SEPARATOR,
                $chemin
            ),
            DIRECTORY_SEPARATOR
        );
}


$chemin = realpath($chemin);

if ($chemin === false || !is_file($chemin)) {
    http_response_code(404);
    exit('Fichier introuvable sur le serveur.');
}


/* =========================================================
   NOM DU FICHIER
   ========================================================= */

$nomFichier = trim(
    (string) (
        $document['nom_original']
        ?? $document['nom']
        ?? 'document'
    )
);

if ($nomFichier === '') {
    $nomFichier = 'document';
}


/*
 * basename évite qu'un chemin soit injecté dans
 * Content-Disposition.
 */

$nomFichier = basename($nomFichier);


/* =========================================================
   TYPE MIME
   ========================================================= */

$mimeType = trim(
    (string) (
        $document['mime_type']
        ?? ''
    )
);

if ($mimeType === '') {
    $mimeType = 'application/octet-stream';
}


/* =========================================================
   EN-TÊTES
   ========================================================= */

header(
    'Content-Type: ' . $mimeType
);

header(
    'Content-Disposition: attachment; filename="' .
    addslashes($nomFichier) .
    '"'
);

header(
    'Content-Length: ' .
    (string) filesize($chemin)
);

header(
    'X-Content-Type-Options: nosniff'
);

header(
    'Cache-Control: private, no-store'
);


/* =========================================================
   ENVOI DU FICHIER
   ========================================================= */

readfile($chemin);

exit;