<?php $view->layout('layout.php', ['title' => 'Admin Dashboard']); ?>

<h2>Admin Dashboard</h2>
<p>Zalogowano jako: <strong><?= h($current_user['name'] ?? '') ?></strong>
   (rola: <?= h($current_user['role'] ?? '') ?>)</p>

<h4>Demo info</h4>
<ul>
    <li>Session handler: P1\Session (SQLite)</li>
    <li>CSRF: token w sesji, walidacja w Controller::validateCsrf()</li>
    <li>Flash: session-based, auto-cleared po wyświetleniu</li>
    <li>Auth guard: AdminController::beforeRoute() → redirect jeśli nie admin</li>
</ul>

<form method="post" action="/logout"><?= $csrf_input ?><button type="submit">Wyloguj</button></form>
