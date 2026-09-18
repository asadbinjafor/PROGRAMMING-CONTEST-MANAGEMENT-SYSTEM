<?php
declare(strict_types=1);

namespace PCMS\Controllers;

use PCMS\Repositories\ContestRepository;
use PCMS\Repositories\ProblemRepository;
use PCMS\Support\Auth;
use PCMS\Support\Flash;
use PCMS\Support\Validator;

final class ProblemController extends BaseController
{
    private ProblemRepository $problems;
    public function __construct(){ $this->problems=new ProblemRepository(); }

    public function index(string $contestId): never
    {
        $contest=(new ContestRepository())->find((int)$contestId);
        if(!$contest){http_response_code(404);$this->page('errors/status',['title'=>'Not found','code'=>404,'message'=>'Contest not found.']);}
        $this->page('problems/index',['title'=>'Problems','contest'=>$contest,'problems'=>$this->problems->forContest((int)$contestId)]);
    }

    public function show(string $id): never
    {
        $problem=$this->problems->find((int)$id);
        if(!$problem){http_response_code(404);$this->page('errors/status',['title'=>'Not found','code'=>404,'message'=>'Problem not found.']);}
        $this->page('problems/show',['title'=>$problem['problem_code'].'. '.$problem['problem_title'],'problem'=>$problem]);
    }

    public function form(string $contestId,?string $id=null): never
    {
        $contest=(new ContestRepository())->find((int)$contestId);$problem=$id?$this->problems->find((int)$id):null;
        if(!$contest||(int)$contest['organizer_user_id']!==Auth::id()){http_response_code(403);$this->page('errors/status',['title'=>'Access denied','code'=>403,'message'=>'You do not own this contest.']);}
        $this->page('problems/form',['title'=>$id?'Edit problem':'Add problem','contest'=>$contest,'problem'=>$problem,'errors'=>$this->errors(),'old'=>$this->old()]);
    }

    public function save(string $contestId,?string $id=null): never
    {
        $d=['contest_id'=>(int)$contestId,'code'=>strtoupper(trim((string)input('code'))),'title'=>trim((string)input('title')),'statement'=>trim((string)input('statement')),'input_format'=>trim((string)input('input_format')),'output_format'=>trim((string)input('output_format')),'constraints_text'=>trim((string)input('constraints_text')),'sample_input'=>trim((string)input('sample_input')),'sample_output'=>trim((string)input('sample_output')),'difficulty'=>(string)input('difficulty'),'time_limit'=>(int)input('time_limit'),'memory_limit'=>(int)input('memory_limit'),'display_order'=>(int)input('display_order')];
        $v=(new Validator())->required('code',$d['code'],'Code')->required('title',$d['title'],'Title')->required('statement',$d['statement'],'Statement')->required('input_format',$d['input_format'],'Input format')->required('output_format',$d['output_format'],'Output format')->in('difficulty',$d['difficulty'],['EASY','MEDIUM','HARD'],'difficulty');
        if($d['time_limit']<1||$d['memory_limit']<1||$d['display_order']<1)$errors=$v->errors()+['limits'=>'Limits and order must be positive.'];else$errors=$v->errors();
        if($errors)$this->fail($id?"/organizer/contests/{$contestId}/problems/{$id}/edit":"/organizer/contests/{$contestId}/problems/create",'Check the form.',$errors);
        $saved=$this->problems->save($id?(int)$id:null,Auth::id(),$d);Flash::add('success','Problem saved.');redirect('/problems/'.$saved);
    }

    public function archive(string $id): never
    {
        $problem=$this->problems->find((int)$id);$this->problems->archive((int)$id,Auth::id());Flash::add('success','Problem archived.');redirect('/organizer/contests/'.($problem['contest_id']??''));
    }

    public function tests(string $id): never
    {
        $problem=$this->problems->find((int)$id);if(!$problem||(int)$problem['organizer_user_id']!==Auth::id()){http_response_code(403);$this->page('errors/status',['title'=>'Access denied','code'=>403,'message'=>'You cannot manage these tests.']);}
        $this->page('problems/tests',['title'=>'Test cases','problem'=>$problem,'tests'=>$this->problems->testCases((int)$id,Auth::id()),'errors'=>$this->errors()]);
    }

    public function addTest(string $id): never
    {
        $d=['test_input'=>trim((string)input('test_input')),'expected_output'=>trim((string)input('expected_output')),'is_hidden'=>(string)input('is_hidden','Y'),'weight'=>(float)input('weight',1)];
        if($d['test_input']===''||$d['expected_output']===''||!in_array($d['is_hidden'],['Y','N'],true)||$d['weight']<=0)$this->fail('/organizer/problems/'.$id.'/tests','Enter a valid test case.');
        $this->problems->saveTestCase(input('test_case_id')?(int)input('test_case_id'):null,(int)$id,Auth::id(),$d);Flash::add('success','Test case saved.');redirect('/organizer/problems/'.$id.'/tests');
    }

    public function deleteTest(string $id): never
    {
        $problemId=(int)input('problem_id');$this->problems->deleteTestCase((int)$id,Auth::id());Flash::add('success','Test case deleted.');redirect('/organizer/problems/'.$problemId.'/tests');
    }
}
