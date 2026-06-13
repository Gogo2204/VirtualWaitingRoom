<?php

use App\Controllers\GradeController;

$gradeController = $container->get(GradeController::class);

$router->post('/rooms/{room_id}/items/{item_id}/grade', [$gradeController, 'createOrUpdate']);

$router->get('/grades/student/{student_id}', [$gradeController, 'getByStudent']);

$router->get('/grades/room/{room_id}', [$gradeController, 'getByRoom']);
