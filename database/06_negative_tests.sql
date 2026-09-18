WHENEVER SQLERROR EXIT SQL.SQLCODE
SET SERVEROUTPUT ON

DECLARE
    v_passed NUMBER := 0;
    PROCEDURE expect_error(p_name VARCHAR2, p_sql VARCHAR2) IS
    BEGIN
        EXECUTE IMMEDIATE p_sql;
        RAISE_APPLICATION_ERROR(-20999,'Expected failure did not occur: '||p_name);
    EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE = -20999 THEN RAISE; END IF;
            v_passed := v_passed + 1;
            DBMS_OUTPUT.PUT_LINE('PASS '||p_name||': '||SQLERRM);
    END;
BEGIN
    expect_error('invalid account status', q'[INSERT INTO app_user(user_id,user_name,email,password_hash,account_status) VALUES(99991,'Invalid','invalid-status@pcms.test','x','UNKNOWN')]');
    expect_error('invalid contest time', q'[UPDATE contest SET end_time=start_time WHERE contest_id=1]');
    expect_error('invalid submission verdict', q'[UPDATE submission SET verdict='MAGIC' WHERE submission_id=1]');
    expect_error('inconsistent clarification answer', q'[UPDATE clarification SET answer=NULL WHERE clarification_id=1]');
    BEGIN
        sp_register_participant(1,3);
        RAISE_APPLICATION_ERROR(-20999,'Expected duplicate registration failure did not occur.');
    EXCEPTION WHEN OTHERS THEN
        IF SQLCODE = -20999 THEN RAISE; END IF;
        v_passed := v_passed + 1;
        DBMS_OUTPUT.PUT_LINE('PASS duplicate registration: '||SQLERRM);
    END;
    ROLLBACK;
    IF v_passed <> 5 THEN RAISE_APPLICATION_ERROR(-20998,'Expected five negative tests.'); END IF;
    DBMS_OUTPUT.PUT_LINE('All five negative database tests passed.');
END;
/
