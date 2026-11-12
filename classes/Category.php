<?php
/**
 * Category - Category Data Access Object
 * Handles category CRUD operations and product relations.
 */

class Category {
    public static function getAll(): array {
        $sql = "SELECT c.*, COUNT(p.id) as product_count 
                FROM `categories` c 
                LEFT JOIN `products` p ON p.category_id = c.id AND p.is_active = 1 
                GROUP BY c.id 
                ORDER BY c.name ASC";
        return Database::fetchAll($sql);
    }

    public static function getById(int $id): ?array {
        return Database::fetch("SELECT * FROM `categories` WHERE `id` = ?", [$id]);
    }

    public static function create(string $name, ?string $description = null, ?string $iconPath = null): array {
        $name = trim($name);
        if (empty($name)) {
            return ['success' => false, 'message' => 'Category name is required.'];
        }

        $existing = Database::fetch("SELECT `id` FROM `categories` WHERE `name` = ?", [$name]);
        if ($existing) {
            return ['success' => false, 'message' => 'A category with this name already exists.'];
        }

        Database::query(
            "INSERT INTO `categories` (`name`, `description`, `icon_3d_path`, `created_at`) VALUES (?, ?, ?, NOW())",
            [$name, $description, $iconPath]
        );

        return [
            'success' => true,
            'message' => 'Category created successfully.',
            'id'      => Database::lastInsertId()
        ];
    }

    public static function update(int $id, string $name, ?string $description = null, ?string $iconPath = null): array {
        $name = trim($name);
        if (empty($name)) {
            return ['success' => false, 'message' => 'Category name is required.'];
        }

        $existing = Database::fetch("SELECT `id` FROM `categories` WHERE `name` = ? AND `id` != ?", [$name, $id]);
        if ($existing) {
            return ['success' => false, 'message' => 'Another category with this name already exists.'];
        }

        $sql = "UPDATE `categories` SET `name` = ?, `description` = ?" . 
               ($iconPath ? ", `icon_3d_path` = ?" : "") . 
               " WHERE `id` = ?";
        
        $params = $iconPath ? [$name, $description, $iconPath, $id] : [$name, $description, $id];
        Database::query($sql, $params);

        return ['success' => true, 'message' => 'Category updated successfully.'];
    }

    public static function delete(int $id): array {
        $productCount = Database::fetch(
            "SELECT COUNT(*) as cnt FROM `products` WHERE `category_id` = ? AND `is_active` = 1",
            [$id]
        );

        if (!empty($productCount['cnt']) && (int)$productCount['cnt'] > 0) {
            return [
                'success' => false,
                'message' => 'Cannot delete category: it currently contains active products.'
            ];
        }

        Database::query("DELETE FROM `categories` WHERE `id` = ?", [$id]);
        return ['success' => true, 'message' => 'Category deleted successfully.'];
    }
}
