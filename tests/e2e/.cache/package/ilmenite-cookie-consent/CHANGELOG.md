# Changelog

## 3.3.0 - 2024-02-04

### What's Changed

* Update NO and DK translation by @annlickander in https://github.com/bernskioldmedia/Ilmenite-Cookie-Consent/pull/75
* Fix configure settings button text by @wikitdev in https://github.com/bernskioldmedia/Ilmenite-Cookie-Consent/pull/77
* Fix logic on showing analytics and marketing by @crstauf in https://github.com/bernskioldmedia/Ilmenite-Cookie-Consent/pull/83
* Added greek translation (Thanks Michail)

### New Contributors

* @annlickander made their first contribution in https://github.com/bernskioldmedia/Ilmenite-Cookie-Consent/pull/75
* @wikitdev made their first contribution in https://github.com/bernskioldmedia/Ilmenite-Cookie-Consent/pull/77
* @crstauf made their first contribution in https://github.com/bernskioldmedia/Ilmenite-Cookie-Consent/pull/83

**Full Changelog**: https://github.com/bernskioldmedia/Ilmenite-Cookie-Consent/compare/3.2.0...3.3.0

## 3.2.0 - 2022-01-29

- Adds Occitan language ([#28](https://github.com/bernskioldmedia/ilmenite-cookie-consent/pull/28))

## Added

- Add matomo integration ([#39](https://github.com/bernskioldmedia/ilmenite-cookie-consent/pull/39))
- Allow customizing consent sections ([#38](https://github.com/bernskioldmedia/ilmenite-cookie-consent/pull/38))

## Changed

- Remove syncing debug mode with WP_DEBUG ([#37](https://github.com/bernskioldmedia/ilmenite-cookie-consent/pull/37))
- Remove jQuery dependencies ([#36](https://github.com/bernskioldmedia/ilmenite-cookie-consent/pull/36))

## Dependency Updates

- Bump browserslist from 4.7.0 to 4.19.1 ([#34](https://github.com/bernskioldmedia/ilmenite-cookie-consent/pull/34))
- Bump ws from 6.2.1 to 6.2.2 ([#35](https://github.com/bernskioldmedia/ilmenite-cookie-consent/pull/35))
- Bump follow-redirects from 1.13.3 to 1.14.7 ([#33](https://github.com/bernskioldmedia/ilmenite-cookie-consent/pull/33))
- Bump url-parse from 1.5.1 to 1.5.3 ([#31](https://github.com/bernskioldmedia/ilmenite-cookie-consent/pull/31))
- Bump path-parse from 1.0.6 to 1.0.7 ([#27](https://github.com/bernskioldmedia/ilmenite-cookie-consent/pull/27))

## [3.1.0] - 2021-07-09

- Added a filter to prevent the banner from loading on specific pages or templates. (#25)
- Declare support for PHP 8 when loading via composer. (#26)

## [3.0.3] - 2021-03-19

- Fixed a bug where domains were included in the disallow list even when they were supposed to be allowed. (#16)

## [3.0.2] - 2021-03-12

- Updated Lithuanian translation (thanks @batiufa)
- Fixed an array offset warning (#14)

## [3.0.1] - 2021-03-07

Fixed an issue where we passed script tags to an wp_add_inline_script function callback, causing a doing_it_wrong notice.

**Version 3.0.0**
Major update with potentially breaking changes.

We are now finally respecting not to set any tracking cookies unless the user has actually accepted all cookies. We keep a running list of trackers that we disable automatically.
From analytics to marketing. You can modify the list of trackers via filters in the code or the settings screen.

As a developer, you can disable the settings screens via filters.

To support this, the plugin has been extended quite a bit. There are numerous new strings, filters and options.

We have also added a new style, "take over", if you'd prefer to force the user to make a choice before allowing them into your website. The "overlay" style has now been made the
default one for new installs.

- Added the `ilcc_preferences_cookie_name` filter to replace the now removed `ilcc_cookie_name` filter.
- Added the `ilcc_categories_cookie_name` filter.
- Added the `ilcc_remember_duration` filter.
- Added the `ilcc_tracker_settings_enabled` filter.
- Removed the `ilcc_has_user_consented` filter.
- Removed the `ilcc_cookie_active_value` filter.
- Replaced `ilcc_edit_text_capability`, `ilcc_edit_title_capability`, `ilcc_edit_button_capability` and `ilcc_edit_policy_url_capability` with a
- simpler `ilcc_edit_texts_capability` that takes the setting as an argument.

**Version 2.0.5**

When no policy URL is set in the customer, the default integrity policy URL from the WordPress settings will be loaded.

For those translating via WPML and Polylang, we have added a configuration file that makes the strings you add in the customizer translatable.

**Version 2.0.4**

Fixed a bug where the consent duration wasn't set properly, resulting in us asking the user to consent way more often. The plugin will now (correctly) remember the consent for 30
days, unless the user clears their cookies.

**Version 2.0.3**

Fixed compatibility issues with jQuery 3. Instead of `$.load(function()` the plugin is now initializing on `.on("load", function()`. Thanks Viktor.

**Version 2.0.2**

Fixed a small issue where our build script wasn't processing fallbacks for the new CSS variables correctly. This could lead to the default style not loading properly in older
browsers (such as IE 11). This update fixes this behavior.

As a result, the variables are now defined on :root {}.

**Version 2.0.1**

Svn is svn. Contains nothing new apart from fixing the release archive. If you managed to update to 2.0.0 in the few minute window before this was addressed, 2.0.1 takes care of
things for you. If not, enjoy the 2.0.0 update.

**Version 2.0.0**

In this major release we've made many code improvements as well as improvements to class names and the JavaScript that powers most of the features. You will also have better and
more access to filters and actions for customization. Also, new customizer settings and a new core style gives you quicker access to control the appearance of the banner.

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

**Version 1.1.4**

Included Danish translation (Thanks Magnus)

**Version 1.1.3**

Included a Hungarian translation (Thanks Miklos)

**Version 1.1.2**

Updated a string in the Spanish translation (thanks ibertrix)

**Version 1.1.1**

We managed to change a string we shouldn't have changed in Version 1.1.0. Sorry about that!

**Version 1.1.0**

It's time we switch this plugin over to above 1.0 releases.

- Changed the textdomain to conform with the plugin name = text domain. This means we will have full support for the WordPress.org Plugin translations.
- Added Italian translation (Thanks Matteo)

**Version 0.2.9**

- Improved German translation (Thanks Frank!)
- Added Lithuanian translation
- Minor Code Tweaks & Improvements (just behind the scenes—Thanks Johan)

**Version 0.2.8**

- Added Spanish translation (Thanks Vigdis!)
- Fixed a bug where the cookie banner height would be outputted in the JS console.

**Version 0.2.7**

- Added Slovak translation (Thanks Peter!)

**Version 0.2.6**

- Added Norwegian (Bokmål) translation (Thanks Kristofer!)
- Updated German translation with missing string
- Fixes dev mode constant
- Remove the GitHub Updater. Plugin will be added to the WordPress respository.

**Version 0.2.5**

- Performance Increase: Don't load scripts and styles if the cookie has already been set.

**Version 0.2.4**

- Fixed a miss in the new CSS

**Version 0.2.3**

- Fixed a bug where the settings wouldn't save due to an incorrectly specified settings area. (Thanks to jnylin https://github.com/jnylin)
- Added mobile friendly default styles

**Version 0.2.2**

- Fixed a bug where the localization function wasn't properly loaded.
- Fixed a bug where some textdomains were not properly specified.

**Version 0.2.1**

- Fixed a bug where the language files weren't properly loaded.

**Version 0.2.0**

- Added GitHub updater
- Added settings field for policy URL
- Minify script and style
- Added German translation

**Version 0.1.0**

- First plugin version.
