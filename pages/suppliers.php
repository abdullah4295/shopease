<?php
/**
 * Suppliers Management - ShopEase
 * Manage wholesale suppliers, phone contacts, and product distribution sources.
 * Role Guard: Admin Only
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/error_handler.php';

Auth::requireRole('admin');

$msg = null;
$msgType = 'info';

// Process CRUD Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $res = Supplier::create($_POST['name'] ?? '', $_POST['phone'] ?? '', $_POST['address'] ?? null);
        $msg = $res['message'];
        $msgType = $res['success'] ? 'success' : 'danger';
    } elseif ($action === 'update' && !empty($_POST['id'])) {
        $res = Supplier::update((int)$_POST['id'], $_POST['name'] ?? '', $_POST['phone'] ?? '', $_POST['address'] ?? null);
        $msg = $res['message'];
        $msgType = $res['success'] ? 'success' : 'danger';
    } elseif ($action === 'delete' && !empty($_POST['id'])) {
        $res = Supplier::delete((int)$_POST['id']);
        $msg = $res['message'];
        $msgType = $res['success'] ? 'success' : 'danger';
    }
}

$suppliers = Supplier::getAll();
$pageTitle = 'Suppliers - ShopEase';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">Wholesale <span class="grad-text">Suppliers</span></h1>
        <p class="text-muted small mb-0">Manage partner vendors, contact phones, and inventory sourcing.</p>
    </div>
    <button class="btn-primary-grad" onclick="openSupplierModal()">
        <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        <span>Add Supplier</span>
    </button>
</div>

<?php if ($msg): ?>
<div class="status-pill <?= e($msgType) ?> w-100 mb-4 py-2 justify-content-center">
    <?= e($msg) ?>
</div>
<?php endif; ?>

<!-- Suppliers Grid -->
<div class="row g-3">
    <?php foreach ($suppliers as $sup): ?>
    <div class="col-md-6 col-lg-4">
        <div class="se-card p-4 h-100 d-flex flex-column justify-content-between" data-tilt>
            <div>
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h2 class="h5 mb-0"><?= e($sup['name']) ?></h2>
                    <span class="status-pill neutral"><?= (int)$sup['product_count'] ?> SKUs</span>
                </div>
                <div class="d-flex align-items-center gap-2 text-muted small mb-2">
                    <svg style="width: 15px; height: 15px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                    <span><?= e($sup['phone']) ?></span>
                </div>
                <p class="text-muted small mb-3">
                    <?= e($sup['address'] ?: 'No address specified.') ?>
                </p>
            </div>

            <div class="d-flex justify-content-end gap-2 border-top pt-3">
                <button type="button" class="btn-ghost" style="padding: 4px 12px; font-size: 12px;" 
                        onclick='editSupplier(<?= json_encode($sup, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>)'>
                    Edit
                </button>
                <form method="POST" onsubmit="return confirm('Delete this supplier? Attached products will be kept safely.');" style="display:inline;">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$sup['id'] ?>">
                    <button type="submit" class="status-pill danger border-0" style="cursor: pointer; padding: 6px 12px;">Delete</button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Supplier Modal -->
<div id="supplierModal" style="display:none; position:fixed; inset:0; background:rgba(20,19,31,0.5); backdrop-filter:blur(4px); z-index:1050; align-items:center; justify-content:center; padding:20px;">
    <div class="se-card se-card-lg p-4" style="width: 100%; max-width: 440px; background: #FFFFFF;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="h5 mb-0" id="supModalTitle">Add Supplier</h3>
            <button type="button" class="btn-ghost" style="padding: 4px 10px;" onclick="closeSupplierModal()">✕</button>
        </div>

        <form method="POST" id="supplierForm">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" id="supAction" value="create">
            <input type="hidden" name="id" id="supId" value="">

            <div class="mb-3">
                <label class="form-label small fw-semibold text-muted mb-1">Company / Supplier Name</label>
                <input type="text" name="name" id="supName" class="se-input" placeholder="e.g. Apex Tech Imports Ltd" required>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold text-muted mb-1">Phone Number</label>
                <input type="text" name="phone" id="supPhone" class="se-input" placeholder="+880 1711-XXXXXX" required>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-semibold text-muted mb-1">Address / Depot</label>
                <textarea name="address" id="supAddress" class="se-input" rows="3" placeholder="Office or warehouse location..."></textarea>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn-ghost" onclick="closeSupplierModal()">Cancel</button>
                <button type="submit" class="btn-primary-grad">Save Supplier</button>
            </div>
        </form>
    </div>
</div>

<script src="<?= asset('js/tilt.js') ?>"></script>
<script>
function openSupplierModal() {
    document.getElementById('supModalTitle').textContent = 'Add Supplier';
    document.getElementById('supAction').value = 'create';
    document.getElementById('supId').value = '';
    document.getElementById('supName').value = '';
    document.getElementById('supPhone').value = '';
    document.getElementById('supAddress').value = '';
    document.getElementById('supplierModal').style.display = 'flex';
}

function editSupplier(sup) {
    document.getElementById('supModalTitle').textContent = 'Edit Supplier';
    document.getElementById('supAction').value = 'update';
    document.getElementById('supId').value = sup.id;
    document.getElementById('supName').value = sup.name;
    document.getElementById('supPhone').value = sup.phone;
    document.getElementById('supAddress').value = sup.address || '';
    document.getElementById('supplierModal').style.display = 'flex';
}

function closeSupplierModal() {
    document.getElementById('supplierModal').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
