<?php foreach ($flash as $msg): ?>
    <div class="flash flash-<?= h($msg['type']) ?>"><?= h($msg['text']) ?></div>
<?php endforeach; ?>
