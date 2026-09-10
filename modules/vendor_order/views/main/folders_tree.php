<?php foreach ($folders as $folder) : ?>
    <div id="<?= 'pfolder_' . $folder->getFieldValue('ID') ?>" class="folder" data-bs-toggle="collapse" data-bs-target="#<?= 'pfolder_' . $folder->getFieldValue('ID') ?>__subFolders" aria-label="<?= $folder->getFieldValue('ID') ?>">
        <i style="color: deepskyblue;" class="bi bi-folder-fill"></i>
        <?= $folder->getFieldValue('NAME') ?>
    </div>
    <div class="collapse" id="<?= 'pfolder_' . $folder->getFieldValue('ID') ?>__subFolders">
        <div class="card card-body mb-0">
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        </div>
    </div>
<?php endforeach; ?>