<?php

namespace App\Controllers;

use App\Services\GradeService;
use App\Middleware\AuthMiddleware;

class GradeController
{
    public function __construct(
        private GradeService $gradeService
    ) {}

    public function createOrUpdate(array $params)
    {
        AuthMiddleware::require('teacher');

        $teacherId = $_SESSION['user_id'];
        $roomId = (int)$params['room_id'];
        $roomItemId = (int)$params['item_id'];

        $input = json_decode(file_get_contents('php://input'), true);
        $grade = (float)$input['grade'];

        if ($grade < 2 || $grade > 6) {
            return json_encode(['error' => 'Grade must be between 2 and 6']);
        }

        try {
            $result = $this->gradeService->createOrUpdateGrade(
                $teacherId,
                $roomId,
                $roomItemId,
                $grade
            );
            return json_encode($result);
        } catch (\Exception $e) {
            return json_encode(['error' => $e->getMessage()]);
        }
    }

    public function getByStudent(array $params)
    {
        AuthMiddleware::require('teacher', 'admin');

        $studentId = (int)$params['student_id'];
        return json_encode($this->gradeService->getGradesForStudent($studentId));
    }

    public function getByRoom(array $params)
    {
        AuthMiddleware::require('teacher', 'admin');

        $roomId = (int)$params['room_id'];
        return json_encode($this->gradeService->getGradesForRoom($roomId));
    }
}
