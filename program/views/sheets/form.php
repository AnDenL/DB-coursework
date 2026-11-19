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
            <div class="form-group">
                <label class="form-label"><?= htmlspecialchars($meta->label) ?></label>
                <?php
                $pField       = $field;
                $pMeta        = $meta;
                $formAttr     = '';
                $costTargetId = '';
                include __DIR__ . '/pivot_modal.php';
                ?>
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
                        <option value="<?= htmlspecialchars($opt) ?>" <?= (string)$val === (string)$opt ? 'selected' : '' ?>>
                            <?= htmlspecialchars(enum_label($opt)) ?>
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

