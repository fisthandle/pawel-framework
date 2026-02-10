<div><?= $csrf_token !== '' ? 'csrf_token' : 'missing_csrf' ?></div>
<?php foreach (($flash ?? []) as $msg): ?>
<div><?= 'flash-' . h($msg['type']) . '-' . h($msg['text']) ?></div>
<?php endforeach; ?>
