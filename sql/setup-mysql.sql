CREATE TABLE data (
    subject TEXT NOT NULL,
    predicate TEXT NOT NULL,
    object TEXT NOT NULL,
    graph TEXT NOT NULL
) CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ENGINE InnoDB;

-- index for subject-primary retrieval (index prefixes: s, sp)
CREATE INDEX idx_spo ON data(subject(32), predicate(32), object(32)); -- Prefix length is arbitrary

-- index for predicate-primary retrieval (i.e. property fetch)
CREATE INDEX idx_pso ON data(predicate(32), subject(32), object(32)); -- Prefix length is arbitrary

