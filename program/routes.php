<?php
require_once 'helpers.php';

$router->add('GET' ,'/', function () {
    $pageTitle = "Home";

    include 'views/home.php';
});

$router->add('GET' ,'/regions', function () {
    $pageTitle = "Regions";

    $table  = 'regions';
    $where  = '';
    $pk          = 'id';
    $currentSort = $pk;
    $currentDir  = strtoupper(get_str('dir')) === 'ASC' ? 'ASC' : 'DESC';

    $orderBy = $currentSort !== '' ? 'ORDER BY ' . ident($currentSort) . " $currentDir" : '';

    $dbc = DB::get_connection();

    $stmt = $dbc->query("DESCRIBE `$table`");
    $scheme = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $query = $dbc->prepare("SELECT * FROM $table $where $orderBy");
    $query->execute();
    $items = $query->fetchAll(PDO::FETCH_OBJ);
    $query->closeCursor();

    include 'views/crud.php';
});

$router->add('GET' ,'/researchers', function () {
    $pageTitle = "Researchers";

    $table  = 'researchers';
    $where  = '';
    $pk          = 'id';
    $currentSort = $pk;
    $currentDir  = strtoupper(get_str('dir')) === 'ASC' ? 'ASC' : 'DESC';

    $orderBy = $currentSort !== '' ? 'ORDER BY ' . ident($currentSort) . " $currentDir" : '';

    $dbc = DB::get_connection();

    $stmt = $dbc->query("DESCRIBE `$table`");
    $columns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), null, 'Field');

    $query = $dbc->prepare("SELECT * FROM $table $where $orderBy");
    $query->execute();
    $items = $query->fetchAll(PDO::FETCH_ASSOC);
    $query->closeCursor();

    $scheme['Values'] = $items;

    include 'views/crud.php';
});
