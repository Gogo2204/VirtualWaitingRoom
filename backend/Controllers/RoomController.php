<?php

namespace App\Controllers;

use App\Services\RoomService;
use App\Middleware\AuthMiddleware;

class RoomController
{
    public function __construct(private RoomService $roomService) {}

    public function createRoom(): void
    {
        $user = AuthMiddleware::user();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            $room = $this->roomService->createRoom((int)$user['sub'], $body);
            http_response_code(201);
            echo json_encode(['success' => true, 'room' => $room]);
        } catch (\InvalidArgumentException $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function listRooms(): void
    {
        $user = AuthMiddleware::user();

        if ($user['role'] === 'student') {
            $rooms = $this->roomService->listRoomsForStudent((int)$user['sub']);
        } else {
            $rooms = $this->roomService->listRooms((int)$user['sub']);
        }

        echo json_encode(['success' => true, 'rooms' => $rooms]);
    }

    public function getRoom(int $roomId): void
    {
        $user = AuthMiddleware::user();

        try {
            $queue = $this->roomService->getQueue($roomId, (int)$user['sub']);
            echo json_encode(['success' => true, 'queue' => $queue]);
        } catch (\RuntimeException $e) {
            $code = (int)$e->getCode();
            http_response_code($code >= 400 && $code < 600 ? $code : 500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function updateRoomStatus(int $roomId): void
    {
        $body   = json_decode(file_get_contents('php://input'), true) ?? [];
        $status = trim($body['status'] ?? '');

        try {
            $this->roomService->updateRoomStatus($roomId, $status);
            echo json_encode(['success' => true]);
        } catch (\InvalidArgumentException $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        } catch (\RuntimeException $e) {
            $code = (int)$e->getCode();
            http_response_code($code >= 400 && $code < 600 ? $code : 500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getQueue(int $roomId): void
    {
        $this->getRoom($roomId);
    }

    public function joinQueue(int $roomId): void
    {
        $user = AuthMiddleware::user();

        try {
            $item = $this->roomService->joinQueue($roomId, (int)$user['sub']);
            http_response_code(201);
            echo json_encode(['success' => true, 'item' => $item]);
        } catch (\RuntimeException $e) {
            $code = (int)$e->getCode();
            http_response_code($code >= 400 && $code < 600 ? $code : 500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function leaveQueue(int $roomId): void
    {
        $user = AuthMiddleware::user();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $roomItemId = (int)($body['room_item_id'] ?? 0);
        if ($roomItemId <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'room_item_id is required.']);
            return;
        }

        try {
            $this->roomService->leaveQueue($roomItemId, (int)$user['sub']);
            echo json_encode(['success' => true]);
        } catch (\RuntimeException $e) {
            $code = (int)$e->getCode();
            http_response_code($code >= 400 && $code < 600 ? $code : 500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function inviteStudent(int $roomId, int $itemId): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $mode = trim($body['mode'] ?? '');

        try {
            $result = $this->roomService->inviteStudent($itemId, $mode);
            echo json_encode(['success' => true] + $result);
        } catch (\InvalidArgumentException $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        } catch (\RuntimeException $e) {
            $code = (int)$e->getCode();
            http_response_code($code >= 400 && $code < 600 ? $code : 500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function studentReturns(int $roomId, int $itemId): void
    {
        try {
            $this->roomService->studentReturns($itemId);
            echo json_encode(['success' => true]);
        } catch (\RuntimeException $e) {
            $code = (int)$e->getCode();
            http_response_code($code >= 400 && $code < 600 ? $code : 500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function setManualSlot(int $roomId, int $itemId): void
    {
        $body     = json_decode(file_get_contents('php://input'), true) ?? [];
        $datetime = trim($body['datetime'] ?? '');

        try {
            $this->roomService->setManualSlot($itemId, $datetime);
            echo json_encode(['success' => true]);
        } catch (\InvalidArgumentException $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        } catch (\RuntimeException $e) {
            $code = (int)$e->getCode();
            http_response_code($code >= 400 && $code < 600 ? $code : 500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function finishMeeting(int $itemId): void
    {
        try {
            $result = $this->roomService->finishMeeting($itemId);
            echo json_encode(['success' => true] + $result);
        } catch (\RuntimeException $e) {
            $code = (int)$e->getCode();
            http_response_code($code >= 400 && $code < 600 ? $code : 500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
