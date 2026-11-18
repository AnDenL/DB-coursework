<?php
$sortField = $result['__sort'][$colName] ?? null;
?>
<th>
    <?php if ($sortField !== null): ?>
        <?php
        $isActive = ($_GET['sort'] ?? '') === $sortField;
        $currDir  = strtoupper((string)($_GET['dir'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
        $nextDir  = ($isActive && $currDir === 'ASC') ? 'DESC' : 'ASC';
        $query    = array_merge(
            array_diff_key($_GET, ['create' => 1, 'edit' => 1, 'error' => 1]),
            ['sort' => $sortField, 'dir' => $nextDir]
        );
        $sortUrl  = ($actionBase ?? $basePath) . '?' . http_build_query($query);
        $arrow    = $isActive ? ($currDir === 'ASC' ? '▲' : '▼') : '⇅';
        ?>
        <a href="<?= htmlspecialchars($sortUrl) ?>" class="sort-link <?= $isActive ? 'active' : '' ?>">
            <span><?= htmlspecialchars((string)($colName ?? '—')) ?></span>
            <span class="sort-arrow"><?= $arrow ?></span>
        </a>
    <?php else: ?>
        <?= htmlspecialchars((string)($colName ?? '—')) ?>
    <?php endif; ?>
</th>
