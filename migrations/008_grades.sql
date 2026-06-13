USE queue_system;

CREATE TABLE IF NOT EXISTS grades (
    id            BIGINT AUTO_INCREMENT PRIMARY KEY,
    room_item_id  BIGINT NOT NULL,
    student_id    BIGINT NOT NULL,
    room_id       BIGINT NOT NULL,
    recorded_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    grade         FLOAT NOT NULL,
    CHECK (grade >= 2 AND grade <= 6),
    UNIQUE (student_id, room_id),
    FOREIGN KEY (room_item_id) REFERENCES room_items(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id)   REFERENCES users(id)      ON DELETE CASCADE,
    FOREIGN KEY (room_id)      REFERENCES rooms(id)      ON DELETE CASCADE
) ENGINE=InnoDB;
