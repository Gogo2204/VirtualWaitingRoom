<?php

namespace App\Services;

use App\Models\User;
use App\Models\TeacherStudent;

class UserService
{
    public function __construct(
        private User $userModel,
        private ?TeacherStudent $teacherStudentModel = null
    ) {}

    public function createTeacher(string $firstName, string $lastName, string $email): array
    {
        if (empty($email)) {
            throw new \InvalidArgumentException('Email is required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email address.');
        }

        if ($this->userModel->findByEmail($email)) {
            throw new \InvalidArgumentException('A user with this email already exists.');
        }

        $tempPassword = $this->generateTempPassword();

        $userId = $this->userModel->create([
            'first_name'    => $firstName,
            'last_name'     => $lastName,
            'email'         => $email,
            'password_hash' => password_hash($tempPassword, PASSWORD_BCRYPT),
            'role'          => 'teacher',
            'status'        => 'registered',
        ]);

        $this->sendWelcomeEmail($email, $firstName ?? 'Teacher', $tempPassword);

        $user = $this->userModel->findById($userId);
        unset($user['password_hash']);

        return $user;
    }

    public function importStudents(array $facultyNumbers, int $teacherId): array
    {
        $created = 0;
        $skipped = 0;
    
        foreach ($facultyNumbers as $fn) {
            $fn = trim((string)$fn);
            if (empty($fn)) continue;
    
            $existing = $this->userModel->findByFacultyNumber($fn);

            if ($existing) {
                if ($this->teacherStudentModel !== null) {
                    $this->teacherStudentModel->importBatch($teacherId, [(int)$existing['id']]);
                }
                $skipped++;
                continue;
            }
    
            $studentId = $this->userModel->create([
                'first_name'     => '',
                'last_name'      => '',
                'email'          => "student_{$fn}@placeholder.local",
                'password_hash'  => '',
                'faculty_number' => $fn,
                'role'           => 'student',
                'status'         => 'imported',
            ]);

            if ($this->teacherStudentModel) {
                $this->teacherStudentModel->assign($teacherId, $studentId);
            }

            $created++;
        }
    
        return ['created' => $created, 'skipped' => $skipped];
    }

    private function generateTempPassword(int $length = 12): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }

    private function sendWelcomeEmail(string $to, string $firstName, string $tempPassword): void
    {
        $subject = 'Your teacher account';
        $message = "Hi {$firstName},\n\n"
            . "Your account has been created.\n\n"
            . "Email:    {$to}\n"
            . "Password: {$tempPassword}\n\n"
            . "Please log in and change your password.\n";

        $headers = 'From: ' . ($_ENV['MAIL_FROM'] ?? 'noreply@waitingRoOm.com');

        $sent = mail($to, $subject, $message, $headers);

        if (!$sent) {
            error_log("Failed to send welcome email to {$to}");
        }
    }
}