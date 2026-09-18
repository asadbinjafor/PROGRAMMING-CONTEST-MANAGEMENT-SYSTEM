<section class="card narrow-card">
    <span class="eyebrow">Account recovery</span>
    <h2>Forgot your password?</h2>
    <p class="muted">Enter your account email. For security, the response is the same whether or not it exists.</p>
    <form method="post" action="<?= e(url('/forgot-password')) ?>" class="grid">
        <?= csrf_field() ?>
        <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" required>
        </div>
        <button class="button">Prepare reset link</button>
        <a href="<?= e(url('/login')) ?>">Return to login</a>
    </form>
</section>
