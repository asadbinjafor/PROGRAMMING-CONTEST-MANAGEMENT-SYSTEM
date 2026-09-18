<?php
declare(strict_types=1);

namespace PCMS\Controllers;

use PCMS\Repositories\ContestRepository;
use PCMS\Support\Database;

final class LeaderboardController extends BaseController
{
    public function index(): never
    {
        $contests=(new ContestRepository())->browse(1,'','',null);$contestId=(int)($_GET['contest']??($contests[0]['contest_id']??0));
        $rows=$contestId?Database::all('SELECT * FROM v_leaderboard WHERE contest_id=:id ORDER BY rank_position,user_id',['id'=>$contestId]):[];
        $contest=$contestId?(new ContestRepository())->find($contestId):null;
        $this->page('leaderboard/index',['title'=>'Leaderboard','contests'=>$contests,'contest'=>$contest,'rows'=>$rows]);
    }
}
