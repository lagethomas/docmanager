<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\CSRF;
use App\Models\DocumentModel;
use App\Models\UserModel;

class HomeController extends Controller
{
    public function index(): void
    {
        $user = Auth::user();
        $docModel = new DocumentModel();
        $userModel = new UserModel();

        $stats = $docModel->getStats(
            userId: isset($user['id']) ? (int) $user['id'] : null,
            userRole: $user['role'] ?? null
        );

        $stats['total_users'] = $userModel->count();

        $recentDocs = $docModel->getRecentPublished(10);
        $mostViewedDocs = $docModel->getMostViewed(10);

        $this->render('home/index', [
            'title' => 'Dashboard — DocManager',
            'user' => $user,
            'csrfToken' => CSRF::token(),
            'page' => 'home',
            'stats' => $stats,
            'recentDocs' => $recentDocs,
            'mostViewedDocs' => $mostViewedDocs,
        ], 'header');
    }
}

