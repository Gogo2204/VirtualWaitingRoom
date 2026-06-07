# TODO — Virtual Waiting Room

## Legend
- [x] Done
- [ ] Not started / needs implementation

---

## Database Migrations

- [x] `users` — roles, status, faculty_number
- [x] `teacher_student` — teacher↔student link
- [x] `subjects` — room purposes (защита, консултация, etc.)
- [x] `rooms` — teacher_id, subject_id, name, description, wait_time_minutes, url, status
- [x] `room_items` — position, status (`waiting` / `invited_temp` / `invited_perm` / `in_session` / `done`), eta
- [x] `comments` — visibility (`teacher_only` / `public`), content
- [x] Unique constraint on `faculty_number`
- [x] **Migration 005** — seed default subjects ("Защита на проект", "Консултация", "Нанасяне на оценка", "Изпит")

---

## Backend — Models

- [x] `User` — create, findByEmail, findByFacultyNumber, getStudents, update, findById
- [x] `Room` — create, getQueue
- [x] `RoomItem` — joinQueue, updateStatus, getCurrentStudent
- [x] `Comment` — create, getForRoomItem
- [x] `Subject` — create
- [x] `TeacherStudent` — assign
- [x] `Room::findById(int $id): ?array` — inherited from Model
- [x] `Room::findByTeacher(int $teacherId): array`
- [x] `Room::updateStatus(int $id, string $status): bool`
- [x] `RoomItem::findById(int $id): ?array` — inherited from Model
- [x] `RoomItem::getNextWaiting(int $roomId): ?array`
- [x] `RoomItem::reorderAfterRemoval(int $roomId, int $removedPosition): void`
- [x] `RoomItem::recalcEtas(int $roomId, int $waitTimeMinutes): void`
- [x] `RoomItem::getByStudentAndRoom(int $studentId, int $roomId): ?array`
- [x] `Subject::getAll(): array`
- [x] `Subject::findById(int $id): ?array`
- [x] `TeacherStudent::importBatch(int $teacherId, array $studentIds): void` — bulk insert, skip duplicates
- [x] `TeacherStudent::isLinked(int $teacherId, int $studentId): bool`

---

## Backend — Services

- [x] `AuthService::login()`
- [x] `AuthService::register()` — student self-registers using faculty number
- [x] `UserService::createTeacher()` — generates temp password, sends email
- [ ] `UserService::importStudentsCsv(int $teacherId, string $csvContent): array` — validate format (first_name, last_name, faculty_number), create/update students, link to teacher, return result summary
- [x] `RoomService::createRoom(int $teacherId, array $data): array`
- [x] `RoomService::listRooms(int $teacherId): array`
- [x] `RoomService::getQueue(int $roomId, int $requesterId): array` — teacher sees all comments; student sees only `public` ones
- [x] `RoomService::joinQueue(int $roomId, int $studentId): array` — append to queue, calculate initial ETA
- [x] `RoomService::leaveQueue(int $roomItemId, int $studentId): void`
- [x] `RoomService::inviteStudent(int $roomItemId, string $mode): array` — mode `temp` or `perm`; generate meeting link + optional access code; update status
- [x] `RoomService::inviteAll(int $roomId): array` — invite entire queue; mark room as group session
- [x] `RoomService::studentReturns(int $roomItemId): void` — called after temp invite ends; restore position, set status back to `waiting`
- [x] `RoomService::setManualSlot(int $roomItemId, string $datetime): void` — teacher assigns explicit start time; update `eta`
- [x] `RoomService::recalcEtas(int $roomId): void` — recompute all ETAs after any queue change
- [ ] `CommentService::addComment(int $roomItemId, int $userId, string $content, string $visibility): array`
- [ ] `SubjectService::create(string $type, ?string $description): array` — admin only
- [ ] `SubjectService::list(): array`
- [ ] `StatisticsService::getRoomStats(int $roomId): array` — avg wait time, avg session duration, count served, peak hours
- [ ] `StatisticsService::getSubjectStats(int $teacherId, int $subjectId): array`

---

## Backend — Controllers & Routes

### Auth (existing)
- [x] `POST /api/auth/login`
- [x] `POST /api/auth/register`

### Users (existing)
- [x] `POST /api/users/teacher` — admin creates teacher

### Users (missing)
- [x] `POST /api/users/import-csv` — teacher uploads CSV; validates, creates students, links them
- [ ] `GET  /api/users/students` — teacher lists their students
- [x] `PUT  /api/users/password` — any user changes own password (for teachers after first login)

### Subjects
- [ ] `GET  /api/subjects` — list all (any authenticated user)
- [ ] `POST /api/subjects` — admin creates new subject/purpose
- [ ] `DELETE /api/subjects/:id` — admin removes subject (only if no rooms reference it)

