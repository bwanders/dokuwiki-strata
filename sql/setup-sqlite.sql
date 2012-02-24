CREATE TABLE data (eid INTEGER PRIMARY KEY, subject, predicate, object, graph);
CREATE INDEX idx_all ON data(subject, predicate, object);
