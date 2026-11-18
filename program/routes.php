<?php
require_once 'helpers.php';
require_once 'sql.php';

$router->add('GET', '/', function () {
    $pageTitle = "Home";
    include 'views/home.php';
});

$router->add('GET', '/reports', function () {
    $pageTitle = "Аналітичні звіти";
    include 'views/reports.php';
});

$tables = [
    'regions' => [
        'title' => 'Regions',
        'pk'    => 'id',
        'columns' => [
            'name'             => 'Назва',
            'description'      => 'Опис',
            'photo_path'       => 'Фото',
            'temperature_zone' => 'Температура',
            'humidity_level'   => 'Вологість',
            'terrain_type'     => 'Рельєф',
            'population'       => 'Населення',
        ],
    ],
    'researchers' => [
        'title' => 'Researchers',
        'pk'    => 'id',
        'columns' => [
            'full_name'       => 'ПІБ',
            'photo_path'      => 'Фото',
            'academic_degree' => 'Науковий ступінь',
            'phone'           => 'Номер телефону',
        ],
    ],
    'equipment' => [
        'title' => 'Equipment',
        'pk'    => 'id',
        'columns' => [
            'name'           => 'Назва',
            'estimated_cost' => 'Вартість',
        ],
    ],
    'expeditions' => [
        'title' => 'Expeditions',
        'pk'    => 'id',
        'columns' => [
            'title'           => 'Назва',
            'region_id'       => new Foreign('Регіон', 'regions', 'name'),
            'budget'          => 'Бюджет',
            'equipment_cost'  => new Subquery('Вартість спорядження', "
                SELECT CONCAT(REPLACE(FORMAT(COALESCE(SUM(ee.count * eq.estimated_cost), 0), 2), ',', ' '), ' грн')
                FROM expedition_equipment ee
                JOIN equipment eq ON eq.id = ee.equipment_id
                WHERE ee.expedition_id = expeditions.id
            ", null, "
                SELECT COALESCE(SUM(ee.count * eq.estimated_cost), 0)
                FROM expedition_equipment ee
                JOIN equipment eq ON eq.id = ee.equipment_id
                WHERE ee.expedition_id = expeditions.id
            "),
            'start_date'      => 'Початок',
            'end_date'        => 'Кінець',
            'members'         => new Subquery('Учасники групи', "
                SELECT JSON_ARRAYAGG(JSON_OBJECT(
                    'id', r.id,
                    'label', CONCAT(r.full_name, ' (', em.researcher_role, ')')
                ))
                FROM expedition_members em
                JOIN researchers r ON r.id = em.researcher_id
                WHERE em.expedition_id = expeditions.id
            ", 'researchers'),
            'equipment'       => new Subquery('Спорядження', "
                SELECT JSON_ARRAYAGG(JSON_OBJECT(
                    'id', eq.id,
                    'label', CONCAT(eq.name, ' — ', ee.count, ' шт.')
                ))
                FROM expedition_equipment ee
                JOIN equipment eq ON eq.id = ee.equipment_id
                WHERE ee.expedition_id = expeditions.id
            ", 'equipment'),
            'members_pivot'   => new Pivot(
                'Учасники експедиції',
                'expedition_members', 'expedition_id', 'researcher_id',
                'researchers', 'full_name',
                'researcher_role', 'Роль'
            ),
            'equipment_pivot' => new Pivot(
                'Спорядження експедиції',
                'expedition_equipment', 'expedition_id', 'equipment_id',
                'equipment', 'name',
                'count', 'Кількість', 'estimated_cost'
            ),
        ],
    ],
    'expedition_members' => [
        'title' => 'Склад експедицій',
        'pk'    => ['expedition_id', 'researcher_id'],
        'columns' => [
            'expedition_id'   => new Foreign('Експедиція', 'expeditions', 'title'),
            'researcher_id'   => new Foreign('Дослідник', 'researchers', 'full_name'),
            'researcher_role' => 'Роль в експедиції',
        ],
    ],
    'expedition_equipment' => [
        'title' => 'Спорядження експедицій',
        'pk'    => ['expedition_id', 'equipment_id'],
        'columns' => [
            'expedition_id' => new Foreign('Експедиція', 'expeditions', 'title'),
            'equipment_id'  => new Foreign('Спорядження', 'equipment', 'name'),
            'count'         => 'Кількість',
            'total_cost'    => new Subquery('Вартість', "
                SELECT CONCAT(REPLACE(FORMAT(expedition_equipment.count * eq.estimated_cost, 2), ',', ' '), ' грн')
                FROM equipment eq WHERE eq.id = expedition_equipment.equipment_id
            "),
        ],
    ],
    'artifacts' => [
        'title' => 'Artifacts',
        'pk'    => 'id',
        'columns' => [
            'name'              => 'Назва',
            'expedition_id'     => new Foreign('Експедиція', 'expeditions', 'title'),
            'description'       => 'Опис',
            'photo_path'        => 'Фото',
            'item_condition'    => 'Стан',
            'estimated_century' => 'Приблизне століття',
            'estimated_value'   => 'Орієнтовна вартість',
        ],
    ],
];

foreach ($tables as $table => $cfg) {
    $pk            = $cfg['pk'];
    $schemaColumns = $cfg['columns'];
    $pageTitle     = $cfg['title'];

    $router->add('GET', "/$table", function () use ($table, $pk, $schemaColumns, $pageTitle, $tables) {
        $editId   = isset($_GET['edit']) && $_GET['edit'] !== '' ? (string)$_GET['edit'] : null;
        $record   = $editId !== null ? find_one($table, $pk, $editId) : null;
        $showForm = isset($_GET['create']) || $record !== null;

        $result = select($table, $pk, $schemaColumns);
        include 'views/crud.php';
    });

    $router->add('POST', "/$table/save", function () use ($table, $pk, $schemaColumns, $pageTitle) {
        $editId = isset($_GET['edit']) && $_GET['edit'] !== '' ? (string)$_GET['edit'] : null;
        try {
            save_row($table, $pk, $schemaColumns, $_POST, $editId);
            redirect("/$table");
        } catch (Throwable $e) {
            if ($e instanceof PDOException && ($e->errorInfo[1] ?? 0) === 1142) {
                redirect("/$table", 'У вас немає прав для зміни даних: поточний користувач має права лише на читання.');
            }
            $record    = $_POST;
            $showForm  = true;
            $formError = $e->getMessage();
            $result    = select($table, $pk, $schemaColumns);
            include 'views/crud.php';
        }
    });

    $router->add('POST', "/$table/delete", function () use ($table, $pk) {
        $rawRedir = $_POST['_redirect'] ?? null;
        $returnTo = ($rawRedir && str_starts_with($rawRedir, '/') && !str_starts_with($rawRedir, '//')) ? $rawRedir : "/$table";
        try {
            delete_row($table, $pk, (string)($_GET['id'] ?? ''));
            redirect($returnTo);
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? 0) === 1142) {
                redirect($returnTo, 'У вас немає прав для видалення даних: поточний користувач має права лише на читання.');
            }
            redirect($returnTo, 'Неможливо видалити запис, оскільки на нього посилаються інші таблиці.');
        }
    });
}

$router->add('POST', '/login', function () {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $cfg  = require __DIR__ . '/config.php';
    $user = trim((string)($_POST['db_user'] ?? ''));
    $pass = (string)($_POST['db_pass'] ?? '');
    $back = safe_return_url($_POST['_redirect'] ?? null, '/expeditions');

    if ($user === '') {
        redirect($back, 'Вкажіть ім’я користувача.');
    }

    if ($user === $cfg['guest_user']) {
        unset($_SESSION['db_user'], $_SESSION['db_pass']);
        redirect($back, 'Bruh ви вже перебуваєте в гостьовому режимі.');
    }

    try {
        DB::connect($user, $pass);
        session_regenerate_id(true);
        $_SESSION['db_user']       = $user;
        $_SESSION['db_pass']       = $pass;
        $_SESSION['last_activity'] = time();
        redirect($back);
    } catch (PDOException) {
        redirect($back, 'Невірний логін або пароль.');
    }
});

$router->add('POST', '/logout', function () {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $back = safe_return_url($_POST['_redirect'] ?? null, '/expeditions');
    $_SESSION = [];
    session_destroy();
    redirect($back);
});
