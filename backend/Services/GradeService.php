<?php

namespace App\Services;

use App\Models\Grade;
use App\Models\Room;
use App\Models\RoomItem;

class GradeService
{
    public function __construct(
        private Grade    $gradeModel,
        private Room     $roomModel,
        private RoomItem $roomItemModel
    ) {}

    public function setGrade(int $teacherId, int $roomId, array $data): array
    {
        $room = $this->roomModel->findById($roomId);

        if (!$room) {
            throw new \InvalidArgumentException('Room not found.');
        }

        if ((int)$room['teacher_id'] !== $teacherId) {
            throw new \RuntimeException('Forbidden.');
        }

        $studentId = (int)($data['student_id'] ?? 0);
        $grade     = $data['grade'] ?? null;

        if ($studentId <= 0) {
            throw new \InvalidArgumentException('student_id is required.');
        }

        if ($grade === null || !is_numeric($grade)) {
            throw new \InvalidArgumentException('A numeric grade is required.');
        }

        $grade = (float)$grade;

        if ($grade < 2 || $grade > 6) {
            throw new \InvalidArgumentException('Grade must be between 2 and 6.');
        }

        $item = $this->roomItemModel->findByRoomAndStudent($roomId, $studentId);

        if (!$item) {
            throw new \InvalidArgumentException('Student has no queue entry in this room.');
        }

        $id = $this->gradeModel->upsert([
            'room_item_id' => (int)$item['id'],
            'student_id'   => $studentId,
            'room_id'      => $roomId,
            'grade'        => $grade,
        ]);

        return $this->gradeModel->findById($id);
    }

    public function getGradesForRoom(int $teacherId, int $roomId): array
    {
        $room = $this->roomModel->findById($roomId);

        if (!$room) {
            throw new \InvalidArgumentException('Room not found.');
        }

        if ((int)$room['teacher_id'] !== $teacherId) {
            throw new \RuntimeException('Forbidden.');
        }

        return $this->gradeModel->findByRoom($roomId);
    }

    public function getGradesForStudent(int $studentId): array
    {
        return $this->gradeModel->findByStudent($studentId);
    }

    public function deleteGrade(int $teacherId, int $roomId, int $studentId): void
    {
        $room = $this->roomModel->findById($roomId);

        if (!$room) {
            throw new \InvalidArgumentException('Room not found.');
        }

        if ((int)$room['teacher_id'] !== $teacherId) {
            throw new \RuntimeException('Forbidden.');
        }

        $deleted = $this->gradeModel->deleteByRoomAndStudent($roomId, $studentId);

        if (!$deleted) {
            throw new \InvalidArgumentException('Grade not found.');
        }
    }
}