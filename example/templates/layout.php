<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title><?= h($title ?? 'P1 Example') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: system-ui, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; color: #333; }
        nav { border-bottom: 2px solid #eee; padding: 10px 0; margin-bottom: 20px; }
        nav a { margin-right: 15px; text-decoration: none; color: #0066cc; }
        nav a:hover { text-decoration: underline; }
        nav .right { float: right; }
        .flash { padding: 10px 15px; margin-bottom: 15px; border-radius: 4px; }
        .flash-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .flash-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .flash-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .flash-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        form label { display: block; margin: 10px 0 5px; font-weight: 600; }
        form input[type=text], form input[type=password] { padding: 8px; width: 250px; border: 1px solid #ccc; border-radius: 4px; }
        form button { padding: 8px 20px; background: #0066cc; color: white; border: none; border-radius: 4px; cursor: pointer; margin-top: 10px; }
        form button:hover { background: #0052a3; }
        footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; color: #999; font-size: 0.9em; }
        .admin-link { color: #cc0000 !important; }
        .logout-inline { display: inline; }
        .logout-inline button { background: none; border: none; color: #0066cc; cursor: pointer; padding: 0; font: inherit; }
        .logout-inline button:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <nav>
        <a href="/">Home</a>
        <a href="/kontakt">Kontakt</a>
        <?php if ($is_admin ?? false): ?>
            <a href="/admin" class="admin-link">Admin</a>
        <?php endif; ?>
        <span class="right">
            <?php if ($is_logged_in ?? false): ?>
                <?= h($current_user['name'] ?? 'User') ?>
                | <form method="post" action="/logout" class="logout-inline"><?= $csrf_input ?><button type="submit">Wyloguj</button></form>
            <?php else: ?>
                <a href="/login">Zaloguj</a>
            <?php endif; ?>
        </span>
    </nav>

    <?= $view->partial('_flash.php', ['flash' => $flash ?? []]) ?>

    <?= $content ?>

    <footer>
        <p>P1 Framework Example &copy; <?= date('Y') ?></p>
    </footer>
</body>
</html>
