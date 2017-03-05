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
    <?php echo _('Addons with an asterisk are already downloaded.'); ?>
</p>

<?php echo $form; ?>

<?php echo foot();
