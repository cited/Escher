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
<div class="mapfig-img">
    <img src="<?php echo img('escher-logo.gif'); ?>" />
</div>

<?php if (!empty($_GET['success'])): ?>
<p class="escher-success"><?php echo $_GET['msg']; ?></p>
<?php elseif (!empty($_GET['error'])): ?>
<p class="escher-error"><?php echo $_GET['msg']; ?></p>
<?php endif; ?>

<p><?php echo __('Select Plugin and Click Upload'); ?></p>

<form action="<?php echo url("escher/index/upload"); ?>" method="post">
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('plugin-name', __('Plugin Name')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php
                $plugins = label_table_options($plugins);
                echo $this->formSelect('plugin-name', '', array(), $plugins);
            ?>
        </div>
    </div>

    <input type="submit" value="<?php echo __('Upload'); ?>" />
</form>

<?php echo foot();
