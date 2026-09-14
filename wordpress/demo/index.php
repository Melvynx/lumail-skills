<?php
/**
 * Browser preview of the plugin UI without WordPress.
 * php -S 127.0.0.1:PORT -t wordpress/demo
 */

declare(strict_types=1);

define('LUMAIL_TEST', true);
define('LUMAIL_VERSION', '0.1.0');

require_once dirname(__DIR__) . '/includes/class-lumail-form.php';

$ajax_url = $_SERVER['SCRIPT_NAME'] ?? '/index.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'lumail_subscribe') {
    header('Content-Type: application/json; charset=utf-8');
    $honeypot = (string) ($_POST[Lumail_Form::HONEYPOT_FIELD] ?? '');
    if (Lumail_Form::honeypot_triggered($honeypot)) {
        echo json_encode(['success' => true, 'data' => ['message' => 'Check your inbox to confirm.']]);
        exit;
    }
    $email = (string) ($_POST['email'] ?? '');
    if (! Lumail_Form::is_valid_email($email)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'data' => ['message' => 'Enter a valid email.']]);
        exit;
    }
    echo json_encode(['success' => true, 'data' => ['message' => 'Check your inbox to confirm.']]);
    exit;
}

$form = Lumail_Form::render(
    [
        'title' => 'Get the newsletter',
        'button' => 'Join',
        'show_name' => 'true',
        'tags' => 'blog',
        'form_id' => 'demo',
    ],
    'demo-nonce',
    $ajax_url
);
$css = file_get_contents(dirname(__DIR__) . '/assets/form.css');
$js = file_get_contents(dirname(__DIR__) . '/assets/form.js');
$admin_css = file_get_contents(dirname(__DIR__) . '/assets/admin.css');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Lumail WordPress plugin</title>
  <style>
    :root { color-scheme: light; }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font: 16px/1.45 ui-sans-serif, system-ui, sans-serif;
      color: #1d2327;
      background: #f0f0f1;
    }
    .shell {
      display: grid;
      gap: 1.25rem;
      padding: 1.5rem;
      max-width: 1080px;
      margin: 0 auto;
    }
    @media (min-width: 900px) {
      .shell { grid-template-columns: 1.15fr 0.85fr; align-items: start; }
    }
    .panel {
      background: #fff;
      border: 1px solid #dcdcde;
      border-radius: 8px;
      padding: 1.25rem 1.4rem 1.5rem;
    }
    .wrap h1 { margin: 0 0 0.35rem; font-size: 1.45rem; }
    .lede { margin: 0 0 1rem; color: #50575e; }
    .form-table { width: 100%; border-collapse: collapse; }
    .form-table th {
      text-align: left;
      width: 9.5rem;
      padding: 0.65rem 0.75rem 0.65rem 0;
      vertical-align: top;
    }
    .form-table td { padding: 0.45rem 0; }
    .regular-text, input[type="url"], input[type="password"], input[type="text"] {
      width: min(100%, 22rem);
      padding: 0.4rem 0.5rem;
    }
    .description { margin: 0.35rem 0 0; color: #646970; font-size: 0.85rem; }
    .button, .button-primary {
      border: 1px solid #2271b1;
      background: #2271b1;
      color: #fff;
      border-radius: 3px;
      padding: 0.4rem 0.8rem;
      font: inherit;
      cursor: pointer;
    }
    .button { background: #f6f7f7; color: #2c3338; border-color: #dcdcde; }
    pre {
      padding: 0.85rem 1rem;
      background: #fff;
      border: 1px solid #dcdcde;
      border-radius: 4px;
      overflow: auto;
    }
    .site h2 { margin: 0 0 0.4rem; letter-spacing: -0.03em; }
    .site p { color: #3c434a; }
    <?php echo $css; ?>
    <?php echo $admin_css; ?>
  </style>
</head>
<body>
  <div class="shell">
    <section class="panel wrap lumail-admin">
      <h1>Lumail</h1>
      <p class="lede">Server-side subscribe form. Shortcode: <code>[lumail_form]</code></p>
      <form>
        <table class="form-table" role="presentation">
          <tr>
            <th scope="row"><label for="lumail-api-token">API token</label></th>
            <td>
              <input id="lumail-api-token" class="regular-text" type="password" value="" placeholder="lum_…9x4k" />
              <p class="description">Create one in Lumail → Settings → API Tokens. Needs the <code>subscribers</code> permission. Leave blank to keep the current token.</p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="lumail-default-tags">Default tags</label></th>
            <td>
              <input id="lumail-default-tags" class="regular-text" type="text" value="wordpress, newsletter" />
            </td>
          </tr>
        </table>
        <p><button type="button" class="button-primary">Save changes</button></p>
      </form>
      <p>
        <button type="button" class="button" id="lumail-ping">Test connection</button>
        <span id="lumail-ping-result" role="status"></span>
      </p>
      <h2>Shortcode</h2>
      <pre>[lumail_form title="Get the newsletter" tags="blog" button="Join" show_name="true"]</pre>
    </section>
    <section class="panel site">
      <h2>On a page</h2>
      <p>The shortcode renders this form. Submit hits the plugin, not Lumail, from the browser.</p>
      <?php echo $form; ?>
    </section>
  </div>
  <script>
    window.lumailForm = {
      ajaxUrl: <?php echo json_encode($ajax_url); ?>,
      success: "Check your inbox to confirm."
    };
  </script>
  <script><?php echo $js; ?></script>
  <script>
    document.getElementById("lumail-ping")?.addEventListener("click", () => {
      const result = document.getElementById("lumail-ping-result");
      if (!result) return;
      result.textContent = "Demo only — use a real WordPress install to ping Lumail.";
      result.className = "";
    });
  </script>
</body>
</html>
