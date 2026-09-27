<?php
declare(strict_types=1);

namespace PCMS\Controllers;

use PCMS\Repositories\ContestRepository;
use PCMS\Support\Auth;
use PCMS\Support\Database;

final class LeaderboardController extends BaseController
{
    public function index(): never
    {
        $repo=new ContestRepository();$contests=$repo->browse(1,'','',null);$contestId=(int)($_GET['contest']??($contests[0]['contest_id']??0));
        if($contestId&&!$repo->canView($contestId,Auth::id(),Auth::hasRole('ADMIN'))){http_response_code(404);$this->page('errors/status',['title'=>'Not found','code'=>404,'message'=>'Leaderboard not found.']);}
        $rows=$contestId?Database::all('SELECT * FROM v_leaderboard WHERE contest_id=:id ORDER BY rank_position,user_id',['id'=>$contestId]):[];
        $contest=$contestId?$repo->find($contestId):null;
        $this->page('leaderboard/index',['title'=>'Leaderboard','contests'=>$contests,'contest'=>$contest,'rows'=>$rows]);
    }
}
