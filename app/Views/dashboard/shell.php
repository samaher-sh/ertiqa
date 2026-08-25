<?php $this->extend('layouts/app') ?>

<?php $this->section('styles') ?>
<link rel="stylesheet" href="<?= av('assets/css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/wizard.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/riskmatrix.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/meetingsummary.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/observations.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/taskdetail.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/senttasks.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/finalreports.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/meetingschedule.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/documentrequests.css') ?>">
<link rel="stylesheet" href="<?= av('assets/css/missionreview.css') ?>">
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script src="<?= av('assets/js/utils.js') ?>"></script>
<script src="<?= av('assets/js/dashboard-data.js') ?>"></script>
<script src="<?= av('assets/js/wizard.js') ?>"></script>
<script src="<?= av('assets/js/riskmatrix.js') ?>"></script>
<script src="<?= av('assets/js/meetingsummary.js') ?>"></script>
<script src="<?= av('assets/js/observations.js') ?>"></script>
<script src="<?= av('assets/js/senttasks.js') ?>"></script>
<script src="<?= av('assets/js/finalreports.js') ?>"></script>
<script src="<?= av('assets/js/meetingschedule.js') ?>"></script>
<script src="<?= av('assets/js/documentrequests.js') ?>"></script>
<script src="<?= av('assets/js/missionreview.js') ?>"></script>
<script src="<?= av('assets/js/dashboard.js') ?>"></script>
<?php $this->endSection() ?>
