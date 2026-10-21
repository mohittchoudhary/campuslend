<?php
/**
 * CampusLend Leaderboard Controller
 */

namespace App\Controllers;

use App\Models\Badge;

class LeaderboardController extends Controller
{
    public function index(): void
    {
        $topHelpers = Badge::getLeaderboard(25);

        $this->render('pages/leaderboard/index', [
            'title' => 'Top Helpers Leaderboard - CampusLend',
            'helpers' => $topHelpers,
            'campusName' => config('app.campus_name', 'Uka Tarsadia University'),
        ]);
    }
}
