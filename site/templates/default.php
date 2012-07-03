<?php snippet('header') ?>
<?php snippet('menu') ?>
<?php snippet('submenu') ?>

<section class="content">
  <h1 class="hidden">Main Content</h1>

  <article>
    <h1><?php echo html($page->title()) ?></h1>
    <?php echo kirbytext($page->text()) ?>
  </article>

</section>

<?php snippet('footer') ?>