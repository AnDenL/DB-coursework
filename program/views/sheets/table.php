<?php
$actionBase = $tableBasePath ?? $basePath;
$canWrite   = DB::can_write();
$isEditing  = $canWrite && isset($editId, $record) && $record !== null;

$headerToField  = [];
$pivotsByTarget = [];
if (isset($schemaColumns)) {
    foreach ($schemaColumns as $f => $m) {
        if ($m instanceof Pivot) {
            $pivotsByTarget[$m->targetTable] = [$f, $m];
        } else {
            $headerToField[is_string($m) ? $m : $m->label] = [$f, $m];
        }
    }
}
$schemeMap = $isEditing ? array_column(get_scheme($table), null, 'Field') : [];
?>

<?php if ($isEditing): ?>
    <form id="inline-edit" method="POST"
          action="<?= htmlspecialchars($actionBase) ?>/save?edit=<?= urlencode((string)$editId) ?>"
          enctype="multipart/form-data"></form>
<?php endif; ?>

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
            <?php
            $rowId       = isset($result['__id']) ? (string)$result['__id'][$i] : '';
            $editThisRow = $isEditing && (string)$editId === $rowId;
            ?>
            <tr style="<?= $editThisRow ? 'background-color: #dbeafe;' : '' ?>">
                <?php foreach ($headers as $colName): ?>
                    <?php if (!$editThisRow): ?>
                        <?php include 'block.php'; ?>
                    <?php else: ?>
                        <?php
                        [$field, $meta] = $headerToField[$colName] ?? [null, null];
                        $col      = $field ? ($schemeMap[$field] ?? null) : null;
                        $val      = $field ? ($record[$field] ?? '') : '';
                        $isPhoto  = $field && str_contains($field, '_path');
                        $required = ($col && $col['Null'] === 'NO' && $col['Default'] === null && !$isPhoto) ? 'required' : '';
                        $maxLen   = ($col && preg_match('/^varchar\((\d+)\)/i', $col['Type'], $m)) ? 'maxlength="' . (int)$m[1] . '"' : '';
                        ?>
                        <td>
                            <?php if ($meta instanceof Subquery && $meta->table !== null && isset($pivotsByTarget[$meta->table])): ?>
                                <?php
                                [$pField, $pMeta] = $pivotsByTarget[$meta->table];
                                $formAttr         = 'form="inline-edit"';
                                $costTargetId     = $pMeta->costColumn ? 'inline-subquery-cost' : '';
                                include __DIR__ . '/pivot_modal.php';
                                ?>

                            <?php elseif (!$col || $meta instanceof Subquery): ?>
                                <?php $cellVal = $result[$colName][$i] ?? '—'; ?>
                                <span <?= $field === 'equipment_cost' ? 'id="inline-subquery-cost"' : '' ?> style="color:var(--text-muted); font-weight:500;">
                                    <?= is_string($cellVal) ? htmlspecialchars($cellVal) : '—' ?>
                                </span>

                            <?php elseif ($meta instanceof Foreign): ?>
                                <?php $options = get_foreign($dbc, $meta->table, $meta->column); ?>
                                <select form="inline-edit" name="<?= htmlspecialchars($field) ?>" class="form-control" style="min-width:140px; padding:0.35rem 0.5rem;" <?= $required ?>>
                                    <option value="">—</option>
                                    <?php foreach ($options as $optId => $opt): ?>
                                        <option value="<?= (int)$optId ?>" <?= (string)$val === (string)$optId ? 'selected' : '' ?>>
                                            <?= htmlspecialchars((string)$opt['label']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                            <?php elseif (str_starts_with($col['Type'], 'enum(')): ?>
                                <select form="inline-edit" name="<?= htmlspecialchars($field) ?>" class="form-control" style="min-width:120px; padding:0.35rem 0.5rem;" <?= $required ?>>
                                    <?php foreach (parse_enum($col['Type']) as $opt): ?>
                                        <option value="<?= htmlspecialchars($opt) ?>" <?= (string)$val === (string)$opt ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(enum_label($opt)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                            <?php elseif ($isPhoto): ?>
                                <div style="display:flex; align-items:center; gap:0.5rem;">
                                    <?php if (!empty($val)): ?>
                                        <img src="/<?= htmlspecialchars(ltrim((string)$val, '/')) ?>" alt=""
                                             style="width: 44px; height: 44px; object-fit: cover; border-radius: 25%; border: 1px solid #ccc; flex-shrink: 0;">
                                    <?php endif; ?>
                                    <input form="inline-edit" type="file" name="<?= htmlspecialchars($field) ?>"
                                           accept="image/jpeg,image/png,image/webp,image/gif"
                                           style="max-width:165px; font-size:0.8rem;">
                                </div>

                            <?php elseif (str_contains($col['Type'], 'text')): ?>
                                <textarea form="inline-edit" name="<?= htmlspecialchars($field) ?>" rows="2"
                                          class="form-control" style="min-width:180px; padding:0.35rem 0.5rem; resize:vertical;" <?= $required ?>><?= htmlspecialchars((string)$val) ?></textarea>

                            <?php else: ?>
                                <?php
                                $bounds = col_bounds($col['Type']);
                                $type   = $bounds !== null ? 'number' : ($col['Type'] === 'date' ? 'date' : 'text');
                                ?>
                                <input form="inline-edit" type="<?= $type ?>" name="<?= htmlspecialchars($field) ?>"
                                       value="<?= htmlspecialchars((string)$val) ?>"
                                       <?= $bounds ? 'min="' . htmlspecialchars($bounds['min']) . '" max="' . htmlspecialchars($bounds['max']) . '" step="' . htmlspecialchars($bounds['step']) . '"' : '' ?>
                                       class="form-control" style="min-width:110px; padding:0.35rem 0.5rem;"
                                       <?= $required ?> <?= $maxLen ?>>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                <?php endforeach; ?>

                <?php if (isset($result['__id']) && $canWrite): ?>
                    <td style="white-space: nowrap;">
                        <?php if ($editThisRow): ?>
                            <button form="inline-edit" type="submit" class="btn btn-primary" style="padding:0.35rem 0.7rem;">Зберегти</button>
                            <a href="<?= htmlspecialchars($actionBase) ?>" class="btn btn-secondary" style="padding:0.35rem 0.7rem;">✕</a>
                        <?php else: ?>
                            <a href="<?= htmlspecialchars($actionBase) ?>?edit=<?= urlencode($rowId) ?>" class="btn btn-secondary">Редагувати</a>
                            <form method="POST" action="<?= htmlspecialchars($actionBase) ?>/delete?id=<?= urlencode($rowId) ?>" style="display:inline;"
                                  onsubmit="return confirm('Видалити цей запис?');">
                                <?php if (!empty($returnUrl)): ?>
                                    <input type="hidden" name="_redirect" value="<?= htmlspecialchars($returnUrl) ?>">
                                <?php endif; ?>
                                <button type="submit" class="btn btn-danger">Видалити</button>
                            </form>
                        <?php endif; ?>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endfor; ?>
    </tbody>
</table>
</div>
