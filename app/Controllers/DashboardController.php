<?php
declare(strict_types=1);

namespace PCMS\Controllers;

use PCMS\Repositories\DashboardRepository;
use PCMS\Support\Auth;

final class DashboardController extends BaseController
{
    public function index(): never
    {
        $repo=new DashboardRepository();
        if(Auth::hasRole('ADMIN'))$this->page('dashboard/admin',['title'=>'Admin dashboard','data'=>$repo->admin()]);
        if(Auth::hasRole('ORGANIZER'))$this->page('dashboard/organizer',['title'=>'Organizer dashboard','data'=>$repo->organizer(Auth::id())]);
        $this->page('dashboard/participant',['title'=>'Dashboard','data'=>$repo->participant(Auth::id())]);
    }
}

