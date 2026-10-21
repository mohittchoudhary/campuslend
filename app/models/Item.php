<?php
/**
 * CampusLend Lend Item Listing Model
 */

namespace App\Models;

class Item extends Model
{
    protected static string $table = 'items';

    /**
     * Browse available catalog with category & condition filters
     */
    public static function getAvailable(array $filters = [], int $limit = 24, int $offset = 0): array
    {
        $where = ["i.is_available = 1", "i.is_paused = 0"];
        $params = [];

        // Category Filter
        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            if (is_numeric($filters['category'])) {
                $where[] = "i.category_id = :cat_id";
                $params[':cat_id'] = (int)$filters['category'];
            } else {
                $where[] = "c.slug = :cat_slug";
                $params[':cat_slug'] = $filters['category'];
            }
        }

        // Condition Filter
        if (!empty($filters['condition']) && $filters['condition'] !== 'all') {
            $where[] = "i.item_condition = :condition";
            $params[':condition'] = $filters['condition'];
        }

        // Search Query
        if (!empty($filters['search'])) {
            $where[] = "(i.title LIKE :search1 OR i.description LIKE :search2 OR i.pickup_location LIKE :search3)";
            $term = '%' . trim($filters['search']) . '%';
            $params[':search1'] = $term;
            $params[':search2'] = $term;
            $params[':search3'] = $term;
        }

        $whereSql = implode(' AND ', $where);

        $sql = "
            SELECT i.*, 
                   c.name as category_name, c.slug as category_slug, c.icon as category_icon,
                   u.full_name as owner_name, u.department, u.trust_score, u.avatar
            FROM items i
            JOIN categories c ON i.category_id = c.id
            JOIN users u ON i.user_id = u.id
            WHERE {$whereSql}
            ORDER BY i.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";

        $countSql = "
            SELECT COUNT(*) 
            FROM items i 
            JOIN categories c ON i.category_id = c.id 
            WHERE {$whereSql}
        ";

        $stmt = self::pdo()->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $items = self::fetchAll($sql, $params);

        return [
            'items' => $items,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    /**
     * Retrieve listing details with owner info
     */
    public static function getWithDetails(int $id): ?array
    {
        $sql = "
            SELECT i.*, 
                   c.name as category_name, c.slug as category_slug, c.icon as category_icon,
                   u.full_name as owner_name, u.department, u.year_of_study, u.phone as owner_phone,
                   u.email as owner_email, u.trust_score, u.total_ratings, u.avatar
            FROM items i
            JOIN categories c ON i.category_id = c.id
            JOIN users u ON i.user_id = u.id
            WHERE i.id = :id
            LIMIT 1
        ";

        return self::fetch($sql, [':id' => $id]);
    }

    /**
     * Create lend listing
     */
    public static function createListing(array $data): int
    {
        return self::insert([
            'user_id' => (int)$data['user_id'],
            'category_id' => (int)$data['category_id'],
            'title' => trim($data['title']),
            'description' => trim($data['description'] ?? ''),
            'photo' => $data['photo'] ?? null,
            'item_condition' => in_array($data['item_condition'], ['Like New', 'Good', 'Fair', 'Usable']) ? $data['item_condition'] : 'Good',
            'pickup_location' => trim($data['pickup_location']),
            'max_duration_hours' => max(1, (int)($data['max_duration_hours'] ?? 24)),
            'is_available' => 1,
            'is_paused' => 0,
        ]);
    }

    /**
     * Toggle paused state
     */
    public static function togglePause(int $id, int $userId): bool
    {
        $item = self::find($id);
        if (!$item || (int)$item['user_id'] !== $userId) {
            return false;
        }

        $newPaused = (int)$item['is_paused'] === 1 ? 0 : 1;
        return self::update($id, ['is_paused' => $newPaused]);
    }

    /**
     * Get user's active and paused listings
     */
    public static function getUserListings(int $userId): array
    {
        $sql = "
            SELECT i.*, c.name as category_name, c.icon as category_icon
            FROM items i
            JOIN categories c ON i.category_id = c.id
            WHERE i.user_id = :uid
            ORDER BY i.id DESC
        ";
        return self::fetchAll($sql, [':uid' => $userId]);
    }
}
