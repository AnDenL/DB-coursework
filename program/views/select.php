<?php
echo '<tbody>';
foreach ($scheme['Values'] as $column) {
    echo '<tr>';
    foreach ($column as $field) {
        if (str_contains((string)($column['Field'] ?? '—'), '_path')) include 'sheets/photo_path.php';
        else include 'sheets/varchar.php';
    }
    echo '</tr>';
}
echo '</tbody>';
