# Lumail WordPress plugin

Subscribe forms for [Lumail](https://lumail.io). Shortcode `[lumail_form]`.
The API token stays on the server. The browser only talks to `admin-ajax.php`.

Requires WordPress 6.2+ and PHP 8.0+.

## Install

```bash
git clone https://github.com/Melvynx/lumail-opensource.git
cp -R lumail-opensource/wordpress /path/to/wp-content/plugins/lumail
```

Activate **Lumail**, then Settings → Lumail:

1. Paste a `lum_` token with the `subscribers` permission
2. Optional default tags (`wordpress, newsletter`)
3. Test connection

Drop this on a page:

```
[lumail_form title="Get the newsletter" tags="blog" button="Join"]
```

## What it does

`POST https://lumail.io/api/v2/subscribers` with:

- `email`, optional `name`, merged tags
- `skipDoubleOptIn: false` — org DOI still applies
- `resubscribe: false` — unsubscribed contacts stay unsubscribed
- `triggerWorkflows: true`
- `ipAddress` from the request

No Gutenberg block, no WooCommerce, no client-side token.

## Tests

```bash
php wordpress/tests/run.php
```

There is no compile step. Zip the folder if you want a download:

```bash
cd wordpress && zip -r ../lumail.zip . -x 'tests/*' 'demo/*' 'BUILD.md'
```

See [BUILD.md](./BUILD.md) for how the plugin is put together.
