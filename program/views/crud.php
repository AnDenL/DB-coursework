<!DOCTYPE html>
<?php include 'head.php'; ?>
<body>
    <?php
        include 'menu.php';
        $basePath = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
        $headers = array_values(array_diff(array_keys($result), ['__id', '__sort']));
        $rowCount = !empty($headers) ? count($result[$headers[0]]) : 0;
        $dbc      = DB::get_connection();
    ?>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title"><?= htmlspecialchars($pageTitle) ?></h1>
            <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                <?php if (!$showForm && DB::can_write()): ?>
                    <a href="<?= htmlspecialchars($basePath) ?>?create=1" class="btn btn-primary">+ Додати запис</a>
                <?php endif; ?>
                <?php if ($showForm && !DB::can_write()): ?>
                    <div class="card" style="border-color: var(--warning); color: var(--warning); font-weight: 500;">
                        Режим лише для перегляду: увійдіть у шапці під обліковим записом із правами запису, щоб створювати або змінювати записи.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($_GET['error'])): ?>
            <div class="card" style="border-color: var(--danger); color: var(--danger); font-weight: 500;">
                <?= htmlspecialchars((string)$_GET['error']) ?>
            </div>
        <?php endif; ?>

        <?php if ($showForm && DB::can_write()): ?>
            <?php include 'sheets/form.php'; ?>
        <?php endif; ?>

        <form method="GET" action="<?= htmlspecialchars($basePath) ?>" class="card" style="display:flex; gap:0.75rem; flex-wrap:wrap; align-items:center;">
            <input type="text" name="q" value="<?= htmlspecialchars((string)($_GET['q'] ?? '')) ?>"
                   placeholder="Пошук..." class="form-control" style="max-width: 240px;">

            <?php foreach ($schemaColumns as $field => $meta): ?>
                <?php if ($meta instanceof Foreign): ?>
                    <?php $opts = get_foreign($dbc, $meta->table, $meta->column); ?>
                    <select name="<?= htmlspecialchars($field) ?>" class="form-control" style="max-width: 220px;">
                        <option value="">Всі: <?= htmlspecialchars($meta->label) ?></option>
                        <?php foreach ($opts as $optId => $opt): ?>
                            <option value="<?= (int)$optId ?>" <?= (string)($_GET[$field] ?? '') === (string)$optId ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$opt['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if (!empty($_GET['sort'])): ?>
                <input type="hidden" name="sort" value="<?= htmlspecialchars((string)$_GET['sort']) ?>">
                <input type="hidden" name="dir" value="<?= htmlspecialchars((string)($_GET['dir'] ?? 'ASC')) ?>">
            <?php endif; ?>

            <button type="submit" class="btn btn-primary">Знайти</button>
            <?php if (!empty(array_diff_key($_GET, ['create' => 1, 'edit' => 1, 'error' => 1]))): ?>
                <a href="<?= htmlspecialchars($basePath) ?>" class="btn btn-secondary">Скинути</a>
            <?php endif; ?>
        </form>

        <?php include 'sheets/table.php'; ?>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>
