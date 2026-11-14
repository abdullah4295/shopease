<?php
/**
 * Product Modals Partial - ShopEase
 * Add/Edit Product Modal & Interactive 3D Model Inspection Modal
 */
?>
<!-- 3D Inspection Modal (<model-viewer>) -->
<div id="product3DModal" style="display:none; position:fixed; inset:0; background:rgba(20,19,31,0.65); backdrop-filter:blur(8px); z-index:1060; align-items:center; justify-content:center; padding:20px;">
    <div class="se-card se-card-lg p-4" style="width: 100%; max-width: 580px; background: #FFFFFF;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="h5 mb-0" id="modal3DTitle">Interactive 3D View</h3>
                <span class="text-muted small" id="modal3DSKU"></span>
            </div>
            <button type="button" class="btn-ghost" style="padding: 4px 10px;" onclick="close3DModal()">✕</button>
        </div>

        <div id="modelViewerWrapper" style="width: 100%; height: 320px; background: radial-gradient(circle, #FAF9FF 0%, #EFEBFD 100%); border-radius: 14px; overflow: hidden; display: flex; align-items: center; justify-content: center; position: relative;">
            <!-- Dynamic model-viewer inserted here -->
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
            <span class="small text-muted" id="modal3DPrice"></span>
            <button type="button" class="btn-solid-dark" onclick="close3DModal()">Done</button>
        </div>
    </div>
</div>

<!-- Add/Edit Product Modal (Admin Only) -->
<?php if ($isAdmin): ?>
<div id="productModal" style="display:none; position:fixed; inset:0; background:rgba(20,19,31,0.5); backdrop-filter:blur(4px); z-index:1050; align-items:center; justify-content:center; padding:20px; overflow-y:auto;">
    <div class="se-card se-card-lg p-4 my-4" style="width: 100%; max-width: 600px; background: #FFFFFF;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="h5 mb-0" id="prodModalTitle">Add New Product</h3>
            <button type="button" class="btn-ghost" style="padding: 4px 10px;" onclick="closeProductModal()">✕</button>
        </div>

        <form method="POST" enctype="multipart/form-data" id="productForm">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" id="prodAction" value="create">
            <input type="hidden" name="id" id="prodId" value="">

            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label small fw-semibold text-muted mb-1">Product Title</label>
                    <input type="text" name="name" id="prodName" class="se-input" placeholder="e.g. AeroSound Pro Headphones" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted mb-1">SKU Code</label>
                    <input type="text" name="sku_code" id="prodSku" class="se-input" placeholder="SKU-XXX-001" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-muted mb-1">Category</label>
                    <select name="category_id" id="prodCat" class="se-input" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-muted mb-1">Supplier</label>
                    <select name="supplier_id" id="prodSup" class="se-input">
                        <option value="">No Supplier</option>
                        <?php foreach ($suppliers as $s): ?>
                            <option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-muted mb-1">Cost Price ($)</label>
                    <input type="number" step="0.01" name="cost_price" id="prodCost" class="se-input" value="0.00" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-muted mb-1">Selling Price ($)</label>
                    <input type="number" step="0.01" name="selling_price" id="prodPrice" class="se-input" value="0.00" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted mb-1">Stock Quantity</label>
                    <input type="number" name="stock_quantity" id="prodStock" class="se-input" value="10" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted mb-1">Low Stock Alert</label>
                    <input type="number" name="low_stock_level" id="prodLow" class="se-input" value="5" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted mb-1">Expiry Date</label>
                    <input type="date" name="expiry_date" id="prodExpiry" class="se-input">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-muted mb-1">2D Product Image</label>
                    <input type="file" name="image" class="se-input" accept="image/jpeg,image/png,image/webp">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-muted mb-1">3D Model File (.glb / .usdz)</label>
                    <input type="file" name="model_3d" class="se-input" accept=".glb,.gltf,.usdz">
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <button type="button" class="btn-ghost" onclick="closeProductModal()">Cancel</button>
                <button type="submit" class="btn-primary-grad">Save to Inventory</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function open3DModal(product) {
    document.getElementById('modal3DTitle').textContent = product.name;
    document.getElementById('modal3DSKU').textContent = product.sku_code + ' • ' + product.category_name;
    document.getElementById('modal3DPrice').textContent = 'Selling Price: $' + parseFloat(product.selling_price).toFixed(2);

    const wrapper = document.getElementById('modelViewerWrapper');
    wrapper.innerHTML = '';

    if (product.model_3d_path) {
        const mv = document.createElement('model-viewer');
        mv.setAttribute('src', '<?= url('') ?>/' + product.model_3d_path);
        mv.setAttribute('alt', product.name);
        mv.setAttribute('auto-rotate', '');
        mv.setAttribute('camera-controls', '');
        mv.setAttribute('ar', '');
        mv.setAttribute('loading', 'lazy');
        mv.style.width = '100%';
        mv.style.height = '100%';
        wrapper.appendChild(mv);
    } else {
        wrapper.innerHTML = `
            <div class="text-center p-4">
                <p class="text-muted small mb-0">No .glb 3D model uploaded for this product yet.<br>Showing standard 2D view.</p>
            </div>
        `;
    }

    document.getElementById('product3DModal').style.display = 'flex';
}

function close3DModal() {
    document.getElementById('product3DModal').style.display = 'none';
    document.getElementById('modelViewerWrapper').innerHTML = '';
}

function openProductModal() {
    document.getElementById('prodModalTitle').textContent = 'Add New Product';
    document.getElementById('prodAction').value = 'create';
    document.getElementById('prodId').value = '';
    document.getElementById('prodName').value = '';
    document.getElementById('prodSku').value = '';
    document.getElementById('prodCat').value = '';
    document.getElementById('prodSup').value = '';
    document.getElementById('prodCost').value = '0.00';
    document.getElementById('prodPrice').value = '0.00';
    document.getElementById('prodStock').value = '10';
    document.getElementById('prodLow').value = '5';
    document.getElementById('prodExpiry').value = '';
    document.getElementById('productModal').style.display = 'flex';
}

function editProduct(p) {
    document.getElementById('prodModalTitle').textContent = 'Edit Product';
    document.getElementById('prodAction').value = 'update';
    document.getElementById('prodId').value = p.id;
    document.getElementById('prodName').value = p.name;
    document.getElementById('prodSku').value = p.sku_code;
    document.getElementById('prodCat').value = p.category_id;
    document.getElementById('prodSup').value = p.supplier_id || '';
    document.getElementById('prodCost').value = p.cost_price;
    document.getElementById('prodPrice').value = p.selling_price;
    document.getElementById('prodStock').value = p.stock_quantity;
    document.getElementById('prodLow').value = p.low_stock_level;
    document.getElementById('prodExpiry').value = p.expiry_date || '';
    document.getElementById('productModal').style.display = 'flex';
}

function closeProductModal() {
    document.getElementById('productModal').style.display = 'none';
}
</script>
