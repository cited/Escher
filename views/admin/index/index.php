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
    <fieldset id="fieldset-omeka-org">
        <legend><?php echo __('From Omeka.org'); ?></legend>
        <div class="field">
            <div class="two columns alpha">
                <?php echo $this->formLabel('plugin', __('Plugin Name')); ?>
            </div>
            <div class="inputs five columns omega">
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
            <div class="inputs five columns omega">
                <?php
                    $themes = label_table_options($themes);
                    echo $this->formSelect('theme', '', array(), $themes);
                ?>
            </div>
        </div>
    </fieldset>
    <fieldset id="fieldset-web">
        <legend><?php echo __('From the web'); ?></legend>
        <div class="field">
            <div class="two columns alpha">
                <?php echo $this->formLabel('webplugin', __('Plugin Name')); ?>
            </div>
            <div class="nputs five columns omega">
                <?php
                    $webplugins = label_table_options($webplugins);
                    echo $this->formSelect('webplugin', '', array(), $webplugins);
                ?>
            </div>
        </div>
    </fieldset>

    <?php echo $csrf; ?>
    <fieldset id="fieldset-submit">
        <div class="field">
            <div class="inputs seven columns omega">
                <input type="submit" value="<?php echo __('Upload'); ?>" />
            </div>
        </div>
    </fieldset>
</form>

<?php echo foot();
