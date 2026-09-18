<?php
declare(strict_types=1);

namespace PCMS\Repositories;

use PCMS\Support\Database;

final class CommunicationRepository
{
    public function announcements(?int $contestId=null,int $page=1,?int $owner=null): array
    {
        $start=(($page-1)*20)+1;$end=$page*20;$filter=$contestId?' AND a.contest_id=:contest_id':'';
        $ownerFilter=$owner?' AND c.organizer_user_id=:owner':'';
        $publicFilter=$owner?'':" AND c.approval_status='APPROVED'";
        $params=['row_start'=>$start,'row_end'=>$end];if($contestId)$params['contest_id']=$contestId;if($owner)$params['owner']=$owner;
        return Database::all("SELECT * FROM (SELECT a.announcement_id,a.contest_id,a.announcement_title,a.message,a.created_at,c.contest_title,u.user_name author_name,ROW_NUMBER() OVER(ORDER BY a.created_at DESC) rn FROM announcement a JOIN contest c ON c.contest_id=a.contest_id JOIN app_user u ON u.user_id=a.author_user_id WHERE a.is_archived='N' {$filter} {$ownerFilter} {$publicFilter}) WHERE rn BETWEEN :row_start AND :row_end",$params);
    }

    public function saveAnnouncement(?int $id,int $owner,array $d): void
    {
        if($id){
            Database::execute("UPDATE announcement a SET announcement_title=:title,message=:message,updated_at=SYSTIMESTAMP WHERE announcement_id=:id AND EXISTS(SELECT 1 FROM contest c WHERE c.contest_id=a.contest_id AND c.organizer_user_id=:owner)",$d+compact('id','owner'));
        } else {
            Database::execute('BEGIN sp_add_announcement(:contest_id,:owner,:title,:message); END;',$d+compact('owner'));
        }
    }

    public function archiveAnnouncement(int $id,int $owner): void
    {
        Database::execute("UPDATE announcement a SET is_archived='Y',updated_at=SYSTIMESTAMP WHERE announcement_id=:id AND EXISTS(SELECT 1 FROM contest c WHERE c.contest_id=a.contest_id AND c.organizer_user_id=:owner)",compact('id','owner'));
    }

    public function clarifications(int $userId,bool $manage=false): array
    {
        $where=$manage?'c.organizer_user_id=:id':"(cl.asker_user_id=:id OR cl.is_public='Y')";
        return Database::all("SELECT cl.*,c.contest_title,a.user_name asker_name,n.user_name answerer_name FROM clarification cl JOIN contest c ON c.contest_id=cl.contest_id JOIN app_user a ON a.user_id=cl.asker_user_id LEFT JOIN app_user n ON n.user_id=cl.answerer_user_id WHERE {$where} ORDER BY cl.asked_at DESC",['id'=>$userId]);
    }

    public function ask(int $contestId,int $userId,string $question): void
    {
        $eligible=(int)Database::scalar("SELECT COUNT(*) FROM contest_registration cr JOIN v_contest_status v ON v.contest_id=cr.contest_id WHERE cr.contest_id=:contest_id AND cr.user_id=:user_id AND cr.registration_status='REGISTERED' AND v.lifecycle_status IN('UPCOMING','ONGOING')",['contest_id'=>$contestId,'user_id'=>$userId]);
        if(!$eligible) throw new \RuntimeException('You must be registered for an eligible contest.');
        Database::execute("INSERT INTO clarification(clarification_id,contest_id,asker_user_id,question) VALUES(seq_clarification.NEXTVAL,:contest_id,:user_id,:question)",['contest_id'=>$contestId,'user_id'=>$userId,'question'=>$question]);
    }

    public function answer(int $id,int $owner,string $answer,string $isPublic): void
    {
        Database::execute('BEGIN sp_answer_clarification(:id,:owner,:answer,:is_public); END;',['id'=>$id,'owner'=>$owner,'answer'=>$answer,'is_public'=>$isPublic]);
    }
}
