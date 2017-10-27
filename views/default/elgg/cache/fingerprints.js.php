<?php

$file = elgg_get_plugins_path() . 'elgg-dev-cache/cache.json';
if (file_exists($file)) {
	$json = file_get_contents($file) ? : new stdClass();
} else {
	$json = new \stdClass();
}
?>
window.cacheFingerprints = <?= $json ?>;

if (requirejs) {
    requirejs.config({
        urlArgs: function (id, url) {
            var args = window.cacheFingerprints[id + '.js'] || (new Date()).getTime();
            return (url.indexOf('?') === -1 ? '?' : '&') + args;
        }
    });
}