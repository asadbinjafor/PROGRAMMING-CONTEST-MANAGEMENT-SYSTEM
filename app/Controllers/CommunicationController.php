<?php
declare(strict_types=1);

namespace PCMS\Controllers;

use PCMS\Repositories\CommunicationRepository;
use PCMS\Repositories\ContestRepository;
use PCMS\Support\Auth;
use PCMS\Support\Flash;
use PCMS\Support\Validator;

final class CommunicationController extends BaseController
{
    private CommunicationRepository $repo;
    public function __construct(){ $this->repo=new CommunicationRepository(); }

    public function announcements(): never { $this->page('communications/announcements',['title'=>'Announcements','announcements'=>$this->repo->announcements(isset($_GET['contest'])?(int)$_GET['contest']:null)]); }

    public function manageAnnouncements(): never { $this->page('communications/manage_announcements',['title'=>'Manage announcements','announcements'=>$this->repo->announcements(null,1,Auth::id()),'contests'=>(new ContestRepository())->browse(1,'','',Auth::id()),'errors'=>$this->errors()]); }

    public function saveAnnouncement(): never
    {
        $d=['contest_id'=>(int)input('contest_id'),'title'=>trim((string)input('title')),'message'=>trim((string)input('message'))];
        $v=(new Validator())->required('title',$d['title'],'Title')->required('message',$d['message'],'Message');
        if($v->fails())$this->fail('/organizer/announcements','Check the announcement.',$v->errors());
        $this->repo->saveAnnouncement(input('announcement_id')?(int)input('announcement_id'):null,Auth::id(),$d);Flash::add('success','Announcement saved.');redirect('/organizer/announcements');
    }

    public function archiveAnnouncement(string $id): never { $this->repo->archiveAnnouncement((int)$id,Auth::id());Flash::add('success','Announcement archived.');redirect('/organizer/announcements'); }

    public function clarifications(): never { $this->page('communications/clarifications',['title'=>'Clarifications','clarifications'=>$this->repo->clarifications(Auth::id()),'contests'=>(new ContestRepository())->browse(1,'',''),'errors'=>$this->errors()]); }

    public function ask(): never
    {
        $question=trim((string)input('question'));$v=(new Validator())->required('question',$question,'Question')->length('question',$question,5,1000,'Question');
        if($v->fails())$this->fail('/clarifications','Check the question.',$v->errors());
        try{$this->repo->ask((int)input('contest_id'),Auth::id(),$question);Flash::add('success','Question submitted.');}catch(\Throwable$e){Flash::add('error',$e->getMessage());}
        redirect('/clarifications');
    }

    public function manageClarifications(): never { $this->page('communications/manage_clarifications',['title'=>'Manage clarifications','clarifications'=>$this->repo->clarifications(Auth::id(),true),'errors'=>$this->errors()]); }

    public function answer(string $id): never
    {
        $answer=trim((string)input('answer'));if($answer==='')$this->fail('/organizer/clarifications','Answer is required.');
        $this->repo->answer((int)$id,Auth::id(),$answer,input('is_public')==='Y'?'Y':'N');Flash::add('success','Clarification answered.');redirect('/organizer/clarifications');
    }
}
