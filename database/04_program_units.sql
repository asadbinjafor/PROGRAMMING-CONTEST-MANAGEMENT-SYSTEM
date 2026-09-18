WHENEVER SQLERROR EXIT SQL.SQLCODE
SET SERVEROUTPUT ON

CREATE OR REPLACE PROCEDURE sp_audit (
    p_actor_user_id IN audit_log.actor_user_id%TYPE,
    p_action_code IN audit_log.action_code%TYPE,
    p_entity_type IN audit_log.entity_type%TYPE,
    p_entity_id IN audit_log.entity_id%TYPE,
    p_details IN audit_log.details%TYPE,
    p_ip_address IN audit_log.ip_address%TYPE
) IS
BEGIN
    INSERT INTO audit_log (audit_id,actor_user_id,action_code,entity_type,entity_id,details,ip_address)
    VALUES (seq_audit_log.NEXTVAL,p_actor_user_id,p_action_code,p_entity_type,p_entity_id,p_details,p_ip_address);
END;
/

CREATE OR REPLACE PROCEDURE sp_register_participant (
    p_contest_id IN contest.contest_id%TYPE,
    p_user_id IN app_user.user_id%TYPE
) IS
    v_count NUMBER;
    v_deadline contest.registration_deadline%TYPE;
    v_approval contest.approval_status%TYPE;
    v_status contest.contest_status%TYPE;
BEGIN
    SELECT registration_deadline, approval_status, contest_status
      INTO v_deadline, v_approval, v_status
      FROM contest WHERE contest_id = p_contest_id FOR UPDATE;
    IF v_approval <> 'APPROVED' OR v_status <> 'ACTIVE' THEN
        RAISE_APPLICATION_ERROR(-20001,'Contest is not open for registration.');
    END IF;
    IF SYSTIMESTAMP > v_deadline THEN
        RAISE_APPLICATION_ERROR(-20002,'Registration deadline has passed.');
    END IF;
    SELECT COUNT(*) INTO v_count FROM user_role ur JOIN role r ON r.role_id=ur.role_id WHERE ur.user_id=p_user_id AND r.role_code='PARTICIPANT';
    IF v_count = 0 THEN RAISE_APPLICATION_ERROR(-20003,'Only participants may register.'); END IF;
    SELECT COUNT(*) INTO v_count FROM contest_registration WHERE contest_id=p_contest_id AND user_id=p_user_id;
    IF v_count = 0 THEN
        INSERT INTO contest_registration (registration_id,contest_id,user_id,registration_status)
        VALUES (seq_registration.NEXTVAL,p_contest_id,p_user_id,'REGISTERED');
    ELSE
        UPDATE contest_registration SET registration_status='REGISTERED',registered_at=SYSTIMESTAMP,cancelled_at=NULL
        WHERE contest_id=p_contest_id AND user_id=p_user_id AND registration_status='CANCELLED';
        IF SQL%ROWCOUNT=0 THEN RAISE_APPLICATION_ERROR(-20004,'Participant is already registered.'); END IF;
    END IF;
EXCEPTION
    WHEN NO_DATA_FOUND THEN RAISE_APPLICATION_ERROR(-20005,'Contest not found.');
END;
/

CREATE OR REPLACE PROCEDURE sp_add_announcement (
    p_contest_id IN contest.contest_id%TYPE,
    p_author_user_id IN app_user.user_id%TYPE,
    p_title IN announcement.announcement_title%TYPE,
    p_message IN announcement.message%TYPE
) IS
    v_owner NUMBER;
BEGIN
    IF TRIM(p_title) IS NULL OR TRIM(p_message) IS NULL THEN RAISE_APPLICATION_ERROR(-20010,'Title and message are required.'); END IF;
    SELECT organizer_user_id INTO v_owner FROM contest WHERE contest_id=p_contest_id;
    IF v_owner <> p_author_user_id THEN RAISE_APPLICATION_ERROR(-20011,'Only the contest organizer may publish.'); END IF;
    INSERT INTO announcement (announcement_id,contest_id,author_user_id,announcement_title,message)
    VALUES (seq_announcement.NEXTVAL,p_contest_id,p_author_user_id,p_title,p_message);
EXCEPTION WHEN NO_DATA_FOUND THEN RAISE_APPLICATION_ERROR(-20012,'Contest not found.');
END;
/

CREATE OR REPLACE PROCEDURE sp_answer_clarification (
    p_clarification_id IN clarification.clarification_id%TYPE,
    p_answerer_user_id IN app_user.user_id%TYPE,
    p_answer IN clarification.answer%TYPE,
    p_is_public IN clarification.is_public%TYPE
) IS
BEGIN
    IF TRIM(p_answer) IS NULL THEN RAISE_APPLICATION_ERROR(-20020,'Answer is required.'); END IF;
    UPDATE clarification cl
       SET answer=p_answer, answerer_user_id=p_answerer_user_id, clarification_status='ANSWERED', is_public=p_is_public, answered_at=SYSTIMESTAMP
     WHERE clarification_id=p_clarification_id
       AND EXISTS (SELECT 1 FROM contest c WHERE c.contest_id=cl.contest_id AND c.organizer_user_id=p_answerer_user_id);
    IF SQL%ROWCOUNT=0 THEN RAISE_APPLICATION_ERROR(-20021,'Clarification not found or access denied.'); END IF;
