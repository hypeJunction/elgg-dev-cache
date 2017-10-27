# Dev Cache Booster

![Elgg 2.3](https://img.shields.io/badge/Elgg-2.3-orange.svg?style=flat-square)

Really hacky cache booster for Elgg developers. It does the job though :)

Instead of timestamping an entire cache and deleting all simplecache resources for every tiny change, this plugin fingerprints each individual asset, rewrites cache URLs and serves assets from disk. So, when you are working with JS/CSS you don't have to worry about
flushing caches, waiting for them to regenerate on the initial request. This really helps boost your productivity, so enjoy and say thank you later.

Once enabled, just add ``$CONFIG->environment = 'development';`` to your ``settings.php``.

