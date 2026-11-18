<?php
$dbc       = DB::get_connection();
$scheme    = array_column(get_scheme($table), null, 'Field');
$backUrl   = '/' . trim($table, '/');
$record  ??= null;
$editId  ??= null;
$formError ??= null;
$preset    ??= [];
$returnUrl ??= null;
$formTitle ??= ($record ? 'Редагування запису' : 'Новий запис');
$actionUrl = $backUrl . '/save' . ($editId !== null ? '?edit=' . urlencode($editId) : '');
$maxBytes  = max_upload_bytes();
$maxMb     = round($maxBytes / 1024 / 1024, 1);
?>

<form method="POST" action="<?= htmlspecialchars($actionUrl) ?>" enctype="multipart/form-data" class="card">
    <div class="page-header" style="margin-bottom: 1rem;">
        <h2 class="card-title" style="margin:0;"><?= htmlspecialchars($formTitle) ?></h2>
        <?php if ($returnUrl === null): ?>
            <a href="<?= htmlspecialchars($backUrl) ?>" class="btn btn-secondary">✕ Скасувати</a>
        <?php endif; ?>
    </div>

    <?php if ($returnUrl !== null): ?>
        <input type="hidden" name="_redirect" value="<?= htmlspecialchars($returnUrl) ?>">
    <?php endif; ?>

    <?php if ($formError): ?>
        <div class="card" style="border-color: var(--danger); color: var(--danger); font-weight: 500;">
            <?= htmlspecialchars($formError) ?>
        </div>
    <?php endif; ?>

    <?php foreach ($schemaColumns as $field => $meta): ?>
        <?php if ($meta instanceof Subquery) continue; ?>

        <?php if ($meta instanceof Pivot): ?>
            <?php
            $pivotScheme = array_column(get_scheme($meta->pivotTable), null, 'Field');
            $extraCol    = $pivotScheme[$meta->extraField] ?? null;
            $isEnum      = $extraCol && str_starts_with($extraCol['Type'], 'enum(');
            $enumOpts    = $isEnum ? parse_enum($extraCol['Type']) : [];

            $costSelect  = $meta->costColumn ? ', ' . ident($meta->costColumn) . ' AS unit_cost' : '';
            $allTargets  = $dbc->query(
                'SELECT id, ' . ident($meta->targetLabel) . " AS label $costSelect FROM " . ident($meta->targetTable) . ' ORDER BY label'
            )->fetchAll();

            $activeMap = [];
            if (isset($record[$field]) && is_array($record[$field])) {
                foreach ($record[$field] as $tid => $r) {
                    if (!empty($r['checked'])) $activeMap[(int)$tid] = $r['extra'] ?? '';
                }
            } elseif ($editId !== null) {
                $st = $dbc->prepare(
                    'SELECT ' . ident($meta->foreignKey) . ' AS fid, ' . ident($meta->extraField) . ' AS extra' .
                    ' FROM ' . ident($meta->pivotTable) . ' WHERE ' . ident($meta->localKey) . ' = :pid'
                );
                $st->execute(['pid' => $editId]);
                foreach ($st->fetchAll() as $r) {
                    $activeMap[(int)$r['fid']] = $r['extra'];
                }
            }
            ?>
            <div class="form-group">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.35rem;">
                    <label class="form-label" style="margin:0;"><?= htmlspecialchars($meta->label) ?></label>
                    <?php if ($meta->costColumn): ?>
                        <span id="pivot-cost-<?= htmlspecialchars($field) ?>" style="font-size:0.88rem; font-weight:600; color:var(--primary);"></span>
                    <?php endif; ?>
                </div>

                <div class="pivot-box" data-pivot="<?= htmlspecialchars($field) ?>">
                    <?php foreach ($allTargets as $t): ?>
                        <?php
                        $tid       = (int)$t['id'];
                        $isChecked = array_key_exists($tid, $activeMap);
                        $extraVal  = $isChecked ? $activeMap[$tid] : ($isEnum ? ($enumOpts[0] ?? '') : '1');
                        $unitCost  = (float)($t['unit_cost'] ?? 0);
                        ?>
                        <div class="pivot-row">
                            <label class="pivot-check">
                                <input type="checkbox"
                                       name="<?= htmlspecialchars($field) ?>[<?= $tid ?>][checked]"
                                       value="1"
                                       data-cost="<?= $unitCost ?>"
                                       <?= $isChecked ? 'checked' : '' ?>>
                                <span>
                                    <?= htmlspecialchars((string)$t['label']) ?>
                                    <?php if ($meta->costColumn): ?>
                                        <small style="color:var(--text-muted);">(<?= number_format($unitCost, 0, '.', ' ') ?> грн)</small>
                                    <?php endif; ?>
                                </span>
                            </label>

                            <?php if ($isEnum): ?>
                                <select name="<?= htmlspecialchars($field) ?>[<?= $tid ?>][extra]" class="form-control pivot-extra">
                                    <?php foreach ($enumOpts as $opt): ?>
                                        <option value="<?= htmlspecialchars($opt) ?>" <?= (string)$extraVal === (string)$opt ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($opt) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input type="number"
                                       name="<?= htmlspecialchars($field) ?>[<?= $tid ?>][extra]"
                                       value="<?= htmlspecialchars((string)$extraVal) ?>"
                                       min="1" max="10000" step="1"
                                       class="form-control pivot-extra">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php continue; ?>
        <?php endif; ?>

        <?php
        $col = $scheme[$field] ?? null;
        if (!$col || str_contains($col['Extra'], 'auto_increment')) continue;

        if (array_key_exists($field, $preset)): ?>
            <input type="hidden" name="<?= htmlspecialchars($field) ?>" value="<?= htmlspecialchars((string)$preset[$field]) ?>">
            <?php continue;
        endif;

        $label    = is_string($meta) ? $meta : $meta->label;
        $val      = $record[$field] ?? '';
        $isPhoto  = str_contains($field, '_path');
        $required = ($col['Null'] === 'NO' && $col['Default'] === null && !$isPhoto) ? 'required' : '';
        $maxLen   = preg_match('/^varchar\((\d+)\)/i', $col['Type'], $m) ? 'maxlength="' . (int)$m[1] . '"' : '';
        $bounds   = col_bounds($col['Type']);
        ?>

        <div class="form-group">
            <label class="form-label"><?= htmlspecialchars($label) ?></label>

            <?php if ($meta instanceof Foreign): ?>
                <?php $options = get_foreign($dbc, $meta->table, $meta->column); ?>
                <select name="<?= htmlspecialchars($field) ?>" class="form-control" <?= $required ?>>
                    <option value="">— Оберіть —</option>
                    <?php foreach ($options as $optId => $opt): ?>
                        <option value="<?= (int)$optId ?>" <?= (string)$val === (string)$optId ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)$opt['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

            <?php elseif (str_starts_with($col['Type'], 'enum(')): ?>
                <select name="<?= htmlspecialchars($field) ?>" class="form-control" <?= $required ?>>
                    <?php foreach (parse_enum($col['Type']) as $opt): ?>
                        <option value="<?= htmlspecialchars($opt) ?>" <?= $val === $opt ? 'selected' : '' ?>>
                            <?= htmlspecialchars($opt) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

            <?php elseif ($isPhoto): ?>
                <?php if (!empty($val)): ?>
                    <div style="margin-bottom: 0.5rem;">
                        <img src="/<?= htmlspecialchars(ltrim((string)$val, '/')) ?>" alt=""
                             style="width: 72px; height: 72px; object-fit: cover; border-radius: 8px;">
                    </div>
                <?php endif; ?>
                <input type="file" name="<?= htmlspecialchars($field) ?>"
                       accept="image/jpeg,image/png,image/webp,image/gif"
                       data-max-bytes="<?= $maxBytes ?>"
                       class="form-control js-photo-input">
                <small style="color: var(--text-muted);">Формати: JPG, PNG, WEBP, GIF (до <?= $maxMb ?> МБ)</small>
                <div class="js-file-error" style="color: var(--danger); font-size: 0.85rem; margin-top: 0.25rem; display: none;"></div>

            <?php elseif (str_contains($col['Type'], 'text')): ?>
                <textarea name="<?= htmlspecialchars($field) ?>" rows="3" class="form-control" <?= $required ?>><?= htmlspecialchars((string)$val) ?></textarea>

            <?php elseif ($bounds !== null): ?>
                <input type="number"
                       name="<?= htmlspecialchars($field) ?>"
                       value="<?= htmlspecialchars((string)$val) ?>"
                       min="<?= htmlspecialchars($bounds['min']) ?>"
                       max="<?= htmlspecialchars($bounds['max']) ?>"
                       step="<?= htmlspecialchars($bounds['step']) ?>"
                       class="form-control" <?= $required ?>>

            <?php else: ?>
                <?php $type = $col['Type'] === 'date' ? 'date' : 'text'; ?>
                <input type="<?= $type ?>" name="<?= htmlspecialchars($field) ?>"
                       value="<?= htmlspecialchars((string)$val) ?>" class="form-control"
                       <?= $required ?> <?= $maxLen ?>>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <button type="submit" class="btn btn-primary">Зберегти</button>
</form>

<script>
document.querySelectorAll('.js-photo-input').forEach(input => {
    input.addEventListener('change', () => {
        const errBox = input.parentElement.querySelector('.js-file-error');
        const file = input.files[0];
        errBox.style.display = 'none';
        errBox.textContent = '';

        if (!file) return;

        const maxBytes = Number(input.dataset.maxBytes || 0);
        const allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

        if (!allowed.includes(file.type)) {
            errBox.textContent = 'Недопустимий формат. Оберіть JPG, PNG, WEBP або GIF.';
            errBox.style.display = 'block';
            input.value = '';
            return;
        }

        if (maxBytes > 0 && file.size > maxBytes) {
            const sizeMb = (file.size / 1024 / 1024).toFixed(1);
            const maxMb = (maxBytes / 1024 / 1024).toFixed(1);
            errBox.textContent = `Файл занадто великий (${sizeMb} МБ). Максимум — ${maxMb} МБ.`;
            errBox.style.display = 'block';
            input.value = '';
        }
    });
});

document.querySelectorAll('.pivot-box').forEach(box => {
    const field = box.dataset.pivot;
    const costLabel = document.getElementById('pivot-cost-' + field);

    const recalc = () => {
        if (!costLabel) return;
        let total = 0;
        box.querySelectorAll('.pivot-row').forEach(row => {
            const cb = row.querySelector('input[type="checkbox"]');
            const qty = Number(row.querySelector('.pivot-extra')?.value || 0);
            if (cb?.checked) total += Number(cb.dataset.cost || 0) * qty;
        });
        const budget = Number(document.querySelector('input[name="budget"]')?.value || 0);
        const diff = budget - total;
        costLabel.innerHTML = `Сума: ${total.toLocaleString('uk-UA')} грн` +
            (budget > 0 ? ` (залишок: <span style="color:${diff < 0 ? 'var(--danger)' : 'var(--success)'}">${diff.toLocaleString('uk-UA')} грн</span>)` : '');
    };

    box.querySelectorAll('.pivot-row').forEach(row => {
        const cb = row.querySelector('input[type="checkbox"]');
        const extra = row.querySelector('.pivot-extra');
        extra?.addEventListener('input', () => { if (cb) cb.checked = true; recalc(); });
        extra?.addEventListener('change', () => { if (cb) cb.checked = true; recalc(); });
        cb?.addEventListener('change', recalc);
    });

    document.querySelector('input[name="budget"]')?.addEventListener('input', recalc);
    recalc();
});

const startInput = document.querySelector('input[name="start_date"]');
const endInput = document.querySelector('input[name="end_date"]');
if (startInput && endInput) {
    const syncDates = () => {
        if (startInput.value) endInput.min = startInput.value;
    };
    startInput.addEventListener('change', syncDates);
    syncDates();
}
</script>
