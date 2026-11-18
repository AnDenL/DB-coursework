<!DOCTYPE html>
<?php include 'head.php'; ?>
<body>
    <?php
    include 'menu.php';
    $dbc  = DB::get_connection();
    $last = $dbc->query("
        SELECT e.id, e.title, e.start_date, e.end_date, r.name AS region_name, r.photo_path
        FROM expeditions e
        LEFT JOIN regions r ON r.id = e.region_id
        ORDER BY e.start_date DESC, e.id DESC
        LIMIT 1
    ")->fetch();
    $bgUrl = !empty($last['photo_path']) ? '/' . ltrim((string)$last['photo_path'], '/') : '';
    ?>

    <div class="container">
        <div class="hero-card" style="<?= $bgUrl ? "--hero-bg: url('" . htmlspecialchars($bgUrl, ENT_QUOTES) . "')" : '' ?>">
            <div class="hero-overlay">
                <h1 class="page-title" style="margin-bottom: 0.5rem;">ІС «Етнографічні експедиції»</h1>
                <p style="max-width: 680px; opacity: 0.92; margin-bottom: 1.25rem;">
                    Інформаційна система обліку польових експедицій, регіонів дослідження, наукового складу, спорядження та знайдених артефактів.
                </p>

                <?php if ($last): ?>
                    <div style="margin-bottom: 1.25rem; font-size: 0.92rem; opacity: 0.95;">
                        Остання експедиція: <strong><?= htmlspecialchars((string)$last['title']) ?></strong>
                        <?php if (!empty($last['region_name'])): ?>
                            — регіон «<?= htmlspecialchars((string)$last['region_name']) ?>»
                        <?php endif; ?>
                        (<?= htmlspecialchars((string)($last['start_date'] ?? '—')) ?> — <?= htmlspecialchars((string)($last['end_date'] ?? 'триває')) ?>)
                    </div>
                <?php endif; ?>

                <div style="display: flex; gap: 0.6rem; flex-wrap: wrap;">
                    <a href="/expeditions" class="btn btn-primary">Експедиції</a>
                    <a href="/regions" class="btn btn-secondary">Регіони</a>
                    <a href="/researchers" class="btn btn-secondary">Дослідники</a>
                    <a href="/equipment" class="btn btn-secondary">Спорядження</a>
                    <a href="/artifacts" class="btn btn-secondary">Артефакти</a>
                    <a href="/reports" class="btn btn-secondary">Аналітика</a>
                </div>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>
