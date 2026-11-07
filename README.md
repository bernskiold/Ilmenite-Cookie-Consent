# 🍪 Ilmenite Cookie Consent
A simple, developer-friendly WordPress plugin that informs users about cookie usage on the site, and lets them both opt in and select their preferences.

There are many WordPress plugins and services out there that do fancy things with cookie consents. We didn't find one we really liked that was both really lightweight and developer friendly. So we created our own.

This plugin isn't meant for the masses who want tons of configurable options in the admin (although it will work and look fine out of the box). Many use this plugin with the default styling because it is light-weight and good-looking. While you can modify texts and choose between three positions in the admin, any more advanced styling requires CSS.

For the developer who wants the functionality and being able to conveniently override the styles and features without bloat—here's a plugin for you. You have filters and actions available to you at every step of the process. From locking down the editing of texts, to changing every option.

## Configuration
The plugin works out of the box with minimal settings. However here are a few things you will probably want to be aware about.

### Set the policy link
You can set the URL to the cookie policy page in the customizer under the "Cookie Banner" section, or use the filter `ilcc_policy_url` to return your own link.

By default we will get the privacy policy page from `Settings > Privacy`.

### Changing/disabling the styling
Out of the box, the plugin includes a lightweight stylesheet with three placement options (overlay, top and takeover). If you don't want to use our default style, you can easily prevent us from including the styles and style it yourself.

Just define the following filter somewhere in your code, such as the theme functions.php file:

    apply_filters( 'ilcc_load_stylesheet', '__return_false' );

Additionally, for quick theming to your theme's custom colors, we support a series of CSS variables set on `body.has-ilcc-banner` like so:

    body.has-ilcc-banner {
        --ilcc-background-color: #ffffff;
		--ilcc-text-color: #1e1e1e;
		--ilcc-link-color: #1e1e1e;
		--ilcc-link-color-hover: #555555;
		--ilcc-close-button: black;
		--ilcc-close-button-hover: #444444;
		--ilcc-close-button-text: white;
		--ilcc-close-button-hover-text: white;
		--ilcc-settings-background-color: #f9f9f9;
		--ilcc-settings-border: #eeeeee;
		--ilcc-toggle-background-color: #ffffff;
		--ilcc-toggle-handle-background-color: #6b6b6b;
		--ilcc-toggle-border-color: #6b6b6b;
		--ilcc-toggle-active-background-color: #2e7d4f;
		--ilcc-toggle-active-handle-background-color: #ffffff;
		--ilcc-focus-color: #1e1e1e; /* Defaults to --ilcc-text-color. */
		--ilcc-radius: 4px;
    }

When changing colors, keep text at a contrast of at least 4.5:1 against the background, and toggle parts and focus outlines at least 3:1, so the banner stays accessible.

### Accessibility
The banner is built to meet WCAG 2.2 AA:

- It is inserted first in the page, so it comes first in reading and keyboard order. The overlay and top styles are a labelled region. The takeover style is a modal dialog: the rest of the page cannot be reached until the visitor makes a choice, and Escape does not dismiss it.
- Category toggles are switches that work with the keyboard (Space and Enter) and announce whether they are on or off.
- The settings panel announces whether it is expanded, and Escape closes it.
- After a choice is made, focus moves to the start of the page.
- All controls have a visible focus outline, and the banner scrolls when it is taller than the screen, for example when zoomed in.
- Animations are turned off for visitors who prefer reduced motion.

If you replace the default stylesheet, keep the collapsed settings panel hidden with `visibility: hidden` or `display: none` so its controls can't be focused while closed.

If you would like to add your own style in addition to the three offered, you can override the style setting with the `ilcc_style` filter. This would let you style outside the three core positions.

### Changing the texts
You can change all texts from the Customizer under the "Cookie Banner" section. Alternatively, you can use our extensive set of filters to return values before rendering. See the list of filters below.

Just set their value somewhere in your code, such as in the functions.php file of your theme:

    function ilcc_modify_consent_text( $text ) {
        $text = __( 'This is my custom text about how we use cookies.', 'YOURTEXTDOMAIN' );
        return $text;
    }

    add_filter( 'ilcc_consent_text', 'ilcc_modify_consent_text' );

    function ilcc_modify_accept_text( $text ) {
        $text = __( 'I Accept', 'YOURTEXTDOMAIN' );
        return $text;
    }

    add_filter( 'ilcc_accept_text', 'ilcc_modify_accept_text' );

### Blocking trackers

Domains in **Settings → Tracker Control** are grouped into necessary, analytics and marketing. Until a visitor consents to a category, nothing from its domains loads.

**The plugin rewrites tracker tags on the server.** Browsers start downloading `<script src>`, `<iframe>`, `<img>` and preload URLs from the HTML before any JavaScript can run. Those requests carry and set third-party cookies. So any tag that points at an analytics or marketing domain is rewritten into inert markup before the page is sent. The rewrite is the same for every visitor, so pages stay cache-safe, and the script blocker restores the tags the visitor has consented to:

| In the page | Sent to the browser |
|---|---|
| `<script src="…">` | `<script type="text/plain" data-ilcc-category="analytics" src="…">` |
| `<iframe src="…">`, `<img src="…">` | `data-src="…" data-ilcc-category="…"` |
| `<link rel="preload" href="…">` | `data-href="…" data-ilcc-category="…"` |

