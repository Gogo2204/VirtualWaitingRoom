ALTER TABLE users
ADD CONSTRAINT uq_faculty_number
UNIQUE(faculty_number);