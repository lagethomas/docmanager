<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;

class ReportController extends Controller
{
    public function index(): void
    {
        $user = Auth::user();
        if ($user === null || ((string) $user['role'] !== 'Admin' && (string) $user['role'] !== 'Aprovador')) {
            $this->redirect('/');
            return;
        }

        $db = Database::getInstance();

        // Totalizadores KPI
        $totalDocs = (int) $db->query('SELECT COUNT(*) FROM cp_documents WHERE deleted_at IS NULL')->fetchColumn();
        $totalApproved = (int) $db->query("SELECT COUNT(*) FROM cp_documents WHERE status = 'aprovado' AND deleted_at IS NULL")->fetchColumn();
        $totalPending = (int) $db->query("SELECT COUNT(*) FROM cp_documents WHERE status = 'pendente' AND deleted_at IS NULL")->fetchColumn();
        $totalRejected = (int) $db->query("SELECT COUNT(*) FROM cp_documents WHERE status = 'rejeitado' AND deleted_at IS NULL")->fetchColumn();
        $totalLogs = (int) $db->query('SELECT COUNT(*) FROM cp_logs')->fetchColumn();

        // Lista de ações distintas para o filtro
        $actionsStmt = $db->query('SELECT DISTINCT action FROM cp_logs ORDER BY action ASC');
        $actions = $actionsStmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];

        // Lista de usuários para o filtro
        $usersStmt = $db->query('SELECT id, login, name FROM cp_users ORDER BY name ASC');
        $usersList = $usersStmt->fetchAll() ?: [];

        // Logs recentes (limite 500 para auditoria paginada)
        $stmtLogs = $db->query(
            'SELECT id, action, description, user_login, user_name, ip, created_at
             FROM cp_logs
             ORDER BY id DESC LIMIT 500'
        );
        $logs = $stmtLogs->fetchAll() ?: [];

        // Top Categorias
        $catStmt = $db->query(
            'SELECT c.name, COUNT(d.id) as total
             FROM cp_doc_categories c
             LEFT JOIN cp_documents d ON d.category_id = c.id AND d.deleted_at IS NULL
             GROUP BY c.id, c.name
             ORDER BY total DESC LIMIT 5'
        );
        $topCategories = $catStmt->fetchAll() ?: [];

        // Top Usuários Mais Ativos
        $activeUsersStmt = $db->query(
            'SELECT user_name, user_login, COUNT(*) as total
             FROM cp_logs
             WHERE user_login IS NOT NULL AND user_login != ""
             GROUP BY user_login, user_name
             ORDER BY total DESC LIMIT 5'
        );
        $topActiveUsers = $activeUsersStmt->fetchAll() ?: [];

        $this->render('relatorios/index', [
            'title' => 'Relatórios & Trilha de Auditoria',
            'user' => $user,
            'page' => 'relatorios',
            'stats' => [
                'total_docs' => $totalDocs,
                'total_approved' => $totalApproved,
                'total_pending' => $totalPending,
                'total_rejected' => $totalRejected,
                'total_logs' => $totalLogs,
            ],
            'actions' => $actions,
            'usersList' => $usersList,
            'logs' => $logs,
            'topCategories' => $topCategories,
            'topActiveUsers' => $topActiveUsers,
        ], 'header');
    }

    public function exportCsv(): void
    {
        $user = Auth::user();
        if ($user === null || ((string) $user['role'] !== 'Admin' && (string) $user['role'] !== 'Aprovador')) {
            $this->jsonError('Não autorizado', 403);
            return;
        }

        $db = Database::getInstance();
        $stmt = $db->query('SELECT id, action, description, user_login, user_name, ip, created_at FROM cp_logs ORDER BY id DESC LIMIT 5000');
        $logs = $stmt->fetchAll() ?: [];

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="auditoria_docmanager_' . date('Y-m-d_H-i') . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Ação', 'Descrição', 'Usuário Login', 'Nome Usuário', 'IP', 'Data/Hora']);

        foreach ($logs as $row) {
            fputcsv($out, [
                $row['id'],
                $row['action'],
                $row['description'],
                $row['user_login'],
                $row['user_name'],
                $row['ip'],
                $row['created_at'],
            ]);
        }

        fclose($out);
        exit;
    }
}
