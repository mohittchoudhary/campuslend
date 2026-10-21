<?php
/**
 * CampusLend Emergency Borrow Request Model
 */

namespace App\Models;

class BorrowRequest extends Model
{
    protected static string $table = 'requests';

    /**
     * Automatically mark expired requests whose needed_by time has elapsed
     */
    public static function autoExpireOldRequests(): int
    {
        $now = date('Y-m-d H:i:s');
        $sql = "UPDATE requests SET status = 'expired' WHERE status = 'open' AND needed_by < :now";
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute([':now' => $now]);
        return $stmt->rowCount();
    }

    /**
     * Retrieve live emergency feed with compound filtering, search, and sorting
     */
    public static function getFeed(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        self::autoExpireOldRequests();

        $where = ["1=1"];
        $params = [];

        // Status Filter
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $where[] = "r.status = :status";
            $params[':status'] = $filters['status'];
        } else {
            // Default show active requests
            $where[] = "r.status IN ('open', 'offered')";
        }

        // Category Filter
        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            if (is_numeric($filters['category'])) {
                $where[] = "r.category_id = :category_id";
                $params[':category_id'] = (int)$filters['category'];
            } else {
                $where[] = "c.slug = :category_slug";
                $params[':category_slug'] = $filters['category'];
            }
        }

        // Urgency Filter
        if (!empty($filters['urgency']) && $filters['urgency'] !== 'all') {
            $where[] = "r.urgency = :urgency";
            $params[':urgency'] = $filters['urgency'];
        }

        // Campus Block Filter
        if (!empty($filters['block']) && $filters['block'] !== 'all') {
            $where[] = "r.campus_block = :block";
            $params[':block'] = $filters['block'];
        }

        // Search Query
        if (!empty($filters['search'])) {
            $where[] = "(r.item_name LIKE :search1 OR r.note LIKE :search2 OR r.room_location LIKE :search3)";
            $term = '%' . trim($filters['search']) . '%';
            $params[':search1'] = $term;
            $params[':search2'] = $term;
            $params[':search3'] = $term;
        }

        $whereSql = implode(' AND ', $where);

        // Sorting: Critical first, then High, then Normal, then nearest needed_by
        $sql = "
            SELECT r.*, 
                   c.name as category_name, c.slug as category_slug, c.icon as category_icon,
                   u.full_name as student_name, u.department, u.year_of_study, u.trust_score, u.avatar,
                   (SELECT COUNT(*) FROM offers o WHERE o.request_id = r.id) as offer_count
            FROM requests r
            JOIN categories c ON r.category_id = c.id
            JOIN users u ON r.user_id = u.id
            WHERE {$whereSql}
            ORDER BY 
                CASE 
                    WHEN r.urgency = 'Critical' THEN 1 
                    WHEN r.urgency = 'High' THEN 2 
                    ELSE 3 
                END,
                r.needed_by ASC,
                r.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";

        $countSql = "
            SELECT COUNT(*) 
            FROM requests r 
            JOIN categories c ON r.category_id = c.id 
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
     * Retrieve single request with category and requester profile
     */
    public static function getWithDetails(int $id): ?array
    {
        self::autoExpireOldRequests();

        $sql = "
            SELECT r.*, 
                   c.name as category_name, c.slug as category_slug, c.icon as category_icon,
                   u.full_name as student_name, u.department, u.year_of_study, u.phone as student_phone, 
                   u.email as student_email, u.trust_score, u.total_ratings, u.avatar
            FROM requests r
            JOIN categories c ON r.category_id = c.id
            JOIN users u ON r.user_id = u.id
            WHERE r.id = :id
            LIMIT 1
        ";

        return self::fetch($sql, [':id' => $id]);
    }

    /**
     * Create emergency borrow request
     */
    public static function createRequest(array $data): int
    {
        return self::insert([
            'user_id' => (int)$data['user_id'],
            'category_id' => (int)$data['category_id'],
            'item_name' => trim($data['item_name']),
            'urgency' => in_array($data['urgency'], ['Critical', 'High', 'Normal']) ? $data['urgency'] : 'Normal',
            'campus_block' => trim($data['campus_block']),
            'room_location' => trim($data['room_location']),
            'needed_by' => $data['needed_by'],
            'note' => trim($data['note'] ?? ''),
            'status' => 'open',
        ]);
    }

    /**
     * Get user's own requests
     */
    public static function getUserRequests(int $userId): array
    {
        $sql = "
            SELECT r.*, c.name as category_name, c.icon as category_icon,
                   (SELECT COUNT(*) FROM offers o WHERE o.request_id = r.id) as offer_count
            FROM requests r
            JOIN categories c ON r.category_id = c.id
            WHERE r.user_id = :uid
            ORDER BY r.id DESC
        ";
        return self::fetchAll($sql, [':uid' => $userId]);
    }
}
