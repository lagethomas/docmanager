<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Helpers\SecurityHelper;
use App\Models\DocumentModel;
use App\Models\SharedLinkModel;

class SharedLinkController extends Controller
{
    public function getLinks(string $docId): void
    {
        $id = (int) $docId;
        $model = new SharedLinkModel();
        $links = $model->getByDocument($id);
        $this->jsonOk($links);
    }

    public function createLink(string $docId): void
    {
        $user = Auth::user();
        if ($user === null) {
            $this->jsonError('Não autorizado', 401);
            return;
        }

        $id = (int) $docId;
        $input = self::input();
        $password = (string) ($input['password'] ?? '');
        $expiresAt = (string) ($input['expires_at'] ?? '');

        $model = new SharedLinkModel();
        $link = $model->createLink($id, (int) $user['id'], $password !== '' ? $password : null, $expiresAt !== '' ? $expiresAt : null);

        $this->jsonOk($link, 201);
    }

    public function deleteLink(string $linkId): void
    {
        $user = Auth::user();
        if ($user === null) {
            $this->jsonError('Não autorizado', 401);
            return;
        }

        $model = new SharedLinkModel();
        $model->deleteLink((int) $linkId);
        $this->jsonOk(['success' => true]);
    }

    public function showPublic(string $token): void
    {
        $model = new SharedLinkModel();
        $link = $model->findByToken($token);

        if ($link === null) {
            $this->render('errors/404', ['title' => 'Link expirado ou inexistente'], 'public_header');
            return;
        }

        if (!empty($link['expires_at']) && strtotime((string) $link['expires_at']) < time()) {
            $this->render('errors/404', ['title' => 'Este link expirou'], 'public_header');
            return;
        }

        $model->incrementViews((int) $link['id']);

        $this->render('docs/public_share', [
            'title' => (string) $link['title'],
            'doc' => $link,
        ], 'public_header');
    }
}
