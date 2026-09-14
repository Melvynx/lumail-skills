=== Lumail ===
Contributors: lumail
Tags: email, newsletter, subscribers, lumail
Requires at least: 6.2
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 0.1.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Subscribe forms that upsert contacts into Lumail via POST /api/v2/subscribers.

== Description ==

Adds `[lumail_form]` to WordPress. Submissions go to Lumail from the server. The API token never reaches the browser.

Create a token with the `subscribers` permission in Lumail → Settings → API Tokens.

== Installation ==

1. Copy the `wordpress` folder into `wp-content/plugins/lumail`
2. Activate Lumail
3. Settings → Lumail → paste a `lum_` token
4. Put `[lumail_form]` in a page

== Changelog ==

= 0.1.0 =
* Settings, shortcode, server-side subscribe, connection test
