<section class="card auth-page">
<div class="auth-art"><div><div class="visual">&lt;/&gt;</div><h2>Programming Contest<br>Management System</h2><p>Compete. Learn. Improve.</p></div></div>
<div class="auth-card"><div><span class="eyebrow">Welcome back</span><h2>Sign in to continue</h2><p class="muted">Access your contests, submissions, and rankings.</p>
<form method="post" action="<?=e(url('/login'))?>" class="grid"><?=csrf_field()?>
<div class="field"><label for="email">Email</label><input id="email" name="email" type="email" autocomplete="email" value="<?=e($old['email']??'')?>" required><?php if(isset($errors['email'])):?><span class="field-error"><?=e($errors['email'])?></span><?php endif;?></div>
<div class="field"><label for="password">Password</label><div class="password-wrap"><input id="password" name="password" type="password" autocomplete="current-password" required><button class="password-toggle" type="button" data-password-toggle="password" aria-label="Show password">◉</button></div><?php if(isset($errors['password'])):?><span class="field-error"><?=e($errors['password'])?></span><?php endif;?></div>
<div class="section-head"><label class="check"><input type="checkbox" name="remember" value="1"> Remember me</label><a href="<?=e(url('/forgot-password'))?>">Forgot password?</a></div>
<button class="button button-block">Login</button></form><div class="divider"></div><p>Don't have an account? <a href="<?=e(url('/register'))?>">Sign up</a></p></div></div>
</section>
