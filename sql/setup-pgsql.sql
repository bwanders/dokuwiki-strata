CREATE TABLE data (
    subject TEXT NOT NULL,
    predicate TEXT NOT NULL,
    object TEXT NOT NULL,
    graph TEXT NOT NULL
);

-- index for subject-primary retrieval (index prefixes: s, sp)
CREATE INDEX idx_spo ON data(lower(subject), lower(predicate), lower(object));

-- index for predicate-primary retrieval (i.e. property fetch)
CREATE INDEX idx_pso ON data(lower(predicate), lower(subject), lower(object));

