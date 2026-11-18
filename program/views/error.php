<!DOCTYPE html>
<?php $pageTitle = $pageTitle ?? 'Помилка'; ?>
<?php include 'head.php'; ?>
<body>
    <div class="navbar">
        <div class="nav-links">
            <a href="/" class="nav-link">Home</a>
            <a href="/expeditions" class="nav-link">Expeditions</a>
        </div>
    </div>

    <div class="container" style="max-width: 680px;">
        <div class="card" style="border-color: var(--danger);">
            <h1 class="card-title" style="color: var(--danger); font-size: 1.3rem;">
                <?= htmlspecialchars($errorTitle ?? 'Виникла помилка під час обробки запиту') ?>
            </h1>
            <p style="margin-bottom: 1.25rem; color: var(--text);">
                <?= htmlspecialchars($errorMessage ?? 'Спробуйте оновити сторінку або повернутися на головну.') ?>
            </p>
            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                <a href="javascript:location.reload()" class="btn btn-primary">Оновити сторінку</a>
                <a href="/" class="btn btn-secondary">На головну</a>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>
