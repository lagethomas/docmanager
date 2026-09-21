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

        $userId = isset($user['id']) ? (int) $user['id'] : null;
        $userRole = (string) ($user['role'] ?? 'Visualizador');
        $locationId = Auth::locationId();
        $areaId = Auth::areaId();

        $stats = $docModel->getStats(
            userId: $userId,
            userRole: $userRole,
            userLocationId: $locationId,
            userAreaId: $areaId
        );

        $stats['total_users'] = $userModel->count();

        $recentDocs = $docModel->getRecentPublished(
            limit: 10,
            userId: $userId,
            userRole: $userRole,
            userLocationId: $locationId,
            userAreaId: $areaId
        );
        $mostViewedDocs = $docModel->getMostViewed(
            limit: 10,
            userId: $userId,
            userRole: $userRole,
            userLocationId: $locationId,
            userAreaId: $areaId
        );

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

