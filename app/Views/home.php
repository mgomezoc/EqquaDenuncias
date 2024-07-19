<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= $title ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php echo view('partials/_pageHeader', ['controlador' => $controlador, 'vista' => $vista]); ?>

<div class="card custom-card">
    <div class="card-body">
    </div>
</div>

<?= $this->endSection() ?>