<div class="section-head"><div><h2>Welcome back, <?=e(user()['user_name'])?>! 👋</h2><p class="muted">Here is your programming activity.</p></div><a class="button" href="<?=e(url('/contests'))?>">Find a contest</a></div>
<section class="grid grid-4">
<?php foreach([['▥','Participated contests',$data['participated']],['</>','Problems solved',$data['solved']],['☆','Current rank','#'.$data['rank']],['★','User rating',$data['rating']]] as [$icon,$label,$value]):?><article class="card metric"><span class="metric-icon"><?=$icon?></span><div><small><?=e($label)?></small><strong><?=e($value)?></strong></div></article><?php endforeach;?>
</section>
<section class="grid grid-3 dashboard-panels">
<article class="card panel-wide"><div class="section-head"><h2>Recent Submissions</h2><a href="<?=e(url('/submissions'))?>">View all →</a></div>
<div class="table-wrap"><table class="data-table"><thead><tr><th>Problem</th><th>Language</th><th>Verdict</th><th>Time</th><th>Date</th></tr></thead><tbody>
<?php foreach(array_slice($data['recent'],0,5) as $row):?><tr><td><a href="<?=e(url('/submissions/'.$row['submission_id']))?>"><?=e($row['problem_code'].'. '.$row['problem_title'])?></a></td><td><?=e($row['language'])?></td><td><span class="badge badge-<?=e(strtolower($row['verdict']))?>"><?=e(str_replace('_',' ',$row['verdict']))?></span></td><td><?=e($row['execution_time_ms']??'—')?> ms</td><td><?=e($row['submitted_at'])?></td></tr><?php endforeach;?>
<?php if(!$data['recent']):?><tr><td colspan="5" class="empty">No submissions yet.</td></tr><?php endif;?></tbody></table></div></article>
<aside class="grid">
<article class="card"><div class="section-head"><h2>Upcoming Contests</h2><a href="<?=e(url('/contests'))?>">View all</a></div><?php foreach(array_slice($data['upcoming'],0,3) as $c):?><p><a href="<?=e(url('/contests/'.$c['contest_id']))?>"><strong><?=e($c['contest_title'])?></strong></a><br><small class="muted"><?=e($c['start_time'])?></small></p><?php endforeach;?><?php if(!$data['upcoming']):?><p class="muted">No upcoming contests.</p><?php endif;?></article>
<article class="card"><div class="section-head"><h2>Announcements</h2><a href="<?=e(url('/announcements'))?>">View all</a></div><?php foreach(array_slice($data['announcements'],0,3) as $a):?><p><strong><?=e($a['announcement_title'])?></strong><br><small class="muted"><?=e($a['contest_title'])?></small></p><?php endforeach;?></article>
</aside></section>
