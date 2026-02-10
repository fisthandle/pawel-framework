<?php $view->layout('layout.php', ['title' => 'Logowanie']); ?>

<h2>Logowanie</h2>
<p>Demo: login <code>admin</code>, hasło <code>admin123</code></p>

<form method="post" action="/login">
    <?= $csrf_input ?>
    <label for="username">Login</label>
    <input type="text" id="username" name="username" required>

    <label for="password">Hasło</label>
    <input type="password" id="password" name="password" required>

    <button type="submit">Zaloguj</button>
</form>
