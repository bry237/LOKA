<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once dirname(__DIR__, 2) . '/core/Database.php';

$user = Authorization::requireRole('Proprietaire');

$pdo = Database::connection();

$idUtilisateur = (int) $user['id_utilisateur'];


/*
|--------------------------------------------------------------------------
| Fonction d'échappement HTML
|--------------------------------------------------------------------------
*/

function e(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| Fonction pour construire l'URL d'une image
|--------------------------------------------------------------------------
*/

function imageUrl(?string $image): string
{
    if (!$image) {
        return '';
    }

    /*
    | Si c'est déjà une URL complète
    */

    if (
        str_starts_with($image, 'http://')
        || str_starts_with($image, 'https://')
        || str_starts_with($image, 'data:')
    ) {
        return $image;
    }

    /*
    | Sinon l'image est stockée dans le projet LOKA
    */

    return '/LOKA/' . ltrim($image, '/');
}


/*
|--------------------------------------------------------------------------
| Récupération du propriétaire connecté
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT p.id_proprietaire
    FROM proprietaire p
    INNER JOIN utilisateur u
        ON u.email = p.email
    WHERE u.id_utilisateur = ?
    LIMIT 1
");

$stmt->execute([$idUtilisateur]);

$idProprietaire = (int) $stmt->fetchColumn();

if (!$idProprietaire) {

    http_response_code(403);

    exit('Propriétaire introuvable.');
}


/*
|--------------------------------------------------------------------------
| Récupération de l'identifiant du bien
|--------------------------------------------------------------------------
*/

$idBien = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$idBien) {

    http_response_code(400);

    exit('Bien invalide.');
}


/*
|--------------------------------------------------------------------------
| Récupération du bien
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        b.id_bien,
        b.id_agence,
        b.id_type_bien,
        b.id_adresse,
        b.reference,
        b.titre,
        b.image_url,
        b.surface,
        b.nombre_pieces,
        b.etage,
        b.loyer,
        b.charges,
        b.caution,
        b.statut,
        b.description,
        b.created_at,
        b.updated_at,
        b.balcon,
        b.terrasse,
        b.jardin,
        b.piscine,
        b.ascenseur,
        b.meuble,
        b.exposition,
        b.chauffage,
        b.chauffage_sol,
        b.climatisation,
        b.acces_pmr,
        b.etat_bien,
        b.classe_energetique,
        b.type_vendeur,

        tb.nom AS type_bien,

        a.ligne1,
        a.code_postal,
        a.ville,
        a.region,
        a.pays

    FROM bien b

    INNER JOIN bien_proprietaire bp
        ON bp.id_bien = b.id_bien

    LEFT JOIN type_bien tb
        ON tb.id_type_bien = b.id_type_bien

    LEFT JOIN adresse a
        ON a.id_adresse = b.id_adresse

    WHERE b.id_bien = ?
      AND bp.id_proprietaire = ?
      AND b.deleted_at IS NULL

    LIMIT 1
");

$stmt->execute([
    $idBien,
    $idProprietaire
]);

$bien = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$bien) {

    http_response_code(404);

    exit('Bien introuvable ou accès refusé.');
}


/*
|--------------------------------------------------------------------------
| Récupération des photos
|--------------------------------------------------------------------------
|
| Les photos viennent de la table bien_image.
|
*/

