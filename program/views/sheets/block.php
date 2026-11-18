<?php $cell = $result[$colName][$i] ?? null; ?>

<?php if (str_contains($colName, 'Фото')): ?>
    <td>
        <?php if (!empty($cell)): ?>
            <img src="/<?= htmlspecialchars(ltrim((string)$cell, '/')) ?>"
                 alt="<?= htmlspecialchars((string)$cell) ?>"
                 style="width: 54px; height: 54px; object-fit: cover; border-radius: 25%; border: 1px solid #ccc;">
        <?php else: ?>
            —
        <?php endif; ?>
    </td>
<?php elseif (is_array($cell)): ?>
    <td>
        <?php $links = isset($cell['url']) ? [$cell] : $cell; ?>
        <?php if (empty($links)): ?>
            <span style="color: var(--text-muted);">Не визначено</span>
        <?php else: ?>
            <div class="cell-scroll">
                <?php foreach ($links as $l): ?>
                    <a href="<?= htmlspecialchars($l['url']) ?>" class="cell-tag">
                        <?= htmlspecialchars((string)$l['label']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </td>
<?php else: ?>
    <td>
        <?= htmlspecialchars((string)($cell ?? 'Не визначено')) ?>
    </td>
<?php endif; ?>
