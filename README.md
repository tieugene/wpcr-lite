# wpcr-lite

WordPress Custom Repo Lite - WP plugin to update custom plugins/themes with custom repository.

## Usage

1. Create custom repo (e.g. `https://example.com/myrepo/`, see below)
1. Add `WPCRL_URL` constant to project:
   ```php
   define ('WPCRL_URL', 'https://example.com/myrepo/');
   ```
1. Add correspondent code to your custom components (plugin/theme) (see below).

### Repository

1. Custom repository can be static web-site.
2. It should contain folders:
   - `plugins/`
   - `themes/`
3. Both of folders should contain files like `mycomponent.json` with at least 2 keys: `"version"`, `"url"` (of new package).
4. `"url"` meta-key points to new zipball, that can be placed anywhere.

### Custom component (plugin/theme)

Custom component must have at least `Version: ...`.
Add code below to your plugin's main file or theme's `fuctions.php`:

```php
add_filter('plugins_loaded', function() {
    // theme: 'after_setup_theme' or 'init'
	if ( class_exists( 'WPCRL\Core' ) )
		WPCRL\Core::get_instance()->register_component( __FILE__ );
    });
```

### Constants
- `WPCRL_URL` - mandatory string to define custom repo base.
- `WPCRL_LOG` - optional int (0..7) for logging (RTFM `LOG_...` PHP constants).
