<?php
/**
 * Supplier - Supplier Data Access Object
 * Handles supplier management and inventory sourcing relations.
 */

class Supplier {
    public static function getAll(): array {
        $sql = "SELECT s.*, COUNT(p.id) as product_count 
                FROM `suppliers` s 
                LEFT JOIN `products` p ON p.supplier_id = s.id AND p.is_active = 1 
                GROUP BY s.id 
                ORDER BY s.name ASC";
        return Database::fetchAll($sql);
    }

    public static function getById(int $id): ?array {
        return Database::fetch("SELECT * FROM `suppliers` WHERE `id` = ?", [$id]);
    }

    public static function create(string $name, string $phone, ?string $address = null): array {
        $name = trim($name);
        $phone = trim($phone);

        if (empty($name) || empty($phone)) {
            return ['success' => false, 'message' => 'Supplier name and contact phone are required.'];
        }

        Database::query(
            "INSERT INTO `suppliers` (`name`, `phone`, `address`, `created_at`) VALUES (?, ?, ?, NOW())",
            [$name, $phone, $address]
        );

        return [
            'success' => true,
            'message' => 'Supplier registered successfully.',
            'id'      => Database::lastInsertId()
        ];
    }

    public static function update(int $id, string $name, string $phone, ?string $address = null): array {
        $name = trim($name);
        $phone = trim($phone);

        if (empty($name) || empty($phone)) {
            return ['success' => false, 'message' => 'Supplier name and contact phone are required.'];
        }

        Database::query(
            "UPDATE `suppliers` SET `name` = ?, `phone` = ?, `address` = ? WHERE `id` = ?",
            [$name, $phone, $address, $id]
        );

        return ['success' => true, 'message' => 'Supplier updated successfully.'];
    }

    public static function delete(int $id): array {
        // Disassociate products from deleted supplier (sets supplier_id to NULL)
        Database::query("UPDATE `products` SET `supplier_id` = NULL WHERE `supplier_id` = ?", [$id]);
        Database::query("DELETE FROM `suppliers` WHERE `id` = ?", [$id]);

        return ['success' => true, 'message' => 'Supplier deleted successfully.'];
    }
}
