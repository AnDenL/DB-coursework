<?php
declare(strict_types=1);

require_once "helpers.php";

function get_foreign(PDO $dbc, string $refTable, string $labelCol): array {
    $t    = ident($refTable);
    $c    = ident($labelCol);
    $rows = $dbc->query("SELECT id, $c FROM $t")->fetchAll(PDO::FETCH_KEY_PAIR);

    $res = [];
    foreach ($rows as $id => $label) {
        $res[$id] = [
            'label' => $label,
            'url'   => "/$refTable?id=$id",
        ];
    }
    return $res;
}

function select(string $table, string|array $pk, array $columns, array $fixedWhere = []): array {
    $dbc    = DB::get_connection();
    $pkCols = (array)$pk;

    $selectParts = [];
    foreach ($pkCols as $i => $col) {
        $selectParts[] = ident($col) . " AS `__pk_$i`";
    }

    $fks        = [];
    $whereParts = [];
    $params     = [];
    $sortExprs  = [];
    $sortMap    = [];

    foreach ($columns as $field => $meta) {
        if ($meta instanceof Pivot) continue;
        $header = is_string($meta) ? $meta : $meta->label;

        if ($meta instanceof Foreign) {
            $selectParts[]      = ident($field);
            $fks[$field]        = get_foreign($dbc, $meta->table, $meta->column);
            $sortExprs[$field]  = ident($field);
            $sortMap[$header]   = $field;
        } elseif ($meta instanceof Subquery) {
            $selectParts[] = "({$meta->sql}) AS " . ident($field);
            if ($meta->table === null) {
                $sortExprs[$field] = $meta->sortSql !== null ? "({$meta->sortSql})" : ident($field);
                $sortMap[$header]  = $field;
            } else {
                $sortMap[$header] = null;
            }
        } else {
            $selectParts[] = ident($field);
            if (!str_contains($field, '_path')) {
                $sortExprs[$field] = ident($field);
                $sortMap[$header]  = $field;
            } else {
                $sortMap[$header] = null;
            }
        }
    }

    if (!empty($fixedWhere)) {
        foreach ($fixedWhere as $col => $val) {
            $whereParts[]         = ident($col) . " = :fixed_$col";
            $params["fixed_$col"] = $val;
        }
    } else {
        if (is_string($pk) && isset($_GET['id']) && $_GET['id'] !== '') {
            $whereParts[] = ident($pk) . ' = :id';
            $params['id'] = (int)$_GET['id'];
        }

        $q = trim((string)($_GET['q'] ?? ''));
        if ($q !== '') {
            $likeParts = [];
            $idx       = 0;
            foreach ($columns as $field => $meta) {
                if (is_string($meta) && !str_contains($field, '_path')) {
                    $paramKey          = "q_$idx";
                    $likeParts[]       = ident($field) . " LIKE :$paramKey";
                    $params[$paramKey] = "%$q%";
                    $idx++;
                }
            }
            if ($likeParts) {
                $whereParts[] = '(' . implode(' OR ', $likeParts) . ')';
            }
        }

        foreach ($columns as $field => $meta) {
            if ($meta instanceof Foreign && isset($_GET[$field]) && $_GET[$field] !== '') {
                $whereParts[]       = ident($field) . " = :f_$field";
                $params["f_$field"] = (int)$_GET[$field];
            }
        }
    }

    $where     = $whereParts ? 'WHERE ' . implode(' AND ', $whereParts) : '';
    $sortField = (string)($_GET['sort'] ?? '');
    $sortDir   = strtoupper((string)($_GET['dir'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

    if ($sortField !== '' && isset($sortExprs[$sortField])) {
        $orderBy = 'ORDER BY ' . $sortExprs[$sortField] . " $sortDir";
    } else {
        $orderCols = array_map(fn($c) => ident($c) . " $sortDir", $pkCols);
        $orderBy   = $orderCols ? 'ORDER BY ' . implode(', ', $orderCols) : '';
    }

    $attributes = implode(', ', $selectParts);
    $tableIdent = ident($table);

    $query = $dbc->prepare("SELECT $attributes FROM $tableIdent $where $orderBy");
    $query->execute($params);
    $items = $query->fetchAll(PDO::FETCH_ASSOC);
    $query->closeCursor();

    $result = [];
    foreach ($columns as $field => $meta) {
        if ($meta instanceof Pivot) continue;
        $header = is_string($meta) ? $meta : $meta->label;
        $values = array_column($items, $field);

        if (isset($fks[$field])) {
            $values = array_map(fn($id) => $fks[$field][$id] ?? 'Не визначено', $values);
        } elseif ($meta instanceof Subquery && $meta->table !== null) {
            $values = array_map(function ($json) use ($meta) {
                $rows = $json ? json_decode((string)$json, true) : [];
                return array_map(fn($r) => [
                    'label' => $r['label'],
                    'url'   => "/{$meta->table}?id={$r['id']}",
                ], $rows ?: []);
            }, $values);
        }

        $result[$header] = $values;
    }

    if ($pkCols) {
        $result['__id'] = array_map(
            fn($row) => implode(':', array_map(fn($i) => (string)$row["__pk_$i"], array_keys($pkCols))),
            $items
        );
    }

    $result['__sort'] = $sortMap;
    return $result;
}

function get_scheme(string $table): array {
    $dbc = DB::get_connection();
    return $dbc->query('DESCRIBE ' . ident($table))->fetchAll(PDO::FETCH_ASSOC);
}

function find_one(string $table, string|array $pk, string $rawId): ?array {
    $dbc = DB::get_connection();
    [$whereSql, $params] = pk_where($pk, $rawId);
    $stmt = $dbc->prepare('SELECT * FROM ' . ident($table) . " WHERE $whereSql");
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function save_row(string $table, string|array $pk, array $columns, array $data, ?string $rawId = null): void {
    $dbc    = DB::get_connection();
    $scheme = get_scheme($table);
    $data   = array_merge($data, handle_uploads($table));

    $values = [];
    foreach ($scheme as $col) {
        $f = $col['Field'];
        if (str_contains($col['Extra'], 'auto_increment') || !array_key_exists($f, $data)) continue;

        $raw = is_string($data[$f]) ? trim($data[$f]) : $data[$f];

        if ($raw === '' || $raw === null) {
            if ($col['Null'] === 'NO' && $col['Default'] === null) {
                throw new RuntimeException("Поле «{$f}» є обов'язковим для заповнення.");
            }
            $values[$f] = null;
            continue;
        }

        if (str_starts_with($col['Type'], 'enum(') && !in_array($raw, parse_enum($col['Type']), true)) {
            throw new RuntimeException("Недопустиме значення для поля «{$f}».");
        }

        $bounds = col_bounds($col['Type']);
        if ($bounds !== null) {
            if (!is_numeric($raw) || (float)$raw < (float)$bounds['min'] || (float)$raw > (float)$bounds['max']) {
                throw new RuntimeException("Значення поля «{$f}» виходить за межі [{$bounds['min']} .. {$bounds['max']}].");
            }
        }

        if (preg_match('/^varchar\((\d+)\)/i', $col['Type'], $m) && mb_strlen((string)$raw) > (int)$m[1]) {
            throw new RuntimeException("Поле «{$f}» перевищує максимум у {$m[1]} символів.");
        }
        if ($f === 'phone' && $raw !== null) {
            $normalized = preg_replace('/[\s\-\(\)]+/', '', (string)$raw);
            if (!preg_match('/^(\+380\d{9}|\+(?!380)[1-9]\d{9,14})$/', $normalized)) {
                throw new RuntimeException('Некоректний номер телефону. Використовуйте формат +380XXXXXXXXX (9 цифр після +380).');
            }
            $raw = $normalized;
        }

        $values[$f] = $raw;
    }

    if (!empty($values['start_date']) && !empty($values['end_date']) && $values['end_date'] < $values['start_date']) {
        throw new RuntimeException('Дата завершення експедиції не може бути раніше дати початку.');
    }

    $dbc->beginTransaction();
    try {
        if ($rawId === null || $rawId === '') {
            $cols         = implode(', ', array_map('ident', array_keys($values)));
            $placeholders = implode(', ', array_map(fn($k) => ":$k", array_keys($values)));
            $sql          = 'INSERT INTO ' . ident($table) . " ($cols) VALUES ($placeholders)";
            $dbc->prepare($sql)->execute($values);
            $parentId = (string)$dbc->lastInsertId();
        } else {
            [$whereSql, $pkParams] = pk_where($pk, $rawId);
            $sets = implode(', ', array_map(fn($k) => ident($k) . " = :$k", array_keys($values)));
            $sql  = 'UPDATE ' . ident($table) . " SET $sets WHERE $whereSql";
            $dbc->prepare($sql)->execute(array_merge($values, $pkParams));
            $parentId = $rawId;
        }

        foreach ($columns as $field => $meta) {
            if (!($meta instanceof Pivot)) continue;

            $pivotScheme = array_column(get_scheme($meta->pivotTable), null, 'Field');
            $extraCol    = $pivotScheme[$meta->extraField] ?? null;

            $del = $dbc->prepare('DELETE FROM ' . ident($meta->pivotTable) . ' WHERE ' . ident($meta->localKey) . ' = :pid');
            $del->execute(['pid' => $parentId]);

            $submitted = is_array($data[$field] ?? null) ? $data[$field] : [];
            $ins = $dbc->prepare(
                'INSERT INTO ' . ident($meta->pivotTable) .
                ' (' . ident($meta->localKey) . ', ' . ident($meta->foreignKey) . ', ' . ident($meta->extraField) . ')' .
                ' VALUES (:pid, :fid, :extra)'
            );

            foreach ($submitted as $targetId => $row) {
                if (empty($row['checked'])) continue;

                $extraVal = trim((string)($row['extra'] ?? ''));
                if ($extraVal === '') {
                    throw new RuntimeException("Вкажіть «{$meta->extraLabel}» для всіх обраних позицій.");
                }

                if ($extraCol) {
                    if (str_starts_with($extraCol['Type'], 'enum(') && !in_array($extraVal, parse_enum($extraCol['Type']), true)) {
                        throw new RuntimeException("Недопустиме значення «{$meta->extraLabel}».");
                    }
                    $b = col_bounds($extraCol['Type']);
                    if ($b !== null && (!is_numeric($extraVal) || (float)$extraVal < max(1, (float)$b['min']) || (float)$extraVal > (float)$b['max'])) {
                        throw new RuntimeException("Кількість у «{$meta->label}» має бути від 1 до {$b['max']}.");
                    }
                }

                $ins->execute([
                    'pid'   => (int)$parentId,
                    'fid'   => (int)$targetId,
                    'extra' => $extraVal,
                ]);
            }
        }

        $dbc->commit();
    } catch (Throwable $e) {
        if ($dbc->inTransaction()) $dbc->rollBack();
        if ($e instanceof PDOException && $e->getCode() === '23000') {
            throw new RuntimeException('Порушено цілісність даних або дублікат запису.');
        }
        throw $e;
    }
}

function delete_row(string $table, string|array $pk, string $rawId): void {
    $dbc = DB::get_connection();
    [$whereSql, $params] = pk_where($pk, $rawId);
    $stmt = $dbc->prepare('DELETE FROM ' . ident($table) . " WHERE $whereSql");
    $stmt->execute($params);
}
