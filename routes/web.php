<?php
declare(strict_types=1);

use PCMS\Controllers\AdminController;
use PCMS\Controllers\AuthController;
use PCMS\Controllers\CommunicationController;
use PCMS\Controllers\ContestController;
use PCMS\Controllers\DashboardController;
use PCMS\Controllers\LeaderboardController;
use PCMS\Controllers\ProblemController;
use PCMS\Controllers\ProfileController;
use PCMS\Controllers\SubmissionController;

return [
    ['GET','/',[ContestController::class,'index'],[]],
    ['GET','/login',[AuthController::class,'loginForm'],['guest']],
    ['POST','/login',[AuthController::class,'login'],['guest','csrf']],
    ['GET','/register',[AuthController::class,'registerForm'],['guest']],
    ['POST','/register',[AuthController::class,'register'],['guest','csrf']],
    ['GET','/forgot-password',[AuthController::class,'forgotForm'],['guest']],
    ['POST','/forgot-password',[AuthController::class,'forgot'],['guest','csrf']],
    ['GET','/reset-password',[AuthController::class,'resetForm'],['guest']],
    ['POST','/reset-password',[AuthController::class,'reset'],['guest','csrf']],
    ['POST','/logout',[AuthController::class,'logout'],['auth','csrf']],

    ['GET','/dashboard',[DashboardController::class,'index'],['auth']],
    ['GET','/contests',[ContestController::class,'index'],[]],
    ['GET','/contests/{id}',[ContestController::class,'show'],[]],
    ['POST','/contests/{id}/register',[ContestController::class,'register'],['auth','role:PARTICIPANT','csrf']],
    ['POST','/contests/{id}/cancel-registration',[ContestController::class,'cancelRegistration'],['auth','role:PARTICIPANT','csrf']],
    ['GET','/contests/{contest_id}/problems',[ProblemController::class,'index'],['auth']],
    ['GET','/problems/{id}',[ProblemController::class,'show'],['auth']],
    ['GET','/problems/{id}/submit',[SubmissionController::class,'form'],['auth','role:PARTICIPANT']],
    ['POST','/problems/{id}/submit',[SubmissionController::class,'create'],['auth','role:PARTICIPANT','csrf']],
    ['GET','/submissions',[SubmissionController::class,'index'],['auth','role:PARTICIPANT']],
    ['GET','/submissions/{id}',[SubmissionController::class,'show'],['auth']],
    ['GET','/leaderboard',[LeaderboardController::class,'index'],[]],
    ['GET','/announcements',[CommunicationController::class,'announcements'],[]],
    ['GET','/clarifications',[CommunicationController::class,'clarifications'],['auth','role:PARTICIPANT']],
    ['POST','/clarifications',[CommunicationController::class,'ask'],['auth','role:PARTICIPANT','csrf']],
    ['GET','/profile',[ProfileController::class,'show'],['auth']],
    ['POST','/profile',[ProfileController::class,'update'],['auth','csrf']],
    ['POST','/profile/password',[ProfileController::class,'password'],['auth','csrf']],

    ['GET','/organizer/contests',[ContestController::class,'manage'],['auth','role:ORGANIZER']],
    ['GET','/organizer/contests/create',[ContestController::class,'form'],['auth','role:ORGANIZER']],
    ['POST','/organizer/contests',[ContestController::class,'save'],['auth','role:ORGANIZER','csrf']],
    ['GET','/organizer/contests/{id}/edit',[ContestController::class,'form'],['auth','role:ORGANIZER']],
    ['POST','/organizer/contests/{id}',[ContestController::class,'save'],['auth','role:ORGANIZER','csrf']],
    ['POST','/organizer/contests/{id}/submit',[ContestController::class,'submit'],['auth','role:ORGANIZER','csrf']],
    ['POST','/organizer/contests/{id}/cancel',[ContestController::class,'cancel'],['auth','role:ORGANIZER','csrf']],
    ['GET','/organizer/contests/{contest_id}/problems/create',[ProblemController::class,'form'],['auth','role:ORGANIZER']],
    ['POST','/organizer/contests/{contest_id}/problems',[ProblemController::class,'save'],['auth','role:ORGANIZER','csrf']],
    ['GET','/organizer/contests/{contest_id}/problems/{id}/edit',[ProblemController::class,'form'],['auth','role:ORGANIZER']],
    ['POST','/organizer/contests/{contest_id}/problems/{id}',[ProblemController::class,'save'],['auth','role:ORGANIZER','csrf']],
    ['POST','/organizer/problems/{id}/archive',[ProblemController::class,'archive'],['auth','role:ORGANIZER','csrf']],
    ['GET','/organizer/problems/{id}/tests',[ProblemController::class,'tests'],['auth','role:ORGANIZER']],
    ['POST','/organizer/problems/{id}/tests',[ProblemController::class,'addTest'],['auth','role:ORGANIZER','csrf']],
    ['POST','/organizer/test-cases/{id}/delete',[ProblemController::class,'deleteTest'],['auth','role:ORGANIZER','csrf']],
    ['GET','/organizer/contests/{id}/participants',[ContestController::class,'participants'],['auth','role:ORGANIZER']],
    ['GET','/organizer/submissions',[SubmissionController::class,'manage'],['auth','role:ORGANIZER']],
    ['POST','/organizer/submissions/{id}/judge',[SubmissionController::class,'judge'],['auth','role:ORGANIZER','csrf']],
    ['GET','/organizer/announcements',[CommunicationController::class,'manageAnnouncements'],['auth','role:ORGANIZER']],
    ['POST','/organizer/announcements',[CommunicationController::class,'saveAnnouncement'],['auth','role:ORGANIZER','csrf']],
    ['POST','/organizer/announcements/{id}/archive',[CommunicationController::class,'archiveAnnouncement'],['auth','role:ORGANIZER','csrf']],
    ['GET','/organizer/clarifications',[CommunicationController::class,'manageClarifications'],['auth','role:ORGANIZER']],
    ['POST','/organizer/clarifications/{id}/answer',[CommunicationController::class,'answer'],['auth','role:ORGANIZER','csrf']],

    ['GET','/admin/users',[AdminController::class,'users'],['auth','role:ADMIN']],
    ['POST','/admin/users/{id}',[AdminController::class,'updateUser'],['auth','role:ADMIN','csrf']],
    ['GET','/admin/approvals',[AdminController::class,'approvals'],['auth','role:ADMIN']],
    ['POST','/admin/approvals/{id}',[AdminController::class,'decide'],['auth','role:ADMIN','csrf']],
    ['GET','/admin/audit',[AdminController::class,'audit'],['auth','role:ADMIN']],
    ['GET','/admin/contests',[AdminController::class,'contests'],['auth','role:ADMIN']],
    ['GET','/admin/submissions',[AdminController::class,'submissions'],['auth','role:ADMIN']],
];
