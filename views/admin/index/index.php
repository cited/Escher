<?php
echo head(array(
    'title' => 'Escher',
    'bodyclass' => 'escher browse'
));
?>
<style>
.escher-success {
    background: #3c763d;
    padding: 10px;
    color: #fff;
    border: 1px solid transparent;
    border-radius: 4px;
}

.escher-error {
    background: #a94442;
    padding: 10px;
    color: #fff;
    border: 1px solid transparent;
    border-radius: 4px;
}
</style>

<?php echo flash(); ?>

<div class="mapfig-img">
    <img src="<?php echo img('escher-logo.gif'); ?>" />
</div>

<?php if ($status == 'success'): ?>
<p class="escher-success"><?php echo $this->message; ?></p>
<?php elseif ($status == 'error'): ?>
<p class="escher-error"><?php echo $this->message; ?></p>
<?php endif; ?>

<p><?php echo __('Select Addon and Click Upload'); ?></p>

<form action="<?php echo url("escher"); ?>" method="post">
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('plugin', __('Plugin Name')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php
                $plugins = label_table_options($plugins);
                echo $this->formSelect('plugin', '', array(), $plugins);
            ?>
        </div>
    </div>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('theme', __('Theme Name')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php
                $themes = label_table_options($themes);
                echo $this->formSelect('theme', '', array(), $themes);
            ?>
        </div>
    </div>

    <?php echo $csrf; ?>

    <div class="field">
        <div class='inputs five columns omega'>
            <input type="submit" value="<?php echo __('Upload'); ?>" />
        </div>
    </div>
</form>

<?php echo foot();
