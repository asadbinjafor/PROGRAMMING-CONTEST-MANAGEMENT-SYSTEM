<?php
declare(strict_types=1);

namespace PCMS\Repositories;

use PCMS\Support\Database;

final class DashboardRepository
{
    public function participant(int $userId): array
    {
        return [
            'participated'=>(int)Database::scalar("SELECT COUNT(*) FROM contest_registration WHERE user_id=:id AND registration_status='REGISTERED'",['id'=>$userId]),
            'solved'=>(int)Database::scalar("SELECT COUNT(DISTINCT problem_id) FROM submission WHERE user_id=:id AND verdict='ACCEPTED'",['id'=>$userId]),
            'rank'=>Database::scalar("SELECT MIN(rank_position) FROM v_leaderboard WHERE user_id=:id",['id'=>$userId]) ?? '—',
            'rating'=>(int)Database::scalar("SELECT rating FROM app_user WHERE user_id=:id",['id'=>$userId]),
            'recent'=>(new SubmissionRepository())->forUser($userId,1),
            'upcoming'=>(new ContestRepository())->browse(1,'UPCOMING',''),
            'announcements'=>(new CommunicationRepository())->announcements(null,1),
        ];
    }

    public function organizer(int $userId): array
    {
        return [
            'contests'=>(int)Database::scalar('SELECT COUNT(*) FROM contest WHERE organizer_user_id=:id',['id'=>$userId]),
            'problems'=>(int)Database::scalar('SELECT COUNT(*) FROM problem p JOIN contest c ON c.contest_id=p.contest_id WHERE c.organizer_user_id=:id',['id'=>$userId]),
            'submissions'=>(int)Database::scalar('SELECT COUNT(*) FROM submission s JOIN problem p ON p.problem_id=s.problem_id JOIN contest c ON c.contest_id=p.contest_id WHERE c.organizer_user_id=:id',['id'=>$userId]),
            'open_clarifications'=>(int)Database::scalar("SELECT COUNT(*) FROM clarification cl JOIN contest c ON c.contest_id=cl.contest_id WHERE c.organizer_user_id=:id AND cl.clarification_status='OPEN'",['id'=>$userId]),
        ];
    }

    public function admin(): array
    {
        return [
            'users'=>(int)Database::scalar('SELECT COUNT(*) FROM app_user'),
            'contests'=>(int)Database::scalar('SELECT COUNT(*) FROM contest'),
            'pending'=>(int)Database::scalar("SELECT COUNT(*) FROM contest WHERE approval_status='PENDING_APPROVAL'"),
            'submissions'=>(int)Database::scalar('SELECT COUNT(*) FROM submission'),
            'audit'=>Database::all('SELECT * FROM (SELECT a.*,u.user_name actor_name FROM audit_log a LEFT JOIN app_user u ON u.user_id=a.actor_user_id ORDER BY a.created_at DESC) WHERE ROWNUM<=20'),
        ];
    }
}

