<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\NotificationModel;

class NotificationController extends Controller
{
    public function getNotifications(): void
    {
        $user = Auth::user();
        if ($user === null) {
            $this->jsonError('Não autorizado', 401);
            return;
        }

        $model = new NotificationModel();
        $data = $model->getForUser((int) $user['id']);
        $this->jsonOk($data);
    }

    public function markAllRead(): void
    {
        $user = Auth::user();
        if ($user === null) {
            $this->jsonError('Não autorizado', 401);
            return;
        }

        $model = new NotificationModel();
        $model->markAllAsRead((int) $user['id']);
        $this->jsonOk(['success' => true]);
    }

    public function markRead(string $id): void
    {
        $user = Auth::user();
        if ($user === null) {
            $this->jsonError('Não autorizado', 401);
            return;
        }

        $model = new NotificationModel();
        $model->markAsRead((int) $id, (int) $user['id']);
        $this->jsonOk(['success' => true]);
    }
}
