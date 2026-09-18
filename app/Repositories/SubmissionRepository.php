<?php
declare(strict_types=1);

namespace PCMS\Repositories;

use PCMS\Support\Database;
use RuntimeException;

final class SubmissionRepository
{
    public function forUser(int $userId,int $page=1): array
    {
        $start=(($page-1)*20)+1;$end=$page*20;
        return Database::all("SELECT * FROM (SELECT s.submission_id,s.language,s.verdict,s.execution_time_ms,s.memory_used_kb,s.submitted_at,p.problem_code,p.problem_title,c.contest_title,ROW_NUMBER() OVER(ORDER BY s.submitted_at DESC) rn FROM submission s JOIN problem p ON p.problem_id=s.problem_id JOIN contest c ON c.contest_id=p.contest_id WHERE s.user_id=:id) WHERE rn BETWEEN :row_start AND :row_end",['id'=>$userId,'row_start'=>$start,'row_end'=>$end]);
    }

    public function find(int $id): ?array
    {
        return Database::one("SELECT s.*,p.problem_code,p.problem_title,p.contest_id,c.contest_title,c.organizer_user_id,u.user_name FROM submission s JOIN problem p ON p.problem_id=s.problem_id JOIN contest c ON c.contest_id=p.contest_id JOIN app_user u ON u.user_id=s.user_id WHERE s.submission_id=:id",['id'=>$id]);
    }

    public function create(int $userId,int $problemId,string $language,string $source): int
    {
        return Database::transaction(function()use($userId,$problemId,$language,$source):int{
            $eligible=(int)Database::scalar("SELECT COUNT(*) FROM problem p JOIN v_contest_status v ON v.contest_id=p.contest_id JOIN contest_registration cr ON cr.contest_id=p.contest_id AND cr.user_id=:user_id AND cr.registration_status='REGISTERED' WHERE p.problem_id=:problem_id AND p.problem_status='ACTIVE' AND v.lifecycle_status='ONGOING'",['user_id'=>$userId,'problem_id'=>$problemId]);
            if(!$eligible) throw new RuntimeException('Submission is allowed only for registered participants during an ongoing contest.');
            $id=(int)Database::scalar('SELECT seq_submission.NEXTVAL FROM dual');
            Database::execute("INSERT INTO submission(submission_id,user_id,problem_id,language,source_code,verdict) VALUES(:id,:user_id,:problem_id,:language,:source,'PENDING')",['id'=>$id,'user_id'=>$userId,'problem_id'=>$problemId,'language'=>$language,'source'=>$source],false);
            return $id;
        });
    }

    public function pendingForOrganizer(int $owner,int $page=1): array
    {
        $start=(($page-1)*20)+1;$end=$page*20;
        return Database::all("SELECT * FROM (SELECT s.submission_id,s.verdict,s.language,s.submitted_at,p.problem_title,c.contest_title,u.user_name,ROW_NUMBER() OVER(ORDER BY s.submitted_at) rn FROM submission s JOIN problem p ON p.problem_id=s.problem_id JOIN contest c ON c.contest_id=p.contest_id JOIN app_user u ON u.user_id=s.user_id WHERE c.organizer_user_id=:owner) WHERE rn BETWEEN :row_start AND :row_end",['owner'=>$owner,'row_start'=>$start,'row_end'=>$end]);
    }

    public function judge(int $id,int $owner,string $verdict,?float $time,?int $memory,?string $message): void
    {
        Database::execute("UPDATE submission s SET verdict=:verdict,execution_time_ms=:time,memory_used_kb=:memory,judge_message=:message,judged_at=SYSTIMESTAMP WHERE submission_id=:id AND EXISTS(SELECT 1 FROM problem p JOIN contest c ON c.contest_id=p.contest_id WHERE p.problem_id=s.problem_id AND c.organizer_user_id=:owner)",compact('id','owner','verdict','time','memory','message'));
    }
}
