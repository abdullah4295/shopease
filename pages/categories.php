<?php
/**
 * Categories Management - ShopEase
 * Add, edit, and organize product categories with 3D icon associations.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/error_handler.php';

Auth::requireLogin();

$msg = null;
$msgType = 'info';

// Handle Form Submissions (Create, Update, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $res = Category::create($_POST['name'] ?? '', $_POST['description'] ?? null);
        $msg = $res['message'];
        $msgType = $res['success'] ? 'success' : 'danger';
    } elseif ($action === 'update' && !empty($_POST['id'])) {
        $res = Category::update((int)$_POST['id'], $_POST['name'] ?? '', $_POST['description'] ?? null);
        $msg = $res['message'];
        $msgType = $res['success'] ? 'success' : 'danger';
    } elseif ($action === 'delete' && !empty($_POST['id'])) {
        $res = Category::delete((int)$_POST['id']);
        $msg = $res['message'];
        $msgType = $res['success'] ? 'success' : 'danger';
    }
}

$categories = Category::getAll();
$pageTitle = 'Categories - ShopEase';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">Product <span class="grad-text">Categories</span></h1>
        <p class="text-muted small mb-0">Organize and group inventory items across departments.</p>
    </div>
    <?php if (Auth::isAdmin()): ?>
    <button class="btn-primary-grad" onclick="openCategoryModal()">
        <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        <span>Add Category</span>
    </button>
    <?php endif; ?>
</div>

<?php if ($msg): ?>
<div class="status-pill <?= e($msgType) ?> w-100 mb-4 py-2 justify-content-center">
    <?= e($msg) ?>
</div>
<?php endif; ?>

<!-- Category Cards Grid -->
<div class="row g-3">
    <?php foreach ($categories as $cat): ?>
    <div class="col-md-6 col-lg-4">
        <div class="se-card p-4 h-100 d-flex flex-column justify-content-between" data-tilt>
            <div>
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h2 class="h5 mb-0"><?= e($cat['name']) ?></h2>
                    <span class="status-pill neutral"><?= (int)$cat['product_count'] ?> Products</span>
                </div>
                <p class="text-muted small mb-4">
                    <?= e($cat['description'] ?: 'No detailed description provided.') ?>
                </p>
            </div>

            <?php if (Auth::isAdmin()): ?>
            <div class="d-flex justify-content-end gap-2 border-top pt-3">
                <button type="button" class="btn-ghost" style="padding: 4px 12px; font-size: 12px;" 
                        onclick='editCategory(<?= json_encode($cat, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>)'>
                    Edit
                </button>
                <form method="POST" onsubmit="return confirm('Delete this category?');" style="display:inline;">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
                    <button type="submit" class="status-pill danger border-0" style="cursor: pointer; padding: 6px 12px;">Delete</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Category Modal -->
<div id="categoryModal" style="display:none; position:fixed; inset:0; background:rgba(20,19,31,0.5); backdrop-filter:blur(4px); z-index:1050; align-items:center; justify-content:center; padding:20px;">
    <div class="se-card se-card-lg p-4" style="width: 100%; max-width: 440px; background: #FFFFFF;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="h5 mb-0" id="catModalTitle">Add Category</h3>
            <button type="button" class="btn-ghost" style="padding: 4px 10px;" onclick="closeCategoryModal()">✕</button>
        </div>

        <form method="POST" id="categoryForm">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" id="catAction" value="create">
            <input type="hidden" name="id" id="catId" value="">

            <div class="mb-3">
                <label class="form-label small fw-semibold text-muted mb-1">Category Name</label>
                <input type="text" name="name" id="catName" class="se-input" placeholder="e.g. Smart Wearables" required>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-semibold text-muted mb-1">Description</label>
                <textarea name="description" id="catDescription" class="se-input" rows="3" placeholder="Category brief..."></textarea>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn-ghost" onclick="closeCategoryModal()">Cancel</button>
                <button type="submit" class="btn-primary-grad">Save Category</button>
            </div>
        </form>
    </div>
</div>

<script src="<?= asset('js/tilt.js') ?>"></script>
<script>
function openCategoryModal() {
    document.getElementById('catModalTitle').textContent = 'Add Category';
    document.getElementById('catAction').value = 'create';
    document.getElementById('catId').value = '';
    document.getElementById('catName').value = '';
    document.getElementById('catDescription').value = '';
    document.getElementById('categoryModal').style.display = 'flex';
}

function editCategory(cat) {
    document.getElementById('catModalTitle').textContent = 'Edit Category';
    document.getElementById('catAction').value = 'update';
    document.getElementById('catId').value = cat.id;
    document.getElementById('catName').value = cat.name;
    document.getElementById('catDescription').value = cat.description || '';
    document.getElementById('categoryModal').style.display = 'flex';
}

function closeCategoryModal() {
    document.getElementById('categoryModal').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
