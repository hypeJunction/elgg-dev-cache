<?php

namespace Elgg\Developers;

use Elgg\Config;
use Elgg\Database\Datalist;
use Elgg\ViewsService;

class SimpleCache extends \Elgg\Cache\SimpleCache {

	static protected $log;

	/** @var Config */
	protected $config;

	/** @var Datalist */
	protected $datalist;

	/** @var ViewsService */
	protected $views;

	/**
	 * Constructor
	 *
	 * @param Config       $config   Elgg's global configuration
	 * @param Datalist     $datalist Elgg's database config storage
	 * @param ViewsService $views    Elgg's views registry
	 */
	public function __construct(Config $config, Datalist $datalist, ViewsService $views) {
		$this->config = $config;
		$this->datalist = $datalist;
		$this->views = $views;
		$this->loadLog();

		parent::__construct($this->config, $this->datalist, $this->views);
	}


	protected function getRootDir() {
		return dirname(dirname(dirname(dirname(__FILE__)))) . '/';
	}

	protected function loadLog() {
		if (isset(self::$log)) {
			return self::$log;
		}

		$dir = $this->getRootDir();
		if (is_file($dir . 'cache.json')) {
			$json = file_get_contents($dir . 'cache.json');
			self::$log = json_decode($json, true);
		} else {
			self::$log = [];
		}

		return self::$log;
	}

	protected function saveLog() {

		$dir = $this->getRootDir();

		if (!is_dir($dir)) {
			mkdir($dir, 0777, true);
		}

		file_put_contents($dir . 'cache.json', json_encode(self::$log));
	}

	public function getUrl($view, $subview = '') {

		// handle `getUrl('js', 'js/blog/save_draft')`
		if (($view === 'js' || $view === 'css') && 0 === strpos($subview, $view . '/')) {
			$view = $subview;
			$subview = '';
		}

		// handle `getUrl('js', 'blog/save_draft')`
		if (!empty($subview)) {
			$view = "$view/$subview";
		}

		if (!$this->views->isCacheableView($view)) {
			return;
		}

		$view = $this->views->canonicalizeViewName($view);

		// should be normalized to canonical form by now: `getUrl('blog/save_draft.js')`
		$this->registerView($view);

		$hash = $this->cache($view);

		return $this->getRoot() . $view . '?' . $hash;
	}

	public function cache($view) {
		$this->loadLog();

		if (preg_match("#^languages/(.*?)\\.js$#", $view, $matches)) {
			$vars = ['language' => $matches[1]];
			$bytes = elgg_view('languages.js', $vars);
		} else {
			$bytes = elgg_view($view);
		}

		$hash = sha1($bytes);

		$cached_file = $this->getRootDir() . 'cache/public/' . $view;

		if ($hash !== self::$log[$view] || !file_exists($cached_file)) {
			$dir = pathinfo($cached_file, PATHINFO_DIRNAME);
			if (!is_dir($dir)) {
				mkdir($dir, 0777, true);
			}

			$hook_type = pathinfo($view, PATHINFO_EXTENSION);
			$hook_params = array(
				'view' => $view,
				'viewtype' => 'default',
				'view_content' => $bytes,
			);

			$bytes = elgg_trigger_plugin_hook('simplecache:generate', $hook_type, $hook_params, $bytes);

			file_put_contents($cached_file, $bytes, FILE_APPEND | LOCK_EX);
			self::$log[$view] = $hash;
			$this->saveLog();
		}

		return $hash;
	}

	public function getRoot() {
		return elgg_normalize_url('mod/elgg-dev-cache/cache/public/');
	}

}