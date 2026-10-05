<?php
declare(strict_types=1);

/*
 * Sidebar commun à toutes les pages propriétaire.
 *
 * $pageActive peut être défini avant l'inclusion :
 *
 * $pageActive = 'dashboard';
 * $pageActive = 'biens';
 * $pageActive = 'locataires';
 * etc.
 */

$pageActive = $pageActive ?? '';
?>

<aside class="sidebar">

    <div class="logo">
        <h2>LOKA</h2>
        <p>Gestion immobilière</p>
    </div>

    <nav>

        <a
            href="/LOKA/app/proprietaires/index.php"
            class="<?= $pageActive === 'dashboard' ? 'active' : '' ?>"
        >
            <i class="fa-solid fa-chart-line"></i>
            <span>Dashboard</span>
        </a>


        <a
            href="/LOKA/app/proprietaires/biens.php"
            class="<?= $pageActive === 'biens' ? 'active' : '' ?>"
        >
            <i class="fa-solid fa-building"></i>
            <span>Mes biens</span>
        </a>


        <a
            href="/LOKA/app/proprietaires/Locataires/index.php"
            class="<?= $pageActive === 'locataires' ? 'active' : '' ?>"
        >
            <i class="fa-solid fa-users"></i>
            <span>Mes locataires</span>
        </a>


        <a
            href="/LOKA/app/proprietaires/mes_contrats.php"
            class="<?= $pageActive === 'contrats' ? 'active' : '' ?>"
        >
            <i class="fa-solid fa-file-contract"></i>
            <span>Mes contrats</span>
        </a>


        <a
            href="/LOKA/app/proprietaires/mes_revenus.php"
            class="<?= $pageActive === 'revenus' ? 'active' : '' ?>"
        >
            <i class="fa-solid fa-wallet"></i>
            <span>Mes revenus</span>
        </a>


        <a
            href="/LOKA/app/proprietaires/paiements.php"
            class="<?= $pageActive === 'paiements' ? 'active' : '' ?>"
        >
            <i class="fa-solid fa-credit-card"></i>
            <span>Paiements</span>
        </a>


        <a
            href="/LOKA/app/proprietaires/quittances.php"
            class="<?= $pageActive === 'quittances' ? 'active' : '' ?>"
        >
            <i class="fa-solid fa-receipt"></i>
            <span>Quittances</span>
        </a>


        <a
            href="/LOKA/app/proprietaires/documents.php"
            class="<?= $pageActive === 'documents' ? 'active' : '' ?>"
        >
            <i class="fa-solid fa-folder-open"></i>
            <span>Documents</span>
        </a>


        <a
            href="/LOKA/app/proprietaires/parametres.php"
            class="<?= $pageActive === 'parametres' ? 'active' : '' ?>"
        >
            <i class="fa-solid fa-gear"></i>
            <span>Paramètres</span>
        </a>

    </nav>

</aside>