<?php
declare(strict_types=1);

namespace PCMS\Repositories;

use PCMS\Support\Database;

final class ProblemRepository
{
    public function forContest(int $contestId,bool $includeArchived=false): array
    {
        $extra=$includeArchived?'':" AND problem_status='ACTIVE'";
        return Database::all("SELECT problem_id,contest_id,problem_code,problem_title,difficulty,time_limit_ms,memory_limit_mb,display_order,problem_status FROM problem WHERE contest_id=:id {$extra} ORDER BY display_order",['id'=>$contestId]);
    }

    public function find(int $id): ?array
    {
        return Database::one("SELECT p.*,c.contest_title,c.organizer_user_id,v.lifecycle_status FROM problem p JOIN contest c ON c.contest_id=p.contest_id JOIN v_contest_status v ON v.contest_id=c.contest_id WHERE p.problem_id=:id",['id'=>$id]);
    }

    public function save(?int $id,int $owner,array $d): int
    {
        if($id){
            Database::execute("UPDATE problem p SET problem_code=:code,problem_title=:title,problem_statement=:statement,input_format=:input_format,output_format=:output_format,constraints_text=:constraints_text,sample_input=:sample_input,sample_output=:sample_output,difficulty=:difficulty,time_limit_ms=:time_limit,memory_limit_mb=:memory_limit,display_order=:display_order,updated_at=SYSTIMESTAMP WHERE problem_id=:id AND EXISTS(SELECT 1 FROM contest c WHERE c.contest_id=p.contest_id AND c.organizer_user_id=:owner)",$d+compact('id','owner'));
            return $id;
        }
        $id=(int)Database::scalar('SELECT seq_problem.NEXTVAL FROM dual');
        Database::execute("INSERT INTO problem(problem_id,contest_id,problem_code,problem_title,problem_statement,input_format,output_format,constraints_text,sample_input,sample_output,difficulty,time_limit_ms,memory_limit_mb,display_order) SELECT :id,:contest_id,:code,:title,:statement,:input_format,:output_format,:constraints_text,:sample_input,:sample_output,:difficulty,:time_limit,:memory_limit,:display_order FROM contest WHERE contest_id=:contest_id AND organizer_user_id=:owner",$d+compact('id','owner'));
        return $id;
    }

    public function archive(int $id,int $owner): void
    {
        Database::execute("UPDATE problem p SET problem_status='ARCHIVED',updated_at=SYSTIMESTAMP WHERE problem_id=:id AND EXISTS(SELECT 1 FROM contest c WHERE c.contest_id=p.contest_id AND c.organizer_user_id=:owner)",compact('id','owner'));
    }

    public function testCases(int $problemId,int $owner): array
    {
        return Database::all("SELECT tc.test_case_id,tc.test_input,tc.expected_output,tc.is_hidden,tc.weight FROM test_case tc JOIN problem p ON p.problem_id=tc.problem_id JOIN contest c ON c.contest_id=p.contest_id WHERE tc.problem_id=:id AND c.organizer_user_id=:owner ORDER BY tc.test_case_id",['id'=>$problemId,'owner'=>$owner]);
    }

    public function saveTestCase(?int $testId,int $problemId,int $owner,array $d): void
    {
        if($testId){
            Database::execute("UPDATE test_case tc SET test_input=:test_input,expected_output=:expected_output,is_hidden=:is_hidden,weight=:weight WHERE test_case_id=:test_id AND problem_id=:problem_id AND EXISTS(SELECT 1 FROM problem p JOIN contest c ON c.contest_id=p.contest_id WHERE p.problem_id=tc.problem_id AND c.organizer_user_id=:owner)",$d+['test_id'=>$testId,'problem_id'=>$problemId,'owner'=>$owner]);
            return;
        }
        $id=(int)Database::scalar('SELECT seq_test_case.NEXTVAL FROM dual');
        Database::execute("INSERT INTO test_case(test_case_id,problem_id,test_input,expected_output,is_hidden,weight) SELECT :id,:problem_id,:test_input,:expected_output,:is_hidden,:weight FROM problem p JOIN contest c ON c.contest_id=p.contest_id WHERE p.problem_id=:problem_id AND c.organizer_user_id=:owner",$d+['id'=>$id,'problem_id'=>$problemId,'owner'=>$owner]);
    }

    public function deleteTestCase(int $testId,int $owner): void
    {
        Database::execute("DELETE FROM test_case tc WHERE test_case_id=:id AND EXISTS(SELECT 1 FROM problem p JOIN contest c ON c.contest_id=p.contest_id WHERE p.problem_id=tc.problem_id AND c.organizer_user_id=:owner)",['id'=>$testId,'owner'=>$owner]);
    }
}