Scripts that other scripts add later, for example with `document.createElement`, are blocked in the browser before they are requested.

**You can write the opt-in markup yourself**, for content that isn't on a listed domain:

```html
<script type="text/plain" data-ilcc-category="marketing" src="https://example.com/pixel.js"></script>
<script type="text/plain" data-ilcc-category="analytics">/* inline code */</script>
<iframe data-ilcc-category="marketing" data-src="https://www.youtube.com/embed/…"></iframe>
```

Add `data-ilcc-ignore` to a tag to keep the plugin from touching it. To turn the server-side rewrite off completely, return `false` from `ilcc_rewrite_tracker_tags`.

**From JavaScript**, use:

- `window.ilccConsent`: `openSettings()`, `hasConsent( category )` and `getCategories()`
- `window.ilccBlocker`: `isBlocked( url )` and `unblock( categories )`

`window.yett.unblock()` still works for older custom code.

**Withdrawn consent:** when a visitor withdraws consent, the page reloads so the trackers stop. Cookies those trackers already set stay in the browser until they expire.

### List of Actions

`ilcc_loaded` - Runs on constructor.

`before_ilcc_init` - Runs before we have run any init actions.

`ilcc_init` - Runs when all init hooks have run.

### List of Filters

`ilcc_accept_text` - Set the accept button text.

`ilcc_consent_text` - Set the consent text. Has $policy_url as argument.

`ilcc_policy_url` - Allows you to modify the Policy URL. Has the url from the options as argument.

`ilcc_style` - Allows you to set your own style name.

`ilcc_edit_texts_capability` - Allows you to modify which capability is required for editing the texts in the cookie banner customizer option. Takes the current setting as the first argument.

`ilcc_edit_style_capability` - Allows you to modify which capability is required for editing the cookie banner style in the customizer. Defaults to `edit_theme_options`.

`ilcc_load_stylesheets` - (bool) Set if you want the stylesheets to be loaded or not. Defaults to true.

`ilcc_enable_customizer` - Return false to disable all the customizer settings, if you'd like to prevent any user from changing any of the settings.

`ilcc_preferences_cookie_name` - The name of the cookie that stores if a visitor has set their cookie preferences.

`ilcc_categories_cookie_name` - The name of the cookie that stores the categories the visitor has opted in to.

`ilcc_remember_duration` - How many days to remember the consent for. Defaults to 90.

`ilcc_tracker_settings_enabled` - Return false to disable the tracker customization settings screen.

`ilcc_is_active_on_page` - Return false to hide the banner from loading. Can be used to prevent the banner from loading on certain pages or templates.

`ilcc_rewrite_tracker_tags` - Return false to stop the plugin from rewriting tracker tags on the server. Trackers are then only blocked in the browser, which can't stop the browser's early downloads.

## Development and tests

Requires Node 26 (see `.nvmrc`) and Composer.

```bash
composer install && npm install
npm run build   # or `npm run start` to rebuild on changes
```

The build uses [`@wordpress/scripts`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/) with the small `webpack.config.js` in the root.

There are two test suites, and CI runs both on every pull request.

**PHP unit tests** (`tests/php`) use PHPUnit with [Brain Monkey](https://giuseppe-mazzapica.gitbook.io/brain-monkey/) instead of a WordPress install. `BackCompatTest` pins everything an update must not break: the public methods, filters, actions, cookie names and the options the Customizer stores. If you have to change one of these, add a deprecation path rather than editing the test.

```bash
composer test
```

**End-to-end tests** (`tests/e2e`) package the plugin like a release, boot a real WordPress with [WordPress Playground](https://wordpress.github.io/wordpress-playground/) (no Docker or MySQL needed), and drive it in Chromium with Playwright:

- `consent.spec.js` covers a fresh install: blocking, the consent choices, Consent Mode, reopening the settings, cache safety and the admin screens.
- `upgrade.spec.js` installs the latest release from WordPress.org, saves settings and a visitor's consent with it, and then runs WordPress's automatic updater to update it to this build. It then checks that the plugin is still active, that saved settings and consent still apply, and that no PHP errors occurred.

```bash
npx playwright install chromium   # once
npm run test:e2e
```

Set `WP_VERSION` (for example `5.0` or `latest`) and `PHP_VERSION` (for example `7.4` or `8.4`) to choose the versions. Each run prints the WordPress version it used. The tests need to reach wordpress.org.

## Translations
Included in the package are complete translations for the following languages:

- Lithuanian (Thanks @batiufa)
- Swedish
- Occitan (Thanks Mejans)
- Norwegian
- Danish
- Hungarian
- Spanish
- German
- Italian
- Slovenian
- Greek (Thanks Michail)

A complete *.pot* file is available in the *translations/* directory. If you use and translate this little plugin, please send us the translation so it can be included!

**Even better** is if you use Translate.WordPress.org for your translations. That way, they will be automatically distributed with the WordPress updater.

However, in some locales, the work with the Translate site is not up to speed. We will continue to support bundled translations because of this.

## Authors
This plugin was created by Bernskiold Media [http://www.bernskioldmedia.com].

## License
This plugin is licensed under GPL. Feel free to use it in personal and commercial projects as you wish.
