<?php
$isEditingNow = DB::can_write() && (isset($_GET['edit']) || isset($_GET['create']));
$stats        = DB::online_stats(180, $isEditingNow);
?>
<footer class="site-footer">
    <span>ІС «Етнографічні експедиції» · Варіант 11</span>
    <div class="footer-stats">
        <span>
            <span class="footer-dot" style="background: var(--success);"></span>
            Онлайн: <strong style="color: #fff;"><?= $stats['online'] ?></strong>
        </span>
        <span>
            <span class="footer-dot" style="background: #60a5fa;"></span>
            Адмінів (RW): <strong style="color: #fff;"><?= $stats['rw'] ?></strong>
        </span>
        <span>
            <span class="footer-dot" style="background: var(--warning);"></span>
            Зараз редагують: <strong style="color: #fff;"><?= $stats['editing'] ?></strong>
        </span>
    </div>
</footer>
