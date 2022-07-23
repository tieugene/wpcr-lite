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

1. Custom repositoy can be static web-site.
2. It should contain subfolders:
   - `plugins`
   - `themes`
1. Both of folders should contain files like `myplugin.json` with at least 2 keys: `"version"`, `"url"` (of new package).

### Custom plugin

Custom plugin must have at least `Version: ...` in its heading comments.
Add code below to your plugin:

```php
add_filter( 'plugins_loaded', 'register_updater' );
function register_updater(): void {
        if ( class_exists( 'WPCRL_Updater' ) ) {
                new WPCRL_Updater( __FILE__ );
        }
}
// or lambda:
// add_filter('plugins_loaded', function() {if (class_exists( 'WPCRL_Updater' )) new WPCRL_Updater( __FILE__ );});
```

## Thanks

Thanks [Matthew Ray](https://github.com/rayman813/smashing-updater-plugin) and [Abid Omar](https://github.com/omarabid/Self-Hosted-WordPress-Plugin-repository) for samples.
