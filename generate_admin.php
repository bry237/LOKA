<?php
declare(strict_types=1);

$basePath = 'c:/xampp/htdocs/LOKA';
$adminPath = $basePath . '/app/administration';

// Helper to create directory
function makeDir($path) {
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }
}

makeDir($basePath . '/layouts');
makeDir($adminPath . '/agences');
makeDir($adminPath . '/utilisateurs');
makeDir($adminPath . '/roles');
makeDir($adminPath . '/abonnements');
makeDir($adminPath . '/audit');
makeDir($adminPath . '/parametres');

// 1. Sidebar Admin
$sidebarContent = <<<HTML
<aside class="sidebar">
    <div class="sidebar-header">
        <h2>LOKA Admin</h2>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-group">ADMINISTRATION</div>
        <a href="/LOKA/app/administration/dashboard/index.php" class="nav-item <?= (\$currentPage === 'dashboard') ? 'active' : '' ?>">Tableau de bord admin</a>
        <a href="/LOKA/app/administration/agences/index.php" class="nav-item <?= (\$currentPage === 'agences') ? 'active' : '' ?>">Agences</a>
        <a href="/LOKA/app/administration/utilisateurs/index.php" class="nav-item <?= (\$currentPage === 'utilisateurs') ? 'active' : '' ?>">Utilisateurs</a>
        
        <div class="nav-group">CONFIGURATION</div>
        <a href="/LOKA/app/administration/roles/index.php" class="nav-item <?= (\$currentPage === 'roles') ? 'active' : '' ?>">Rôles & Permissions</a>
        <a href="/LOKA/app/administration/abonnements/index.php" class="nav-item <?= (\$currentPage === 'abonnements') ? 'active' : '' ?>">Abonnements</a>
        <a href="/LOKA/app/administration/parametres/index.php" class="nav-item <?= (\$currentPage === 'parametres') ? 'active' : '' ?>">Paramètres</a>
        
        <div class="nav-group">AUDIT</div>
        <a href="/LOKA/app/administration/audit/index.php" class="nav-item <?= (\$currentPage === 'audit') ? 'active' : '' ?>">Journal d'audit</a>
        
        <div class="nav-group">RETOUR</div>
        <a href="/LOKA/app/dashboard/index.php" class="nav-item">Retour au portail</a>
    </nav>
</aside>
HTML;
file_put_contents($basePath . '/layouts/sidebar_admin.php', $sidebarContent);

// Function to generate standard files
function writeStandardFile($path, $content) {
    file_put_contents($path, $content);
}

// 2. Agences
$agenceModel = <<<PHP
<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/core/Database.php';

class AgenceModel {
    private \$db;

    public function __construct() {
        \$this->db = Database::connection();
    }

    public function findAll(\$search = '', \$status = '') {
        \$sql = "SELECT * FROM agence WHERE deleted_at IS NULL";
        \$params = [];
        if (\$search) {
            \$sql .= " AND nom LIKE :search";
            \$params[':search'] = '%' . \$search . '%';
        }
        if (\$status) {
            \$sql .= " AND statut = :statut";
            \$params[':statut'] = \$status;
        }
        \$stmt = \$this->db->prepare(\$sql);
        \$stmt->execute(\$params);
        return \$stmt->fetchAll();
    }

    public function findById(\$id) {
        \$stmt = \$this->db->prepare("SELECT * FROM agence WHERE id_agence = :id AND deleted_at IS NULL");
        \$stmt->execute([':id' => \$id]);
        return \$stmt->fetch();
    }

    public function create(\$data) {
        \$sql = "INSERT INTO agence (nom, email, telephone, adresse, ville, code_postal, pays, statut, created_at) 
                VALUES (:nom, :email, :telephone, :adresse, :ville, :code_postal, :pays, :statut, NOW())";
        \$stmt = \$this->db->prepare(\$sql);
        \$stmt->execute([
            ':nom' => \$data['nom'],
            ':email' => \$data['email'],
            ':telephone' => \$data['telephone'],
            ':adresse' => \$data['adresse'],
            ':ville' => \$data['ville'],
            ':code_postal' => \$data['code_postal'],
            ':pays' => \$data['pays'],
            ':statut' => 'ACTIVE'
        ]);
        return \$this->db->lastInsertId();
    }

