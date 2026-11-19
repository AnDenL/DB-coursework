<?php
$currUri  = (string)($_SERVER['REQUEST_URI'] ?? '/expeditions');
$loggedIn = DB::is_logged_in();
$canWrite = DB::can_write();
?>
<div class="navbar">
    <nav>
        <div class="nav-links">
            <a href="/"            class="nav-link">Home</a>
            <a href="/expeditions" class="nav-link">Expeditions</a>
            <a href="/regions"     class="nav-link">Regions</a>
            <a href="/researchers" class="nav-link">Researchers</a>
            <a href="/equipment"   class="nav-link">Equipment</a>
            <a href="/artifacts"   class="nav-link">Artifacts</a>
            <a href="/reports"     class="nav-link">Reports</a>
        </div>
    </nav>

    <div class="nav-auth">
        <span class="db-badge <?= $canWrite ? 'rw' : 'ro' ?>">
            DB: <?= htmlspecialchars(DB::current_user()) ?> (<?= $canWrite ? 'RW' : 'SELECT' ?>)
        </span>

        <?php if ($loggedIn): ?>
            <form method="POST" action="/logout" style="margin:0;">
                <input type="hidden" name="_redirect" value="<?= htmlspecialchars($currUri) ?>">
                <button type="submit" class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.85rem;">Вийти</button>
            </form>
        <?php else: ?>
            <form method="POST" action="/login" class="nav-login-form">
                <input type="hidden" name="_redirect" value="<?= htmlspecialchars($currUri) ?>">
                <input type="text" name="db_user" placeholder="Логін БД" class="nav-input" required>
                <input type="password" name="db_pass" placeholder="Пароль" class="nav-input">
                <button type="submit" class="btn btn-primary" style="padding: 0.35rem 0.75rem; font-size: 0.85rem;">Увійти</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php if (!empty($_SESSION['flash_warning'])): ?>
    <div class="container" style="margin-bottom: 0;">
        <div class="card" style="border-color: var(--warning); color: var(--warning); font-weight: 500; margin-bottom: 0;">
            <?= htmlspecialchars((string)$_SESSION['flash_warning']) ?>
        </div>
    </div>
    <?php unset($_SESSION['flash_warning']); ?>
<?php endif; ?>
