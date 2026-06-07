<?php

namespace App\Controllers;

use App\Services\StatisticsService;
use App\Middleware\AuthMiddleware;

class StatisticsController
{
    public function __construct(private StatisticsService $statisticsService) {}

    public function roomStats(int $roomId): void
    {
        $user = AuthMiddleware::user();

        try {
            $stats = $this->statisticsService->getRoomStats($roomId, (int)$user['sub'], $user['role']);
            echo json_encode(['success' => true, 'stats' => $stats]);
        } catch (\RuntimeException $e) {
            $code = (int)$e->getCode();
            http_response_code($code >= 400 && $code < 600 ? $code : 500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function subjectStats(): void
    {
        $user      = AuthMiddleware::user();
        $subjectId = isset($_GET['subject_id']) && is_numeric($_GET['subject_id'])
            ? (int)$_GET['subject_id']
            : null;

        $stats = $this->statisticsService->getSubjectStats((int)$user['sub'], $subjectId);
        echo json_encode(['success' => true, 'stats' => $stats]);
    }
}
