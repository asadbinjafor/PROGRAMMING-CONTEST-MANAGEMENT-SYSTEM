<div class="section-head">
    <div>
        <h2>Your contests</h2>
        <p class="muted">Draft, submit, and manage approved contests.</p>
    </div>
    <a class="button" href="<?= e(url('/organizer/contests/create')) ?>">+ Create contest</a>
</div>

<div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Contest</th><th>Schedule</th><th>Approval</th><th>Lifecycle</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($contests as $c): ?>
            <tr>
                <td><strong><?= e($c['contest_title']) ?></strong><br><small><?= e($c['visibility']) ?></small></td>
                <td><?= e($c['start_time']) ?></td>
                <td><span class="badge badge-<?= e(strtolower($c['approval_status'])) ?>"><?= e(str_replace('_', ' ', $c['approval_status'])) ?></span></td>
                <td><span class="badge badge-<?= e(strtolower($c['lifecycle_status'])) ?>"><?= e($c['lifecycle_status']) ?></span></td>
                <td>
                    <div class="actions">
                        <a class="button-outline button-small" href="<?= e(url('/organizer/contests/'.$c['contest_id'].'/edit')) ?>">Edit</a>
                        <a class="button-outline button-small" href="<?= e(url('/contests/'.$c['contest_id'].'/problems')) ?>">Problems</a>
                        <a class="button-outline button-small" href="<?= e(url('/organizer/contests/'.$c['contest_id'].'/participants')) ?>">Participants</a>
                        <?php if (in_array($c['approval_status'], ['DRAFT', 'REJECTED'], true)): ?>
                            <form method="post" action="<?= e(url('/organizer/contests/'.$c['contest_id'].'/submit')) ?>">
                                <?= csrf_field() ?>
                                <button class="button button-small">Submit</button>
                            </form>
                        <?php endif; ?>
                        <form method="post" action="<?= e(url('/organizer/contests/'.$c['contest_id'].'/cancel')) ?>" data-confirm="Cancel this contest?">
                            <?= csrf_field() ?>
                            <button class="button-danger button-small">Cancel</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$contests): ?><tr><td colspan="5" class="empty">No contests yet.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
