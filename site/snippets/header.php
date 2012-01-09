<!DOCTYPE html>
<html lang="en">
<head>
  
  <title><?php echo html($site->title()) ?> - <?php echo html($page->title()) ?></title>
  <meta charset="utf-8" />
  <meta name="description" content="<?php echo html($site->description()) ?>" />
  <meta name="keywords" content="<?php echo html($site->keywords()) ?>" />
  <meta name="robots" content="index, follow" />

  <link rel="shortcut icon" href="<?php echo url('assets/images/favicon.png') ?>" type="image/png" />
  <link rel="icon" href="<?php echo url('assets/images/favicon.png') ?>" type="image/png" />
  <link rel="apple-touch-icon" href="<?php echo u('assets/images/apple-touch-icon.png') ?>" />

  <?php echo css('assets/styles/styles.css') ?>

</head>

<body>

  <header>
    <h1><a href="<?php echo url() ?>"><img src="<?php echo url('assets/images/logo.png') ?>" width="115" height="41" alt="<?php echo h($site->title()) ?>" /></a></h1>
  </header>