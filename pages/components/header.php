<?php
global $plugin;
if (isset($plugin)) {
    $jsPath = $plugin->getJsPath();
} else {
    $jsPath = '/api/page/plugin/TrendVisionOne/main.js';
}
?>
<!-- Required JavaScript files -->
<script src="<?php echo $jsPath; ?>"></script>
