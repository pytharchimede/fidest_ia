<?php
/** @var array $documents */
?>
<section class="documents-list">
    <?php foreach ($documents ?? [] as $document): ?>
        <article>
            <strong><?= htmlspecialchars($document['original_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
            <span><?= htmlspecialchars($document['status'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
        </article>
    <?php endforeach; ?>
</section>
