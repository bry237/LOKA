<?php

declare(strict_types=1);

require_once __DIR__ . '/PaiementController.php';

$controller = new PaiementController();

$idEcheance = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$idEcheance) {
    http_response_code(400);
    exit('Échéance invalide.');
}

$echeance = $controller->getMyEcheance($idEcheance);

if ($echeance === null) {
    http_response_code(404);
    exit('Échéance introuvable.');
}

$montantRestant = $controller->getMontantRestant($idEcheance);

if ($montantRestant <= 0) {
    header(
        'Location: detail.php?id=' . $idEcheance
    );
    exit;
}

$dateEcheance = !empty($echeance['date_echeance'])
    ? date(
        'd/m/Y',
        strtotime($echeance['date_echeance'])
    )
    : '-';

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Payer | LOKA</title>

    <link rel="stylesheet"
          href="paiement.css">

    <style>

        .payer-page {
            padding: 35px 34px;
        }

        .payer-header {
            margin-bottom: 25px;
        }

        .payer-header h1 {
            margin-bottom: 7px;
            color: #043f42;
            font-size: 27px;
        }

        .payer-header p {
            color: #687878;
            font-size: 13px;
        }

        .payer-layout {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 25px;
            align-items: start;
        }

        .amount-card,
        .method-card {
            background: white;
            border: 1px solid #dde8e8;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 3px 12px rgba(4, 63, 66, 0.04);
        }

        .amount-card h2,
        .method-card h2 {
            margin-bottom: 18px;
            color: #043f42;
            font-size: 18px;
        }

        .amount-main {
            padding: 20px;
            background: #effbfa;
            border-radius: 12px;
            text-align: center;
        }

        .amount-main span {
            display: block;
            margin-bottom: 8px;
            color: #687878;
            font-size: 12px;
        }

        .amount-main strong {
            color: #043f42;
            font-size: 27px;
        }

        .due-date {
            margin-top: 18px;
            color: #687878;
            font-size: 13px;
        }

        .payment-methods {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .payment-method {
            position: relative;
        }

        .payment-method input {
            position: absolute;
            opacity: 0;
        }

        .payment-method label {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 17px;
            border: 1px solid #dde8e8;
            border-radius: 11px;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .payment-method label:hover {
            border-color: #0f8b8d;
            background: #f7fdfd;
        }

        .payment-method input:checked + label {
            border-color: #0f8b8d;
            background: #effbfa;
        }

        .method-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: #effbfa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
        }

        .method-text strong {
            display: block;
            margin-bottom: 4px;
            color: #043f42;
            font-size: 14px;
        }

        .method-text span {
            color: #687878;
            font-size: 12px;
        }

                .payment-method input:disabled + label {
            opacity: 0.55;
            cursor: not-allowed;
        }

        .payment-method input:disabled + label:hover {
            border-color: #dde8e8;
            background: transparent;
        }

        .payer-error {
            margin-bottom: 16px;
            padding: 12px 14px;
            border: 1px solid #f3c2c2;
            border-radius: 10px;
            background: #fdf0f0;
            color: #a12a2a;
            font-size: 13px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #0f8b8d;
            font-size: 13px;
            font-weight: 600;
        }

        .back-link:hover {
            color: #075e63;
        }

        .payer-actions {
            margin-top: 25px;
            display: flex;
            gap: 10px;
        }

        @media (max-width: 800px) {

            .payer-layout {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 650px) {

            .payer-page {
                padding: 25px 20px;
            }

        }

    </style>

</head>

<body>

<aside class="sidebar">

    <div class="logo">
        LOKA
    </div>


    <nav class="sidebar-menu">

        <a
            href="../index.php"
            class="menu-item"
        >
            <span class="menu-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg></span>
            <span>Accueil</span>
        </a>


        <a
            href="../detail.php"
            class="menu-item"
        >
            <span class="menu-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/></svg></span>
            <span>Mon profil</span>
        </a>


        <a
            href="../logement.php"
            class="menu-item"
        >
            <span class="menu-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></svg></span>
            <span>Mon logement</span>
        </a>


        <a
            href="../recherche.php"
            class="menu-item"
        >
            <span class="menu-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></span>
            <span>Recherche</span>
        </a>


        <a href="/LOKA/app/locataires/contrats/index.php">

                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg>
                </span>

                <span>
                    Mon contrat
                </span>
            </a>


        <!-- PAGE ACTIVE -->

        <a
            href="paiement.php"
            class="menu-item active"
        >
            <span class="menu-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
            <span>Mes paiements</span>
        </a>


        <a
            href="/LOKA/app/maintenance/index.php"
            class="menu-item"
        >
            <span class="menu-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94Z"/></svg></span>
            <span>Maintenance</span>
        </a>


        <a
            href="/LOKA/app/documents/index.php"
            class="menu-item"
        >
            <span class="menu-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/></svg></span>
            <span>Documents</span>
        </a>

        <a href="/LOKA/app/locataires/quittances/index.php"
              >
                <span class="menu-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/><path d="M9 7h7M9 11h5M9 15h3"/></svg></span>
                <span>Quittances</span>
            </a>


        <a
            href="../notifications/notification.php"
            class="menu-item"
        >
            <span class="menu-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></span>
            <span>Notifications</span>
        </a>

    </nav>

    <div class="sidebar-bottom">

            <a href="/LOKA/app/authentification/deconnexion.php">
                <span class="menu-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg></span>
                <span>Déconnexion</span>
            </a>

        </div>

</aside>

<div class="main-content">

    <header class="top-header">

        <div class="welcome">

            <h1>
                Bonjour Pascale
                <span class="wave"></span>
           </h1>

            <p>
                Bienvenue dans votre espace locataire
            </p>

        </div>

        <div class="user-information">

            <div class="avatar">
                PN
            </div>

            <div class="user-name">

                <strong>
                    Pascale NGASSA
                </strong>

                <span>
                    Locataire
                </span>

            </div>

        </div>

    </header>


    <main class="page-container">

        <section class="payer-page">

            <a href="detail.php?id=<?= $idEcheance ?>"
               class="back-link">

                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg> Retour au détail

            </a>


            <div class="payer-header">

                <span class="section-label">
                    RÈGLEMENT
                </span>

                <h1>
                    Payer mon échéance
               </h1>

                <p>
                    Choisissez votre moyen de paiement.
                </p>

            </div>


            <div class="payer-layout">


                <!-- MONTANT -->

                <div class="amount-card">

                    <h2>
                        Montant à payer
                    </h2>

                    <div class="amount-main">

                        <span>
                            Montant restant
                        </span>

                        <strong>

                            <?= number_format(
                                $montantRestant,
                                2,
                                ',',
                                ' '
                            ) ?> €

                        </strong>

                    </div>

                    <div class="due-date">

                        Date d'échéance :
                        <strong>
                            <?= htmlspecialchars($dateEcheance) ?>
                        </strong>

                    </div>

                </div>


                <!-- MOYENS DE PAIEMENT -->

                <div class="method-card">

                    <h2>
                        Moyen de paiement
                    </h2>

                    <?php if (!empty($_GET['error'])): ?>
                        <div class="payer-error" role="alert">
                            <?= htmlspecialchars((string) $_GET['error']) ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="checkout.php">

                    <input type="hidden" name="csrf_token"
                           value="<?= htmlspecialchars(Auth::csrfToken()) ?>">
                    <input type="hidden" name="id_echeance"
                           value="<?= (int) $idEcheance ?>">

                    <div class="payment-methods"></div>


                    <div class="payment-methods">


                        <div class="payment-method">

                            <input
                                type="radio"
                                id="card"
                                name="mode_paiement"
                                value="CARD"
                                checked
                            >

                            <label for="card">

                                <div class="method-icon">
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                </div>

                                <div class="method-text">

                                    <strong>
                                        Carte bancaire
                                    </strong>

                                    <span>
                                        Paiement sécurisé par carte.
                                    </span>

                                </div>

                            </label>

                        </div>


                        <div class="payment-method">

                            <input
                                type="radio"
                                id="paypal"
                                name="mode_paiement"
                                value="PAYPAL"
                            >

                            <label for="paypal">

                                <div class="method-icon">
                                    🅿️
                                </div>

                                <div class="method-text">

                                    <strong>
                                        PayPal
                                    </strong>

                                    <span>
                                        Paiement via votre compte PayPal.
                                    </span>

                                </div>

                            </label>

                        </div>


                    </div>


                    <div class="payer-actions">

                        <a
                            href="detail.php?id=<?= $idEcheance ?>"
                            class="button button-secondary"
                        >
                            Annuler
                        </a>


                        <button
                            type="submit"
                            class="button button-primary"
                        >
                            Payer
                        </button>

                    </div>

                </form>

                </div>

            </div>

        </section>

    </main>

</div>


</body>

</html>