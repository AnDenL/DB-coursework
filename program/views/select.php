<?php
foreach ($result as $column => $values) {
    echo '<thead>';

    echo '<thead>';
    echo '<tbody>';
    foreach ($column as $field) {
        if (str_contains((string)($column[0] ?? '—'), '_path')) include 'sheets/photo_path.php';
        else include 'sheets/varchar.php';
    }
    echo '</tbody>';    
}

echo '<tbody>';
foreach ($result['Fields'] as $column) {
    echo '<tr>';
    foreach ($column as $field) {
        if (str_contains((string)($column[0] ?? '—'), '_path')) include 'sheets/photo_path.php';
        else include 'sheets/varchar.php';
    }
    echo '</tr>';
}
echo '</tbody>';