END;
/

CREATE OR REPLACE FUNCTION fn_user_submission_count (p_user_id IN app_user.user_id%TYPE) RETURN NUMBER IS
    v_count NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_count FROM submission WHERE user_id=p_user_id;
    RETURN v_count;
END;
/

CREATE OR REPLACE FUNCTION fn_contest_problem_count (p_contest_id IN contest.contest_id%TYPE) RETURN NUMBER IS
    v_count NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_count FROM problem WHERE contest_id=p_contest_id;
    RETURN v_count;
END;
/

CREATE OR REPLACE TRIGGER trg_participant_rating_change
AFTER UPDATE OF rating ON app_user FOR EACH ROW
BEGIN
    INSERT INTO audit_log (audit_id,actor_user_id,action_code,entity_type,entity_id,details,ip_address)
    VALUES (seq_audit_log.NEXTVAL,NULL,'RATING_CHANGED','APP_USER',:NEW.user_id,'Previous='||:OLD.rating||'; New='||:NEW.rating||'; Difference='||(:NEW.rating-:OLD.rating),NULL);
END;
/

CREATE OR REPLACE TRIGGER trg_new_submission_row
AFTER INSERT ON submission FOR EACH ROW
BEGIN
    INSERT INTO audit_log (audit_id,actor_user_id,action_code,entity_type,entity_id,details,ip_address)
    VALUES (seq_audit_log.NEXTVAL,:NEW.user_id,'SUBMISSION_CREATED','SUBMISSION',:NEW.submission_id,'Problem='||:NEW.problem_id||'; Language='||:NEW.language||'; Verdict='||NVL(:NEW.verdict,'NOT SET'),NULL);
END;
/

CREATE OR REPLACE TRIGGER trg_submission_statement
AFTER INSERT ON submission
BEGIN
    DBMS_OUTPUT.PUT_LINE('Submission insert statement completed.');
END;
/

CREATE OR REPLACE TRIGGER trg_contest_status_statement
AFTER UPDATE OF contest_status ON contest
BEGIN
    DBMS_OUTPUT.PUT_LINE('Contest status update statement completed.');
END;
/

CREATE OR REPLACE PACKAGE user_pack AS
    PROCEDURE display_user_name(p_user_id app_user.user_id%TYPE);
    PROCEDURE display_user_rating(p_user_id app_user.user_id%TYPE);
END user_pack;
/
CREATE OR REPLACE PACKAGE BODY user_pack AS
    PROCEDURE display_user_name(p_user_id app_user.user_id%TYPE) IS v_name app_user.user_name%TYPE;
    BEGIN SELECT user_name INTO v_name FROM app_user WHERE user_id=p_user_id; DBMS_OUTPUT.PUT_LINE('User Name: '||v_name);
    EXCEPTION WHEN NO_DATA_FOUND THEN DBMS_OUTPUT.PUT_LINE('User not found.'); END;
    PROCEDURE display_user_rating(p_user_id app_user.user_id%TYPE) IS v_rating app_user.rating%TYPE;
    BEGIN SELECT rating INTO v_rating FROM app_user WHERE user_id=p_user_id; DBMS_OUTPUT.PUT_LINE('User Rating: '||v_rating);
    EXCEPTION WHEN NO_DATA_FOUND THEN DBMS_OUTPUT.PUT_LINE('User not found.'); END;
END user_pack;
/

CREATE OR REPLACE PACKAGE problem_pack AS
    PROCEDURE display_problem_title(p_problem_id problem.problem_id%TYPE);
    PROCEDURE display_problem_difficulty(p_problem_id problem.problem_id%TYPE);
END problem_pack;
/
CREATE OR REPLACE PACKAGE BODY problem_pack AS
    PROCEDURE display_problem_title(p_problem_id problem.problem_id%TYPE) IS v_title problem.problem_title%TYPE;
    BEGIN SELECT problem_title INTO v_title FROM problem WHERE problem_id=p_problem_id; DBMS_OUTPUT.PUT_LINE('Problem Title: '||v_title);
    EXCEPTION WHEN NO_DATA_FOUND THEN DBMS_OUTPUT.PUT_LINE('Problem not found.'); END;
    PROCEDURE display_problem_difficulty(p_problem_id problem.problem_id%TYPE) IS v_difficulty problem.difficulty%TYPE;
    BEGIN SELECT difficulty INTO v_difficulty FROM problem WHERE problem_id=p_problem_id; DBMS_OUTPUT.PUT_LINE('Problem Difficulty: '||v_difficulty);
    EXCEPTION WHEN NO_DATA_FOUND THEN DBMS_OUTPUT.PUT_LINE('Problem not found.'); END;
END problem_pack;
/

PROMPT 04_program_units.sql complete
