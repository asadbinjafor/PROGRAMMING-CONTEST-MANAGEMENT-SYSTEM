WHENEVER SQLERROR EXIT SQL.SQLCODE
SET DEFINE OFF

CREATE TABLE role (
    role_id NUMBER PRIMARY KEY,
    role_code VARCHAR2(20) NOT NULL UNIQUE,
    role_name VARCHAR2(60) NOT NULL
);

CREATE TABLE app_user (
    user_id NUMBER PRIMARY KEY,
    user_name VARCHAR2(100) NOT NULL,
    email VARCHAR2(150) NOT NULL UNIQUE,
    password_hash VARCHAR2(255) NOT NULL,
    institution VARCHAR2(150),
    rating NUMBER(8,2) DEFAULT 0 NOT NULL,
    profile_image VARCHAR2(255),
    account_status VARCHAR2(20) DEFAULT 'ACTIVE' NOT NULL,
    remember_selector VARCHAR2(64),
    remember_validator_hash VARCHAR2(255),
    remember_expires_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT SYSTIMESTAMP NOT NULL,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT SYSTIMESTAMP NOT NULL
);

CREATE TABLE user_role (
    user_id NUMBER NOT NULL,
    role_id NUMBER NOT NULL,
    assigned_at TIMESTAMP WITH TIME ZONE DEFAULT SYSTIMESTAMP NOT NULL,
    CONSTRAINT pk_user_role PRIMARY KEY (user_id, role_id),
    CONSTRAINT fk_ur_user FOREIGN KEY (user_id) REFERENCES app_user(user_id),
    CONSTRAINT fk_ur_role FOREIGN KEY (role_id) REFERENCES role(role_id)
);

CREATE TABLE contest (
    contest_id NUMBER PRIMARY KEY,
    organizer_user_id NUMBER NOT NULL,
    contest_title VARCHAR2(150) NOT NULL,
    slug VARCHAR2(170) NOT NULL UNIQUE,
    description VARCHAR2(2000),
    start_time TIMESTAMP WITH TIME ZONE NOT NULL,
    end_time TIMESTAMP WITH TIME ZONE NOT NULL,
    registration_deadline TIMESTAMP WITH TIME ZONE NOT NULL,
    visibility VARCHAR2(20) DEFAULT 'PUBLIC' NOT NULL,
    invite_code_hash VARCHAR2(255),
    approval_status VARCHAR2(20) DEFAULT 'DRAFT' NOT NULL,
    contest_status VARCHAR2(20) DEFAULT 'ACTIVE' NOT NULL,
    rejection_reason VARCHAR2(1000),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT SYSTIMESTAMP NOT NULL,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT SYSTIMESTAMP NOT NULL,
    CONSTRAINT fk_contest_organizer FOREIGN KEY (organizer_user_id) REFERENCES app_user(user_id)
);

CREATE TABLE contest_registration (
    registration_id NUMBER PRIMARY KEY,
    contest_id NUMBER NOT NULL,
    user_id NUMBER NOT NULL,
    registration_status VARCHAR2(20) DEFAULT 'REGISTERED' NOT NULL,
    registered_at TIMESTAMP WITH TIME ZONE DEFAULT SYSTIMESTAMP NOT NULL,
    cancelled_at TIMESTAMP WITH TIME ZONE,
    CONSTRAINT uq_registration UNIQUE (contest_id, user_id),
    CONSTRAINT fk_registration_contest FOREIGN KEY (contest_id) REFERENCES contest(contest_id),
    CONSTRAINT fk_registration_user FOREIGN KEY (user_id) REFERENCES app_user(user_id)
);

CREATE TABLE problem (
    problem_id NUMBER PRIMARY KEY,
    contest_id NUMBER NOT NULL,
    problem_code VARCHAR2(10) NOT NULL,
    problem_title VARCHAR2(150) NOT NULL,
    problem_statement CLOB NOT NULL,
    input_format CLOB NOT NULL,
    output_format CLOB NOT NULL,
    constraints_text CLOB,
    sample_input CLOB,
    sample_output CLOB,
    difficulty VARCHAR2(20) NOT NULL,
    time_limit_ms NUMBER NOT NULL,
    memory_limit_mb NUMBER NOT NULL,
    display_order NUMBER NOT NULL,
    problem_status VARCHAR2(20) DEFAULT 'ACTIVE' NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT SYSTIMESTAMP NOT NULL,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT SYSTIMESTAMP NOT NULL,
    CONSTRAINT uq_problem_code UNIQUE (contest_id, problem_code),
    CONSTRAINT fk_problem_contest FOREIGN KEY (contest_id) REFERENCES contest(contest_id)
);

