<?php
declare(strict_types=1);

namespace PCMS\Controllers;

use PCMS\Repositories\ContestRepository;
use PCMS\Repositories\ProblemRepository;
use PCMS\Support\Auth;
use PCMS\Support\Flash;
use PCMS\Support\Validator;

final class ContestController extends BaseController
{
    private ContestRepository $contests;
    public function __construct(){ $this->contests=new ContestRepository(); }

    public function index(): never
    {
        $page=max(1,(int)($_GET['page']??1));$status=strtoupper((string)($_GET['status']??''));$search=trim((string)($_GET['q']??''));
        $this->page('contests/index',['title'=>'Contests','contests'=>$this->contests->browse($page,$status,$search),'page'=>$page,'status'=>$status,'search'=>$search]);
    }

    public function show(string $id): never
    {
        $contest=$this->contests->find((int)$id);if(!$contest)$this->notFound();
        $registered=false;if(Auth::check())foreach($this->contests->registrations(Auth::id()) as $r)if((int)$r['contest_id']===(int)$id&&$r['registration_status']==='REGISTERED')$registered=true;
        $this->page('contests/show',['title'=>$contest['contest_title'],'contest'=>$contest,'problems'=>(new ProblemRepository())->forContest((int)$id),'registered'=>$registered]);
    }

    public function register(string $id): never
    {
        try{$this->contests->register((int)$id,Auth::id());Flash::add('success','Registration confirmed.');}catch(\Throwable $e){Flash::add('error',$e->getMessage());}
        redirect('/contests/'.$id);
    }

    public function cancelRegistration(string $id): never
    {
        $this->contests->cancelRegistration((int)$id,Auth::id());Flash::add('success','Registration cancelled.');redirect('/contests/'.$id);
    }

    public function manage(): never
    {
        $this->page('contests/manage',['title'=>'Manage contests','contests'=>$this->contests->browse(max(1,(int)($_GET['page']??1)),'','',Auth::id())]);
    }

    public function form(?string $id=null): never
    {
        $contest=$id?$this->contests->find((int)$id):null;
        if($contest&&(int)$contest['organizer_user_id']!==Auth::id())$this->forbidden();
        $this->page('contests/form',['title'=>$id?'Edit contest':'Create contest','contest'=>$contest,'errors'=>$this->errors(),'old'=>$this->old()]);
    }

    public function save(?string $id=null): never
    {
        $d=['title'=>trim((string)input('title')),'slug'=>strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',(string)input('slug',(string)input('title'))),'-')),'description'=>trim((string)input('description')),'start_time'=>$this->oracleTime((string)input('start_time')),'end_time'=>$this->oracleTime((string)input('end_time')),'deadline'=>$this->oracleTime((string)input('deadline')),'visibility'=>(string)input('visibility')];
        $v=(new Validator())->required('title',$d['title'],'Title')->required('start_time',input('start_time'),'Start time')->required('end_time',input('end_time'),'End time')->required('deadline',input('deadline'),'Registration deadline')->in('visibility',$d['visibility'],['PUBLIC','PRIVATE'],'visibility');
        if($v->fails())$this->fail($id?'/organizer/contests/'.$id.'/edit':'/organizer/contests/create','Check the form.',$v->errors());
        $saved=$this->contests->save($id?(int)$id:null,Auth::id(),$d);Flash::add('success','Contest saved.');redirect('/organizer/contests/'.$saved.'/edit');
    }

    public function submit(string $id): never { $this->contests->submitForApproval((int)$id,Auth::id());Flash::add('success','Contest submitted for approval.');redirect('/organizer/contests'); }
    public function cancel(string $id): never { $this->contests->cancel((int)$id,Auth::id());Flash::add('success','Contest cancelled.');redirect('/organizer/contests'); }
    public function participants(string $id): never { $contest=$this->contests->find((int)$id);if(!$contest||(int)$contest['organizer_user_id']!==Auth::id())$this->forbidden();$this->page('contests/participants',['title'=>'Contest participants','contest'=>$contest,'participants'=>$this->contests->participants((int)$id,Auth::id())]); }
    private function oracleTime(string $value): string { return str_replace('T',' ',$value).':00 '.date_default_timezone_get(); }
    private function notFound(): never { http_response_code(404);$this->page('errors/status',['title'=>'Contest not found','code'=>404,'message'=>'This contest does not exist.']); }
    private function forbidden(): never { http_response_code(403);$this->page('errors/status',['title'=>'Access denied','code'=>403,'message'=>'You do not own this contest.']); }
}
