INSERT INTO users (first_name, last_name, email, password_hash, role, status)
VALUES (
    'Teacher',
    'User',
    'teacher@teacher.com',
    '$2y$10$nhSxQY0VePaqXqRvlWSx.uneh7zgXM0GrtkR8Ndm3OZjyxxZLtM/y',
    'teacher',
    'registered'
),
(
    'Student',
    'User',
    'student@student.com',
    '$2y$10$nhSxQY0VePaqXqRvlWSx.uneh7zgXM0GrtkR8Ndm3OZjyxxZLtM/y',
    'student',
    'registered'
);