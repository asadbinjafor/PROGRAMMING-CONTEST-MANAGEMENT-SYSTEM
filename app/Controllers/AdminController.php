<?php
declare(strict_types=1);

namespace PCMS\Controllers;

use PCMS\Repositories\ContestRepository;
use PCMS\Repositories\UserRepository;
use PCMS\Support\Database;
use PCMS\Support\Flash;

final class AdminController extends BaseController
{
    public function users(): never
    {
        $this->page('admin/users',['title'=>'Manage users','users'=>(new UserRepository())->list(max(1,(int)($_GET['page']??1)),trim((string)($_GET['q']??'')))]);
    }

    public function updateUser(string $id): never
    {
        $status=(string)input('status');$role=(string)input('role');
        if(!in_array($status,['ACTIVE','SUSPENDED','DISABLED'],true)||!in_array($role,['PARTICIPANT','ORGANIZER','ADMIN'],true)){Flash::add('error','Invalid account update.');redirect('/admin/users');}
        $repo=new UserRepository();$repo->setStatus((int)$id,$status);$repo->assignRole((int)$id,$role);Flash::add('success','User access updated.');redirect('/admin/users');
    }

    public function approvals(): never
    {
        $contests=Database::all("SELECT c.*,u.user_name organizer_name FROM contest c JOIN app_user u ON u.user_id=c.organizer_user_id WHERE c.approval_status='PENDING_APPROVAL' ORDER BY c.created_at");
        $this->page('admin/approvals',['title'=>'Contest approvals','contests'=>$contests]);
    }

    public function decide(string $id): never
    {
        $decision=(string)input('decision');$reason=trim((string)input('reason'))?:null;
        if(!in_array($decision,['APPROVED','REJECTED'],true)||($decision==='REJECTED'&&!$reason)){Flash::add('error','A valid decision and rejection reason are required.');redirect('/admin/approvals');}
        (new ContestRepository())->approve((int)$id,$decision,$reason);
        Database::execute("INSERT INTO audit_log(audit_id,actor_user_id,action_code,entity_type,entity_id,details,ip_address) VALUES(seq_audit_log.NEXTVAL,:actor,:action,'CONTEST',:id,:details,:ip)",['actor'=>\PCMS\Support\Auth::id(),'action'=>'CONTEST_'.$decision,'id'=>(int)$id,'details'=>$reason,'ip'=>$_SERVER['REMOTE_ADDR']??null]);
        Flash::add('success','Contest decision saved.');redirect('/admin/approvals');
    }

    public function audit(): never
    {
        $rows=Database::all('SELECT * FROM (SELECT a.*,u.user_name actor_name FROM audit_log a LEFT JOIN app_user u ON u.user_id=a.actor_user_id ORDER BY a.created_at DESC) WHERE ROWNUM<=200');
        $this->page('admin/audit',['title'=>'Audit log','rows'=>$rows]);
    }

    public function contests(): never
    {
        $rows=Database::all('SELECT v.*,u.user_name organizer_name FROM v_contest_status v JOIN app_user u ON u.user_id=v.organizer_user_id ORDER BY v.created_at DESC');
        $this->page('admin/contests',['title'=>'All contests','contests'=>$rows]);
    }

    public function submissions(): never
    {
        $rows=Database::all('SELECT * FROM (SELECT s.submission_id,s.verdict,s.language,s.submitted_at,p.problem_title,c.contest_title,u.user_name FROM submission s JOIN problem p ON p.problem_id=s.problem_id JOIN contest c ON c.contest_id=p.contest_id JOIN app_user u ON u.user_id=s.user_id ORDER BY s.submitted_at DESC) WHERE ROWNUM<=200');
        $this->page('submissions/manage',['title'=>'All submissions','submissions'=>$rows]);
    }
}
