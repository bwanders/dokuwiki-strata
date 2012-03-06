CREATE TABLE data (eid INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, subject TEXT NOT NULL, predicate TEXT NOT NULL, object TEXT NOT NULL, graph TEXT NOT NULL) CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
CREATE INDEX idx_all ON data(subject(32), predicate(32), object(32)); -- Prefix length is chosen randomly

