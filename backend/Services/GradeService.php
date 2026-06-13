<?php

namespace App\Services;

use App\Models\Grade;
use App\Models\Room;
use App\Models\RoomItem;

class GradeService
{
    public function __construct(
        private \PDO $db,
        private Grade $gradeModel,
        private Room $roomModel,
        private RoomItem $roomItemModel
    ) {}

    public function createOrUpdateGrade(
        int $teacherId,
        int $roomId,
        int $roomItemId,
        float $grade
    ): array {
        $room = $this->roomModel->findById($roomId);
        if (!$room || (int)$room['teacher_id'] !== $teacherId) {
            throw new \Exception("Unauthorized: Room does not belong to teacher");
        }

        $item = $this->roomItemModel->findById($roomItemId);
        if (!$item || (int)$item['room_id'] !== $roomId) {
            throw new \Exception("Invalid room item");
        }

        $studentId = (int)$item['student_id'];

        $existing = $this->gradeModel->findByStudentAndRoom($studentId, $roomId);

        if ($existing) {
            $this->gradeModel->updateGrade($studentId, $roomId, $grade);
            return [
                'updated' => true,
                'grade' => $this->gradeModel->findByStudentAndRoom($studentId, $roomId)
            ];
        }

        $id = $this->gradeModel->create($roomItemId, $studentId, $roomId, $grade);

        return [
            'created' => true,
            'grade' => $this->gradeModel->findById($id)
        ];
    }

    public function getGradesForRoom(int $roomId): array
    {
        return $this->gradeModel->getByRoom($roomId);
    }

    public function getGradesForStudent(int $studentId): array
    {
        return $this->gradeModel->getByStudent($studentId);
    }
}
