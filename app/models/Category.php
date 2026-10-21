<?php
/**
 * CampusLend Category Model
 */

namespace App\Models;

class Category extends Model
{
    protected static string $table = 'categories';

    public static function allOrdered(): array
    {
        return self::all('id ASC');
    }

    public static function findBySlug(string $slug): ?array
    {
        return self::findBy('slug', trim($slug));
    }

    public static function getWithItemCounts(): array
    {
        $sql = "
            SELECT c.*, COUNT(i.id) as item_count 
            FROM categories c 
            LEFT JOIN items i ON c.id = i.category_id AND i.is_available = 1 AND i.is_paused = 0 
            GROUP BY c.id 
            ORDER BY c.id ASC
        ";
        return self::fetchAll($sql);
    }

    public static function createCategory(string $name, string $icon, ?string $description = null): int
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        return self::insert([
            'name' => trim($name),
            'slug' => $slug,
            'icon' => trim($icon) ?: '📦',
            'description' => trim($description ?? ''),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function updateCategory(int $id, string $name, string $icon, ?string $description = null): bool
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        return self::update($id, [
            'name' => trim($name),
            'slug' => $slug,
            'icon' => trim($icon) ?: '📦',
            'description' => trim($description ?? ''),
        ]);
    }

    public static function deleteCategory(int $id): bool
    {
        return self::delete($id);
    }
}

