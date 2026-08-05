<?php
// ============================================================
// /_admin/manage_companies.php
// Pannello gestione aziende: elenco completo con possibilità di
// attivare/disattivare la visibilità pubblica (campo `attiva`).
//
// Accesso: solo dopo AdminAuth::requireAdminSession().
// Nessuna migrazione DB necessaria: il campo `attiva` esiste già
// nella tabella `06_company`.
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/admin_auth.class.php';
require_once __DIR__ . '/../libs/user_tier.class.php';

$admin_id = AdminAuth::requireAdminSession();

// -------------------------------------------------------------------
// Gestione azioni POST (activate / deactivate)
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $company_id = (int)($_POST['company_id'] ?? 0);
    $action = in_array($_POST['action'] ?? '', ['activate', 'deactivate', 'delete'], true)
        ? $_POST['action']
        : '';

    if ($company_id > 0 && $action !== '') {

        if ($action === 'delete') {
            // ----- Cancellazione completa azienda + tabelle figlie + file -----
            $upload_base = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/upload_image/06_company/';

            // Logo + immagini gallery PRIMA del DELETE
            $stmt = $pdo->prepare("SELECT logo FROM `06_company` WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $company_id]);
            $company_row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $stmt = $pdo->prepare("SELECT immagine FROM `06_company_gallery` WHERE company_id = :id");
            $stmt->execute([':id' => $company_id]);
            $gallery_images = $stmt->fetchAll(PDO::FETCH_ASSOC);

            try {
                $pdo->beginTransaction();
                // Tabelle figlie (associazioni e gallery)
                $pdo->prepare("DELETE FROM `06_company_gallery` WHERE company_id = :id")->execute([':id' => $company_id]);
                $pdo->prepare("DELETE FROM `06_company_products` WHERE company_id = :id")->execute([':id' => $company_id]);
                $pdo->prepare("DELETE FROM `06_company_products_special` WHERE company_id = :id")->execute([':id' => $company_id]);
                $pdo->prepare("DELETE FROM `06_company_services` WHERE company_id = :id")->execute([':id' => $company_id]);
                $pdo->prepare("DELETE FROM `06_company` WHERE id = :id LIMIT 1")->execute([':id' => $company_id]);
                $pdo->commit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log('[Allonwheel] admin delete company error (id=' . $company_id . '): ' . $e->getMessage());
                $_SESSION['admin_error'] = 'Database error while deleting the company.';
                header('Location: /_admin/manage_companies.php');
                exit;
            }

            // Cleanup file DOPO il commit, con protezione path-traversal
            $delete_file = static function (string $dir, ?string $filename): void {
                $filename = basename((string)$filename);
                if ($filename === '' || $filename === 'no_image.jpg') { return; }
                $full = realpath($dir . $filename);
                $base = realpath($dir);
                if ($full === false || $base === false) { return; }
                if (strpos($full, $base . DIRECTORY_SEPARATOR) !== 0) { return; }
                if (is_file($full)) { @unlink($full); }
            };
            $logo = (string)($company_row['logo'] ?? '');
            foreach (['original/', 'thumbnail/'] as $sub) {
                $delete_file($upload_base . $sub, $logo);
                foreach ($gallery_images as $g) {
                    $delete_file($upload_base . $sub, $g['immagine'] ?? '');
                }
            }
            // Compatibilita' vecchi upload flat (pre-refactoring)
            $delete_file($upload_base, $logo);
            foreach ($gallery_images as $g) { $delete_file($upload_base, $g['immagine'] ?? ''); }

            $_SESSION['admin_success'] = "Company #{$company_id} deleted permanently.";
            UserTier::logAdminAction(
                $pdo, $admin_id, 'delete_company', null,
                '06_company #' . $company_id . ' deleted (gallery, products, special, services)',
                $_SERVER['REMOTE_ADDR'] ?? ''
            );

        } else {
            // ----- Attiva / disattiva visibilita' -----
            $new_attiva = $action === 'activate' ? 1 : 0;

            $stmt = $pdo->prepare("
                UPDATE `06_company`
                SET `attiva` = :a
                WHERE `id` = :id
                LIMIT 1
            ");

            $stmt->execute([
                ':a'  => $new_attiva,
                ':id' => $company_id
            ]);

            $label = $action === 'activate' ? 'activated' : 'deactivated';

            $_SESSION['admin_success'] = "Company #{$company_id} {$label}.";

            UserTier::logAdminAction(
                $pdo, $admin_id, 'manage_company', null,
                '06_company #' . $company_id . ' ' . $label,
                $_SERVER['REMOTE_ADDR'] ?? ''
            );
        }
    }

    header('Location: /_admin/manage_companies.php');
    exit;
}

// -------------------------------------------------------------------
// Filtro attiva
// -------------------------------------------------------------------
$filter = in_array($_GET['filter'] ?? '', ['all', 'active', 'inactive'], true)
    ? $_GET['filter']
    : 'all';

$where = '';

switch ($filter) {
    case 'active':
        $where = 'WHERE c.attiva = 1';
        break;

    case 'inactive':
        $where = 'WHERE c.attiva = 0';
        break;

    default:
        $where = '';
        break;
}

// -------------------------------------------------------------------
// Recupero aziende
// -------------------------------------------------------------------
$sql = "
    SELECT
        c.id,
        c.ragione_sociale,
        c.citta,
        c.provincia,
        c.nazione,
        c.email,
        c.attiva,
        c.data_inserimento,
        c.logo,
        u.username,
        u.email AS user_email,

        (
            SELECT COUNT(*)
            FROM `06_company_gallery` g
            WHERE g.company_id = c.id
        ) AS gallery_count,

        (
            SELECT COUNT(*)
            FROM `06_company_products` p
            WHERE p.company_id = c.id
        ) AS products_count

    FROM `06_company` c

    LEFT JOIN `users` u
        ON u.id_user = c.user_id

    {$where}

    ORDER BY c.data_inserimento DESC
    LIMIT 500
";

$companies = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$counts = $pdo->query("
    SELECT
        SUM(attiva = 1) AS active_count,
        SUM(attiva = 0) AS inactive_count,
        COUNT(*)        AS total
    FROM `06_company`
")->fetch(PDO::FETCH_ASSOC);

csrf_generate();

$csrf_token = $_SESSION['csrf_token'] ?? '';

$success = $_SESSION['admin_success'] ?? '';
$error   = $_SESSION['admin_error'] ?? '';

unset($_SESSION['admin_success'], $_SESSION['admin_error']);

$admin_title  = 'Manage Companies';
$admin_active = 'companies';
require __DIR__ . '/admin_header.php';
?>

        </div>

        <!-- Riepilogo e filtri -->
        <div class="post_box">

            <h2>Companies overview</h2>

            <p>
                <strong>Active:</strong>
                <?php echo (int)$counts['active_count']; ?>

                &nbsp;|&nbsp;

                <strong>Inactive:</strong>
                <?php echo (int)$counts['inactive_count']; ?>

                &nbsp;|&nbsp;

                <strong>Total:</strong>
                <?php echo (int)$counts['total']; ?>
            </p>

            <div class="post_meta">

                <a href="?filter=all"<?php echo $filter === 'all' ? ' class="admin_nav_active"' : ''; ?>>
                    All (<?php echo (int)$counts['total']; ?>)
                </a>

                &nbsp;|&nbsp;

                <a href="?filter=active"<?php echo $filter === 'active' ? ' class="admin_nav_active"' : ''; ?>>
                    Active (<?php echo (int)$counts['active_count']; ?>)
                </a>

                &nbsp;|&nbsp;

                <a href="?filter=inactive"<?php echo $filter === 'inactive' ? ' class="admin_nav_active"' : ''; ?>>
                    Inactive (<?php echo (int)$counts['inactive_count']; ?>)
                </a>

                <div class="cleaner"></div>
            </div>
        </div>

        <!-- Tabella aziende -->
        <div class="post_box">

            <h2><?php echo ucfirst($filter); ?> companies</h2>

            <?php if (empty($companies)): ?>

                <p><em>No companies found.</em></p>

            <?php else: ?>

                <table border="1" cellpadding="6" cellspacing="0" class="admin_table">

                    <thead>
                        <tr class="admin_thead_row">
                            <th style="text-align: center">ID</th>
                            <th style="text-align: center">Logo</th>
                            <th style="text-align: center">Company</th>
                            <th style="text-align: center">Location</th>
                            <th style="text-align: center">Owner</th>
                            <th style="text-align: center">Registered</th>
                            <th style="text-align: center">Content</th>
                            <th style="text-align: center">Status</th>
                            <th style="text-align: center">Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($companies as $co): ?>

                        <?php $is_active = (int)$co['attiva'] === 1; ?>

                        <tr<?php echo !$is_active ? ' class="admin_row_inactive"' : ''; ?>>

                            <td align="center" style="text-align: center"><?php echo (int)$co['id']; ?></td>

                            <td style="text-align: center">

                                <?php if (!empty($co['logo'])): ?>

                                    <a class="pirobox" href="/uploads/06_company/<?php echo htmlspecialchars($co['logo'], ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars($co['ragione_sociale'] ?? 'Company logo', ENT_QUOTES, 'UTF-8'); ?>">
                                        <img 
                                            src="/uploads/06_company/<?php echo htmlspecialchars($co['logo'], ENT_QUOTES, 'UTF-8'); ?>"
                                            alt=""
                                            class="admin_thumb" loading="lazy" decoding="async" />
                                    </a>

                                <?php else: ?>

                                    <em class="admin_muted">&mdash;</em>

                                <?php endif; ?>

                            </td>

                            <td style="text-align: center">

                                <strong>
                                    <?php echo htmlspecialchars($co['ragione_sociale'], ENT_QUOTES, 'UTF-8'); ?>
                                </strong>

                                <br />

                                <small>
                                    <?php echo htmlspecialchars($co['email'], ENT_QUOTES, 'UTF-8'); ?>
                                </small>

                            </td>

                            <td style="text-align: center">

                                <?php echo htmlspecialchars($co['citta'] ?? '', ENT_QUOTES, 'UTF-8'); ?>

                                <?php if (!empty($co['provincia'])): ?>
                                    (<?php echo htmlspecialchars($co['provincia'], ENT_QUOTES, 'UTF-8'); ?>)
                                <?php endif; ?>

                                <br />

                                <small>
                                    <?php echo htmlspecialchars($co['nazione'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </small>

                            </td>

                            <td style="text-align: center">

                                <?php echo htmlspecialchars($co['username'] ?? '—', ENT_QUOTES, 'UTF-8'); ?>

                                <br />

                                <small>
                                    <?php echo htmlspecialchars($co['user_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </small>

                            </td>

                            <td style="text-align: center">
                                <?php
                                echo htmlspecialchars(
                                    date('Y-m-d', strtotime($co['data_inserimento'])),
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>
                            </td>

                            <td style="text-align: center">

                                <?php echo (int)$co['products_count']; ?> products

                                <br />

                                <small>
                                    <?php echo (int)$co['gallery_count']; ?> gallery images
                                </small>

                            </td>

                            <td style="text-align: center">

                                <?php if ($is_active): ?>

                                    <strong class="admin_ok">active</strong>

                                <?php else: ?>

                                    <em class="admin_bad">inactive</em>

                                <?php endif; ?>

                            </td>

                            <td style="text-align: center">

                                <a href="/06_company/06_02_view_company.php?id=<?php echo (int)$co['id']; ?>" target="_blank">
                                    View
                                </a>

                                &nbsp;

                                <?php if ($is_active): ?>

                                    <form
                                        method="post"
                                        action="manage_companies.php<?php echo $filter !== 'all' ? '?filter=' . urlencode($filter) : ''; ?>"
                                        class="admin_inline_form"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>"
                                        />

                                        <input
                                            type="hidden"
                                            name="company_id"
                                            value="<?php echo (int)$co['id']; ?>"
                                        />

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="deactivate"
                                        />

                                        <button type="submit" class="more">
                                            Deactivate
                                        </button>

                                    </form>

                                <?php else: ?>

                                    <form
                                        method="post"
                                        action="manage_companies.php<?php echo $filter !== 'all' ? '?filter=' . urlencode($filter) : ''; ?>"
                                        class="admin_inline_form"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>"
                                        />

                                        <input
                                            type="hidden"
                                            name="company_id"
                                            value="<?php echo (int)$co['id']; ?>"
                                        />

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="activate"
                                        />

                                        <button type="submit" class="more">
                                            Activate
                                        </button>

                                    </form>

                                <?php endif; ?>

                                &nbsp;

                                <form
                                    method="post"
                                    action="manage_companies.php<?php echo $filter !== 'all' ? '?filter=' . urlencode($filter) : ''; ?>"
                                    class="admin_inline_form" >
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>" />
                                    <input type="hidden" name="company_id" value="<?php echo (int)$co['id']; ?>" />
                                    <input type="hidden" name="action" value="delete" />
                                    <button type="submit" class="more">Delete</button>
                                </form>

                            </td>

                      </tr>

                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <div class="post_box">
            <h3>Notes</h3>

            <p>
                Inactive companies are hidden from the public Supplier Directory
                but their data is preserved.
                <br />
                Every activate/deactivate action is logged in
                <code>admin_audit_log</code>.
            </p>

        </div>

    
<?php require __DIR__ . '/admin_footer.php'; ?>
