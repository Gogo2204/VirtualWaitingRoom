<?php

namespace App\Controllers;

use App\Models\Subject;

class SubjectController
{
    public function __construct(private Subject $subjectModel) {}

    public function list(): void
    {
        echo json_encode(['success' => true, 'subjects' => $this->subjectModel->getAll()]);
    }
}
