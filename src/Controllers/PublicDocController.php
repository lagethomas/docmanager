<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CategoryModel;
use App\Models\DocumentModel;
use App\Services\PdfService;

class PublicDocController extends Controller
{
    public function index(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $catId = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int) $_GET['category_id'] : null;
        $sort = (string) ($_GET['sort'] ?? 'updated_desc');
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $settingsModel = new \App\Models\SettingsModel();
        $perPage = (int) $settingsModel->get('per_page', '10');
        if ($perPage <= 0) {
            $perPage = 10;
        }

        $docModel = new DocumentModel();
        $totalDocs = $docModel->countPublicDocs($q, $catId);
        $totalPages = max(1, (int) ceil($totalDocs / $perPage));

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $docs = $docModel->getPublicDocs($q, $catId, $page, $perPage, $sort);
        $publicStats = $docModel->getPublicStats();

        $catModel = new CategoryModel();
        $categories = $catModel->withPublicCounts();

        $this->render('docs/public_catalog', [
            'title' => 'Biblioteca — Documentos Públicos',
            'docs' => $docs,
            'categories' => $categories,
            'publicStats' => $publicStats,
            'query' => $q,
            'categoryId' => $catId,
            'sort' => $sort,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalDocs' => $totalDocs,
            'perPage' => $perPage,
            'page' => 'public_catalog',
        ], 'public_header');
    }

    public function show(string $id): void
    {
        $intId = (int) $id;
        $docModel = new DocumentModel();
        $doc = $docModel->findPublicDoc($intId);

        if ($doc === null) {
            http_response_code(404);
            $this->render('errors/404', [
                'title' => 'Documento Não Encontrado',
            ], 'public_header');
            return;
        }

        $docModel->incrementViews($intId);
        $doc['views_count'] = ((int) ($doc['views_count'] ?? 0)) + 1;
        $doc['content'] = \App\Helpers\SecurityHelper::maskPskTags((string) ($doc['content'] ?? ''));

        $this->render('docs/public_view', [
            'title' => ($doc['title'] ?? 'Documento') . ' — Biblioteca',
            'doc' => $doc,
            'page' => 'public_view',
        ], 'public_header');
    }

    public function exportPdf(string $id): void
    {
        $intId = (int) $id;
        $docModel = new DocumentModel();
        $doc = $docModel->findPublicDoc($intId);

        if ($doc === null) {
            http_response_code(404);
            $this->render('errors/404', [
                'title' => 'Documento Não Encontrado',
            ], 'public_header');
            return;
        }

        $doc['content'] = \App\Helpers\SecurityHelper::maskPskTags((string) ($doc['content'] ?? ''));

        $download = isset($_GET['download']) && $_GET['download'] === '1';

        $pdfService = new PdfService();
        $pdfService->renderDocumentPdf($doc, $download);
    }
}
