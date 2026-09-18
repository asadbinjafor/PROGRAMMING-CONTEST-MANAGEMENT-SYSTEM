WHENEVER SQLERROR EXIT SQL.SQLCODE

ALTER TABLE app_user ADD CONSTRAINT ck_user_status CHECK (account_status IN ('ACTIVE','SUSPENDED','DISABLED'));
ALTER TABLE app_user ADD CONSTRAINT ck_user_rating CHECK (rating >= 0);
ALTER TABLE contest ADD CONSTRAINT ck_contest_visibility CHECK (visibility IN ('PUBLIC','PRIVATE'));
ALTER TABLE contest ADD CONSTRAINT ck_contest_approval CHECK (approval_status IN ('DRAFT','PENDING_APPROVAL','APPROVED','REJECTED'));
ALTER TABLE contest ADD CONSTRAINT ck_contest_status CHECK (contest_status IN ('ACTIVE','CANCELLED'));
ALTER TABLE contest ADD CONSTRAINT ck_contest_time CHECK (end_time > start_time);
ALTER TABLE contest ADD CONSTRAINT ck_registration_deadline CHECK (registration_deadline <= start_time);
ALTER TABLE contest_registration ADD CONSTRAINT ck_registration_status CHECK (registration_status IN ('REGISTERED','CANCELLED','WAITLISTED'));
ALTER TABLE problem ADD CONSTRAINT ck_problem_difficulty CHECK (difficulty IN ('EASY','MEDIUM','HARD'));
ALTER TABLE problem ADD CONSTRAINT ck_problem_limits CHECK (time_limit_ms > 0 AND memory_limit_mb > 0 AND display_order > 0);
ALTER TABLE problem ADD CONSTRAINT ck_problem_status CHECK (problem_status IN ('ACTIVE','ARCHIVED'));
ALTER TABLE test_case ADD CONSTRAINT ck_test_hidden CHECK (is_hidden IN ('Y','N'));
ALTER TABLE test_case ADD CONSTRAINT ck_test_weight CHECK (weight > 0);
ALTER TABLE submission ADD CONSTRAINT ck_submission_language CHECK (language IN ('C','CPP','JAVA','PYTHON'));
ALTER TABLE submission ADD CONSTRAINT ck_submission_verdict CHECK (verdict IN ('PENDING','QUEUED','RUNNING','ACCEPTED','WRONG_ANSWER','TIME_LIMIT_EXCEEDED','MEMORY_LIMIT_EXCEEDED','RUNTIME_ERROR','COMPILATION_ERROR','SYSTEM_ERROR'));
ALTER TABLE submission ADD CONSTRAINT ck_submission_metrics CHECK ((execution_time_ms IS NULL OR execution_time_ms >= 0) AND (memory_used_kb IS NULL OR memory_used_kb >= 0));
ALTER TABLE announcement ADD CONSTRAINT ck_announcement_archived CHECK (is_archived IN ('Y','N'));
ALTER TABLE clarification ADD CONSTRAINT ck_clarification_status CHECK (clarification_status IN ('OPEN','ANSWERED','CLOSED'));
ALTER TABLE clarification ADD CONSTRAINT ck_clarification_public CHECK (is_public IN ('Y','N'));
ALTER TABLE clarification ADD CONSTRAINT ck_clarification_answer CHECK ((clarification_status = 'OPEN' AND answer IS NULL AND answerer_user_id IS NULL AND answered_at IS NULL) OR (clarification_status IN ('ANSWERED','CLOSED') AND answer IS NOT NULL AND answerer_user_id IS NOT NULL AND answered_at IS NOT NULL));

