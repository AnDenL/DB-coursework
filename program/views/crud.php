<!DOCTYPE html>
<?php include 'head.php'; ?>
<body>
    <?php
        include 'menu.php';
        $basePath = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
        $headers  = array_values(array_diff(array_keys($result), ['__id', '__sort']));
        $rowCount = !empty($headers) ? count($result[$headers[0]]) : 0;
        $dbc      = DB::get_connection();
        $canWrite = DB::can_write();
        $isCreate = $canWrite && (isset($_GET['create']) || (!empty($showForm) && ($editId ?? null) === null));
    ?>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title"><?= htmlspecialchars($pageTitle) ?></h1>
            <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                <?php if (!$isCreate && ($editId ?? null) === null && $canWrite): ?>
                    <a href="<?= htmlspecialchars($basePath) ?>?create=1" class="btn btn-primary">+ Додати запис</a>
                <?php endif; ?>
                <?php if (!empty($showForm) && !$canWrite): ?>
                    <div class="card" style="border-color: var(--warning); color: var(--warning); font-weight: 500;">
                        Режим лише для перегляду: увійдіть у шапці під обліковим записом із правами запису, щоб створювати або змінювати записи.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($_GET['error']) || !empty($formError)): ?>
            <div class="card" style="border-color: var(--danger); color: var(--danger); font-weight: 500;">
                <?= htmlspecialchars((string)($_GET['error'] ?? $formError)) ?>
            </div>
        <?php endif; ?>

        <?php if ($isCreate): ?>
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

    <script>
    document.querySelectorAll('.js-photo-input').forEach(input => {
        input.addEventListener('change', () => {
            const errBox = input.parentElement.querySelector('.js-file-error');
            const file = input.files[0];
            if (errBox) {
                errBox.style.display = 'none';
                errBox.textContent = '';
            }
            if (!file) return;

            const maxBytes = Number(input.dataset.maxBytes || 0);
            const allowed  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            let msg = '';

            if (!allowed.includes(file.type)) {
                msg = 'Недопустимий формат. Оберіть JPG, PNG, WEBP або GIF.';
            } else if (maxBytes > 0 && file.size > maxBytes) {
                msg = `Файл занадто великий (${(file.size / 1024 / 1024).toFixed(1)} МБ). Максимум — ${(maxBytes / 1024 / 1024).toFixed(1)} МБ.`;
            }

            if (msg) {
                if (errBox) {
                    errBox.textContent = msg;
                    errBox.style.display = 'block';
                } else {
                    alert(msg);
                }
                input.value = '';
            }
        });
    });

    document.querySelectorAll('[data-open-pivot]').forEach(btn => {
        btn.addEventListener('click', () => {
            const dialog = document.getElementById(btn.dataset.openPivot);
            if (!dialog) return;
            dialog.showModal();
            const search = dialog.querySelector('.pivot-search');
            if (search) {
                search.value = '';
                search.dispatchEvent(new Event('input'));
                search.focus();
            }
        });
    });

    document.querySelectorAll('.pivot-modal').forEach(dialog => {
        const field       = dialog.dataset.pivot;
        const countEl     = document.getElementById('pivot-count-' + field);
        const costEl      = document.getElementById('pivot-cost-' + field);
        const cellCostEl  = dialog.dataset.costTarget ? document.getElementById(dialog.dataset.costTarget) : null;
        const summaryEl   = dialog.querySelector('.pivot-modal-summary');
        const searchInput = dialog.querySelector('.pivot-search');

        const recalc = () => {
            let count = 0;
            let total = 0;
            dialog.querySelectorAll('.pivot-row').forEach(row => {
                const cb  = row.querySelector('input[type="checkbox"]');
                const qty = Number(row.querySelector('.pivot-extra')?.value || 0);
                if (cb?.checked) {
                    count++;
                    total += Number(cb.dataset.cost || 0) * qty;
                }
            });

            if (countEl) countEl.textContent = String(count);

            const budget = Number(document.querySelector('input[name="budget"]')?.value || 0);
            const diff   = budget - total;

            if (costEl) {
                costEl.innerHTML = `Сума: ${total.toLocaleString('uk-UA')} грн` +
                    (budget > 0 ? ` (залишок: <span style="color:${diff < 0 ? 'var(--danger)' : 'var(--success)'}">${diff.toLocaleString('uk-UA')} грн</span>)` : '');
            }
            if (cellCostEl) {
                cellCostEl.textContent = total.toLocaleString('uk-UA', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' грн';
            }
            if (summaryEl) {
                summaryEl.textContent = costEl
                    ? `Обрано позицій: ${count} · Сума: ${total.toLocaleString('uk-UA')} грн`
                    : `Обрано позицій: ${count}`;
            }
        };

        dialog.querySelectorAll('.pivot-row').forEach(row => {
            const cb    = row.querySelector('input[type="checkbox"]');
            const extra = row.querySelector('.pivot-extra');

            const syncRow = () => {
                if (!extra || !cb) return;
                extra.hidden   = !cb.checked;
                extra.disabled = !cb.checked;
            };

            cb?.addEventListener('change', () => { syncRow(); recalc(); });
            extra?.addEventListener('input', recalc);
            extra?.addEventListener('change', recalc);
            syncRow();
        });

        if (searchInput) {
            searchInput.addEventListener('keydown', e => {
                if (e.key === 'Enter') e.preventDefault();
            });
            searchInput.addEventListener('input', () => {
                const q = searchInput.value.trim().toLowerCase();
                dialog.querySelectorAll('.pivot-row').forEach(row => {
                    const label = row.dataset.label || '';
                    row.style.display = (q === '' || label.includes(q)) ? '' : 'none';
                });
            });
        }

        dialog.querySelectorAll('[data-close-pivot]').forEach(btn => {
            btn.addEventListener('click', () => dialog.close());
        });
        dialog.addEventListener('click', e => {
            if (e.target === dialog) dialog.close();
        });

        document.querySelector('input[name="budget"]')?.addEventListener('input', recalc);
        recalc();
    });

    const startInput = document.querySelector('input[name="start_date"]');
    const endInput   = document.querySelector('input[name="end_date"]');
    if (startInput && endInput) {
        const syncDates = () => {
            if (startInput.value) endInput.min = startInput.value;
        };
        startInput.addEventListener('change', syncDates);
        syncDates();
    }
    </script>
</body>
</html>
