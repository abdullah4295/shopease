<?php
/**
 * Product - Product Catalog & 3D Inventory Data Access Object
 * Handles product CRUD, search, category filtering, stock rules, and soft deletion.
 */

class Product {
    /**
     * Retrieve paginated products with filtering
     */
    public static function getPaginated(int $page = 1, int $perPage = 12, array $filters = []): array {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $where = ["p.is_active = 1"];
        $params = [];

        // Search by keyword (name or SKU)
        if (!empty($filters['search'])) {
            $where[] = "(p.name LIKE ? OR p.sku_code LIKE ?)";
            $searchTerm = '%' . trim($filters['search']) . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Filter by category
        if (!empty($filters['category_id'])) {
            $where[] = "p.category_id = ?";
            $params[] = (int)$filters['category_id'];
        }

        // Filter by stock level
        if (!empty($filters['stock_status'])) {
            if ($filters['stock_status'] === 'low') {
                $where[] = "p.stock_quantity <= p.low_stock_level AND p.stock_quantity > 0";
            } elseif ($filters['stock_status'] === 'out') {
                $where[] = "p.stock_quantity <= 0";
            }
        }

        $whereSql = implode(' AND ', $where);

        // Get total matching count
        $countSql = "SELECT COUNT(*) as total FROM `products` p WHERE {$whereSql}";
        $countStmt = Database::query($countSql, $params);
        $total = (int)$countStmt->fetch()['total'];

        // Get paginated items with joined names
        $sql = "SELECT p.*, c.name as category_name, s.name as supplier_name 
                FROM `products` p 
                LEFT JOIN `categories` c ON c.id = p.category_id 
                LEFT JOIN `suppliers` s ON s.id = p.supplier_id 
                WHERE {$whereSql} 
                ORDER BY p.id DESC 
                LIMIT {$perPage} OFFSET {$offset}";

        $items = Database::fetchAll($sql, $params);

        return [
            'items'       => $items,
            'total'       => $total,
            'totalPages'  => (int)ceil($total / $perPage),
            'currentPage' => $page,
            'perPage'     => $perPage
        ];
    }

    public static function getById(int $id): ?array {
        $sql = "SELECT p.*, c.name as category_name, s.name as supplier_name 
                FROM `products` p 
                LEFT JOIN `categories` c ON c.id = p.category_id 
                LEFT JOIN `suppliers` s ON s.id = p.supplier_id 
                WHERE p.id = ?";
        return Database::fetch($sql, [$id]);
    }

    public static function create(array $data): array {
        $validation = self::validate($data);
        if (!$validation['success']) {
            return $validation;
        }

        $sql = "INSERT INTO `products` 
                (`category_id`, `supplier_id`, `name`, `sku_code`, `cost_price`, `selling_price`, 
                 `stock_quantity`, `low_stock_level`, `expiry_date`, `image`, `model_3d_path`, 
                 `model_3d_usdz_path`, `is_active`, `created_at`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";

        Database::query($sql, [
            (int)$data['category_id'],
            !empty($data['supplier_id']) ? (int)$data['supplier_id'] : null,
            trim($data['name']),
            trim($data['sku_code']),
            (float)($data['cost_price'] ?? 0.00),
            (float)($data['selling_price'] ?? 0.00),
            (int)($data['stock_quantity'] ?? 0),
            (int)($data['low_stock_level'] ?? 5),
            !empty($data['expiry_date']) ? $data['expiry_date'] : null,
            $data['image'] ?? null,
            $data['model_3d_path'] ?? null,
            $data['model_3d_usdz_path'] ?? null
        ]);

        return [
            'success' => true,
            'message' => 'Product added to inventory successfully.',
            'id'      => Database::lastInsertId()
        ];
    }

    public static function update(int $id, array $data): array {
        $validation = self::validate($data, $id);
        if (!$validation['success']) {
            return $validation;
        }

        $fields = [
            "`category_id` = ?",
            "`supplier_id` = ?",
            "`name` = ?",
            "`sku_code` = ?",
            "`cost_price` = ?",
            "`selling_price` = ?",
            "`stock_quantity` = ?",
            "`low_stock_level` = ?",
            "`expiry_date` = ?"
        ];

        $params = [
            (int)$data['category_id'],
            !empty($data['supplier_id']) ? (int)$data['supplier_id'] : null,
            trim($data['name']),
            trim($data['sku_code']),
            (float)($data['cost_price'] ?? 0.00),
            (float)($data['selling_price'] ?? 0.00),
            (int)($data['stock_quantity'] ?? 0),
            (int)($data['low_stock_level'] ?? 5),
            !empty($data['expiry_date']) ? $data['expiry_date'] : null
        ];

        if (!empty($data['image'])) {
            $fields[] = "`image` = ?";
            $params[] = $data['image'];
        }

        if (array_key_exists('model_3d_path', $data) && $data['model_3d_path'] !== null) {
            $fields[] = "`model_3d_path` = ?";
            $params[] = $data['model_3d_path'];
        }

        if (array_key_exists('model_3d_usdz_path', $data) && $data['model_3d_usdz_path'] !== null) {
            $fields[] = "`model_3d_usdz_path` = ?";
            $params[] = $data['model_3d_usdz_path'];
        }

        $params[] = $id;
        $sql = "UPDATE `products` SET " . implode(', ', $fields) . " WHERE `id` = ?";
        Database::query($sql, $params);

        return ['success' => true, 'message' => 'Product updated successfully.'];
    }

    /**
     * Soft delete: products with prior sales are flagged is_active=0, never erased
     */
    public static function delete(int $id): array {
        $salesCount = Database::fetch(
            "SELECT COUNT(*) as cnt FROM `sale_items` WHERE `product_id` = ?",
            [$id]
        );

        if (!empty($salesCount['cnt']) && (int)$salesCount['cnt'] > 0) {
            // Soft delete
            Database::query("UPDATE `products` SET `is_active` = 0 WHERE `id` = ?", [$id]);
            return [
                'success' => true,
                'message' => 'Product has existing sales history; archived safely (soft deleted).'
            ];
        }

        // Hard delete if completely unused
        Database::query("DELETE FROM `products` WHERE `id` = ?", [$id]);
        return ['success' => true, 'message' => 'Product permanently removed.'];
    }

    private static function validate(array $data, ?int $ignoreId = null): array {
        if (empty($data['name']) || strlen(trim($data['name'])) < 2) {
            return ['success' => false, 'message' => 'Product title must be at least 2 characters.'];
        }

        if (empty($data['sku_code'])) {
            return ['success' => false, 'message' => 'Unique SKU code is required.'];
        }

        if (empty($data['category_id'])) {
            return ['success' => false, 'message' => 'Please select a valid product category.'];
        }

        // Check SKU uniqueness
        $sql = "SELECT `id` FROM `products` WHERE `sku_code` = ?";
        $params = [trim($data['sku_code'])];
        if ($ignoreId) {
            $sql .= " AND `id` != ?";
            $params[] = $ignoreId;
        }

        if (Database::fetch($sql, $params)) {
            return ['success' => false, 'message' => 'SKU code is already registered to another item.'];
        }

        if (isset($data['selling_price']) && (float)$data['selling_price'] < 0) {
            return ['success' => false, 'message' => 'Selling price cannot be negative.'];
        }

        return ['success' => true];
    }
}
