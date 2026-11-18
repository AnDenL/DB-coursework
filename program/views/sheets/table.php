<?php
$columns = array_keys($result);
$rowCount = !empty($columns) ? count($result[$columns[0]]) : 0;
?>

<table class="table">
    <thead>
        <tr>
            <?php foreach ($columns as $colName) {
                include 'column_head.php';
            }?>
        </tr>
    </thead>
    <tbody>
        <?php for ($i = 0; $i < $rowCount; $i++): ?>
            <tr>
                <?php foreach ($columns as $colName) {
                    include 'block.php';
                }?>
            </tr>
        <?php endfor; ?>
    </tbody>
</table>
