<?php
echo '<thead>';
echo '<tr>';
foreach ($scheme['Values'][0] as $colName => $val) {
    include 'sheets/column_head.php';
}
echo '</tr>';
echo '</thead>';
