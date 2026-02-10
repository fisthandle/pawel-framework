<?php foreach ($items as $item): ?>
    <?= $view->partial('_item.php', ['item' => $item]) ?>
<?php endforeach; ?>
