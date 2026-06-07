<?php

namespace App\Services;

use App\Models\Room;
use App\Models\RoomItem;
use App\Models\Comment;
use App\Models\Subject;
use App\Models\TeacherStudent;

class RoomService
{
    public function __construct(
        private Room $roomModel,
        private RoomItem $roomItemModel,
        private Comment $commentModel,
        private Subject $subjectModel,
        private TeacherStudent $teacherStudentModel
    ) {}

    public function createRoom(int $teacherId, array $data): array
    {
        $name      = trim($data['name'] ?? '');
        $subjectId = (int)($data['subject_id'] ?? 0);

        if ($name === '') {
            throw new \InvalidArgumentException('Room name is required.');
        }

        if ($subjectId <= 0 || !$this->subjectModel->findById($subjectId)) {
            throw new \InvalidArgumentException('Invalid subject_id.');
        }

        $id = $this->roomModel->create([
            'teacher_id'        => $teacherId,
            'subject_id'        => $subjectId,
            'name'              => $name,
            'description'       => trim($data['description'] ?? ''),
            'wait_time_minutes' => max(1, (int)($data['wait_time_minutes'] ?? 15)),
            'url'               => trim($data['url'] ?? ''),
        ]);

        return $this->roomModel->findById($id);
    }

    public function listRooms(int $teacherId): array
    {
        return $this->roomModel->findByTeacher($teacherId);
    }

    public function listRoomsForStudent(int $studentId): array
    {
        $teacherIds = $this->teacherStudentModel->getTeacherIds($studentId);

        if (empty($teacherIds)) {
            return [];
        }

        return $this->roomModel->findByTeachersWithStatus($teacherIds, 'open');
    }

    public function getQueue(int $roomId, int $requesterId): array
    {
        $room = $this->roomModel->findById($roomId);
        if (!$room) {
            throw new \RuntimeException('Room not found.', 404);
        }

        $isTeacher = ((int)$room['teacher_id'] === $requesterId);

        $items = $this->roomModel->getQueue($roomId);

        foreach ($items as &$item) {
            $comments = $this->commentModel->getForRoomItem((int)$item['id']);

            if (!$isTeacher) {
                $comments = array_values(array_filter(
                    $comments,
                    fn($c) => $c['visibility'] === 'public'
                ));
            }

            $item['comments'] = $comments;
        }
        unset($item);

        return $items;
    }

    public function joinQueue(int $roomId, int $studentId): array
    {
        $room = $this->roomModel->findById($roomId);
        if (!$room) {
            throw new \RuntimeException('Room not found.', 404);
        }

        if ($room['status'] !== 'open') {
            throw new \RuntimeException('Room is not open.', 422);
        }

        if ($this->roomItemModel->getByStudentAndRoom($studentId, $roomId)) {
            throw new \RuntimeException('Already in queue.', 422);
        }

        $queue    = $this->roomModel->getQueue($roomId);
        $position = count($queue) + 1;

        $itemId = $this->roomItemModel->joinQueue($roomId, $studentId, $position);
        $this->recalcEtas($roomId);

        $item = $this->roomItemModel->findById($itemId);
        if (!$item) {
            throw new \RuntimeException('Failed to retrieve queue item.', 500);
        }

        return $item;
    }

    public function leaveQueue(int $roomItemId, int $studentId): void
    {
        $item = $this->roomItemModel->findById($roomItemId);
        if (!$item) {
            throw new \RuntimeException('Queue item not found.', 404);
        }

        if ((int)$item['student_id'] !== $studentId) {
            throw new \RuntimeException('Forbidden.', 403);
        }

        if ($item['status'] !== 'waiting') {
            throw new \RuntimeException('Cannot leave queue in current status.', 422);
        }

        $roomId   = (int)$item['room_id'];
        $position = (int)$item['position'];

        $this->roomItemModel->delete($roomItemId);
        $this->roomItemModel->reorderAfterRemoval($roomId, $position);
        $this->recalcEtas($roomId);
    }

    public function updateRoomStatus(int $roomId, string $status): void
    {
        $room = $this->roomModel->findById($roomId);
        if (!$room) {
            throw new \RuntimeException('Room not found.', 404);
        }

        $allowed = ['open', 'closed', 'archived'];
        if (!in_array($status, $allowed, true)) {
            throw new \InvalidArgumentException('Status must be one of: ' . implode(', ', $allowed));
        }

        $this->roomModel->updateStatus($roomId, $status);
    }

    public function inviteStudent(int $roomItemId, string $mode): array
    {
        if (!in_array($mode, ['temp', 'perm'], true)) {
            throw new \InvalidArgumentException('Mode must be "temp" or "perm".');
        }

        $item = $this->roomItemModel->findById($roomItemId);
        if (!$item) {
            throw new \RuntimeException('Queue item not found.', 404);
        }

        if ($item['status'] !== 'waiting') {
            throw new \RuntimeException('Student is not in waiting status.', 422);
        }

        $room   = $this->roomModel->findById((int)$item['room_id']);
        $status = $mode === 'temp' ? 'invited_temp' : 'invited_perm';

        $this->roomItemModel->updateStatus($roomItemId, $status);

        return [
            'item'         => $this->roomItemModel->findById($roomItemId),
            'meeting_link' => $room['url'] ?? '',
        ];
    }

    public function inviteAll(int $roomId): array
    {
        $room = $this->roomModel->findById($roomId);
        if (!$room) {
            throw new \RuntimeException('Room not found.', 404);
        }

        $waiting = array_values(array_filter(
            $this->roomModel->getQueue($roomId),
            fn($i) => $i['status'] === 'waiting'
        ));

        foreach ($waiting as $item) {
            $this->roomItemModel->updateStatus((int)$item['id'], 'invited_perm');
        }

        return [
            'invited_count' => count($waiting),
            'meeting_link'  => $room['url'] ?? '',
        ];
    }

    public function studentReturns(int $roomItemId): void
    {
        $item = $this->roomItemModel->findById($roomItemId);
        if (!$item) {
            throw new \RuntimeException('Queue item not found.', 404);
        }

        if ($item['status'] !== 'invited_temp') {
            throw new \RuntimeException('Student is not in invited_temp status.', 422);
        }

        $this->roomItemModel->updateStatus($roomItemId, 'waiting');
        $this->recalcEtas((int)$item['room_id']);
    }

    public function setManualSlot(int $roomItemId, string $datetime): void
    {
        $item = $this->roomItemModel->findById($roomItemId);
        if (!$item) {
            throw new \RuntimeException('Queue item not found.', 404);
        }

        if (!strtotime($datetime)) {
            throw new \InvalidArgumentException('Invalid datetime format.');
        }

        $this->roomItemModel->setEta($roomItemId, $datetime);
    }

    public function recalcEtas(int $roomId): void
    {
        $room = $this->roomModel->findById($roomId);
        if (!$room) {
            return;
        }

        $this->roomItemModel->recalcEtas($roomId, (int)$room['wait_time_minutes']);
    }
}
