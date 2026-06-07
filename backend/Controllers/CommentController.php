<?php

namespace App\Controllers;

use App\Services\CommentService;
use App\Middleware\AuthMiddleware;

class CommentController
{
    public function __construct(private CommentService $commentService) {}

    public function add(int $itemId): void
    {
        $user = AuthMiddleware::user();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $content    = trim($body['content'] ?? '');
        $visibility = trim($body['visibility'] ?? 'teacher_only');

        try {
            $comment = $this->commentService->addComment($itemId, (int)$user['sub'], $content, $visibility);
            http_response_code(201);
            echo json_encode(['success' => true, 'comment' => $comment]);
        } catch (\InvalidArgumentException $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        } catch (\RuntimeException $e) {
            $code = (int)$e->getCode();
            http_response_code($code >= 400 && $code < 600 ? $code : 500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function list(int $itemId): void
    {
        $user     = AuthMiddleware::user();
        $comments = $this->commentService->getComments($itemId, (int)$user['sub'], $user['role']);
        echo json_encode(['success' => true, 'comments' => $comments]);
    }
}
