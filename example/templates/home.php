<?php $view->layout('layout.php', ['title' => 'P1 Example']); ?>

<h1>P1 Framework</h1>
<p>Demo aplikacji na frameworku P1. Port fragmentu projektu ProwadzacyStrzelanie.eu.</p>

<h3>Co demonstruje ten example:</h3>
<ul>
    <li><a href="/kontakt">Kontakt</a> &mdash; statyczna strona z layoutem</li>
    <li><a href="/login">Login</a> &mdash; formularz z CSRF, flash messages, redirect</li>
    <li><a href="/admin">Admin</a> &mdash; strona z auth guardem (beforeRoute)</li>
</ul>
