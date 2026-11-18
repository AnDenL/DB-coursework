<?php
$actionBase = $tableBasePath ?? $basePath;
$canWrite = DB::can_write();
?>

<div class="table-wrap">
<table class="table">
    <thead>
        <tr>
            <?php foreach ($headers as $colName) {
                include 'column_head.php';
            } ?>
            <?php if (isset($result['__id']) && $canWrite): ?>
                <th>Дії</th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php for ($i = 0; $i < $rowCount; $i++): ?>
            <?php $rowId = isset($result['__id']) ? (string)$result['__id'][$i] : ''; ?>
            <tr style="<?= ($editId ?? null) === $rowId ? 'background-color: #dbeafe;' : '' ?>">
                <?php foreach ($headers as $colName) {
                    include 'block.php';
                } ?>
                <?php if (isset($result['__id']) && $canWrite): ?>
                    <td style="white-space: nowrap;">
                        <a href="<?= htmlspecialchars($actionBase) ?>?edit=<?= urlencode($rowId) ?>" class="btn btn-secondary">Редагувати</a>
                        <form method="POST" action="<?= htmlspecialchars($actionBase) ?>/delete?id=<?= urlencode($rowId) ?>" style="display:inline;"
                              onsubmit="return confirm('Видалити цей запис?');">
                            <?php if (!empty($returnUrl)): ?>
                                <input type="hidden" name="_redirect" value="<?= htmlspecialchars($returnUrl) ?>">
                            <?php endif; ?>
                            <button type="submit" class="btn btn-danger">Видалити</button>
                        </form>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endfor; ?>
    </tbody>
</table>
</div>
