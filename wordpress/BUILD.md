# How this WordPress plugin is built

WordPress plugins are PHP files WordPress discovers from a header comment.
There is no bundler. You copy the folder into `wp-content/plugins/` and
activate it.

## 1. Bootstrap (`lumail.php`)

The file in the plugin root must start with:

```php
/**
 * Plugin Name: Lumail
 * Version: 0.1.0
 * Requires PHP: 8.0
 */
```

WordPress scans that header. Then the file loads classes and calls
`Lumail_Plugin::instance()->boot()`, which registers hooks:

| Hook | Why |
| --- | --- |
| `init` | `[lumail_form]` shortcode |
| `admin_menu` / `admin_init` | Settings → Lumail |
| `wp_ajax_lumail_subscribe` + `nopriv` | logged-in and anonymous POST |
| `wp_ajax_lumail_ping` | admin connection test only |

## 2. Settings (token never in HTML)

`Lumail_Settings` stores one option: `lumail_settings`. The token field is
always empty on render. Saving a blank field keeps the previous token. The
placeholder shows `lum_…` plus the last four characters.

Capability: `manage_options`. Nonce: WordPress Settings API.

## 3. Form (shortcode → HTML)

`[lumail_form tags="blog" show_name="true"]` renders a `<form>` with:

- hidden `nonce`, `action=lumail_subscribe`, shortcode tags
- honeypot `website` (CSS off-screen; bots fill it)
- email (+ optional name)

`assets/form.js` `fetch`es `admin-ajax.php`. No Lumail URL, no token.

## 4. AJAX → Lumail API

`Lumail_Ajax::subscribe`:

1. `check_ajax_referer`
2. honeypot → fake success
3. `sanitize_email` / `sanitize_text_field`
4. rate limit: one POST per email+IP per minute
5. merge default tags + shortcode tags
6. `Lumail_Client::create_subscriber`

Client:

```
POST {api_base}/v2/subscribers
Authorization: Bearer lum_…
Content-Type: application/json
```

Body from `Lumail_Form::subscriber_payload()` — DOI on, resubscribe off,
workflows on, IP attached.

Admin **Test connection** is `GET /v2/subscribers?limit=1`. 200 = token works.

## 5. Why this is the easy plugin

Hooks + one HTTP call. No Gutenberg (that is React), no custom tables, no
cron. The hard parts that *are* in here: nonce, sanitization, honeypot,
rate limit, token not in the page, DOI/resubscribe defaults.

## 6. Local check without WordPress

```bash
php tests/run.php
```

That file defines `LUMAIL_TEST`, includes the client + form classes, and
mocks HTTP. It does not boot WordPress.

## 7. Drop it on a real site

```bash
cp -R wordpress /path/to/wordpress/wp-content/plugins/lumail
```

Then wp-admin → Plugins → Activate Lumail.
