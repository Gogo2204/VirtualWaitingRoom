<?php

namespace App\Services;

use App\Models\Room;
use App\Models\RoomHistory;
use App\Models\RoomItem;
use App\Models\TeacherStudent;

class StatisticsService
{
    public function __construct(
        private Room $roomModel,
        private RoomHistory $roomHistoryModel,
        private RoomItem $roomItemModel,
        private TeacherStudent $teacherStudentModel
    ) {}

    public function getRoomStats(int $roomId, int $requesterId, string $requesterRole): array
    {
        $room = $this->roomModel->findById($roomId);
        if (!$room) {
            throw new \RuntimeException('Room not found.', 404);
        }

        if ($requesterRole === 'student') {
            $teacherIds = $this->teacherStudentModel->getTeacherIds($requesterId);
            if (!in_array((int)$room['teacher_id'], array_map('intval', $teacherIds), true)) {
                throw new \RuntimeException('Forbidden.', 403);
            }
        } elseif ($requesterRole !== 'admin' && (int)$room['teacher_id'] !== $requesterId) {
            throw new \RuntimeException('Forbidden.', 403);
        }

        $historical = $this->roomHistoryModel->getStatsForRoom($roomId);
        $counts     = $this->roomItemModel->getStatusCounts($roomId);

        return array_merge($historical, [
            'currently_waiting'    => $counts['waiting'] ?? 0,
            'currently_in_meeting' => ($counts['invited_temp'] ?? 0) + ($counts['invited_perm'] ?? 0),
        ]);
    }

    public function getSubjectStats(int $teacherId, ?int $subjectId): array
    {
        return $this->roomHistoryModel->getStatsForTeacherBySubject($teacherId, $subjectId);
    }
}
