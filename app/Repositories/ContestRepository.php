<?php
declare(strict_types=1);

namespace PCMS\Repositories;

use PCMS\Support\Database;

final class ContestRepository
{
    public function browse(int $page = 1, string $status = '', string $search = '', ?int $organizer = null): array
    {
        $start=(($page-1)*12)+1; $end=$page*12; $q='%'.strtolower($search).'%';
        $ownerSql=$organizer ? ' AND c.organizer_user_id=:owner' : " AND c.approval_status='APPROVED' AND c.visibility='PUBLIC'";
        $statusSql=$status ? ' AND v.lifecycle_status=:status' : '';
        $params=['q'=>$q,'row_start'=>$start,'row_end'=>$end];
        if($organizer) $params['owner']=$organizer; if($status) $params['status']=$status;
        return Database::all("SELECT * FROM (SELECT v.contest_id,v.contest_title,v.slug,v.description,v.start_time,v.end_time,v.registration_deadline,v.visibility,v.approval_status,v.lifecycle_status,v.duration_minutes,v.participant_count,c.organizer_user_id,u.user_name organizer_name,ROW_NUMBER() OVER(ORDER BY c.start_time DESC) rn FROM v_contest_status v JOIN contest c ON c.contest_id=v.contest_id JOIN app_user u ON u.user_id=c.organizer_user_id WHERE LOWER(c.contest_title) LIKE :q {$ownerSql} {$statusSql}) ranked WHERE rn BETWEEN :row_start AND :row_end", $params);
    }

    public function find(int $id): ?array
    {
        return Database::one("SELECT v.*,u.user_name organizer_name FROM v_contest_status v JOIN app_user u ON u.user_id=v.organizer_user_id WHERE v.contest_id=:id", ['id'=>$id]);
    }

    public function canView(int $id, ?int $viewerId, bool $isAdmin = false): bool
    {
        if ($isAdmin) return true;
        return (int)Database::scalar("SELECT COUNT(*) FROM contest c WHERE c.contest_id=:id AND
            ((c.approval_status='APPROVED' AND c.visibility='PUBLIC') OR c.organizer_user_id=:owner OR
             EXISTS(SELECT 1 FROM contest_registration cr WHERE cr.contest_id=c.contest_id AND cr.user_id=:registered_user AND cr.registration_status='REGISTERED'))",
            ['id'=>$id,'owner'=>$viewerId,'registered_user'=>$viewerId]) > 0;
    }

    public function registrations(int $userId): array
    {
        return Database::all("SELECT contest_id,registration_status FROM contest_registration WHERE user_id=:id", ['id'=>$userId]);
    }

    public function register(int $contestId, int $userId): void
    {
        Database::execute('SELECT sp_register_participant(:contest_id,:user_id)', ['contest_id'=>$contestId,'user_id'=>$userId]);
    }

    public function cancelRegistration(int $contestId, int $userId): void
    {
        Database::execute("UPDATE contest_registration cr SET registration_status='CANCELLED',cancelled_at=now() FROM contest c WHERE cr.contest_id=c.contest_id AND cr.contest_id=:contest_id AND cr.user_id=:user_id AND cr.registration_status='REGISTERED' AND now()<c.start_time", ['contest_id'=>$contestId,'user_id'=>$userId]);
    }

    public function save(?int $id, int $owner, array $d): int
    {
        if($id){
            Database::execute("UPDATE contest SET contest_title=:title,slug=:slug,description=:description,start_time=CAST(:start_time AS timestamptz),end_time=CAST(:end_time AS timestamptz),registration_deadline=CAST(:deadline AS timestamptz),visibility=:visibility,approval_status='DRAFT',updated_at=now() WHERE contest_id=:id AND organizer_user_id=:owner", $d+compact('id','owner'));
            return $id;
        }
        $id=(int)Database::scalar("SELECT nextval('seq_contest')");
        Database::execute("INSERT INTO contest(contest_id,organizer_user_id,contest_title,slug,description,start_time,end_time,registration_deadline,visibility,approval_status,contest_status) VALUES(:id,:owner,:title,:slug,:description,CAST(:start_time AS timestamptz),CAST(:end_time AS timestamptz),CAST(:deadline AS timestamptz),:visibility,'DRAFT','ACTIVE')", $d+compact('id','owner'));
        return $id;
    }

    public function submitForApproval(int $id,int $owner): void
    {
        Database::execute("UPDATE contest SET approval_status='PENDING_APPROVAL',rejection_reason=NULL,updated_at=now() WHERE contest_id=:id AND organizer_user_id=:owner AND approval_status IN('DRAFT','REJECTED')",compact('id','owner'));
    }

    public function cancel(int $id,int $owner): void
    {
        Database::execute("UPDATE contest SET contest_status='CANCELLED',updated_at=now() WHERE contest_id=:id AND organizer_user_id=:owner",compact('id','owner'));
    }

    public function approve(int $id,string $decision,?string $reason): void
    {
        Database::execute("UPDATE contest SET approval_status=:decision,rejection_reason=:reason,updated_at=now() WHERE contest_id=:id AND approval_status='PENDING_APPROVAL'",compact('id','decision','reason'));
    }

    public function participants(int $contestId,int $owner): array
    {
        return Database::all("SELECT cr.registration_id,cr.registration_status,cr.registered_at,u.user_id,u.user_name,u.email,u.institution,u.rating FROM contest_registration cr JOIN app_user u ON u.user_id=cr.user_id JOIN contest c ON c.contest_id=cr.contest_id WHERE cr.contest_id=:contest_id AND c.organizer_user_id=:owner ORDER BY cr.registered_at",['contest_id'=>$contestId,'owner'=>$owner]);
    }
}
