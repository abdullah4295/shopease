<?php
/**
 * Products Catalog & 3D Inventory Management - ShopEase
 * Full CRUD, 3D model (.glb/.usdz) uploads, card-tilt, search, filter, and pagination.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/error_handler.php';

Auth::requireLogin();
$isAdmin = Auth::isAdmin();

$msg = null;
$msgType = 'info';

// Process Product Submissions (Add, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete' && $isAdmin && !empty($_POST['id'])) {
        $res = Product::delete((int)$_POST['id']);
        $msg = $res['message'];
        $msgType = $res['success'] ? 'success' : 'danger';
    } elseif (($action === 'create' || $action === 'update') && $isAdmin) {
        $data = [
            'name'            => $_POST['name'] ?? '',
            'sku_code'        => $_POST['sku_code'] ?? '',
            'category_id'     => $_POST['category_id'] ?? '',
            'supplier_id'     => $_POST['supplier_id'] ?? null,
            'cost_price'      => $_POST['cost_price'] ?? 0.00,
            'selling_price'   => $_POST['selling_price'] ?? 0.00,
            'stock_quantity'  => $_POST['stock_quantity'] ?? 0,
            'low_stock_level' => $_POST['low_stock_level'] ?? 5,
            'expiry_date'     => !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null
        ];

        // Process 2D Image Upload
        if (!empty($_FILES['image']['name'])) {
            $imgUpload = Upload::image($_FILES['image']);
            if ($imgUpload['success']) {
                $data['image'] = $imgUpload['path'];
            } else {
                $msg = $imgUpload['message'];
                $msgType = 'danger';
            }
        }

        // Process 3D Model Upload (.glb, .gltf, .usdz)
        if (empty($msg) && !empty($_FILES['model_3d']['name'])) {
            $modelUpload = Upload::model3D($_FILES['model_3d']);
            if ($modelUpload['success']) {
                $ext = strtolower(pathinfo($_FILES['model_3d']['name'], PATHINFO_EXTENSION));
                if ($ext === 'usdz') {
                    $data['model_3d_usdz_path'] = $modelUpload['path'];
                } else {
                    $data['model_3d_path'] = $modelUpload['path'];
                }
            } else {
                $msg = $modelUpload['message'];
                $msgType = 'danger';
            }
        }

        if (empty($msg)) {
            if ($action === 'create') {
                $res = Product::create($data);
            } else {
                $res = Product::update((int)$_POST['id'], $data);
            }
            $msg = $res['message'];
            $msgType = $res['success'] ? 'success' : 'danger';
        }
    }
}

// Filters & Pagination
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$search = trim($_GET['search'] ?? '');
$categoryId = !empty($_GET['category_id']) ? (int)$_GET['category_id'] : null;
$stockStatus = $_GET['stock_status'] ?? null;

$catalog = Product::getPaginated($currentPage, 9, [
    'search'       => $search,
    'category_id'  => $categoryId,
    'stock_status' => $stockStatus
]);

$categories = Category::getAll();
$suppliers = Supplier::getAll();

$pageTitle = 'Products & 3D Inventory - ShopEase';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">Products & <span class="grad-text">3D Inventory</span></h1>
        <p class="text-muted small mb-0">Browse catalog, inspect 3D assets, and manage stock quantities.</p>
    </div>
    <?php if ($isAdmin): ?>
    <button class="btn-primary-grad" onclick="openProductModal()">
        <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        <span>Add Product</span>
    </button>
    <?php endif; ?>
</div>

<?php if ($msg): ?>
<div class="status-pill <?= e($msgType) ?> w-100 mb-4 py-2 justify-content-center">
    <?= e($msg) ?>
</div>
<?php endif; ?>

<!-- Search & Filtering Bar -->
<div class="se-card p-3 mb-4">
    <form method="GET" class="row g-2 align-items-center">
        <div class="col-md-5">
            <input type="text" name="search" class="se-input se-input-pill" placeholder="Search by product name or SKU..." value="<?= e($search) ?>">
        </div>
        <div class="col-md-3">
            <select name="category_id" class="se-input se-input-pill">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int)$cat['id'] ?>" <?= ($categoryId === (int)$cat['id']) ? 'selected' : '' ?>>
                        <?= e($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="stock_status" class="se-input se-input-pill">
                <option value="">All Stock Levels</option>
                <option value="low" <?= ($stockStatus === 'low') ? 'selected' : '' ?>>Low Stock (<= 5)</option>
                <option value="out" <?= ($stockStatus === 'out') ? 'selected' : '' ?>>Out of Stock</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn-primary-grad w-100" style="padding: 9px 16px;">Filter</button>
            <?php if ($search || $categoryId || $stockStatus): ?>
                <a href="<?= url('pages/products.php') ?>" class="btn-ghost" title="Reset Filters" style="padding: 9px 14px;">✕</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Products Grid (with 3D Card-Tilt) -->
<div class="row g-3">
    <?php if (empty($catalog['items'])): ?>
    <div class="col-12">
        <div class="se-card p-5 text-center text-muted">
            <p class="mb-0">No matching products found in inventory.</p>
        </div>
    </div>
    <?php endif; ?>

    <?php foreach ($catalog['items'] as $p): ?>
    <div class="col-md-6 col-lg-4">
        <div class="se-card p-3 h-100 d-flex flex-column justify-content-between" data-tilt>
            <div>
                <!-- Thumbnail Preview Container -->
                <div style="position: relative; height: 170px; border-radius: 12px; background: var(--bg); overflow: hidden; display: flex; align-items: center; justify-content: center; margin-bottom: 14px;">
                    <?php if (!empty($p['image']) && file_exists(ROOT_PATH . '/' . $p['image'])): ?>
                        <img src="<?= asset($p['image']) ?>" alt="<?= e($p['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, rgba(109,91,240,0.12), rgba(147,51,234,0.18)); display: flex; align-items: center; justify-content: center;">
                            <svg style="width: 48px; height: 48px; color: var(--primary); opacity: 0.7;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($p['model_3d_path'])): ?>
                        <span class="status-pill" style="position: absolute; top: 10px; right: 10px; background: rgba(255,255,255,0.92); backdrop-filter: blur(8px); color: var(--primary); box-shadow: 0 2px 8px rgba(0,0,0,0.06); font-size: 11px; font-weight: 700;">
                            3D Model
                        </span>
                    <?php endif; ?>

                    <span class="status-pill neutral" style="position: absolute; top: 10px; left: 10px; font-size: 11px; background: rgba(255,255,255,0.9); backdrop-filter: blur(8px);">
                        <?= e($p['category_name']) ?>
                    </span>
                </div>

                <!-- Product Details -->
                <div class="d-flex justify-content-between align-items-baseline mb-1">
                    <span class="small text-muted" style="font-family: monospace; font-size: 11.5px;"><?= e($p['sku_code']) ?></span>
                    <?php if ((int)$p['stock_quantity'] <= 0): ?>
                        <span class="status-pill danger" style="padding: 2px 6px; font-size: 11px;">Out of stock</span>
                    <?php elseif ((int)$p['stock_quantity'] <= (int)$p['low_stock_level']): ?>
                        <span class="status-pill warning" style="padding: 2px 6px; font-size: 11px;">Low: <?= (int)$p['stock_quantity'] ?> left</span>
                    <?php else: ?>
                        <span class="status-pill success" style="padding: 2px 6px; font-size: 11px;"><?= (int)$p['stock_quantity'] ?> in stock</span>
                    <?php endif; ?>
                </div>

                <h2 class="h6 mb-2" style="font-size: 14.5px; line-height: 1.35;"><?= e($p['name']) ?></h2>
            </div>

            <div class="border-top pt-3 mt-2">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="total-val" style="font-size: 18px; color: var(--ink);">$<?= number_format((float)$p['selling_price'], 2) ?></span>
                    <button type="button" class="btn-ghost" style="padding: 4px 10px; font-size: 12px;" 
                            onclick='open3DModal(<?= json_encode($p, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>)'>
                        3D Inspect
                    </button>
                </div>

                <?php if ($isAdmin): ?>
                <div class="d-flex justify-content-end gap-2 pt-1 border-top" style="border-color: rgba(20,19,31,0.04) !important;">
                    <button type="button" class="btn-ghost" style="padding: 3px 10px; font-size: 11.5px;" 
                            onclick='editProduct(<?= json_encode($p, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>)'>
                        Edit
                    </button>
                    <form method="POST" onsubmit="return confirm('Archive/delete product <?= e($p['sku_code']) ?>?');" style="display:inline;">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button type="submit" class="status-pill danger border-0" style="cursor: pointer; padding: 4px 10px; font-size: 11.5px;">Archive</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Pagination Controls -->
<?php if ($catalog['totalPages'] > 1): ?>
<div class="d-flex justify-content-center gap-2 mt-4">
    <?php for ($i = 1; $i <= $catalog['totalPages']; $i++): ?>
        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category_id=<?= urlencode($categoryId ?? '') ?>&stock_status=<?= urlencode($stockStatus ?? '') ?>" 
           class="<?= ($i === $catalog['currentPage']) ? 'btn-primary-grad' : 'btn-ghost' ?>" 
           style="padding: 6px 14px; font-size: 13px;">
            <?= $i ?>
        </a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<!-- Modals & Scripts Partial -->
<?php require_once __DIR__ . '/../includes/product_modals.php'; ?>
<script src="<?= asset('js/tilt.js') ?>"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
