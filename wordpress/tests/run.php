<?php
/**
 * Tiny assertion runner. No PHPUnit, no WordPress.
 *
 * php tests/run.php
 */

declare(strict_types=1);

define('LUMAIL_TEST', true);
define('LUMAIL_VERSION', '0.1.0');

require_once dirname(__DIR__) . '/includes/class-lumail-client.php';
require_once dirname(__DIR__) . '/includes/class-lumail-form.php';

$failed = 0;
$passed = 0;

function expect(bool $ok, string $label): void
{
    global $failed, $passed;
    if ($ok) {
        $passed++;
        echo "PASS  {$label}\n";
        return;
    }
    $failed++;
    echo "FAIL  {$label}\n";
}

expect(Lumail_Form::parse_tags('WordPress, Blog,, vip') === ['wordpress', 'blog', 'vip'], 'parse_tags splits and lowercases');
expect(Lumail_Form::parse_tags('') === [], 'parse_tags empty');
expect(Lumail_Form::merge_tags(['blog'], ['blog', 'wp']) === ['blog', 'wp'], 'merge_tags unique');
expect(Lumail_Form::is_valid_email('ada@example.com'), 'valid email');
expect(! Lumail_Form::is_valid_email('ada'), 'invalid email');
expect(Lumail_Form::honeypot_triggered('https://spam.test'), 'honeypot filled');
expect(! Lumail_Form::honeypot_triggered(''), 'honeypot empty');

$payload = Lumail_Form::subscriber_payload([
    'email' => ' Ada@Example.com ',
    'name' => 'Ada',
    'tags' => ['blog'],
    'ip_address' => '203.0.113.10',
]);
expect($payload['email'] === 'ada@example.com', 'payload lowercases email');
expect($payload['name'] === 'Ada', 'payload keeps name');
expect($payload['tags'] === ['blog'], 'payload tags');
expect($payload['resubscribe'] === false, 'public form does not resubscribe');
expect($payload['skipDoubleOptIn'] === false, 'public form never skips DOI');
expect($payload['triggerWorkflows'] === true, 'workflows fire');
expect($payload['ipAddress'] === '203.0.113.10', 'ip forwarded');

$markup = Lumail_Form::render(
    ['title' => 'Join', 'button' => 'Go', 'show_name' => 'true', 'tags' => 'blog', 'form_id' => 't1'],
    'nonce-value',
    '/wp-admin/admin-ajax.php'
);
expect(str_contains($markup, 'name="nonce" value="nonce-value"'), 'nonce in form');
expect(str_contains($markup, 'name="website"'), 'honeypot present');
expect(str_contains($markup, 'type="email"'), 'email field');
expect(str_contains($markup, 'Join'), 'title rendered escaped');
expect(! str_contains($markup, 'lum_'), 'token never in markup');

$captured = null;
$client = new Lumail_Client(
    'lum_testtoken_xxxxxxxx',
    'https://lumail.io/api',
    function (string $method, string $url, array $headers, ?string $body) use (&$captured): array {
        $captured = compact('method', 'url', 'headers', 'body');
        return [
            'status' => 200,
            'body' => json_encode([
                'id' => 'sub_abc',
                'email' => 'ada@example.com',
                'status' => 'PENDING_CONFIRMATION',
            ]),
            'error' => null,
        ];
    }
);

$result = $client->create_subscriber($payload);
expect($result['ok'] === true, 'create_subscriber 200 is ok');
expect($result['data']['id'] === 'sub_abc', 'create_subscriber returns id');
expect($captured['method'] === 'POST', 'POST /v2/subscribers');
expect($captured['url'] === 'https://lumail.io/api/v2/subscribers', 'create URL');
expect($captured['headers']['Authorization'] === 'Bearer lum_testtoken_xxxxxxxx', 'bearer token');
$sent = json_decode((string) $captured['body'], true);
expect(is_array($sent) && $sent['skipDoubleOptIn'] === false, 'JSON keeps skipDoubleOptIn false');

$missing = new Lumail_Client('', 'https://lumail.io/api', function (): array {
    throw new RuntimeException('transport must not run without a token');
});
$blocked = $missing->create_subscriber($payload);
expect($blocked['ok'] === false, 'missing token does not call HTTP');
expect(! str_contains($blocked['message'], 'lum_testtoken'), 'errors do not leak other tokens');

$unauthorized = new Lumail_Client(
    'lum_badtoken_xxxxxxxx',
    'https://lumail.io/api',
    function (): array {
        return [
            'status' => 401,
            'body' => json_encode(['name' => 'missing_api_key', 'message' => 'Invalid token']),
            'error' => null,
        ];
    }
);
$denied = $unauthorized->ping();
expect($denied['ok'] === false && $denied['status'] === 401, 'ping surfaces 401');
expect($denied['message'] === 'Invalid token', 'ping uses API message');

$unconfigured = new Lumail_Client('not-a-token');
expect(! $unconfigured->is_configured(), 'token must start with lum_');

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed === 0 ? 0 : 1);
