<?php $this->extend('layouts/app') ?>

<?php $this->section('styles') ?>
<link rel="stylesheet" href="<?= av('assets/css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/wizard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/observations.css') ?>">
<?php $this->endSection() ?>

<?php $this->section('content') ?>
<div class="flex flex-col gap-4">
  <?= view('dashboard/observations/_linked_task_selector', [
      'missions'          => $missions,
      'selectedMissionId' => $selectedMissionId,
      'formAction'        => base_url('dashboard/observations/create'),
  ]) ?>

  <div class="obs-disabled-wrap<?= $selectedMissionId ? '' : ' locked' ?>">
    <?= view('dashboard/observations/_form', [
        'observation'       => null,
        'mission'           => $mission,
        'selectedMissionId' => $selectedMissionId,
        'backUrl'           => base_url('dashboard/observations') . ($selectedMissionId ? '?mission_id=' . $selectedMissionId : ''),
    ]) ?>
  </div>
</div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script src="<?= av('assets/js/utils.js') ?>"></script>
<script src="<?= av('assets/js/mvc-layout.js') ?>"></script>
<script src="<?= av('assets/js/observations-page.js') ?>"></script>
<?php $this->endSection() ?>
