<?php
require_once 'helpers.php';
require_once 'sql.php';

$router->add('GET' ,'/', function () {
    $pageTitle = "Home";

    include 'views/home.php';
});

$router->add('GET' ,'/regions', function () {
    $pageTitle = "Regions";

    $result = select('regions', 'id');

    include 'views/crud.php';
});

$router->add('GET' ,'/researchers', function () {
    $pageTitle = "Researchers";

    $result = select('researchers', 'id');

    include 'views/crud.php';
});


$router->add('GET' ,'/equipment', function () {
    $pageTitle = "Equipment";

    $result = select('equipment', 'id');

    include 'views/crud.php';
});

$router->add('GET' ,'/expeditions', function () {
    $pageTitle = "Expeditions";

    $result = select('expeditions', 'id');

    include 'views/crud.php';
});

$router->add('GET' ,'/artifacts', function () {
    $pageTitle = "Artifacts";

    $result = select('artifacts', 'id');

    include 'views/crud.php';
});
