<?php
/**
 * CampusLend Smart Matcher Client
 * Connects PHP platform with Python Microservice for AI-powered lender ranking.
 * Implements resilient fallback to native SQL/PHP ranking if Python is offline.
 */

namespace App\Services;

use App\Models\Item;
use App\Models\BorrowRequest;
use Exception;

class SmartMatcher
{
    private static string $pythonEndpoint = 'http://127.0.0.1:5000/match';

    /**
     * Get ranked suggested lenders for an active emergency request
     *
     * @param array $request
     * @param int $limit
     * @return array ['source' => 'python_ai'|'php_fallback', 'matches' => array]
     */
    public static function suggestLenders(array $request, int $limit = 5): array
    {
        $reqId = (int)$request['id'];
        $categoryId = (int)$request['category_id'];
        $borrowerId = (int)$request['user_id'];
        $reqBlock = trim($request['building_block'] ?? '');

        // 1. Fetch potential available candidate items from DB (excluding borrower's own items)
        $candidates = Item::fetchAll("
            SELECT i.id, i.user_id, i.title, i.category_id, i.photo, i.item_condition,
                   i.pickup_location, i.pickup_location as building_block, i.max_duration_hours, i.is_available, i.is_paused,
                   u.full_name, u.email, u.trust_score, u.department
            FROM items i
            JOIN users u ON i.user_id = u.id
            WHERE i.user_id != :borrower_id
              AND i.is_available = 1
              AND i.is_paused = 0
              AND u.is_banned = 0
            LIMIT 50
        ", [':borrower_id' => $borrowerId]);

        if (empty($candidates)) {
            return [
                'source' => 'none',
                'matches' => [],
            ];
        }

        // 2. Attempt Python Microservice Ranking via HTTP POST
        try {
            $payload = json_encode([
                'request' => [
                    'id' => $reqId,
                    'title' => $request['title'],
                    'category_id' => $categoryId,
                    'building_block' => $reqBlock,
                    'urgency' => $request['urgency'] ?? 'normal',
                ],
                'candidates' => $candidates,
            ]);

            $ch = curl_init(self::$pythonEndpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 2); // 2-second strict timeout
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($httpCode === 200 && !empty($response)) {
                $data = json_decode($response, true);
                if (!empty($data['matches'])) {
                    return [
                        'source' => 'python_ai',
                        'matches' => array_slice($data['matches'], 0, $limit),
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Python service unreachable - proceed to graceful fallback
        }

        // 3. Resilient Pure PHP/SQL Fallback Algorithm
        // Used when Python service is offline or unreachable
        $ranked = [];
        $reqTokens = array_filter(explode(' ', strtolower($request['title'])));

        foreach ($candidates as $item) {
            $score = 0.0;
            $reasons = [];

            // Category match (+40)
            if ((int)$item['category_id'] === $categoryId) {
                $score += 40.0;
                $reasons[] = "Category Match (+40)";
            }

            // Proximity match (+30)
            $itemBlock = strtolower(trim($item['building_block'] ?? ''));
            if (!empty($reqBlock) && !empty($itemBlock) && (str_contains($itemBlock, strtolower($reqBlock)) || str_contains(strtolower($reqBlock), $itemBlock))) {
                $score += 30.0;
                $reasons[] = "Same Block: {$item['building_block']} (+30)";
            }

            // Trust score (+20)
            $trust = (float)($item['trust_score'] ?? 5.0);
            $trustPts = round(($trust / 5.0) * 20.0, 1);
            $score += $trustPts;
            $reasons[] = "Trust Score {$trust} (+{$trustPts})";

            // Keyword match (+10)
            $itemTitleLower = strtolower($item['title']);
            foreach ($reqTokens as $token) {
                if (strlen($token) > 2 && str_contains($itemTitleLower, $token)) {
                    $score += 10.0;
                    $reasons[] = "Keyword match (+10)";
                    break;
                }
            }

            $item['match_score'] = min($score, 100.0);
            $item['match_reasons'] = $reasons;
            $ranked[] = $item;
        }

        usort($ranked, fn($a, $b) => $b['match_score'] <=> $a['match_score']);

        return [
            'source' => 'php_fallback',
            'matches' => array_slice($ranked, 0, $limit),
        ];
    }
}
