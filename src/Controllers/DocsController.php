<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\DocArticle;

class DocsController extends Controller
{
    public function index(): void
    {
        $user = Auth::user();
        if ($user === null || $user['role'] !== 'Admin') {
            $this->redirect('/');
            return;
        }

        $articles = DocArticle::all();

        $this->render('sistema_docs/index', [
            'title' => 'Manual do Sistema - Documentação Técnica',
            'user' => $user,
            'page' => 'sistema_docs',
            'articles' => $articles,
        ], 'header');
    }

    public function show(string $slug): void
    {
        $user = Auth::user();
        if ($user === null || $user['role'] !== 'Admin') {
            $this->redirect('/');
            return;
        }

        $article = DocArticle::findBySlug($slug);

        if ($article === null) {
            http_response_code(404);
            $this->render('errors/404', [
                'title' => 'Artigo Não Encontrado',
                'user' => $user,
                'page' => 'sistema_docs',
            ], 'header');
            return;
        }

        $allArticles = DocArticle::all();

        $this->render('sistema_docs/show', [
            'title' => $article['title'] . ' - Manual do Sistema',
            'user' => $user,
            'page' => 'sistema_docs',
            'article' => $article,
            'allArticles' => $allArticles,
        ], 'header');
    }
}