CREATE TABLE test_case (
    test_case_id NUMBER PRIMARY KEY,
    problem_id NUMBER NOT NULL,
    test_input CLOB NOT NULL,
    expected_output CLOB NOT NULL,
    is_hidden CHAR(1) DEFAULT 'Y' NOT NULL,
    weight NUMBER(5,2) DEFAULT 1 NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT SYSTIMESTAMP NOT NULL,
    CONSTRAINT fk_test_case_problem FOREIGN KEY (problem_id) REFERENCES problem(problem_id)
);

CREATE TABLE submission (
    submission_id NUMBER PRIMARY KEY,
    user_id NUMBER NOT NULL,
    problem_id NUMBER NOT NULL,
    language VARCHAR2(30) NOT NULL,
    source_code CLOB NOT NULL,
    verdict VARCHAR2(40) DEFAULT 'PENDING' NOT NULL,
    execution_time_ms NUMBER(12,3),
    memory_used_kb NUMBER,
    judge_message VARCHAR2(2000),
    submitted_at TIMESTAMP WITH TIME ZONE DEFAULT SYSTIMESTAMP NOT NULL,
    judged_at TIMESTAMP WITH TIME ZONE,
    CONSTRAINT fk_submission_user FOREIGN KEY (user_id) REFERENCES app_user(user_id),
    CONSTRAINT fk_submission_problem FOREIGN KEY (problem_id) REFERENCES problem(problem_id)
);

CREATE TABLE announcement (
    announcement_id NUMBER PRIMARY KEY,
    contest_id NUMBER NOT NULL,
    author_user_id NUMBER NOT NULL,
    announcement_title VARCHAR2(150) NOT NULL,
    message VARCHAR2(2000) NOT NULL,
    is_archived CHAR(1) DEFAULT 'N' NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT SYSTIMESTAMP NOT NULL,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT SYSTIMESTAMP NOT NULL,
    CONSTRAINT fk_announcement_contest FOREIGN KEY (contest_id) REFERENCES contest(contest_id),
    CONSTRAINT fk_announcement_author FOREIGN KEY (author_user_id) REFERENCES app_user(user_id)
);

CREATE TABLE clarification (
    clarification_id NUMBER PRIMARY KEY,
    contest_id NUMBER NOT NULL,
    asker_user_id NUMBER NOT NULL,
    answerer_user_id NUMBER,
    question VARCHAR2(1000) NOT NULL,
    answer VARCHAR2(2000),
    clarification_status VARCHAR2(20) DEFAULT 'OPEN' NOT NULL,
    is_public CHAR(1) DEFAULT 'N' NOT NULL,
    asked_at TIMESTAMP WITH TIME ZONE DEFAULT SYSTIMESTAMP NOT NULL,
    answered_at TIMESTAMP WITH TIME ZONE,
    CONSTRAINT fk_clarification_contest FOREIGN KEY (contest_id) REFERENCES contest(contest_id),
    CONSTRAINT fk_clarification_asker FOREIGN KEY (asker_user_id) REFERENCES app_user(user_id),
    CONSTRAINT fk_clarification_answerer FOREIGN KEY (answerer_user_id) REFERENCES app_user(user_id)
);

CREATE TABLE password_reset_token (
    token_id NUMBER PRIMARY KEY,
    user_id NUMBER NOT NULL,
    selector VARCHAR2(64) NOT NULL UNIQUE,
    validator_hash VARCHAR2(255) NOT NULL,
    expires_at TIMESTAMP WITH TIME ZONE NOT NULL,
    used_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT SYSTIMESTAMP NOT NULL,
    CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES app_user(user_id)
);

CREATE TABLE audit_log (
    audit_id NUMBER PRIMARY KEY,
    actor_user_id NUMBER,
    action_code VARCHAR2(60) NOT NULL,
    entity_type VARCHAR2(40) NOT NULL,
    entity_id NUMBER,
    details VARCHAR2(2000),
    ip_address VARCHAR2(64),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT SYSTIMESTAMP NOT NULL,
    CONSTRAINT fk_audit_actor FOREIGN KEY (actor_user_id) REFERENCES app_user(user_id)
);

CREATE SEQUENCE seq_app_user START WITH 100 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE seq_contest START WITH 100 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE seq_registration START WITH 1000 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE seq_problem START WITH 1000 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE seq_test_case START WITH 10000 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE seq_submission START WITH 10000 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE seq_announcement START WITH 1000 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE seq_clarification START WITH 1000 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE seq_password_reset START WITH 1000 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE seq_audit_log START WITH 10000 INCREMENT BY 1 NOCACHE;

PROMPT 01_schema.sql complete

