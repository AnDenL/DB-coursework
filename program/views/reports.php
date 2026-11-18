<!DOCTYPE html>
<?php include 'head.php'; ?>
<body>
    <?php
    include 'menu.php';
    $dbc = DB::get_connection();

    $byExpedition = $dbc->query("
        SELECT e.title AS expedition, r.name AS region, e.budget,
               COUNT(DISTINCT a.id) AS artifacts_count,
               COALESCE(SUM(a.estimated_value), 0) AS total_artifacts_value
        FROM expeditions e
        LEFT JOIN regions r ON r.id = e.region_id
        LEFT JOIN artifacts a ON a.expedition_id = e.id
        GROUP BY e.id, e.title, r.name, e.budget
        ORDER BY total_artifacts_value DESC
    ")->fetchAll();

    $byResearcher = $dbc->query("
        SELECT res.full_name, res.academic_degree,
               COUNT(em.expedition_id) AS expeditions_count
        FROM researchers res
        LEFT JOIN expedition_members em ON em.researcher_id = res.id
        GROUP BY res.id, res.full_name, res.academic_degree
        ORDER BY expeditions_count DESC
    ")->fetchAll();

    $byEquipment = $dbc->query("
        SELECT eq.name, eq.estimated_cost,
               COALESCE(SUM(ee.count), 0) AS total_used,
               COALESCE(SUM(ee.count), 0) * eq.estimated_cost AS total_cost
        FROM equipment eq
        LEFT JOIN expedition_equipment ee ON ee.equipment_id = eq.id
        GROUP BY eq.id, eq.name, eq.estimated_cost
        ORDER BY total_cost DESC
    ")->fetchAll();
    ?>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Зведена аналітика етнографічних експедицій</h1>
        </div>

        <div class="card">
            <div class="table-wrap">
            <h2 class="card-title">1. Результативність експедицій та оціночна вартість знахідок</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Експедиція</th>
                        <th>Регіон</th>
                        <th>Бюджет (грн)</th>
                        <th>Знайдено артефактів</th>
                        <th>Оціночна вартість знахідок (грн)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($byExpedition as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['expedition']) ?></td>
                            <td><?= htmlspecialchars($row['region'] ?? '—') ?></td>
                            <td><?= number_format((float)$row['budget'], 0, '.', ' ') ?></td>
                            <td><?= (int)$row['artifacts_count'] ?></td>
                            <td><?= number_format((float)$row['total_artifacts_value'], 0, '.', ' ') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>

        <div class="card">
            <div class="table-wrap">
            <h2 class="card-title">2. Залученість наукових співробітників</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>ПІБ дослідника</th>
                        <th>Науковий ступінь</th>
                        <th>Кількість експедицій</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($byResearcher as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                            <td><?= htmlspecialchars($row['academic_degree'] ?? '—') ?></td>
                            <td><?= (int)$row['expeditions_count'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>

        <div class="card">
            <div class="table-wrap">
            <h2 class="card-title">3. Використання та вартість польового спорядження</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Найменування</th>
                        <th>Ціна за од. (грн)</th>
                        <th>Видано всього (шт.)</th>
                        <th>Сумарна вартість (грн)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($byEquipment as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= number_format((float)$row['estimated_cost'], 0, '.', ' ') ?></td>
                            <td><?= (int)$row['total_used'] ?></td>
                            <td><?= number_format((float)$row['total_cost'], 0, '.', ' ') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>
