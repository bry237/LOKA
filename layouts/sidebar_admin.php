<?php
declare(strict_types=1);

$currentPage = $currentPage ?? '';
?>
<aside class="sidebar admin-sidebar">
    <div class="sidebar-header">
        <h2>LOKA Admin</h2>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-group">ADMINISTRATION</div>
        <a href="/LOKA/app/administration/dashboard/index.php" class="nav-item <?= ($currentPage === 'dashboard') ? 'active' : '' ?>">Tableau de bord admin</a>
        <a href="/LOKA/app/administration/agences/index.php" class="nav-item <?= ($currentPage === 'agences') ? 'active' : '' ?>">Agences</a>
        <a href="/LOKA/app/administration/utilisateurs/index.php" class="nav-item <?= ($currentPage === 'utilisateurs') ? 'active' : '' ?>">Utilisateurs</a>
        
        <div class="nav-group">CONFIGURATION</div>
        <a href="/LOKA/app/administration/roles/index.php" class="nav-item <?= ($currentPage === 'roles') ? 'active' : '' ?>">Rôles & Permissions</a>
        <a href="/LOKA/app/administration/abonnements/index.php" class="nav-item <?= ($currentPage === 'abonnements') ? 'active' : '' ?>">Abonnements</a>
        <a href="/LOKA/app/administration/parametres/index.php" class="nav-item <?= ($currentPage === 'parametres') ? 'active' : '' ?>">Paramètres</a>
        
        <div class="nav-group">AUDIT</div>
        <a href="/LOKA/app/administration/audit/index.php" class="nav-item <?= ($currentPage === 'audit') ? 'active' : '' ?>">Journal d'audit</a>
        
        <div class="nav-group">RETOUR</div>
        <a href="/LOKA/app/dashboard/index.php" class="nav-item">Retour au portail</a>
    </nav>
</aside>
