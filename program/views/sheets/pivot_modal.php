<?php
/**
 * Очікує:
 * $pField      — назва поля Pivot (напр. 'members_pivot')
 * $pMeta       — об'єкт Pivot
 * $dbc         — PDO
 * $editId      — ID запису або null
 * $record      — масив даних форми або null
 * $formAttr    — рядок атрибута форми ('form="inline-edit"' або '')
 * $costTargetId — ID елемента в таблиці для живого оновлення суми (або '')
 */
$formAttr     ??= '';
$costTargetId ??= '';
$modalId        = 'pivot-modal-' . $pField;

$pScheme  = array_column(get_scheme($pMeta->pivotTable), null, 'Field');
$extraCol = $pScheme[$pMeta->extraField] ?? null;
$isEnum   = $extraCol && str_starts_with($extraCol['Type'], 'enum(');
$enumOpts = $isEnum ? parse_enum($extraCol['Type']) : [];

$costSelect = $pMeta->costColumn ? ', ' . ident($pMeta->costColumn) . ' AS unit_cost' : '';
$allTargets = $dbc->query(
    'SELECT id, ' . ident($pMeta->targetLabel) . " AS label $costSelect FROM " . ident($pMeta->targetTable) . ' ORDER BY label'
)->fetchAll();

$activeMap = [];
if (isset($record[$pField]) && is_array($record[$pField])) {
    foreach ($record[$pField] as $tid => $r) {
        if (!empty($r['checked'])) {
            $activeMap[(int)$tid] = $r['extra'] ?? '';
        }
    }
} elseif ($editId !== null) {
    $st = $dbc->prepare(
        'SELECT ' . ident($pMeta->foreignKey) . ' AS fid, ' . ident($pMeta->extraField) . ' AS extra' .
        ' FROM ' . ident($pMeta->pivotTable) . ' WHERE ' . ident($pMeta->localKey) . ' = :pid'
    );
    $st->execute(['pid' => $editId]);
    foreach ($st->fetchAll() as $r) {
        $activeMap[(int)$r['fid']] = $r['extra'];
    }
}
?>

<div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
    <button type="button" class="btn btn-secondary" style="padding:0.35rem 0.7rem; font-size:0.85rem;"
            data-open-pivot="<?= htmlspecialchars($modalId) ?>">
        Обрано: <strong id="pivot-count-<?= htmlspecialchars($pField) ?>"><?= count($activeMap) ?></strong> ✎
    </button>
    <?php if ($pMeta->costColumn): ?>
        <span id="pivot-cost-<?= htmlspecialchars($pField) ?>" style="font-size:0.85rem; font-weight:600; color:var(--primary);"></span>
    <?php endif; ?>
</div>

<dialog id="<?= htmlspecialchars($modalId) ?>" class="pivot-modal"
        data-pivot="<?= htmlspecialchars($pField) ?>"
        data-cost-target="<?= htmlspecialchars($costTargetId) ?>">
    <div class="pivot-modal-inner">
        <div class="pivot-modal-header">
            <h3 style="margin:0; font-size:1.1rem;"><?= htmlspecialchars($pMeta->label) ?></h3>
            <button type="button" class="btn btn-secondary" style="padding:0.25rem 0.6rem;" data-close-pivot>✕</button>
        </div>

        <input type="search" class="form-control pivot-search" placeholder="Пошук за назвою..." autocomplete="off">

        <div class="pivot-box" style="max-height: 52vh;">
            <?php foreach ($allTargets as $t): ?>
                <?php
                $tid       = (int)$t['id'];
                $isChecked = array_key_exists($tid, $activeMap);
                $extraVal  = $isChecked ? $activeMap[$tid] : ($isEnum ? ($enumOpts[0] ?? '') : '1');
                $unitCost  = (float)($t['unit_cost'] ?? 0);
                ?>
                <div class="pivot-row" data-label="<?= htmlspecialchars(mb_strtolower((string)$t['label'])) ?>">
                    <label class="pivot-check">
                        <input <?= $formAttr ?> type="checkbox"
                               name="<?= htmlspecialchars($pField) ?>[<?= $tid ?>][checked]"
                               value="1"
                               data-cost="<?= $unitCost ?>"
                               <?= $isChecked ? 'checked' : '' ?>>
                        <span>
                            <?= htmlspecialchars((string)$t['label']) ?>
                            <?php if ($pMeta->costColumn): ?>
                                <small style="color:var(--text-muted);">(<?= number_format($unitCost, 0, '.', ' ') ?> грн)</small>
                            <?php endif; ?>
                        </span>
                    </label>

                    <?php if ($isEnum): ?>
                        <select <?= $formAttr ?> name="<?= htmlspecialchars($pField) ?>[<?= $tid ?>][extra]"
                                class="form-control pivot-extra"
                                <?= $isChecked ? '' : 'hidden disabled' ?>>
                            <?php foreach ($enumOpts as $opt): ?>
                                <option value="<?= htmlspecialchars($opt) ?>" <?= (string)$extraVal === (string)$opt ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(enum_label($opt)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input <?= $formAttr ?> type="number"
                               name="<?= htmlspecialchars($pField) ?>[<?= $tid ?>][extra]"
                               value="<?= htmlspecialchars((string)$extraVal) ?>"
                               min="1" max="10000" step="1"
                               class="form-control pivot-extra"
                               <?= $isChecked ? '' : 'hidden disabled' ?>>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="pivot-modal-footer">
            <span class="pivot-modal-summary" style="font-size:0.88rem; color:var(--text-muted);"></span>
            <button type="button" class="btn btn-primary" data-close-pivot>Готово</button>
        </div>
    </div>
</dialog>