CREATE INDEX ix_user_role_role ON user_role(role_id);
CREATE INDEX ix_contest_organizer ON contest(organizer_user_id);
CREATE INDEX ix_contest_schedule ON contest(approval_status, contest_status, start_time, end_time);
CREATE INDEX ix_registration_user ON contest_registration(user_id, registration_status);
CREATE INDEX ix_problem_contest ON problem(contest_id, display_order);
CREATE INDEX ix_test_case_problem ON test_case(problem_id);
CREATE INDEX ix_submission_user_date ON submission(user_id, submitted_at);
CREATE INDEX ix_submission_problem_date ON submission(problem_id, submitted_at);
CREATE INDEX ix_submission_verdict ON submission(verdict);
CREATE INDEX ix_announcement_contest ON announcement(contest_id, created_at);
CREATE INDEX ix_clarification_contest ON clarification(contest_id, clarification_status, asked_at);
CREATE INDEX ix_clarification_asker ON clarification(asker_user_id, asked_at);
CREATE INDEX ix_reset_user ON password_reset_token(user_id, expires_at);
CREATE INDEX ix_audit_actor_date ON audit_log(actor_user_id, created_at);
CREATE INDEX ix_audit_entity ON audit_log(entity_type, entity_id);

CREATE OR REPLACE VIEW v_contest_status AS
SELECT c.*,
       CASE
           WHEN c.contest_status = 'CANCELLED' THEN 'CANCELLED'
           WHEN c.approval_status <> 'APPROVED' THEN c.approval_status
           WHEN SYSTIMESTAMP < c.start_time THEN 'UPCOMING'
           WHEN SYSTIMESTAMP <= c.end_time THEN 'ONGOING'
           ELSE 'COMPLETED'
       END AS lifecycle_status,
       ROUND((CAST(SYS_EXTRACT_UTC(c.end_time) AS DATE) - CAST(SYS_EXTRACT_UTC(c.start_time) AS DATE)) * 24 * 60) AS duration_minutes,
       (SELECT COUNT(*) FROM contest_registration cr WHERE cr.contest_id = c.contest_id AND cr.registration_status = 'REGISTERED') AS participant_count
FROM contest c;

CREATE OR REPLACE VIEW v_leaderboard AS
WITH accepted AS (
    SELECT p.contest_id, s.user_id, s.problem_id, MIN(s.submitted_at) accepted_at
    FROM submission s JOIN problem p ON p.problem_id = s.problem_id
    WHERE s.verdict = 'ACCEPTED'
    GROUP BY p.contest_id, s.user_id, s.problem_id
), score AS (
    SELECT cr.contest_id,
           cr.user_id,
           COUNT(a.problem_id) solved_count,
           NVL(SUM(
               CASE WHEN a.problem_id IS NULL THEN 0 ELSE
                   ROUND((CAST(SYS_EXTRACT_UTC(a.accepted_at) AS DATE) - CAST(SYS_EXTRACT_UTC(c.start_time) AS DATE)) * 24 * 60)
                   + 20 * (SELECT COUNT(*) FROM submission sw JOIN problem pw ON pw.problem_id = sw.problem_id WHERE pw.contest_id = cr.contest_id AND sw.user_id = cr.user_id AND sw.problem_id = a.problem_id AND sw.submitted_at < a.accepted_at AND sw.verdict IN ('WRONG_ANSWER','TIME_LIMIT_EXCEEDED','MEMORY_LIMIT_EXCEEDED','RUNTIME_ERROR'))
               END
           ), 0) penalty_minutes
    FROM contest_registration cr
    JOIN contest c ON c.contest_id = cr.contest_id
    LEFT JOIN accepted a ON a.contest_id = cr.contest_id AND a.user_id = cr.user_id
    WHERE cr.registration_status = 'REGISTERED'
    GROUP BY cr.contest_id, cr.user_id
)
SELECT s.contest_id, s.user_id, u.user_name, u.institution, u.rating,
       s.solved_count, s.penalty_minutes,
       DENSE_RANK() OVER (PARTITION BY s.contest_id ORDER BY s.solved_count DESC, s.penalty_minutes ASC) rank_position
FROM score s JOIN app_user u ON u.user_id = s.user_id;

PROMPT 02_constraints_indexes.sql complete
