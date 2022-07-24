# wpcr-lite

WordPress Custom Repo Lite

## Usage

1. Create custom repo (e.g. `https://example.com/myrepo/`, see below)
1. Add `WPCRL_URL` constant to project:
   ```php
   define ('WPCRL_URL', 'https://example.com/myrepo/');
   ```
1. Add correspondent code to your custom plugins (see below).

### Repository

1. Custom repository can be static web-site.
2. It should contain folders:
   - `plugins`
   - `themes`
1. Both of folders should contain files like `myplugin.json` with at least 2 keys: `"version"`, `"url"` (of new package).

### Custom plugin

Custom plugin must have at least `Version: ...` in its heading comments.
Add code below to your plugin's main file or theme's `fuctions.php`:

```php
add_filter('plugins_loaded', function() {
    // theme: 'after_setup_theme' or 'init'
	if ( class_exists( 'WPCRL_Core' ) )
		WPCRL_Core::get_instance()->register_plugin( __FILE__ );
    });
```

## RTFM
- [Matthew Ray](https://www.smashingmagazine.com/2015/08/deploy-wordpress-plugins-with-github-using-transients/): [plugin lib](https://github.com/rayman813/smashing-updater-plugin)
- Abid Omar: [plugin lib](https://github.com/omarabid/Self-Hosted-WordPress-Plugin-repository)
