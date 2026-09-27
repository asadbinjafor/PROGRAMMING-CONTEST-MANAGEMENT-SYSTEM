-- Run once in a new Supabase project, using SQL Editor as the database owner.
BEGIN;

CREATE SEQUENCE seq_app_user START 100;
CREATE SEQUENCE seq_contest START 100;
CREATE SEQUENCE seq_registration START 1000;
CREATE SEQUENCE seq_problem START 1000;
CREATE SEQUENCE seq_test_case START 10000;
CREATE SEQUENCE seq_submission START 10000;
CREATE SEQUENCE seq_announcement START 1000;
CREATE SEQUENCE seq_clarification START 1000;
CREATE SEQUENCE seq_password_reset START 1000;
CREATE SEQUENCE seq_audit_log START 10000;

CREATE TABLE role (
    role_id BIGINT PRIMARY KEY,
    role_code VARCHAR(20) NOT NULL UNIQUE,
    role_name VARCHAR(60) NOT NULL
);
CREATE TABLE app_user (
    user_id BIGINT PRIMARY KEY DEFAULT nextval('seq_app_user'),
    user_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    institution VARCHAR(150),
    rating NUMERIC(8,2) NOT NULL DEFAULT 0 CHECK (rating >= 0),
    profile_image VARCHAR(255),
    account_status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE' CHECK (account_status IN ('ACTIVE','SUSPENDED','DISABLED')),
    remember_selector VARCHAR(64),
    remember_validator_hash VARCHAR(255),
    remember_expires_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE TABLE user_role (
    user_id BIGINT NOT NULL REFERENCES app_user(user_id),
    role_id BIGINT NOT NULL REFERENCES role(role_id),
    assigned_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    PRIMARY KEY (user_id, role_id)
);
CREATE TABLE contest (
    contest_id BIGINT PRIMARY KEY DEFAULT nextval('seq_contest'),
    organizer_user_id BIGINT NOT NULL REFERENCES app_user(user_id),
    contest_title VARCHAR(150) NOT NULL,
    slug VARCHAR(170) NOT NULL UNIQUE,
    description VARCHAR(2000),
    start_time TIMESTAMPTZ NOT NULL,
    end_time TIMESTAMPTZ NOT NULL,
    registration_deadline TIMESTAMPTZ NOT NULL,
    visibility VARCHAR(20) NOT NULL DEFAULT 'PUBLIC' CHECK (visibility IN ('PUBLIC','PRIVATE')),
    invite_code_hash VARCHAR(255),
    approval_status VARCHAR(20) NOT NULL DEFAULT 'DRAFT' CHECK (approval_status IN ('DRAFT','PENDING_APPROVAL','APPROVED','REJECTED')),
    contest_status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE' CHECK (contest_status IN ('ACTIVE','CANCELLED')),
    rejection_reason VARCHAR(1000),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    CHECK (end_time > start_time),
    CHECK (registration_deadline <= start_time)
);
CREATE TABLE contest_registration (
    registration_id BIGINT PRIMARY KEY DEFAULT nextval('seq_registration'),
    contest_id BIGINT NOT NULL REFERENCES contest(contest_id),
    user_id BIGINT NOT NULL REFERENCES app_user(user_id),
    registration_status VARCHAR(20) NOT NULL DEFAULT 'REGISTERED' CHECK (registration_status IN ('REGISTERED','CANCELLED','WAITLISTED')),
    registered_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    cancelled_at TIMESTAMPTZ,
    UNIQUE (contest_id, user_id)
);
CREATE TABLE problem (
    problem_id BIGINT PRIMARY KEY DEFAULT nextval('seq_problem'),
    contest_id BIGINT NOT NULL REFERENCES contest(contest_id),
    problem_code VARCHAR(10) NOT NULL,
    problem_title VARCHAR(150) NOT NULL,
    problem_statement TEXT NOT NULL,
    input_format TEXT NOT NULL,
    output_format TEXT NOT NULL,
    constraints_text TEXT,
    sample_input TEXT,
    sample_output TEXT,
    difficulty VARCHAR(20) NOT NULL CHECK (difficulty IN ('EASY','MEDIUM','HARD')),
    time_limit_ms INTEGER NOT NULL CHECK (time_limit_ms > 0),
    memory_limit_mb INTEGER NOT NULL CHECK (memory_limit_mb > 0),
    display_order INTEGER NOT NULL CHECK (display_order > 0),
    problem_status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE' CHECK (problem_status IN ('ACTIVE','ARCHIVED')),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE (contest_id, problem_code)
);
CREATE TABLE test_case (
    test_case_id BIGINT PRIMARY KEY DEFAULT nextval('seq_test_case'),
    problem_id BIGINT NOT NULL REFERENCES problem(problem_id),
    test_input TEXT NOT NULL,
    expected_output TEXT NOT NULL,
    is_hidden CHAR(1) NOT NULL DEFAULT 'Y' CHECK (is_hidden IN ('Y','N')),
    weight NUMERIC(5,2) NOT NULL DEFAULT 1 CHECK (weight > 0),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE TABLE submission (
    submission_id BIGINT PRIMARY KEY DEFAULT nextval('seq_submission'),
    user_id BIGINT NOT NULL REFERENCES app_user(user_id),
    problem_id BIGINT NOT NULL REFERENCES problem(problem_id),
    language VARCHAR(30) NOT NULL CHECK (language IN ('C','CPP','JAVA','PYTHON')),
    source_code TEXT NOT NULL,
    verdict VARCHAR(40) NOT NULL DEFAULT 'PENDING' CHECK (verdict IN ('PENDING','QUEUED','RUNNING','ACCEPTED','WRONG_ANSWER','TIME_LIMIT_EXCEEDED','MEMORY_LIMIT_EXCEEDED','RUNTIME_ERROR','COMPILATION_ERROR','SYSTEM_ERROR')),
    execution_time_ms NUMERIC(12,3) CHECK (execution_time_ms >= 0),
    memory_used_kb BIGINT CHECK (memory_used_kb >= 0),
    judge_message VARCHAR(2000),
    submitted_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    judged_at TIMESTAMPTZ
);
CREATE TABLE announcement (
    announcement_id BIGINT PRIMARY KEY DEFAULT nextval('seq_announcement'),
    contest_id BIGINT NOT NULL REFERENCES contest(contest_id),
    author_user_id BIGINT NOT NULL REFERENCES app_user(user_id),
    announcement_title VARCHAR(150) NOT NULL,
    message VARCHAR(2000) NOT NULL,
    is_archived CHAR(1) NOT NULL DEFAULT 'N' CHECK (is_archived IN ('Y','N')),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE TABLE clarification (
    clarification_id BIGINT PRIMARY KEY DEFAULT nextval('seq_clarification'),
    contest_id BIGINT NOT NULL REFERENCES contest(contest_id),
    asker_user_id BIGINT NOT NULL REFERENCES app_user(user_id),
    answerer_user_id BIGINT REFERENCES app_user(user_id),
    question VARCHAR(1000) NOT NULL,
    answer VARCHAR(2000),
    clarification_status VARCHAR(20) NOT NULL DEFAULT 'OPEN' CHECK (clarification_status IN ('OPEN','ANSWERED','CLOSED')),
    is_public CHAR(1) NOT NULL DEFAULT 'N' CHECK (is_public IN ('Y','N')),
    asked_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    answered_at TIMESTAMPTZ,
    CHECK ((clarification_status = 'OPEN' AND answer IS NULL AND answerer_user_id IS NULL AND answered_at IS NULL)
        OR (clarification_status IN ('ANSWERED','CLOSED') AND answer IS NOT NULL AND answerer_user_id IS NOT NULL AND answered_at IS NOT NULL))
);
CREATE TABLE password_reset_token (
    token_id BIGINT PRIMARY KEY DEFAULT nextval('seq_password_reset'),
    user_id BIGINT NOT NULL REFERENCES app_user(user_id),
    selector VARCHAR(64) NOT NULL UNIQUE,
    validator_hash VARCHAR(255) NOT NULL,
    expires_at TIMESTAMPTZ NOT NULL,
    used_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE TABLE audit_log (
    audit_id BIGINT PRIMARY KEY DEFAULT nextval('seq_audit_log'),
    actor_user_id BIGINT REFERENCES app_user(user_id),
    action_code VARCHAR(60) NOT NULL,
    entity_type VARCHAR(40) NOT NULL,
    entity_id BIGINT,
    details VARCHAR(2000),
    ip_address VARCHAR(64),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

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
CREATE INDEX ix_clarification_contest ON clarification(contest_id, clarification_status);
CREATE INDEX ix_reset_user ON password_reset_token(user_id, expires_at);
CREATE INDEX ix_audit_actor_date ON audit_log(actor_user_id, created_at);
CREATE INDEX ix_audit_entity ON audit_log(entity_type, entity_id);

CREATE VIEW v_contest_status AS
SELECT c.*,
       CASE WHEN c.contest_status = 'CANCELLED' THEN 'CANCELLED'
            WHEN c.approval_status <> 'APPROVED' THEN c.approval_status
            WHEN now() < c.start_time THEN 'UPCOMING'
            WHEN now() <= c.end_time THEN 'ONGOING'
            ELSE 'COMPLETED' END AS lifecycle_status,
       ROUND(EXTRACT(EPOCH FROM (c.end_time - c.start_time)) / 60)::BIGINT AS duration_minutes,
       (SELECT COUNT(*) FROM contest_registration cr WHERE cr.contest_id = c.contest_id AND cr.registration_status = 'REGISTERED') AS participant_count
FROM contest c;

CREATE VIEW v_leaderboard AS
WITH accepted AS (
    SELECT p.contest_id, s.user_id, s.problem_id, MIN(s.submitted_at) accepted_at
    FROM submission s JOIN problem p ON p.problem_id = s.problem_id
    WHERE s.verdict = 'ACCEPTED'
    GROUP BY p.contest_id, s.user_id, s.problem_id
), score AS (
    SELECT cr.contest_id, cr.user_id, COUNT(a.problem_id) solved_count,
           COALESCE(SUM(CASE WHEN a.problem_id IS NULL THEN 0 ELSE
               ROUND(EXTRACT(EPOCH FROM (a.accepted_at - c.start_time)) / 60)
               + 20 * (SELECT COUNT(*) FROM submission sw JOIN problem pw ON pw.problem_id = sw.problem_id
                       WHERE pw.contest_id = cr.contest_id AND sw.user_id = cr.user_id AND sw.problem_id = a.problem_id
                         AND sw.submitted_at < a.accepted_at AND sw.verdict IN ('WRONG_ANSWER','TIME_LIMIT_EXCEEDED','MEMORY_LIMIT_EXCEEDED','RUNTIME_ERROR'))
           END), 0) penalty_minutes
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

CREATE FUNCTION sp_register_participant(p_contest_id BIGINT, p_user_id BIGINT) RETURNS void LANGUAGE plpgsql AS $$
DECLARE v_contest contest%ROWTYPE; v_status VARCHAR(20);
BEGIN
    SELECT * INTO v_contest FROM contest WHERE contest_id = p_contest_id FOR UPDATE;
    IF NOT FOUND THEN RAISE EXCEPTION 'Contest not found.'; END IF;
    IF v_contest.approval_status <> 'APPROVED' OR v_contest.contest_status <> 'ACTIVE' OR v_contest.visibility <> 'PUBLIC' THEN
        RAISE EXCEPTION 'Contest is not open for registration.';
    END IF;
    IF now() > v_contest.registration_deadline THEN RAISE EXCEPTION 'Registration deadline has passed.'; END IF;
    IF NOT EXISTS (SELECT 1 FROM user_role ur JOIN role r ON r.role_id=ur.role_id WHERE ur.user_id=p_user_id AND r.role_code='PARTICIPANT') THEN
        RAISE EXCEPTION 'Only participants may register.';
    END IF;
    SELECT registration_status INTO v_status FROM contest_registration WHERE contest_id=p_contest_id AND user_id=p_user_id FOR UPDATE;
    IF NOT FOUND THEN
        INSERT INTO contest_registration(contest_id,user_id) VALUES(p_contest_id,p_user_id);
    ELSIF v_status='CANCELLED' THEN
        UPDATE contest_registration SET registration_status='REGISTERED',registered_at=now(),cancelled_at=NULL
        WHERE contest_id=p_contest_id AND user_id=p_user_id;
    ELSE RAISE EXCEPTION 'Participant is already registered.';
    END IF;
END $$;

CREATE FUNCTION sp_add_announcement(p_contest_id BIGINT, p_author_user_id BIGINT, p_title TEXT, p_message TEXT) RETURNS void LANGUAGE plpgsql AS $$
BEGIN
    IF NULLIF(TRIM(p_title),'') IS NULL OR NULLIF(TRIM(p_message),'') IS NULL THEN RAISE EXCEPTION 'Title and message are required.'; END IF;
    IF NOT EXISTS (SELECT 1 FROM contest WHERE contest_id=p_contest_id AND organizer_user_id=p_author_user_id) THEN
        RAISE EXCEPTION 'Contest not found or access denied.';
    END IF;
    INSERT INTO announcement(contest_id,author_user_id,announcement_title,message) VALUES(p_contest_id,p_author_user_id,p_title,p_message);
END $$;

CREATE FUNCTION sp_answer_clarification(p_id BIGINT, p_owner BIGINT, p_answer TEXT, p_is_public CHAR(1)) RETURNS void LANGUAGE plpgsql AS $$
BEGIN
    IF NULLIF(TRIM(p_answer),'') IS NULL THEN RAISE EXCEPTION 'Answer is required.'; END IF;
    UPDATE clarification cl SET answer=p_answer,answerer_user_id=p_owner,clarification_status='ANSWERED',is_public=p_is_public,answered_at=now()
    WHERE cl.clarification_id=p_id AND EXISTS(SELECT 1 FROM contest c WHERE c.contest_id=cl.contest_id AND c.organizer_user_id=p_owner);
    IF NOT FOUND THEN RAISE EXCEPTION 'Clarification not found or access denied.'; END IF;
END $$;

CREATE FUNCTION audit_submission_created() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
    INSERT INTO audit_log(actor_user_id,action_code,entity_type,entity_id,details)
    VALUES(NEW.user_id,'SUBMISSION_CREATED','SUBMISSION',NEW.submission_id,
           'Problem='||NEW.problem_id||'; Language='||NEW.language||'; Verdict='||COALESCE(NEW.verdict,'NOT SET'));
    RETURN NEW;
END $$;
CREATE TRIGGER trg_new_submission_row AFTER INSERT ON submission FOR EACH ROW EXECUTE FUNCTION audit_submission_created();

CREATE FUNCTION audit_rating_changed() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
    INSERT INTO audit_log(action_code,entity_type,entity_id,details)
    VALUES('RATING_CHANGED','APP_USER',NEW.user_id,'Previous='||OLD.rating||'; New='||NEW.rating||'; Difference='||(NEW.rating-OLD.rating));
    RETURN NEW;
END $$;
CREATE TRIGGER trg_participant_rating_change AFTER UPDATE OF rating ON app_user FOR EACH ROW EXECUTE FUNCTION audit_rating_changed();

-- PHP is the only data access layer. Do not expose PCMS tables through Supabase's Data API.
ALTER TABLE role ENABLE ROW LEVEL SECURITY;
ALTER TABLE app_user ENABLE ROW LEVEL SECURITY;
ALTER TABLE user_role ENABLE ROW LEVEL SECURITY;
ALTER TABLE contest ENABLE ROW LEVEL SECURITY;
ALTER TABLE contest_registration ENABLE ROW LEVEL SECURITY;
ALTER TABLE problem ENABLE ROW LEVEL SECURITY;
ALTER TABLE test_case ENABLE ROW LEVEL SECURITY;
ALTER TABLE submission ENABLE ROW LEVEL SECURITY;
ALTER TABLE announcement ENABLE ROW LEVEL SECURITY;
ALTER TABLE clarification ENABLE ROW LEVEL SECURITY;
ALTER TABLE password_reset_token ENABLE ROW LEVEL SECURITY;
ALTER TABLE audit_log ENABLE ROW LEVEL SECURITY;
REVOKE ALL ON role, app_user, user_role, contest, contest_registration, problem,
    test_case, submission, announcement, clarification, password_reset_token,
    audit_log, v_contest_status, v_leaderboard
    FROM anon, authenticated, service_role;
REVOKE ALL ON SEQUENCE seq_app_user, seq_contest, seq_registration, seq_problem,
    seq_test_case, seq_submission, seq_announcement, seq_clarification,
    seq_password_reset, seq_audit_log FROM anon, authenticated, service_role;
REVOKE ALL ON FUNCTION sp_register_participant(BIGINT, BIGINT),
    sp_add_announcement(BIGINT, BIGINT, TEXT, TEXT),
    sp_answer_clarification(BIGINT, BIGINT, TEXT, CHARACTER),
    audit_submission_created(), audit_rating_changed()
    FROM PUBLIC, anon, authenticated, service_role;

COMMIT;
