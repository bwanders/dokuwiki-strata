CREATE TABLE data (
    subject TEXT NOT NULL COLLATE NOCASE,
    predicate TEXT NOT NULL COLLATE NOCASE,
    object TEXT NOT NULL COLLATE NOCASE,
    graph TEXT NOT NULL COLLATE NOCASE
);

-- index for subject-primary retrieval (index prefixes: s, sp)
CREATE INDEX idx_spo ON data(subject, predicate, object);

-- index for predicate-primary retrieval (i.e. property fetch)
CREATE INDEX idx_pso ON data(predicate, subject, object);
