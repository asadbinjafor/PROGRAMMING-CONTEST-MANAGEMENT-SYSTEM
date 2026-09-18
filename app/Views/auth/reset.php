<section class="card narrow-card">
    <span class="eyebrow">Secure reset</span>
    <h2>Choose a new password</h2>
    <form method="post" action="<?= e(url('/reset-password')) ?>" class="grid">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <div class="field">
            <label for="password">New password</label>
            <input id="password" name="password" type="password" minlength="10" required>
        </div>
        <div class="field">
            <label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required>
        </div>
        <button class="button">Reset password</button>
    </form>
</section>
