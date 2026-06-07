<?php

namespace App\Services;

use App\Models\Room;
use App\Models\RoomHistory;

class StatisticsService
{
    public function __construct(
        private Room $roomModel,
        private RoomHistory $roomHistoryModel
    ) {}

    public function getRoomStats(int $roomId, int $requesterId, string $requesterRole): array
    {
        $room = $this->roomModel->findById($roomId);
        if (!$room) {
            throw new \RuntimeException('Room not found.', 404);
        }

        if ($requesterRole !== 'admin' && (int)$room['teacher_id'] !== $requesterId) {
            throw new \RuntimeException('Forbidden.', 403);
        }

        return $this->roomHistoryModel->getStatsForRoom($roomId);
    }

    public function getSubjectStats(int $teacherId, ?int $subjectId): array
    {
        return $this->roomHistoryModel->getStatsForTeacherBySubject($teacherId, $subjectId);
    }
}