    public function update(\$id, \$data) {
        \$sql = "UPDATE agence SET nom = :nom, email = :email, telephone = :telephone, 
                adresse = :adresse, ville = :ville, code_postal = :code_postal, pays = :pays, updated_at = NOW() 
                WHERE id_agence = :id";
        \$stmt = \$this->db->prepare(\$sql);
        return \$stmt->execute([
            ':nom' => \$data['nom'],
            ':email' => \$data['email'],
            ':telephone' => \$data['telephone'],
            ':adresse' => \$data['adresse'],
            ':ville' => \$data['ville'],
            ':code_postal' => \$data['code_postal'],
            ':pays' => \$data['pays'],
            ':id' => \$id
        ]);
    }
    
    public function updateStatus(\$id, \$statut) {
        \$sql = "UPDATE agence SET statut = :statut, updated_at = NOW() WHERE id_agence = :id";
        \$stmt = \$this->db->prepare(\$sql);
        return \$stmt->execute([':statut' => \$statut, ':id' => \$id]);
    }
    
    public function getStats() {
        \$stmt = \$this->db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN statut = 'ACTIVE' THEN 1 ELSE 0 END) as actives,
                SUM(CASE WHEN statut = 'SUSPENDED' THEN 1 ELSE 0 END) as suspendues
            FROM agence WHERE deleted_at IS NULL
        ");
        return \$stmt->fetch();
    }
}
PHP;

$agenceController = <<<PHP
<?php
declare(strict_types=1);
require_once __DIR__ . '/AgenceModel.php';

class AgenceController {
    public function validate(\$data) {
        \$errors = [];
        if (empty(\$data['nom'])) \$errors['nom'] = "Le nom est requis.";
        if (empty(\$data['email']) || !filter_var(\$data['email'], FILTER_VALIDATE_EMAIL)) \$errors['email'] = "Email invalide.";
        return \$errors;
    }
}
PHP;