$stmt = $pdo->prepare("
    SELECT
        id_image,
        id_bien,
        image_url,
        est_principale,
        ordre

    FROM bien_image

    WHERE id_bien = ?

    ORDER BY
        est_principale DESC,
        ordre ASC,
        id_image ASC
");

$stmt->execute([$idBien]);

$images = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Compatibilité avec l'ancienne image_url
|--------------------------------------------------------------------------
|
| Si aucune image n'est encore présente dans bien_image,
| on utilise l'image enregistrée dans bien.image_url.
|
*/

if (empty($images) && !empty($bien['image_url'])) {

    $images[] = [
        'id_image' => 0,
        'id_bien' => $idBien,
        'image_url' => $bien['image_url'],
        'est_principale' => 1,
        'ordre' => 1
    ];
}


/*
|--------------------------------------------------------------------------
| Statut du bien
|--------------------------------------------------------------------------
*/

$statuts = [
    'CREATED' => 'Créé',
    'AVAILABLE' => 'Disponible',
    'OCCUPIED' => 'Occupé',
    'MAINTENANCE' => 'Maintenance',
    'ARCHIVED' => 'Archivé'
];

$statutLabel =
    $statuts[$bien['statut']]
    ?? $bien['statut'];


/*
|--------------------------------------------------------------------------
| Calcul du montant total
|--------------------------------------------------------------------------
*/

$loyer = (float) $bien['loyer'];

$charges = (float) $bien['charges'];

$totalMensuel = $loyer + $charges;


/*
|--------------------------------------------------------------------------
| Adresse complète
|--------------------------------------------------------------------------
*/

$adresseComplete = trim(
    ($bien['ligne1'] ?? '')
    . ' '
    . ($bien['code_postal'] ?? '')
    . ' '
    . ($bien['ville'] ?? '')
);


/*
|--------------------------------------------------------------------------
| Message de succès
|--------------------------------------------------------------------------
*/

$success = isset($_GET['success']);

?>

<!DOCTYPE html>

<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= e($bien['titre']) ?> - LOKA
    </title>


    <link
        rel="stylesheet"
        href="proprietaire.css"
    >


    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- =====================================================
         CSS DE LA GALERIE
    ====================================================== -->

    <style>

        /* =====================================================
           GALERIE
        ===================================================== */

        .property-gallery{
            width:100%;
            margin-bottom:30px;
        }


        .gallery-container{
            position:relative;

            width:100%;
            height:430px;

            overflow:hidden;

            border-radius:22px;

            background:#E8F7F7;

            box-shadow:
                0 12px 30px rgba(0,0,0,.10);
        }


        .gallery-slide{
            position:absolute;

            inset:0;

            width:100%;
            height:100%;

            display:none;
        }


        .gallery-slide.active{
            display:block;
        }


        .gallery-slide img{
            width:100%;
            height:100%;

            object-fit:cover;

            display:block;
        }


        /* =====================================================
           BOUTONS PRECEDENT / SUIVANT
        ===================================================== */

        .gallery-arrow{
            position:absolute;

            top:50%;

            transform:translateY(-50%);

            width:56px;
            height:56px;

            border:none;

            border-radius:50%;

            background:rgba(255,255,255,.96);

            color:#172525;

            display:flex;

            align-items:center;
            justify-content:center;

            font-size:20px;

            cursor:pointer;

            box-shadow:
                0 6px 18px rgba(0,0,0,.15);

            z-index:10;

            transition:
                background .2s ease,
                color .2s ease,
                transform .2s ease;
        }


        .gallery-arrow:hover{
            background:#0F8B8D;

            color:#FFFFFF;

            transform:
                translateY(-50%)
                scale(1.05);
        }


        .gallery-prev{
            left:18px;
        }


        .gallery-next{
            right:18px;
        }


        /* =====================================================
           COEUR / FAVORI
        ===================================================== */

        .gallery-favorite{
            position:absolute;

            top:18px;
            right:18px;

            width:48px;
            height:48px;

            border:none;

            border-radius:50%;

            background:#FFFFFF;

            color:#475656;

            display:flex;

            align-items:center;
            justify-content:center;

            font-size:19px;

            cursor:pointer;

            z-index:10;

            box-shadow:
                0 6px 18px rgba(0,0,0,.12);
        }


        .gallery-favorite:hover{
            color:#0F8B8D;
        }


        /* =====================================================
           POINTS
        ===================================================== */

        .gallery-dots{
            position:absolute;

            bottom:18px;
            left:50%;

            transform:translateX(-50%);

            display:flex;

            align-items:center;

            gap:9px;

            z-index:10;

            padding:7px 12px;

            border-radius:20px;

            background:rgba(0,0,0,.12);
        }


        .gallery-dot{
            width:9px;
            height:9px;

            border:none;

            border-radius:50%;

            padding:0;

            background:rgba(255,255,255,.65);

            cursor:pointer;

            transition:
                width .2s ease,
                background .2s ease;
        }


        .gallery-dot.active{
            width:30px;

            border-radius:10px;

            background:#FFFFFF;
        }


        /* =====================================================
           COMPTEUR
        ===================================================== */

        .gallery-counter{
            position:absolute;

            top:18px;
            left:18px;

            background:rgba(0,0,0,.45);

            color:#FFFFFF;

            padding:7px 12px;

            border-radius:20px;

            font-size:13px;

            font-weight:600;

            z-index:10;
        }


        /* =====================================================
           AUCUNE IMAGE
        ===================================================== */

        .gallery-no-image{
            width:100%;
            height:430px;

            display:flex;

            flex-direction:column;

            align-items:center;

            justify-content:center;

            gap:12px;

            border-radius:22px;

            background:#E8F7F7;

            color:#0F8B8D;
        }


        .gallery-no-image i{
            font-size:60px;
        }


        .gallery-no-image span{
            color:#64748B;

            font-size:15px;
        }


        /* =====================================================
           HEADER DETAIL
        ===================================================== */

        .detail-header{
            display:flex;

            justify-content:space-between;

            align-items:flex-start;

            gap:20px;

            margin-bottom:25px;
        }


        .detail-title h1{
            margin:0 0 8px;

            font-size:34px;

            color:#172525;
        }


        .detail-title p{
            margin:0;

            color:#64748B;
        }


        .detail-status{
            flex-shrink:0;

            background:#E8F7F7;

            color:#0F8B8D;

            padding:10px 18px;

            border-radius:999px;

            font-weight:700;
        }


        /* =====================================================
           CARTES D'INFORMATIONS
        ===================================================== */

        .detail-info-grid{
            display:grid;

            grid-template-columns:
                repeat(2, minmax(0,1fr));

            gap:22px;

            margin-bottom:25px;
        }


        .detail-card{
            background:#FFFFFF;

            padding:25px;

            border-radius:20px;

            box-shadow:
                0 8px 24px rgba(0,0,0,.06);
        }


        .detail-card h3{
            margin:0 0 18px;

            color:#172525;

            font-size:19px;
        }


        .detail-info-item{
            display:flex;

            justify-content:space-between;

            gap:20px;

            padding:12px 0;

            border-bottom:1px solid #ECECEC;
        }


        .detail-info-item:last-child{
            border-bottom:none;
        }


        .detail-info-item span:first-child{
            color:#64748B;
        }


        .detail-info-item strong{
            color:#172525;

            text-align:right;
        }


        .detail-total{
            font-size:30px;

            font-weight:800;

            color:#0F8B8D;

            margin-top:15px;
        }


        .detail-total-label{
            color:#64748B;

            font-size:14px;
        }


        /* =====================================================
           DESCRIPTION
        ===================================================== */

        .detail-description{
            background:#FFFFFF;

            padding:25px;

            border-radius:20px;

            box-shadow:
                0 8px 24px rgba(0,0,0,.06);

            margin-bottom:25px;
        }


        .detail-description h3{
            margin:0 0 15px;

            color:#172525;
        }


        .detail-description p{
            color:#64748B;

            line-height:1.7;

            margin:0;
        }


        /* =====================================================
           ACTIONS
        ===================================================== */

        .detail-actions{
            display:flex;

            justify-content:flex-end;

            gap:12px;

            margin-top:30px;

            margin-bottom:30px;
        }


        .detail-action{
            display:inline-flex;

            align-items:center;

            justify-content:center;

            gap:8px;

            padding:13px 20px;

            border-radius:12px;

            text-decoration:none;

            font-weight:600;

            transition:.2s ease;
        }


        .detail-action-primary{
            background:#0F8B8D;

            color:#FFFFFF;
        }


        .detail-action-primary:hover{
            background:#075E63;
        }


        .detail-action-danger{
            background:#FFFFFF;

            color:#DC2626;

            border:2px solid #DC2626;
        }


        .detail-action-danger:hover{
            background:#DC2626;

            color:#FFFFFF;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media(max-width:768px){

            .gallery-container{
                height:300px;

                border-radius:18px;
            }


            .gallery-no-image{
                height:300px;
            }


            .gallery-arrow{
                width:45px;
                height:45px;

                font-size:16px;
            }


            .gallery-prev{
                left:10px;
            }


            .gallery-next{
                right:10px;
            }


            .gallery-favorite{
                top:10px;
                right:10px;

                width:42px;
                height:42px;
            }


            .gallery-counter{
                top:10px;
                left:10px;
            }


            .detail-header{
                flex-direction:column;
            }


            .detail-title h1{
                font-size:28px;
            }


            .detail-status{
                align-self:flex-start;
            }


            .detail-info-grid{
                grid-template-columns:1fr;
            }


            .detail-actions{
                flex-direction:column;
            }


            .detail-action{
                width:100%;
            }

        }

        /* =====================================================
   FENÊTRE DE CONFIRMATION DE SUPPRESSION
===================================================== */

.delete-modal {
    position: fixed;

    inset: 0;

    display: none;

    align-items: center;

    justify-content: center;

    padding: 20px;

    z-index: 9999;
}


/*
|--------------------------------------------------------------------------
| Fenêtre visible
|--------------------------------------------------------------------------
*/

.delete-modal.active {
    display: flex;
}


/*
|--------------------------------------------------------------------------
| Fond sombre
|--------------------------------------------------------------------------
*/

.delete-modal-overlay {
    position: absolute;

    inset: 0;

    background: rgba(15, 23, 42, 0.55);

    backdrop-filter: blur(3px);
}


/*
|--------------------------------------------------------------------------
| Boîte
|--------------------------------------------------------------------------
*/

.delete-modal-box {
    position: relative;

    z-index: 2;

    width: 100%;

    max-width: 450px;

    padding: 32px;

    background: #FFFFFF;

    border-radius: 22px;

    text-align: center;

    box-shadow:
        0 25px 70px rgba(0, 0, 0, 0.20);

    animation: deleteModalAppear 0.2s ease;
}


/*
|--------------------------------------------------------------------------
| Animation
|--------------------------------------------------------------------------
*/

@keyframes deleteModalAppear {

    from {

        opacity: 0;

        transform:
            translateY(15px)
            scale(0.96);

    }

    to {

        opacity: 1;

        transform:
            translateY(0)
            scale(1);

    }

}


/*
|--------------------------------------------------------------------------
| Icône
|--------------------------------------------------------------------------
*/

.delete-modal-icon {

    width: 68px;

    height: 68px;

    margin: 0 auto 20px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 50%;

    background: #FEE2E2;

    color: #DC2626;

    font-size: 26px;
}


/*
|--------------------------------------------------------------------------
| Titre
|--------------------------------------------------------------------------
*/

.delete-modal-box h2 {

    margin: 0 0 15px;

    color: #172525;

    font-size: 24px;

    font-weight: 700;
}


/*
|--------------------------------------------------------------------------
| Message
|--------------------------------------------------------------------------
*/

.delete-modal-message {

    margin: 0;

    color: #64748B;

    font-size: 15px;

    line-height: 1.6;
}


.delete-modal-message strong {

    color: #172525;

    font-weight: 700;
}


/*
|--------------------------------------------------------------------------
| Avertissement
|--------------------------------------------------------------------------
*/

.delete-modal-warning {

    margin: 12px 0 25px;

    color: #DC2626;

    font-size: 13px;

    font-weight: 600;
}


/*
|--------------------------------------------------------------------------
| Boutons
|--------------------------------------------------------------------------
*/

.delete-modal-actions {

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 12px;
}


/*
|--------------------------------------------------------------------------
| Bouton Annuler
|--------------------------------------------------------------------------
*/

.delete-modal-cancel {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    padding: 12px 20px;

    border: 1px solid #D1D5DB;

    border-radius: 10px;

    background: #FFFFFF;

    color: #475569;

    font-family: inherit;

    font-size: 14px;

    font-weight: 600;

    cursor: pointer;

    transition:
        background 0.2s ease,
        border-color 0.2s ease;
}


.delete-modal-cancel:hover {

    background: #F8FAFC;

    border-color: #CBD5E1;
}


/*
|--------------------------------------------------------------------------
| Bouton confirmer
|--------------------------------------------------------------------------
*/

.delete-modal-confirm {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    padding: 12px 20px;

    border-radius: 10px;

    background: #DC2626;

    color: #FFFFFF;

    text-decoration: none;

    font-size: 14px;

    font-weight: 600;

    transition:
        background 0.2s ease,
        transform 0.2s ease;
}


.delete-modal-confirm:hover {

    background: #B91C1C;

    transform: translateY(-1px);
}


/*
|--------------------------------------------------------------------------
| Responsive
|--------------------------------------------------------------------------
*/

@media (max-width: 600px) {

    .delete-modal-box {

        padding: 25px;

        border-radius: 18px;

    }


    .delete-modal-actions {

        flex-direction: column;

        width: 100%;

    }


    .delete-modal-cancel,
    .delete-modal-confirm {

        width: 100%;

        box-sizing: border-box;

    }

}

    </style>

</head>


<body>


<div class="content">


    <!-- =====================================================
         RETOUR
    ====================================================== -->

    <a
        href="biens.php"
        class="back-link"
    >

        <i class="fa-solid fa-arrow-left"></i>

        Retour à mes biens

    </a>


    <!-- =====================================================
         GALERIE PHOTOS
    ====================================================== -->

    <section class="property-gallery">


        <?php if (!empty($images)): ?>


            <div class="gallery-container">


                <?php foreach ($images as $index => $image): ?>

                    <div
                        class="gallery-slide <?= $index === 0 ? 'active' : '' ?>"
                        data-slide="<?= $index ?>"
                    >

                        <img
                            src="<?= e(imageUrl($image['image_url'])) ?>"
                            alt="<?= e($bien['titre']) ?> - Photo <?= $index + 1 ?>"
                        >

                    </div>

                <?php endforeach; ?>


                <!-- =========================================
                     COMPTEUR
                ========================================== -->

                <?php if (count($images) > 1): ?>

                    <div class="gallery-counter">

                        <span id="currentImage">
                            1
                        </span>

                        /

                        <?= count($images) ?>

                    </div>

                <?php endif; ?>


                <!-- =========================================
                     FAVORI
                ========================================== -->

                <button
                    type="button"
                    class="gallery-favorite"
                    title="Ajouter aux favoris"
                >

                    <i class="fa-regular fa-heart"></i>

                </button>


                <!-- =========================================
                     FLECHE GAUCHE
                ========================================== -->

                <?php if (count($images) > 1): ?>

                    <button
                        type="button"
                        class="gallery-arrow gallery-prev"
                        id="galleryPrev"
                        aria-label="Photo précédente"
                    >

                        <i class="fa-solid fa-chevron-left"></i>

                    </button>


                    <!-- =====================================
                         FLECHE DROITE
                    ====================================== -->

                    <button
                        type="button"
                        class="gallery-arrow gallery-next"
                        id="galleryNext"
                        aria-label="Photo suivante"
                    >

                        <i class="fa-solid fa-chevron-right"></i>

                    </button>


                    <!-- =====================================
                         POINTS
                    ====================================== -->

                    <div
                        class="gallery-dots"
                        id="galleryDots"
                    >

                        <?php foreach ($images as $index => $image): ?>

                            <button
                                type="button"
                                class="gallery-dot <?= $index === 0 ? 'active' : '' ?>"
                                data-slide="<?= $index ?>"
                                aria-label="Afficher la photo <?= $index + 1 ?>"
                            ></button>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>


            </div>


        <?php else: ?>


            <!-- =============================================
                 AUCUNE PHOTO
            ============================================== -->

            <div class="gallery-no-image">

                <i class="fa-regular fa-image"></i>

                <span>
                    Aucune photo disponible
                </span>

            </div>


        <?php endif; ?>


    </section>


    <!-- =====================================================
         TITRE DU BIEN
    ====================================================== -->

    <div class="detail-header">


        <div class="detail-title">

            <h1>
                <?= e($bien['titre']) ?>
            </h1>

            <p>
                Référence :
                <strong>
                    <?= e($bien['reference']) ?>
                </strong>
            </p>

        </div>


        <div class="detail-status">

            <?= e($statutLabel) ?>

        </div>


    </div>


    <!-- =====================================================
         INFORMATIONS
    ====================================================== -->

    <div class="detail-info-grid">


        <!-- =================================================
             CARTE INFORMATIONS DU BIEN
        ================================================== -->

        <div class="detail-card">


            <h3>

                <i
                    class="fa-solid fa-house"
                    style="color:#0F8B8D;margin-right:8px;"
                ></i>

                Informations du bien

            </h3>


            <div class="detail-info-item">

                <span>
                    Type
                </span>

                <strong>
                    <?= e($bien['type_bien']) ?>
                </strong>

            </div>


            <div class="detail-info-item">

                <span>
                    Surface
                </span>

                <strong>
                    <?= number_format(
                        (float)$bien['surface'],
                        2,
                        ',',
                        ' '
                    ) ?>
                    m²
                </strong>

            </div>


            <div class="detail-info-item">

                <span>
                    Nombre de pièces
                </span>

                <strong>
                    <?= (int)$bien['nombre_pieces'] ?>
                </strong>

            </div>


            <div class="detail-info-item">

                <span>
                    Étage
                </span>

                <strong>

                    <?= $bien['etage'] !== null
                        ? e((string)$bien['etage'])
                        : '—'
                    ?>

                </strong>

            </div>


            <div class="detail-info-item">

                <span>
                    Meublé
                </span>

                <strong>
                    <?= e($bien['meuble']) ?>
                </strong>

            </div>


            <div class="detail-info-item">

                <span>
                    Exposition
                </span>

                <strong>

                    <?= $bien['exposition']
                        ? e($bien['exposition'])
                        : '—'
                    ?>

                </strong>

            </div>


        </div>


        <!-- =================================================
             CARTE ADRESSE
        ================================================== -->

        <div class="detail-card">


            <h3>

                <i
                    class="fa-solid fa-location-dot"
                    style="color:#0F8B8D;margin-right:8px;"
                ></i>

                Localisation

            </h3>


            <div class="detail-info-item">

                <span>
                    Adresse
                </span>

                <strong>
                    <?= e($bien['ligne1']) ?>
                </strong>

            </div>


            <div class="detail-info-item">

                <span>
                    Ville
                </span>

                <strong>
                    <?= e($bien['ville']) ?>
                </strong>

            </div>


            <div class="detail-info-item">

                <span>
                    Code postal
                </span>

                <strong>
                    <?= e($bien['code_postal']) ?>
                </strong>

            </div>


            <div class="detail-info-item">

                <span>
                    Région
                </span>

                <strong>

                    <?= $bien['region']
                        ? e($bien['region'])
                        : '—'
                    ?>

                </strong>

            </div>


            <div class="detail-info-item">

                <span>
                    Pays
                </span>

                <strong>
                    <?= e($bien['pays']) ?>
                </strong>

            </div>


        </div>


        <!-- =================================================
             CARTE FINANCIERE
        ================================================== -->

        <div class="detail-card">


            <h3>

                <i
                    class="fa-solid fa-coins"
                    style="color:#0F8B8D;margin-right:8px;"
                ></i>

                Informations financières

            </h3>


            <div class="detail-info-item">

                <span>
                    Loyer
                </span>

                <strong>
                    <?= number_format(
                        $loyer,
                        0,
                        ',',
                        ' '
                    ) ?>
                    £
                </strong>

            </div>


            <div class="detail-info-item">

                <span>
                    Charges
                </span>

                <strong>
                    <?= number_format(
                        $charges,
                        0,
                        ',',
                        ' '
                    ) ?>
                    £
                </strong>

            </div>


            <div class="detail-info-item">

                <span>
                    Caution
                </span>

                <strong>
                    <?= number_format(
                        (float)$bien['caution'],
                        0,
                        ',',
                        ' '
                    ) ?>
                    £
                </strong>

            </div>


            <div class="detail-total-label">

                Total mensuel

            </div>


            <div class="detail-total">

                <?= number_format(
                    $totalMensuel,
                    0,
                    ',',
                    ' '
                ) ?>

                £

            </div>


        </div>


        <!-- =================================================
             CARTE EQUIPEMENTS
        ================================================== -->

        <div class="detail-card">


            <h3>

                <i
                    class="fa-solid fa-list-check"
                    style="color:#0F8B8D;margin-right:8px;"
                ></i>

                Équipements

            </h3>


            <div class="detail-info-item">

                <span>
                    Balcon
                </span>

                <strong>
                    <?= (int)$bien['balcon'] === 1
                        ? 'Oui'
                        : 'Non'
                    ?>
                </strong>

            </div>


            <div class="detail-info-item">

                <span>
                    Terrasse
                </span>

                <strong>
                    <?= (int)$bien['terrasse'] === 1
                        ? 'Oui'
                        : 'Non'
                    ?>
                </strong>

            </div>


            <div class="detail-info-item">

                <span>
                    Jardin
                </span>

                <strong>
                    <?= (int)$bien['jardin'] === 1
                        ? 'Oui'
                        : 'Non'
                    ?>
                </strong>

            </div>


            <div class="detail-info-item">

                <span>
                    Piscine
                </span>

                <strong>
                    <?= (int)$bien['piscine'] === 1
                        ? 'Oui'
                        : 'Non'
                    ?>
                </strong>

            </div>


            <div class="detail-info-item">

                <span>
                    Ascenseur
                </span>

                <strong>
                    <?= (int)$bien['ascenseur'] === 1
                        ? 'Oui'
                        : 'Non'
                    ?>
                </strong>

            </div>


            <div class="detail-info-item">

                <span>
                    Climatisation
                </span>

                <strong>
                    <?= e($bien['climatisation']) ?>
                </strong>

            </div>


        </div>


    </div>


    <!-- =====================================================
         DESCRIPTION
    ====================================================== -->

    <?php if (!empty($bien['description'])): ?>

        <div class="detail-description">


            <h3>

                <i
                    class="fa-solid fa-align-left"
                    style="color:#0F8B8D;margin-right:8px;"
                ></i>

                Description

            </h3>


            <p>
                <?= nl2br(e($bien['description'])) ?>
            </p>


        </div>

    <?php endif; ?>


    <!-- =====================================================
         ACTIONS
    ====================================================== -->

    <div class="detail-actions">


        <a
            href="modifier.php?id=<?= $idBien ?>"
            class="detail-action detail-action-primary"
        >

            <i class="fa-solid fa-pen"></i>

            Modifier

        </a>


      <button
    type="button"
    class="detail-action detail-action-danger"
    id="deletePropertyButton"
>
    <i class="fa-solid fa-trash"></i>
    Supprimer
</button>


    </div>


</div>

<!-- =====================================================
     FENÊTRE DE CONFIRMATION DE SUPPRESSION
====================================================== -->

<div
    class="delete-modal"
    id="deleteModal"
    aria-hidden="true"
>

    <div class="delete-modal-overlay"></div>

    <div
        class="delete-modal-box"
        role="dialog"
        aria-modal="true"
        aria-labelledby="deleteModalTitle"
    >

        <!-- Icône -->

        <div class="delete-modal-icon">

            <i class="fa-solid fa-trash"></i>

        </div>


        <!-- Titre -->

        <h2 id="deleteModalTitle">
            Supprimer ce bien ?
        </h2>


        <!-- Message -->

        <p class="delete-modal-message">

            Êtes-vous sûr de vouloir supprimer

            <strong>
                <?= e($bien['titre']) ?>
            </strong>

            ?

        </p>


        <!-- Avertissement -->

        <p class="delete-modal-warning">

            Cette action retirera ce bien de votre liste.

        </p>


        <!-- Actions -->

        <div class="delete-modal-actions">

            <button
                type="button"
                class="delete-modal-cancel"
                id="cancelDelete"
            >

                Annuler

            </button>


            <a
                href="supprimer.php?id=<?= $idBien ?>"
                class="delete-modal-confirm"
            >

                <i class="fa-solid fa-trash"></i>

                Supprimer le bien

            </a>

        </div>

    </div>

</div>


<!-- =========================================================
     JAVASCRIPT GALERIE
========================================================== -->

<script>

document.addEventListener('DOMContentLoaded', function () {

    const slides = document.querySelectorAll(
        '.gallery-slide'
    );

    const dots = document.querySelectorAll(
        '.gallery-dot'
    );

    const prevButton = document.getElementById(
        'galleryPrev'
    );

    const nextButton = document.getElementById(
        'galleryNext'
    );

    const currentImage = document.getElementById(
        'currentImage'
    );


    /*
    |--------------------------------------------------------------------------
    | Pas de galerie
    |--------------------------------------------------------------------------
    */

    if (slides.length === 0) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Position actuelle
    |--------------------------------------------------------------------------
    */

    let currentIndex = 0;


    /*
    |--------------------------------------------------------------------------
    | Afficher une image
    |--------------------------------------------------------------------------
    */

    function showSlide(index) {

        /*
        | Boucle vers la dernière image
        */

        if (index < 0) {

            index = slides.length - 1;
        }


        /*
        | Boucle vers la première image
        */

        if (index >= slides.length) {

            index = 0;
        }


        currentIndex = index;


        /*
        | Toutes les images sont masquées
        */

        slides.forEach(function (slide) {

            slide.classList.remove('active');

        });


        /*
        | Image actuelle
        */

        slides[currentIndex].classList.add(
            'active'
        );


        /*
        | Mise à jour des points
        */

        dots.forEach(function (dot, dotIndex) {

            dot.classList.toggle(
                'active',
                dotIndex === currentIndex
            );

        });


        /*
        | Mise à jour du compteur
        */

        if (currentImage) {

            currentImage.textContent =
                currentIndex + 1;
        }

    }


    /*
    |--------------------------------------------------------------------------
    | Flèche précédente
    |--------------------------------------------------------------------------
    */

    if (prevButton) {

        prevButton.addEventListener(
            'click',
            function () {

                showSlide(
                    currentIndex - 1
                );

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Flèche suivante
    |--------------------------------------------------------------------------
    */

    if (nextButton) {

        nextButton.addEventListener(
            'click',
            function () {

                showSlide(
                    currentIndex + 1
                );

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Clic sur les points
    |--------------------------------------------------------------------------
    */

    dots.forEach(function (dot) {

        dot.addEventListener(
            'click',
            function () {

                const index =
                    parseInt(
                        this.dataset.slide,
                        10
                    );

                showSlide(index);

            }
        );

    });


    /*
    |--------------------------------------------------------------------------
    | Navigation clavier
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'keydown',
        function (event) {

            if (event.key === 'ArrowLeft') {

                showSlide(
                    currentIndex - 1
                );

            }

            if (event.key === 'ArrowRight') {

                showSlide(
                    currentIndex + 1
                );

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Navigation tactile / swipe téléphone
    |--------------------------------------------------------------------------
    */

    let touchStartX = 0;

    let touchEndX = 0;


    const gallery =
        document.querySelector(
            '.gallery-container'
        );


    if (gallery) {

        gallery.addEventListener(
            'touchstart',
            function (event) {

                touchStartX =
                    event.changedTouches[0].screenX;

            },
            {
                passive:true
            }
        );


        gallery.addEventListener(
            'touchend',
            function (event) {

                touchEndX =
                    event.changedTouches[0].screenX;


                const difference =
                    touchStartX - touchEndX;


                /*
                | Swipe vers la gauche
                */

                if (difference > 50) {

                    showSlide(
                        currentIndex + 1
                    );

                }


                /*
                | Swipe vers la droite
                */

                if (difference < -50) {

                    showSlide(
                        currentIndex - 1
                    );

                }

            },
            {
                passive:true
            }
        );

    }


});

/*
|--------------------------------------------------------------------------
| FENÊTRE DE CONFIRMATION DE SUPPRESSION
|--------------------------------------------------------------------------
*/

const deletePropertyButton =
    document.getElementById('deletePropertyButton');

const deleteModal =
    document.getElementById('deleteModal');

const cancelDelete =
    document.getElementById('cancelDelete');

const deleteModalOverlay =
    document.querySelector('.delete-modal-overlay');


/*
|--------------------------------------------------------------------------
| Ouvrir la fenêtre
|--------------------------------------------------------------------------
*/

if (deletePropertyButton && deleteModal) {

    deletePropertyButton.addEventListener(
        'click',
        function () {

            deleteModal.classList.add('active');

            deleteModal.setAttribute(
                'aria-hidden',
                'false'
            );

        }
    );

}


/*
|--------------------------------------------------------------------------
| Fermer la fenêtre
|--------------------------------------------------------------------------
*/

function closeDeleteModal() {

    if (!deleteModal) {

        return;

    }


    deleteModal.classList.remove('active');

    deleteModal.setAttribute(
        'aria-hidden',
        'true'
    );

}


/*
|--------------------------------------------------------------------------
| Bouton Annuler
|--------------------------------------------------------------------------
*/

if (cancelDelete) {

    cancelDelete.addEventListener(
        'click',
        function () {

            closeDeleteModal();

        }
    );

}


/*
|--------------------------------------------------------------------------
| Cliquer sur le fond
|--------------------------------------------------------------------------
*/

if (deleteModalOverlay) {

    deleteModalOverlay.addEventListener(
        'click',
        function () {

            closeDeleteModal();

        }
    );

}


/*
|--------------------------------------------------------------------------
| Touche Échap
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'keydown',
    function (event) {

        if (event.key === 'Escape') {

            closeDeleteModal();

        }

    }
);

</script>


</body>

</html>