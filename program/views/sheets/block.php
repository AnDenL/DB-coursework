<?php if (str_contains($colName, '_path')): ?>
    <td>
        <img src="<?= htmlspecialchars((string)($result[$colName][$i] ?? '—')) ?>"
            alt="<?= htmlspecialchars((string)($result[$colName][$i] ?? '—')) ?>"
            style="width: 54px; height: 54px; object-fit: cover; border-radius: 25%; border: 1px solid #ccc;">
    </td>
<?php else : ?>
    <td>
        <?= htmlspecialchars((string)($result[$colName][$i] ?? '—')) ?>
    </td>
<?php endif; ?>
