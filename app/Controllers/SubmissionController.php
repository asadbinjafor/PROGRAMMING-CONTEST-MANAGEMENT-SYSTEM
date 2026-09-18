<?php
declare(strict_types=1);

namespace PCMS\Controllers;

use PCMS\Repositories\ProblemRepository;
use PCMS\Repositories\SubmissionRepository;
use PCMS\Support\Auth;
use PCMS\Support\Flash;
use PCMS\Support\Validator;

final class SubmissionController extends BaseController
{
    private SubmissionRepository $submissions;
    public function __construct(){ $this->submissions=new SubmissionRepository(); }

    public function index(): never { $this->page('submissions/index',['title'=>'My submissions','submissions'=>$this->submissions->forUser(Auth::id(),max(1,(int)($_GET['page']??1)))]); }

    public function form(string $problemId): never
    {
        $problem=(new ProblemRepository())->find((int)$problemId);if(!$problem){http_response_code(404);$this->page('errors/status',['title'=>'Not found','code'=>404,'message'=>'Problem not found.']);}
        $this->page('submissions/form',['title'=>'Submit solution','problem'=>$problem,'errors'=>$this->errors(),'old'=>$this->old()]);
    }

    public function create(string $problemId): never
    {
        $language=(string)input('language');$source=(string)input('source_code');
        $v=(new Validator())->in('language',$language,['C','CPP','JAVA','PYTHON'],'language')->required('source_code',$source,'Source code')->length('source_code',$source,5,100000,'Source code');
        if($v->fails())$this->fail('/problems/'.$problemId.'/submit','Check the submission.',$v->errors());
        try{$id=$this->submissions->create(Auth::id(),(int)$problemId,$language,$source);Flash::add('success','Submission queued for judging.');redirect('/submissions/'.$id);}catch(\Throwable$e){$this->fail('/problems/'.$problemId.'/submit',$e->getMessage());}
    }

    public function show(string $id): never
    {
        $submission=$this->submissions->find((int)$id);if(!$submission){http_response_code(404);$this->page('errors/status',['title'=>'Not found','code'=>404,'message'=>'Submission not found.']);}
        $allowed=(int)$submission['user_id']===Auth::id()||(int)$submission['organizer_user_id']===Auth::id()||Auth::hasRole('ADMIN');
        if(!$allowed){http_response_code(403);$this->page('errors/status',['title'=>'Access denied','code'=>403,'message'=>'This submission is private.']);}
        $this->page('submissions/show',['title'=>'Submission #'.$id,'submission'=>$submission]);
    }

    public function manage(): never { $this->page('submissions/manage',['title'=>'Submission monitoring','submissions'=>$this->submissions->pendingForOrganizer(Auth::id(),max(1,(int)($_GET['page']??1)))]); }

    public function judge(string $id): never
    {
        $verdict=(string)input('verdict');$allowed=['ACCEPTED','WRONG_ANSWER','TIME_LIMIT_EXCEEDED','MEMORY_LIMIT_EXCEEDED','RUNTIME_ERROR','COMPILATION_ERROR','SYSTEM_ERROR'];
        if(!in_array($verdict,$allowed,true))$this->fail('/submissions/'.$id,'Select a valid verdict.');
        $this->submissions->judge((int)$id,Auth::id(),$verdict,input('execution_time')!==''?(float)input('execution_time'):null,input('memory_used')!==''?(int)input('memory_used'):null,trim((string)input('judge_message'))?:null);
        Flash::add('success','Verdict recorded.');redirect('/submissions/'.$id);
    }
}

