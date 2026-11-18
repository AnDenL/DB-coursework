<?php
declare(strict_types=1);

function select(string $table, string $pk) {
    $where  = '';
    $currentSort = $pk;
    $currentDir  = strtoupper(get_str('dir')) === 'ASC' ? 'ASC' : 'DESC';

    $orderBy = $currentSort !== '' ? 'ORDER BY ' . ident($currentSort) . " $currentDir" : '';

    $dbc = DB::get_connection();

    $stmt = $dbc->query("DESCRIBE `$table`");
    $scheme = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $query = $dbc->prepare("SELECT * FROM $table $where $orderBy");
    $query->execute();
    $items = $query->fetchAll(PDO::FETCH_ASSOC);
    $query->closeCursor();

    $result = [];

    foreach ($scheme as $field) {
        $result[$field] = array_column($items, $field);
    }

    return $result;
}
