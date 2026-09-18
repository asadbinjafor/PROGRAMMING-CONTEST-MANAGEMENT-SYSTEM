<?php
$auth=user();$path=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH);
$nav=[['Dashboard','/dashboard','▦'],['Contests','/contests','♜'],['Leaderboard','/leaderboard','▥'],['Announcements','/announcements','◈']];
if($auth&&has_role('PARTICIPANT')){$nav[]=['My Submissions','/submissions','↻'];$nav[]=['Clarifications','/clarifications','□'];}
if($auth&&has_role('ORGANIZER')){$nav[]=['Manage Contests','/organizer/contests','◆'];$nav[]=['Submissions','/organizer/submissions','⌁'];$nav[]=['Clarifications','/organizer/clarifications','□'];$nav[]=['Publish','/organizer/announcements','◈'];}
if($auth&&has_role('ADMIN')){$nav[]=['Users','/admin/users','♙'];$nav[]=['All Contests','/admin/contests','◆'];$nav[]=['All Submissions','/admin/submissions','⌁'];$nav[]=['Approvals','/admin/approvals','✓'];$nav[]=['Audit Log','/admin/audit','≡'];}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="<?=e(\PCMS\Support\Csrf::token())?>">
<title><?=e($title??'PCMS')?> · PCMS</title>
<link rel="stylesheet" href="<?=e(url('/assets/css/app.css'))?>"><script defer src="<?=e(url('/assets/js/app.js'))?>"></script>
</head>
<body class="<?=$auth?'app-shell':'guest-shell'?>">
<a class="skip-link" href="#main">Skip to content</a>
<?php if($auth): ?>
<button class="mobile-menu" type="button" data-menu aria-label="Open navigation">☰</button>
<aside class="sidebar" data-sidebar>
<a class="brand brand-light" href="<?=e(url('/dashboard'))?>"><span class="brand-mark">&lt;/&gt;</span> PCMS</a>
<nav aria-label="Main navigation">
<?php foreach($nav as [$label,$href,$icon]): ?><a href="<?=e(url($href))?>" class="<?=str_starts_with($path,$href)?'active':''?>"><span aria-hidden="true"><?=$icon?></span><?=e($label)?></a><?php endforeach; ?>
</nav>
<div class="sidebar-bottom">
<a href="<?=e(url('/profile'))?>"><span aria-hidden="true">⚙</span> Profile & Settings</a>
<form method="post" action="<?=e(url('/logout'))?>"><?=csrf_field()?><button class="nav-button"><span aria-hidden="true">⇥</span> Logout</button></form>
</div></aside>
<div class="app-area">
<header class="topbar"><div><h1><?=e($title??'PCMS')?></h1><p><?=e($auth['user_name'])?> · <?=e(implode(' / ',$auth['roles']??[]))?></p></div><a class="avatar" href="<?=e(url('/profile'))?>" aria-label="Open profile"><?=e(strtoupper(substr($auth['user_name'],0,1)))?></a></header>
<?php else: ?>
<header class="public-header"><a class="brand" href="<?=e(url('/'))?>"><span class="brand-mark">&lt;/&gt;</span> PCMS</a><nav><a href="<?=e(url('/contests'))?>">Contests</a><a href="<?=e(url('/leaderboard'))?>">Leaderboard</a><a href="<?=e(url('/announcements'))?>">Announcements</a><a class="button button-small" href="<?=e(url('/login'))?>">Sign in</a></nav></header>
<?php endif; ?>
<main id="main" class="main-content">
<?php foreach($flashes as $flash): ?><div class="flash flash-<?=e($flash['type'])?>" role="status"><span><?=e($flash['message'])?></span><button type="button" data-dismiss aria-label="Dismiss">×</button></div><?php endforeach; ?>
<?=$content?>
</main>
<?php if($auth): ?></div><?php endif; ?>
<footer class="site-footer"><strong>PCMS</strong><span>© <?=date('Y')?> Programming Contest Management System.</span><a href="<?=e(url('/contests'))?>">Contests</a><a href="<?=e(url('/announcements'))?>">Announcements</a></footer>
</body></html>
