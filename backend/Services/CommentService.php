<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\RoomItem;

class CommentService
{
    public function __construct(
        private Comment $commentModel,
        private RoomItem $roomItemModel
    ) {}

    public function addComment(int $roomItemId, int $userId, string $content, string $visibility): array
    {
        $content = trim($content);
        if ($content === '') {
            throw new \InvalidArgumentException('Content is required.');
        }

        if (!in_array($visibility, ['teacher_only', 'public'], true)) {
            throw new \InvalidArgumentException('Visibility must be "teacher_only" or "public".');
        }

        if (!$this->roomItemModel->findById($roomItemId)) {
            throw new \RuntimeException('Queue item not found.', 404);
        }

        $id = $this->commentModel->create([
            'room_item_id' => $roomItemId,
            'user_id'      => $userId,
            'visibility'   => $visibility,
            'content'      => $content,
        ]);

        return $this->commentModel->findById($id);
    }

    public function getComments(int $roomItemId, string $requesterRole): array
    {
        $comments = $this->commentModel->getForRoomItem($roomItemId);

        if ($requesterRole !== 'teacher' && $requesterRole !== 'admin') {
            $comments = array_values(array_filter(
                $comments,
                fn($c) => $c['visibility'] === 'public'
            ));
        }

        return $comments;
    }
}
