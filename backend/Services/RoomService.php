<?php

namespace App\Services;

use App\Models\Room;
use App\Models\RoomItem;
use App\Models\Comment;
use App\Models\Subject;
use App\Models\TeacherStudent;
use App\Models\RoomHistory;

class RoomService
{
    public function __construct(
        private Room $roomModel,
        private RoomItem $roomItemModel,
        private Comment $commentModel,
        private Subject $subjectModel,
        private TeacherStudent $teacherStudentModel,
        private RoomHistory $roomHistoryModel
    ) {}

    public function createRoom(int $teacherId, array $data): array
    {
        $name      = trim($data['name'] ?? '');
        $subjectId = (int)($data['subject_id'] ?? 0);

        if ($name === '') {
            throw new \InvalidArgumentException('Room name is required.');
        }

        if ($subjectId <= 0 || !$this->subjectModel->findById($subjectId)) {
            throw new \InvalidArgumentException('Please select a valid purpose.');
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
            $comments      = $this->commentModel->getForRoomItem((int)$item['id']);
            $itemStudentId = (int)$item['student_id'];

            $item['comments'] = array_values(array_filter(
                $comments,
                function ($c) use ($isTeacher, $requesterId, $itemStudentId) {
                    if ($c['visibility'] === 'public') return true;
                    if ($isTeacher) return true;
                    return $requesterId === $itemStudentId;
                }
            ));
        }
        unset($item);

        $doneIds = array_map('intval', array_column(
            array_filter($items, fn($i) => $i['status'] === 'done'),
            'id'
        ));
        $times = $this->roomHistoryModel->getTimesForItems($doneIds);

        foreach ($items as &$item) {
            if ($item['status'] === 'done') {
                $item['times'] = $times[(int)$item['id']] ?? ['queue_seconds' => 0, 'meeting_seconds' => 0];
            }
            if ($item['status'] === 'invited_perm') {
                $item['meeting_link'] = $room['url'] ?? '';
            }
        }
        unset($item);

        return [
            'room' => [
                'id'          => (int)$room['id'],
                'name'        => $room['name'],
                'description' => $room['description'] ?? '',
                'status'      => $room['status'],
                'url'         => $room['url'] ?? '',
            ],
            'queue' => $items,
        ];
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
        $this->roomHistoryModel->record($itemId, $studentId, $roomId, 'joined');
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

        if ($mode === 'perm') {
            $this->roomHistoryModel->record(
                $roomItemId,
                (int)$item['student_id'],
                (int)$item['room_id'],
                'invited'
            );
        }

        return [
            'item'         => $this->roomItemModel->findById($roomItemId),
            'meeting_link' => $room['url'] ?? '',
        ];
    }

    public function finishMeeting(int $roomItemId): array
    {
        $item = $this->roomItemModel->findById($roomItemId);
        if (!$item) {
            throw new \RuntimeException('Queue item not found.', 404);
        }

        if ($item['status'] !== 'invited_perm') {
            throw new \RuntimeException('Student is not in a permanent meeting.', 422);
        }

        $this->roomItemModel->updateStatus($roomItemId, 'done');
        $this->roomHistoryModel->record(
            $roomItemId,
            (int)$item['student_id'],
            (int)$item['room_id'],
            'done'
        );

        return [
            'item'  => $this->roomItemModel->findById($roomItemId),
            'times' => $this->roomHistoryModel->getTimes($roomItemId),
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

    public function inviteAllTemp(int $roomId): int
    {
        if (!$this->roomModel->findById($roomId)) {
            throw new \RuntimeException('Room not found.', 404);
        }
        return $this->roomItemModel->updateStatusBulk($roomId, 'waiting', 'invited_temp');
    }

    public function returnAll(int $roomId): int
    {
        if (!$this->roomModel->findById($roomId)) {
            throw new \RuntimeException('Room not found.', 404);
        }
        $count = $this->roomItemModel->updateStatusBulk($roomId, 'invited_temp', 'waiting');
        if ($count > 0) $this->recalcEtas($roomId);
        return $count;
    }

    public function setEtaAll(int $roomId, string $startDatetime): void
    {
        $room = $this->roomModel->findById($roomId);
        if (!$room) throw new \RuntimeException('Room not found.', 404);
        if (!strtotime($startDatetime)) throw new \InvalidArgumentException('Invalid start time.');
        $this->roomItemModel->recalcEtasFromTime($roomId, (int)$room['wait_time_minutes'], $startDatetime);
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
