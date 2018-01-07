<?php
echo head(array(
    'title' => 'Escher',
    'bodyclass' => 'escher browse'
));
?>
<?php echo flash(); ?>

<div class="mapfig-img">
    <img src="<?php echo img('escher-logo.gif'); ?>" />
</div>

<h4><?php echo __('Select Addon and Click Upload'); ?></h4>
<p class="explanation">
    <?php echo __('For more information on addons, see the pages %splugins%s and %sthemes%s or on %sOmeka.org%s.',
        '<a href="https://daniel-km.github.io/UpgradeToOmekaS/omeka_plugins.html">', '</a>',
        '<a href="https://daniel-km.github.io/UpgradeToOmekaS/omeka_themes.html">', '</a>',
        '<a href="https://omeka.org/classic">', '</a>'); ?>
</p>
<p class="explanation">
    <?php echo _('Addons with an asterisk are already downloaded.'); ?>
</p>

<?php echo $form; ?>

<?php echo foot();
