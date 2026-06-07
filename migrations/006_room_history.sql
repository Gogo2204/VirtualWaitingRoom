USE queue_system;

CREATE TABLE IF NOT EXISTS room_history (
    id           BIGINT AUTO_INCREMENT PRIMARY KEY,
    room_item_id BIGINT NOT NULL,
    student_id   BIGINT NOT NULL,
    room_id      BIGINT NOT NULL,
    event        ENUM('joined', 'invited', 'done') NOT NULL,
    recorded_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_item_id) REFERENCES room_items(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id)   REFERENCES users(id)      ON DELETE CASCADE,
    FOREIGN KEY (room_id)      REFERENCES rooms(id)       ON DELETE CASCADE
) ENGINE=InnoDB;