### Rooms
- [x] `POST   /api/rooms` — teacher creates room (requires teacher role)
- [x] `GET    /api/rooms` — teacher lists own rooms; student lists rooms where they are linked to the teacher
- [x] `GET    /api/rooms/:id` — get room details + queue
- [x] `PATCH  /api/rooms/:id/status` — teacher opens/closes/archives room
- [x] `GET    /api/rooms/:id/queue` — ordered queue with ETAs and (filtered) comments

### Queue Actions
- [x] `POST   /api/rooms/:id/queue` — student joins queue
- [x] `DELETE /api/rooms/:id/queue` — student leaves queue
- [x] `POST   /api/rooms/:id/queue/:itemId/invite` — teacher invites student (`mode=temp|perm`)
- [x] `POST   /api/rooms/:id/queue/:itemId/return` — student returns after temp invite
- [x] `POST   /api/rooms/:id/queue/:itemId/slot` — teacher sets manual time slot
- [x] `POST   /api/rooms/:id/invite-all` — teacher invites entire queue

### Comments
- [ ] `POST /api/rooms/:id/queue/:itemId/comments` — add comment (student or teacher)
- [ ] `GET  /api/rooms/:id/queue/:itemId/comments` — get comments (visibility filtered)

### Statistics
- [ ] `GET /api/stats/rooms/:id` — stats for a specific room
- [ ] `GET /api/stats/subjects/:id` — stats by subject/purpose for teacher

### Student History
- [ ] `GET /api/history` — student sees own past queue entries with wait time, comments, outcomes

---

## Frontend Pages

- [x] `/login` — login form
- [x] `/register` — student self-registration with faculty number
- [x] `/dashboard` — placeholder; admin section to create teacher

### Admin Dashboard (expand `/dashboard` or new page)
- [ ] Create teacher form (exists as prototype)
- [ ] Manage subjects — list, add, delete room purposes
- [ ] CSV import UI — upload form, show import result (created / updated / errors)

### Teacher Dashboard (`/dashboard` teacher view)
- [ ] List own rooms with status badges
- [ ] Create room form — name, subject (dropdown), description, wait_time_minutes, meeting_type, url/config, access_code
- [ ] Open/close/archive room controls

### Teacher Queue View (`/rooms/:id`)
- [ ] Live queue list ordered by position
- [ ] Show student name, ETA, comments (all visibilities)
- [ ] "Invite" button per student — opens modal: temp or perm invite → shows generated link + access code
- [ ] "Invite All" button
- [ ] Set manual time slot per student
- [ ] Drag-to-reorder (optional, recomputes ETAs)

### Student Dashboard (`/dashboard` student view)
- [ ] List available rooms (from teachers they are linked to)
- [ ] Join queue button per room
- [ ] Show own position in queue + ETA countdown
- [ ] Show meeting link + access code when invited
- [ ] Leave queue button
- [ ] Add comment form (choose visibility: teacher only / public)
- [ ] View other public comments in the same queue

### Statistics Page (`/stats`)
- [ ] Teacher selects subject/room → see avg wait time, avg session time, count served, peak hours
- [ ] Chart or table view

### Student History Page (`/history`)
- [ ] List of past queue entries — room name, subject, date, wait time, comments

---

## Use Case Coverage Checklist

| # | Use Case | Status |
|---|----------|--------|
| 1 | Teacher imports CSV with students | Not started |
| 2 | Student registers via faculty number | **Done** |
| 3 | Admin creates teacher (with email) | **Done** |
| 4 | Admin adds room purposes (subjects) | Model done, no API/UI |
| 5 | Teacher creates room/queue | Model done, no API/UI |
| 6 | Student joins queue + sees ETA | Model done, no API/UI |
| 7 | Student adds comment (visibility choice) | Model done, no API/UI |
| 8 | Teacher views queue with comments + ETAs | Model done, no API/UI |
| 9 | Teacher invites student → meeting link + access code | Schema ready, not implemented |
| 10 | Teacher sets manual time slot per student | Schema ready (`eta`), not implemented |
| 11 | System auto-calculates ETA (default 15 min/person) | Schema ready, logic missing |
| 12 | Teacher temp-invites student (keeps queue position) | Status enum ready, logic missing |
| 13 | Teacher perm-invites student (loses queue position) | Status enum ready, logic missing |
| 14 | Teacher invites entire queue | Not started |
| 15 | Statistics by subject/room | Not started — needs `actual_start`/`actual_end` fields |
| 16 | Student views personal history | Not started |

---

## Notes on Schema Gaps

- `rooms` is missing `meeting_type` and `access_code` — needed before implementing UC9.
- `room_items` is missing `actual_start` / `actual_end` — needed before implementing UC15/16 statistics.
- `subjects` has only a `type` string; a `description` column is useful but not blocking.
- ETA recalculation must run after every queue change (join, leave, invite, manual slot).
- Temp-invite flow: on invite → save current `position` somewhere (add `saved_position INT NULL` to `room_items` or use the existing `position`) → on return → restore status to `waiting` at the saved position, shift others if needed.
