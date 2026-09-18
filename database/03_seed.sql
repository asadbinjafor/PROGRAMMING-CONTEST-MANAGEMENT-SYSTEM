WHENEVER SQLERROR EXIT SQL.SQLCODE
SET DEFINE OFF

INSERT INTO role VALUES (1, 'PARTICIPANT', 'Participant');
INSERT INTO role VALUES (2, 'ORGANIZER', 'Organizer');
INSERT INTO role VALUES (3, 'ADMIN', 'Administrator');

INSERT INTO app_user (user_id,user_name,email,password_hash,institution,rating,account_status) VALUES (1,'PCMS Administrator','admin@pcms.test','$2y$12$H6FfYEC.A24JncZZV2ptVuuy25XWSAdbwhcU4VZee6nytUfOeUz5C','AIUB',1900,'ACTIVE');
INSERT INTO app_user (user_id,user_name,email,password_hash,institution,rating,account_status) VALUES (2,'Asad Bin Jafor','organizer@pcms.test','$2y$12$H6FfYEC.A24JncZZV2ptVuuy25XWSAdbwhcU4VZee6nytUfOeUz5C','AIUB',1750,'ACTIVE');
INSERT INTO app_user (user_id,user_name,email,password_hash,institution,rating,account_status) VALUES (3,'Taki Tajuar','participant@pcms.test','$2y$12$H6FfYEC.A24JncZZV2ptVuuy25XWSAdbwhcU4VZee6nytUfOeUz5C','AIUB',1567,'ACTIVE');
INSERT INTO app_user (user_id,user_name,email,password_hash,institution,rating,account_status) VALUES (4,'Shishir Mahmud','shishir@pcms.test','$2y$12$H6FfYEC.A24JncZZV2ptVuuy25XWSAdbwhcU4VZee6nytUfOeUz5C','AIUB',1620,'ACTIVE');

INSERT INTO user_role VALUES (1,3,SYSTIMESTAMP);
INSERT INTO user_role VALUES (2,2,SYSTIMESTAMP);
INSERT INTO user_role VALUES (3,1,SYSTIMESTAMP);
INSERT INTO user_role VALUES (4,1,SYSTIMESTAMP);

INSERT INTO contest (contest_id,organizer_user_id,contest_title,slug,description,start_time,end_time,registration_deadline,visibility,approval_status,contest_status)
VALUES (1,2,'PCMS CodeSprint 2026','pcms-codesprint-2026','A four-hour programming contest focused on algorithms, data structures, and careful problem solving.',TO_TIMESTAMP_TZ('2026-12-20 10:00:00 Asia/Dhaka','YYYY-MM-DD HH24:MI:SS TZR'),TO_TIMESTAMP_TZ('2026-12-20 14:00:00 Asia/Dhaka','YYYY-MM-DD HH24:MI:SS TZR'),TO_TIMESTAMP_TZ('2026-12-19 23:59:00 Asia/Dhaka','YYYY-MM-DD HH24:MI:SS TZR'),'PUBLIC','APPROVED','ACTIVE');

INSERT INTO contest_registration (registration_id,contest_id,user_id,registration_status) VALUES (1,1,3,'REGISTERED');
INSERT INTO contest_registration (registration_id,contest_id,user_id,registration_status) VALUES (2,1,4,'REGISTERED');

INSERT INTO problem (problem_id,contest_id,problem_code,problem_title,problem_statement,input_format,output_format,constraints_text,sample_input,sample_output,difficulty,time_limit_ms,memory_limit_mb,display_order)
VALUES (1,1,'A','Two Sum','Given an array of integers and a target, print the zero-based indices of two distinct values whose sum equals the target. Exactly one valid pair exists.','The first line contains n. The second line contains n integers. The third line contains target.','Print the two zero-based indices in ascending order.','2 <= n <= 100000; values fit in signed 32-bit integers.','5'||CHR(10)||'2 7 11 15 3'||CHR(10)||'9','0 1','EASY',1000,64,1);
INSERT INTO problem (problem_id,contest_id,problem_code,problem_title,problem_statement,input_format,output_format,constraints_text,sample_input,sample_output,difficulty,time_limit_ms,memory_limit_mb,display_order)
VALUES (2,1,'B','Array Rotation','Rotate an array to the right by k positions.','The first line contains n and k. The second line contains n integers.','Print the rotated array.','1 <= n <= 200000.','5 2'||CHR(10)||'1 2 3 4 5','4 5 1 2 3','EASY',1000,64,2);
INSERT INTO problem (problem_id,contest_id,problem_code,problem_title,problem_statement,input_format,output_format,constraints_text,sample_input,sample_output,difficulty,time_limit_ms,memory_limit_mb,display_order)
VALUES (3,1,'C','Longest Subarray','Find the longest subarray satisfying the supplied limit.','See the complete contest statement.','Print the maximum length.','1 <= n <= 200000.','5 7'||CHR(10)||'2 1 5 1 3','3','MEDIUM',2000,128,3);

INSERT INTO test_case (test_case_id,problem_id,test_input,expected_output,is_hidden) VALUES (1,1,'5'||CHR(10)||'2 7 11 15 3'||CHR(10)||'9','0 1','N');
INSERT INTO test_case (test_case_id,problem_id,test_input,expected_output,is_hidden) VALUES (2,1,'4'||CHR(10)||'3 2 4 8'||CHR(10)||'6','1 2','Y');

INSERT INTO submission (submission_id,user_id,problem_id,language,source_code,verdict,execution_time_ms,memory_used_kb,submitted_at,judged_at)
VALUES (1,3,1,'CPP','#include <iostream>'||CHR(10)||'int main(){return 0;}','ACCEPTED',32,1229,TO_TIMESTAMP_TZ('2026-12-20 10:25:00 Asia/Dhaka','YYYY-MM-DD HH24:MI:SS TZR'),TO_TIMESTAMP_TZ('2026-12-20 10:25:05 Asia/Dhaka','YYYY-MM-DD HH24:MI:SS TZR'));
INSERT INTO submission (submission_id,user_id,problem_id,language,source_code,verdict,submitted_at,judged_at)
VALUES (2,4,1,'JAVA','class Main { public static void main(String[] a){} }','WRONG_ANSWER',TO_TIMESTAMP_TZ('2026-12-20 10:20:00 Asia/Dhaka','YYYY-MM-DD HH24:MI:SS TZR'),TO_TIMESTAMP_TZ('2026-12-20 10:20:06 Asia/Dhaka','YYYY-MM-DD HH24:MI:SS TZR'));

INSERT INTO announcement (announcement_id,contest_id,author_user_id,announcement_title,message) VALUES (1,1,2,'Schedule released','PCMS CodeSprint 2026 will run on 20 December 2026 from 10:00 AM to 2:00 PM Asia/Dhaka.');
INSERT INTO clarification (clarification_id,contest_id,asker_user_id,answerer_user_id,question,answer,clarification_status,is_public,answered_at) VALUES (1,1,3,2,'Are indices zero-based?','Yes. Print zero-based indices.','ANSWERED','Y',SYSTIMESTAMP);

COMMIT;
PROMPT Demo password for every seeded account: Password123!
PROMPT 03_seed.sql complete

