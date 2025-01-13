<?php
global $plugin;
if (isset($plugin)) {
    echo '<!-- Required JavaScript files -->';
    echo '<script src="' . $plugin->getJsPath() . '"></script>';
} else {
    echo '<!-- Required JavaScript files -->';
    echo '<script src="/plugin/TrendVisionOne/main.js"></script>';
}
?>
