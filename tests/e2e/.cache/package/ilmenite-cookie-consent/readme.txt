=== Ilmenite Cookie Consent ===
Contributors: Erik Bernskiold, annlickander, bernskioldmedia
Tags: cookies, cookie notice, eu cookie law, cookie compliance, cookie banner, cookie consent, gdpr, privacy, tracker prevention, tracker
Requires at least: 5.0
Tested up to: 6.5
Requires PHP: 7.2
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A simple, developer-friendly WordPress plugin with minimum bloat that lets visitors know that the site is using cookies.

== Description ==

There are many WordPress plugins out there which does a lot of fancy things with the cookie consent. We didn't find one we really liked that was really lightweight and developer friendly and so we created our own.

It isn't meant for the masses who want tons of configurable options in the admin (although it will work and look fine out of the box). Many use this plugin with the default styling because it is so light-weight and good-looking.

For the developer who wants the functionality and being able to conveniently override the styles in the theme without bloat—here's a plugin for you. You have filters and actions available to you at every step of the process.

See the installation section for more information on how to install. The FAQ section has important information on how to customize the plugin.

= Translations =

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

A complete *.pot* file is available in the *translations/* directory. If you use and translate this little plugin, please send us the translation so it can be included!

**Even better** is if you use Translate.WordPress.org for your translations. That way, they will be automatically distributed with the WordPress updater.
However, in some locales, the work with the Translate site is not up to speed. We will continue to support included translations for a while.

== Installation ==

We recommend using the built-in plugin installer in WordPress. If you wish to install the plugin manually:

1. Upload `ilmenite-cookie-consent` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Set the link to your cookie policy under Appearance > Customize > Cookie Banner, or set a privacy policy page under Settings > Privacy.

== Frequently Asked Questions ==

= How do I set the cookie policy link? =
You can set the URL to the cookie policy page in the customizer under the "Cookie Banner" section, or use the filter `ilcc_policy_url` to return your own link.

= How do I customize the style or disable it completely? =
Out of the box, the plugin includes a lightweight stylesheet with two placement options (top & overlay). If you don't want to use our default coloring, you can easily prevent us from including the styles.

Just define the following filter somewhere in your code, such as the theme functions.php file:

    add_filter( 'ilcc_load_stylesheet', '__return_false' );

Additionally, for quick theming to your theme's custom colors, we support a series of CSS variables set on `body.has-ilcc-banner` like so:

    body.has-ilcc-banner {
        --ilcc-background-color: #282b2d;
        --ilcc-text-color: #ccc;
        --ilcc-link-color: #ccc;
        --ilcc-link-color-hover: #fff;
        --ilcc-banner-spacing: 1.4rem 0;
        --ilcc-close-button: #474d50;
        --ilcc-close-button-hover: #666;
        --ilcc-close-button-text: white;
        --ilcc-close-button-hover-text: white;
        --ilcc-button-radius: 4px;
    }

If you would like to add your own style in addition to the two offered, you can override the style setting with the `ilcc_style` filter. This would let you style outside the two core positions.

= Can I change the texts and/or button label? =
You can change the the two lines of text and the button label from the customizer under the "Cookie Banner" section. Alternatively you can use a set of filters to return values before rendering.

Modiyfing the title: `ilcc_consent_title`
Modiyfing the text info: `ilcc_consent_text`
Modiyfing the accept button label: `ilcc_accept_text`

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

 = What actions are available? =

`ilcc_loaded` - Runs on constructor.

`before_ilcc_init` - Runs before we have run any init actions.

`ilcc_init` - Runs when all init hooks have run.

= What filters are available? =

`ilcc_has_user_consented` - Specifiy if the user has accepted or not. True or false value. Has arguments $cookie_name and $cookie_value.

`ilcc_cookie_active_value` - Set which value is "active" for the cookie, ie. consented. Defaults to 1.

`ilcc_cookie_name` - Set the name of the cookie. Defaults to 'EUConsentCookie'.

`ilcc_accept_text` - Set the accept button text.

`ilcc_consent_text` - Set the consent text. Has $policy_url as argument.

`ilcc_policy_url` - Allows you to modify the Policy URL. Has the url from the options as argument.

`ilcc_style` - Allows you to set your own style name.

`ilcc_edit_text_capability` - Allows you to modify which capability is required for editing the cookie banner text (below the title) in the customizer. Defaults to `edit_theme_options`.

`ilcc_edit_title_capability` - Allows you to modify which capability is required for editing the cookie banner title in the customizer. Defaults to `edit_theme_options`.

`ilcc_edit_button_capability` - Allows you to modify which capability is required for editing the cookie banner button label in the customizer. Defaults to `edit_theme_options`.

`ilcc_edit_policy_url_capability` - Allows you to modify which capability is required for editing the policy URL in the customizer. Defaults to `edit_theme_options`.

`ilcc_edit_style_capability` - Allows you to modify which capability is required for editing the cookie banner style in the customizer. Defaults to `edit_theme_options`.

`ilcc_load_stylesheets` - (bool) Set if you want the stylesheets to be loaded or not. Defaults to true.

`ilcc_enable_customizer` - Return false to disable all the customizer settings, if you'd like to prevent any user from changing any of the settings.

`ilcc_preferences_cookie_name` - The name of the cookie that stores if a visitor has set their cookie preferences.

`ilcc_categories_cookie_name` - The name of the cookie that stores the categories the visitor has opted in to.

`ilcc_tracker_settings_enabled` - Return false to disable the tracker customization settings screen.

`ilcc_is_active_on_page` - Return false to hide the banner from loading. Can be used to prevent the banner from loading on certain pages or templates.

`ilcc_enable_default_consent_mode?` - Return false to disable the default consent mode. This is useful if you want to provide your own default configuration.

== Screenshots ==

1. The "top" style design of the cookie consent box out of the box.
2. The "overlay" style design of the cookie consent box, enabled in the customizer.
3. Customizer controls are available for all texts and URL.

== Changelog ==

= Version 3.4.0 =
- Add Google Consent Mode v2 support

= Version 3.3.0 =
- Update NO and DK translation
- Added greek translations (thanks Michail)
- Fix a bug where the configure setting button text setting in the customizer would not save (thanks wikitdev)
- Fix a bug with the logic on showing analytics and marketing (thanks crstauf)

= Version 3.2.0 =
- Add matomo integration
- Allow customizing consent sections
- Remove syncing debug mode with WP_DEBUG
- Remove jQuery dependencies
- Adds Occitan language (Thanks Mejans)

= Version 3.1.0 =
- Added a filter to prevent the banner from loading on specific pages or templates.
- Declare support for PHP 8 when loading via composer.

= Version 3.0.3 =
- Fixed a bug where domains were included in the disallow list even when they were supposed to be allowed. (#16)

= Version 3.0.2 =
- Updated Lithuanian translation (thanks @batiufa)
- Fixed an array offset warning (#14)

= Version 3.0.1 =
Fixed an issue where we passed script tags to an wp_add_inline_script function callback, causing a doing_it_wrong notice.

= Version 3.0.0 =
Major update with potentially breaking changes.

We are now finally respecting not to set any tracking cookies unless the user has actually accepted all cookies. We keep a running list of trackers that we disable automatically. From analytics to marketing. You can modify the list of trackers via filters in the code or the settings screen.

As a developer, you can disable the settings screens via filters.

To support this, the plugin has been extended quite a bit. There are numerous new strings, filters and options.

We have also added a new style, "take over", if you'd prefer to force the user to make a choice before allowing them into your website. The "overlay" style has now been made the default one for new installs.

- Added the `ilcc_preferences_cookie_name` filter to replace the now removed `ilcc_cookie_name` filter.
- Added the `ilcc_categories_cookie_name` filter.
- Added the `ilcc_remember_duration` filter.
- Added the `ilcc_tracker_settings_enabled` filter.
- Removed the `ilcc_has_user_consented` filter.
- Removed the `ilcc_cookie_active_value` filter.
- Replaced `ilcc_edit_text_capability`, `ilcc_edit_title_capability`, `ilcc_edit_button_capability` and `ilcc_edit_policy_url_capability` with a simpler `ilcc_edit_texts_capability` that takes the setting as an argument.

= Version 2.0.5 =
When no policy URL is set in the customer, the default integrity policy URL from the WordPress settings will be loaded.

For those translating via WPML and Polylang, we have added a configuration file that makes the strings you add in the customizer translatable.

= Version 2.0.4 =
Fixed a bug where the consent duration wasn't set properly, resulting in us asking the user to consent way more often. The plugin will now (correctly) remember the consent for 30 days, unless the user clears their cookies.

= Version 2.0.3 =
Fixed compatibility issues with jQuery 3. Thanks Viktor.

= Version 2.0.2 =
Fixed a small issue where our build script wasn't processing fallbacks for the new CSS variables correctly.

This could lead to the default style not loading properly in older browsers (such as IE 11). This update fixes
this behavior.

As a result, the variables are now defined on :root {}.

= Version 2.0.1 =
Svn is svn. Contains nothing new apart from fixing the release archive.
If you managed to update to 2.0.0 in the few minute window before this was
addressed, 2.0.1 takes care of things for you. If not, enjoy the 2.0.0 update.

= Version 2.0.0 =

In this major release we've made many code improvements as well as improvements to class names
and the JavaScript that powers most of the features. You will also have better and more
access to filters and actions for customization. Also, new customizer settings and a new core style
gives you quicker access to control the appearance of the banner.

- Improvement: Switched to setting the policy URL in the customizer instead of under Settings > Reading.
- Improvement: Added customizer settings for all texts as well.
- Improvement: Added a second core style "Overlay", offering the option of showing the banner overlaid at the bottom instead of at the top.
- Improvement: Better class names for the consent box.
- Improvement: Re-structured the JavaScript code.
- Improvement: Ensure we get languages from all possible storage folders in WordPress.
- Improvement: Added filter to disable stylesheet loading.
- Improvement: Never process any of the the JS or CSS logic if the user has already consented.
- Improvement: Added filter when we check if user has consented.
- Improvement: Added filter for cookie name.
- Improvement: Added filter for cookie acceptance value.
- Improvement: Modified consent text filter to include the policy URL as a variable.
- Improvement: Added filter for when getting the policy URL.
- Improvement: Switched from an `<a>` tag for the acceptance button, to a more proper `button`.
- Improvement: Added filters for controlling who may edit the settings in the customizer.
- Bug: Fixed a bug where the consent block could add to the DOM multiple times.

= Version 1.1.4 =
Added Danish translation. (Thanks Magnus!)

= Version 1.1.3 =
Added an Hungarian translation. (Thanks Miklos)

= Version 1.1.2 =
Updated a string in the Spanish translation (thanks ibertrix)

= Version 1.1.1 =
We managed to change a string we shouldn't have changed in Version 1.1.0. Sorry about that!

= Version 1.1.0 =
It's time we switch this plugin over to above 1.0 releases.
- Changed the textdomain to conform with the plugin name = text domain. This means we will have full support for the WordPress.org Plugin translations.
- Added Italian translation (Thanks Matteo)

= Version 0.2.9 =
- Improved German translation (Thanks Frank!)
- Added Lithuanian translation
- Minor Code Tweaks & Improvements (just behind the scenes—Thanks Johan)

= Version 0.2.8 =
- Added Spanish translation (Thanks Vigdis!)
- Fixed a bug where the cookie banner height would be outputted in the JS console.

= Version 0.2.7 =
- Added Slovak language support (Thanks Peter!)

= Version 0.2.6 =
- Added Norwegian (Bokmål) translation (Thanks Kristofer!)
- Updated German translation with missing string
- Fixes dev mode constant
- Remove the GitHub Updater. Plugin will be added to the WordPress respository.

= Version 0.2.5**
- Performance Increase: Don't load scripts and styles if the cookie has already been set.

= Version 0.2.4 =
- Fixed a miss in the new CSS

= Version 0.2.3 =
- Fixed a bug where the settings wouldn't save due to an incorrectly specified settings area. (Thanks to jnylin https://github.com/jnylin)
- Added mobile friendly default styles

= Version 0.2.2 =
- Fixed a bug where the localization function wasn't properly loaded.
- Fixed a bug where some textdomains were not properly specified.

= Version 0.2.1 =
- Fixed a bug where the language files weren't properly loaded.

= Version 0.2.0 =
- Added GitHub updater
- Added settings field for policy URL
- Minify script and style
- Added German translation

= Version 0.1.0 =
- First plugin version.

== Upgrade Notice ==

= Version 2.0.5 =
When no policy URL is set in the customer, the default integrity policy URL from the WordPress settings will be loaded.

For those translating via WPML and Polylang, we have added a configuration file that makes the strings you add in the customizer translatable.

= 1.2.0 =

In this release we've made some code improvements as well as improvements to class names and the JavaScript that powers most of the features. You will also have better and more access to filters and actions for customization.

- Improvement: Better class names for the consent box.
- Improvement: Switched to setting the policy URL in the customizer instead of under Settings > Reading.
- Improvement: Re-structured the JavaScript code.
- Improvement: Ensure we get languages from all possible storage folders in WordPress.
- Improvement: Added filter to disable stylesheet loading.
- Improvement: Never process any of the the JS or CSS logic if the user has already consented.
- Improvement: Added filter when we check if user has consented.
- Improvement: Added filter for cookie name.
- Improvement: Added filter for cookie acceptance value.
- Improvement: Modified consent text filter to include the policy URL as a variable.
- Improvement: Added filter for when getting the policy URL.
- Improvement: Switched from an `<a>` tag for the acceptance button, to a more proper `button`.
- Improvement: Added filter to edit the capbility required for editing the Policy URL in the customizer. Defaults to `edit_theme_options`.
- Bug: Fixed a bug where the consent block could add to the DOM multiple times.

= 1.1.1 =
We managed to change a string we shouldn't have changed in Version 1.1.0. Sorry about that!

= 1.1.0 =
It's time we switch this plugin over to above 1.0 releases.
- Changed the textdomain to conform with the plugin name = text domain. This means we will have full support for the WordPress.org Plugin translations.
- Added Italian translation (Thanks Matteo)

= 0.2.9 =
Improved the German translation.

= 0.2.8 =
Spanish translation has been added, along with a minor bug fix.

= 0.2.7 =
Added Slovak language.

= 0.2.6 =
Improved translations and added Norwegian to the list of supported languages.