$agenceIndex = <<<PHP
<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/core/Authorization.php';
\$user = Authorization::requireRole('Administrateur plateforme');
require_once __DIR__ . '/AgenceModel.php';
\$model = new AgenceModel();
\$search = \$_GET['search'] ?? '';
\$status = \$_GET['status'] ?? '';
\$agences = \$model->findAll(\$search, \$status);
\$stats = \$model->getStats();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Agences | LOKA Admin</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php \$currentPage = 'agences'; require_once dirname(__DIR__, 3) . '/layouts/sidebar_admin.php'; ?>
    <div class="content">
        <header class="topbar"><h1>Gestion des Agences</h1></header>
        <main class="page">
            <div class="stats-cards">
                <div class="card">Total: <?= htmlspecialchars((string)\$stats['total'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="card">Actives: <?= htmlspecialchars((string)\$stats['actives'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="card">Suspendues: <?= htmlspecialchars((string)\$stats['suspendues'], ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <a href="ajouter.php" class="btn btn-primary">Ajouter une agence</a>
            <table>
                <thead><tr><th>Nom</th><th>Email</th><th>Statut</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach (\$agences as \$a): ?>
                    <tr>
                        <td><?= htmlspecialchars(\$a['nom'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars(\$a['email'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars(\$a['statut'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a href="detail.php?id=<?= \$a['id_agence'] ?>">Détail</a>
                            <a href="modifier.php?id=<?= \$a['id_agence'] ?>">Modifier</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>
</div>
</body>
</html>
PHP;

$agenceDetail = <<<PHP
<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/core/Authorization.php';
\$user = Authorization::requireRole('Administrateur plateforme');
require_once __DIR__ . '/AgenceModel.php';
\$model = new AgenceModel();
\$id = \$_GET['id'] ?? null;
\$agence = \$model->findById(\$id);
if (!\$agence) die('Agence introuvable');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Détail Agence | LOKA Admin</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php \$currentPage = 'agences'; require_once dirname(__DIR__, 3) . '/layouts/sidebar_admin.php'; ?>
    <div class="content">
        <header class="topbar"><h1>Agence: <?= htmlspecialchars(\$agence['nom'], ENT_QUOTES, 'UTF-8') ?></h1></header>
        <main class="page">
            <p>Email: <?= htmlspecialchars(\$agence['email'], ENT_QUOTES, 'UTF-8') ?></p>
            <p>Statut: <?= htmlspecialchars(\$agence['statut'], ENT_QUOTES, 'UTF-8') ?></p>
        </main>
    </div>
</div>
</body>
</html>
PHP;

$agenceAjouter = <<<PHP
<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/core/Authorization.php';
\$user = Authorization::requireRole('Administrateur plateforme');
require_once __DIR__ . '/AgenceModel.php';
require_once __DIR__ . '/AgenceController.php';

\$errors = [];
if (\$_SERVER['REQUEST_METHOD'] === 'POST') {
    \$controller = new AgenceController();
    \$errors = \$controller->validate(\$_POST);
    if (empty(\$errors)) {
        \$model = new AgenceModel();
        \$id = \$model->create(\$_POST);
        header('Location: detail.php?id=' . \$id);
        exit;
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Ajouter Agence | LOKA Admin</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php \$currentPage = 'agences'; require_once dirname(__DIR__, 3) . '/layouts/sidebar_admin.php'; ?>
    <div class="content">
        <header class="topbar"><h1>Ajouter une Agence</h1></header>
        <main class="page">
            <form method="POST">
                <div>
                    <label>Nom:</label>
                    <input type="text" name="nom" required>
                </div>
                <div>
                    <label>Email:</label>
                    <input type="email" name="email" required>
                </div>
                <!-- other fields -->
                <button type="submit">Enregistrer</button>
            </form>
        </main>
    </div>
</div>
</body>
</html>
PHP;

$agenceModifier = <<<PHP
<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/core/Authorization.php';
\$user = Authorization::requireRole('Administrateur plateforme');
require_once __DIR__ . '/AgenceModel.php';
require_once __DIR__ . '/AgenceController.php';

\$id = \$_GET['id'] ?? null;
\$model = new AgenceModel();
\$agence = \$model->findById(\$id);
if (!\$agence) die('Introuvable');

\$errors = [];
if (\$_SERVER['REQUEST_METHOD'] === 'POST') {
    \$controller = new AgenceController();
    \$errors = \$controller->validate(\$_POST);
    if (empty(\$errors)) {
        \$model->update(\$id, \$_POST);
        header('Location: detail.php?id=' . \$id);
        exit;
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Modifier Agence | LOKA Admin</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php \$currentPage = 'agences'; require_once dirname(__DIR__, 3) . '/layouts/sidebar_admin.php'; ?>
    <div class="content">
        <header class="topbar"><h1>Modifier Agence</h1></header>
        <main class="page">
            <form method="POST">
                <div>
                    <label>Nom:</label>
                    <input type="text" name="nom" value="<?= htmlspecialchars(\$agence['nom'], ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div>
                    <label>Email:</label>
                    <input type="email" name="email" value="<?= htmlspecialchars(\$agence['email'], ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <!-- other fields -->
                <button type="submit">Enregistrer</button>
            </form>
        </main>
    </div>
</div>
</body>
</html>
PHP;

writeStandardFile(\$adminPath . '/agences/AgenceModel.php', \$agenceModel);
writeStandardFile(\$adminPath . '/agences/AgenceController.php', \$agenceController);
writeStandardFile(\$adminPath . '/agences/index.php', \$agenceIndex);
writeStandardFile(\$adminPath . '/agences/detail.php', \$agenceDetail);
writeStandardFile(\$adminPath . '/agences/ajouter.php', \$agenceAjouter);
writeStandardFile(\$adminPath . '/agences/modifier.php', \$agenceModifier);


// 3. Utilisateurs
$utilisateurModel = <<<PHP
<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/core/Database.php';

class UtilisateurModel {
    private \$db;

    public function __construct() {
        \$this->db = Database::connection();
    }

    public function findAll(\$search = '', \$role = '', \$statut = '', \$agence = '') {
        \$sql = "SELECT u.*, r.nom as role_nom, a.nom as agence_nom 
                FROM utilisateur u 
                LEFT JOIN role r ON u.id_role = r.id_role 
                LEFT JOIN agence a ON u.id_agence = a.id_agence 
                WHERE u.deleted_at IS NULL";
        \$params = [];
        if (\$search) {
            \$sql .= " AND (u.nom LIKE :search OR u.email LIKE :search)";
            \$params[':search'] = '%' . \$search . '%';
        }
        if (\$role) {
            \$sql .= " AND u.id_role = :role";
            \$params[':role'] = \$role;
        }
        if (\$statut) {
            \$sql .= " AND u.statut = :statut";
            \$params[':statut'] = \$statut;
        }
        if (\$agence) {
            \$sql .= " AND u.id_agence = :agence";
            \$params[':agence'] = \$agence;
        }
        \$stmt = \$this->db->prepare(\$sql);
        \$stmt->execute(\$params);
        return \$stmt->fetchAll();
    }

    public function findById(\$id) {
        \$stmt = \$this->db->prepare("SELECT * FROM utilisateur WHERE id_utilisateur = :id AND deleted_at IS NULL");
        \$stmt->execute([':id' => \$id]);
        return \$stmt->fetch();
    }

    public function create(\$data) {
        \$sql = "INSERT INTO utilisateur (id_agence, id_role, nom, prenom, email, mot_de_passe, telephone, statut, created_at) 
                VALUES (:id_agence, :id_role, :nom, :prenom, :email, :mot_de_passe, :telephone, :statut, NOW())";
        \$stmt = \$this->db->prepare(\$sql);
        \$hash = password_hash(\$data['mot_de_passe'], PASSWORD_DEFAULT);
        \$stmt->execute([
            ':id_agence' => \$data['id_agence'] ?: null,
            ':id_role' => \$data['id_role'] ?: null,
            ':nom' => \$data['nom'],
            ':prenom' => \$data['prenom'],
            ':email' => \$data['email'],
            ':mot_de_passe' => \$hash,
            ':telephone' => \$data['telephone'],
            ':statut' => 'ACTIVE'
        ]);
        return \$this->db->lastInsertId();
    }

    public function update(\$id, \$data) {
        \$sql = "UPDATE utilisateur SET id_agence = :id_agence, id_role = :id_role, nom = :nom, prenom = :prenom, 
                email = :email, telephone = :telephone, updated_at = NOW() WHERE id_utilisateur = :id";
        \$stmt = \$this->db->prepare(\$sql);
        return \$stmt->execute([
            ':id_agence' => \$data['id_agence'] ?: null,
            ':id_role' => \$data['id_role'] ?: null,
            ':nom' => \$data['nom'],
            ':prenom' => \$data['prenom'],
            ':email' => \$data['email'],
            ':telephone' => \$data['telephone'],
            ':id' => \$id
        ]);
    }
    
    public function updateStatus(\$id, \$statut) {
        \$sql = "UPDATE utilisateur SET statut = :statut, updated_at = NOW() WHERE id_utilisateur = :id";
        \$stmt = \$this->db->prepare(\$sql);
        return \$stmt->execute([':statut' => \$statut, ':id' => \$id]);
    }
    
    public function getStats() {
        \$stmt = \$this->db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN statut = 'ACTIVE' THEN 1 ELSE 0 END) as actifs
            FROM utilisateur WHERE deleted_at IS NULL
        ");
        return \$stmt->fetch();
    }
}
PHP;

$utilisateurIndex = <<<PHP
<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/core/Authorization.php';
\$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence');
require_once __DIR__ . '/UtilisateurModel.php';
\$model = new UtilisateurModel();
\$utilisateurs = \$model->findAll();
\$stats = \$model->getStats();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Utilisateurs | LOKA Admin</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php \$currentPage = 'utilisateurs'; require_once dirname(__DIR__, 3) . '/layouts/sidebar_admin.php'; ?>
    <div class="content">
        <header class="topbar"><h1>Gestion des Utilisateurs</h1></header>
        <main class="page">
            <a href="ajouter.php" class="btn btn-primary">Ajouter un utilisateur</a>
            <table>
                <thead><tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Agence</th><th>Statut</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach (\$utilisateurs as \$u): ?>
                    <tr>
                        <td><?= htmlspecialchars(\$u['nom'] . ' ' . \$u['prenom'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars(\$u['email'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)\$u['role_nom'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)\$u['agence_nom'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars(\$u['statut'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a href="detail.php?id=<?= \$u['id_utilisateur'] ?>">Détail</a>
                            <a href="modifier.php?id=<?= \$u['id_utilisateur'] ?>">Modifier</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>
</div>
</body>
</html>
PHP;

writeStandardFile(\$adminPath . '/utilisateurs/UtilisateurModel.php', \$utilisateurModel);
writeStandardFile(\$adminPath . '/utilisateurs/UtilisateurController.php', "<?php declare(strict_types=1); class UtilisateurController {}");
writeStandardFile(\$adminPath . '/utilisateurs/index.php', \$utilisateurIndex);
writeStandardFile(\$adminPath . '/utilisateurs/detail.php', "<?php declare(strict_types=1); echo 'detail';");
writeStandardFile(\$adminPath . '/utilisateurs/ajouter.php', "<?php declare(strict_types=1); echo 'ajouter';");
writeStandardFile(\$adminPath . '/utilisateurs/modifier.php', "<?php declare(strict_types=1); echo 'modifier';");
writeStandardFile(\$adminPath . '/utilisateurs/activer.php', "<?php declare(strict_types=1); echo 'activer';");
writeStandardFile(\$adminPath . '/utilisateurs/desactiver.php', "<?php declare(strict_types=1); echo 'desactiver';");

// 4. Roles
writeStandardFile(\$adminPath . '/roles/RoleModel.php', "<?php declare(strict_types=1); class RoleModel {}");
writeStandardFile(\$adminPath . '/roles/RoleController.php', "<?php declare(strict_types=1); class RoleController {}");
writeStandardFile(\$adminPath . '/roles/index.php', "<?php declare(strict_types=1); require_once dirname(__DIR__, 3) . '/core/Authorization.php'; Authorization::requireRole('Administrateur plateforme', 'Administrateur agence'); \$currentPage = 'roles'; require_once dirname(__DIR__, 3) . '/layouts/sidebar_admin.php'; echo 'Roles Index';");
writeStandardFile(\$adminPath . '/roles/ajouter.php', "<?php declare(strict_types=1); echo 'ajouter';");
writeStandardFile(\$adminPath . '/roles/modifier.php', "<?php declare(strict_types=1); echo 'modifier';");
writeStandardFile(\$adminPath . '/roles/permissions.php', "<?php declare(strict_types=1); echo 'permissions';");

// 5. Abonnements
writeStandardFile(\$adminPath . '/abonnements/AbonnementModel.php', "<?php declare(strict_types=1); class AbonnementModel {}");
writeStandardFile(\$adminPath . '/abonnements/AbonnementController.php', "<?php declare(strict_types=1); class AbonnementController {}");
writeStandardFile(\$adminPath . '/abonnements/index.php', "<?php declare(strict_types=1); require_once dirname(__DIR__, 3) . '/core/Authorization.php'; Authorization::requireRole('Administrateur plateforme', 'Administrateur agence'); \$currentPage = 'abonnements'; require_once dirname(__DIR__, 3) . '/layouts/sidebar_admin.php'; echo 'Abonnements Index';");
writeStandardFile(\$adminPath . '/abonnements/ajouter.php', "<?php declare(strict_types=1); echo 'ajouter';");
writeStandardFile(\$adminPath . '/abonnements/modifier.php', "<?php declare(strict_types=1); echo 'modifier';");

// 6. Audit
writeStandardFile(\$adminPath . '/audit/AuditModel.php', "<?php declare(strict_types=1); class AuditModel {}");
writeStandardFile(\$adminPath . '/audit/AuditController.php', "<?php declare(strict_types=1); class AuditController {}");
writeStandardFile(\$adminPath . '/audit/index.php', "<?php declare(strict_types=1); require_once dirname(__DIR__, 3) . '/core/Authorization.php'; Authorization::requireRole('Administrateur plateforme', 'Administrateur agence'); \$currentPage = 'audit'; require_once dirname(__DIR__, 3) . '/layouts/sidebar_admin.php'; echo 'Audit Index';");
writeStandardFile(\$adminPath . '/audit/detail.php', "<?php declare(strict_types=1); echo 'detail';");

// 7. Parametres
writeStandardFile(\$adminPath . '/parametres/ParametreController.php', "<?php declare(strict_types=1); class ParametreController {}");
writeStandardFile(\$adminPath . '/parametres/index.php', "<?php declare(strict_types=1); require_once dirname(__DIR__, 3) . '/core/Authorization.php'; Authorization::requireRole('Administrateur plateforme', 'Administrateur agence'); \$currentPage = 'parametres'; require_once dirname(__DIR__, 3) . '/layouts/sidebar_admin.php'; echo 'Parametres Index';");
writeStandardFile(\$adminPath . '/parametres/agence.php', "<?php declare(strict_types=1); echo 'agence';");
writeStandardFile(\$adminPath . '/parametres/notifications.php', "<?php declare(strict_types=1); echo 'notifications';");

echo "Admin files generated successfully.";
?>
