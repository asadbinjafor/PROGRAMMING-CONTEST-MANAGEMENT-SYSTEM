WHENEVER SQLERROR EXIT SQL.SQLCODE
SET SERVEROUTPUT ON
SET PAGESIZE 100
SET LINESIZE 200

PROMPT === Object status: expected zero INVALID ===
SELECT object_type, object_name, status FROM user_objects WHERE object_name IN ('ROLE','APP_USER','USER_ROLE','CONTEST','CONTEST_REGISTRATION','PROBLEM','TEST_CASE','SUBMISSION','ANNOUNCEMENT','CLARIFICATION','PASSWORD_RESET_TOKEN','AUDIT_LOG','V_CONTEST_STATUS','V_LEADERBOARD','SP_REGISTER_PARTICIPANT','SP_ADD_ANNOUNCEMENT','SP_ANSWER_CLARIFICATION','USER_PACK','PROBLEM_PACK') AND status <> 'VALID';

PROMPT === Compiler errors: expected no rows ===
SELECT name, type, line, position, text FROM user_errors ORDER BY name, sequence;

PROMPT === Seed counts ===
SELECT 'USERS' entity, COUNT(*) total FROM app_user
UNION ALL SELECT 'CONTESTS',COUNT(*) FROM contest
UNION ALL SELECT 'REGISTRATIONS',COUNT(*) FROM contest_registration
UNION ALL SELECT 'PROBLEMS',COUNT(*) FROM problem
UNION ALL SELECT 'SUBMISSIONS',COUNT(*) FROM submission;

PROMPT === Derived contest status ===
SELECT contest_id,contest_title,lifecycle_status,duration_minutes,participant_count FROM v_contest_status;

PROMPT === Leaderboard ===
SELECT contest_id,rank_position,user_name,solved_count,penalty_minutes FROM v_leaderboard ORDER BY contest_id,rank_position;

BEGIN
    DBMS_OUTPUT.PUT_LINE('Participant submissions='||fn_user_submission_count(3));
    DBMS_OUTPUT.PUT_LINE('Contest problems='||fn_contest_problem_count(1));
    user_pack.display_user_name(3);
    problem_pack.display_problem_title(1);
END;
/

PROMPT 05_verify.sql complete

