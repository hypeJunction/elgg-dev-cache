<?php

/**
 * elgg-dev-cache
 *
 * @author    Ismayil Khayredinov <info@hypejunction.com>
 * @copyright Copyright (c) 2017, Ismayil Khayredinov
 */

require_once __DIR__ . '/autoloader.php';

if (elgg_get_config('environment') !== 'development') {
	return;
}

$config = _elgg_services()->config;
$datalist = _elgg_services()->datalist;
$views = _elgg_services()->views;

if (!_elgg_services()->simpleCache instanceof \Elgg\Developers\SimpleCache) {
	$simplecache = new \Elgg\Developers\SimpleCache($config, $datalist, $views);
	_elgg_services()->setValue('simpleCache', $simplecache);
	_elgg_services()->amdConfig->setBaseUrl($simplecache->getRoot());
}

elgg_register_event_handler('init', 'system', function () use ($views, $datalist, $config, $simplecache) {

	$view_list = $views->listViews();
	$view_list[] = "languages/en.js";
	if (get_current_language() !== 'en') {
		$lang = get_current_language();
		$view_list[] = "languages/$lang.js";
	}

	// @todo: this is handy because you don't need to flush cache all the time
	// but it adds a couple of seconds to load time
	// maybe better to move to flush cache hook, and add a grunt job to watch file changes
	foreach ($view_list as $view) {
		// Generate a full static view cache
		if ($views->isCacheableView($view)) {
			$simplecache->cache($view);
		}
	}

	// Update hashes for views registered too early in the boot process
	$site_url = elgg_get_site_url();
	global $GLOBALS;
	$map = $GLOBALS['_ELGG']->externals_map;

	foreach ($map as $type => $assets) {
		foreach ($assets as $name => $asset) {
			$url = $asset->url;
			if (strpos($url, $site_url) === 0) {
				$url = substr($url, strlen($site_url));
				$regex = "^cache\/\d+\/default\/(.*)$";
				preg_match("/$regex/i", $url, $matches);
				$view = $matches[1];
				if ($view) {
					$url = elgg_get_simplecache_url($view);
					if ($type === 'css') {
						elgg_register_css($name, $url);
						if ($asset->loaded) {
							elgg_load_css($name);
						}
					} else {
						elgg_register_js($name, elgg_get_simplecache_url($view), $asset->location);
						if ($asset->loaded) {
							elgg_load_js($name);
						}
					}
				}
			}
		}
	}

	elgg_register_simplecache_view('elgg/cache/fingerprints.js');
	elgg_register_js('elgg.cache.fingerprints', elgg_get_simplecache_url('elgg/cache/fingerprints.js'), 'head');
	elgg_load_js('elgg.cache.fingerprints');

}, 9999);