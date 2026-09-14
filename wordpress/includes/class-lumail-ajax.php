<?php
/**
 * admin-ajax.php handlers. Token never leaves the server.
 *
 * @package Lumail
 */

if (! defined('ABSPATH')) {
    exit;
}

final class Lumail_Ajax
{
    public static function subscribe(): void
    {
        check_ajax_referer('lumail_subscribe', 'nonce');

        $honeypot = isset($_POST[Lumail_Form::HONEYPOT_FIELD])
            ? sanitize_text_field(wp_unslash((string) $_POST[Lumail_Form::HONEYPOT_FIELD]))
            : '';
        if (Lumail_Form::honeypot_triggered($honeypot)) {
            wp_send_json_success(['message' => Lumail_Settings::get()['success_message']]);
        }

        $email = isset($_POST['email']) ? sanitize_email(wp_unslash((string) $_POST['email'])) : '';
        if (! Lumail_Form::is_valid_email($email)) {
            wp_send_json_error(['message' => 'Enter a valid email.'], 422);
        }

        $ip = Lumail_Plugin::client_ip();
        $rate_key = 'lumail_rate_' . md5($ip . '|' . strtolower($email));
        if (get_transient($rate_key)) {
            wp_send_json_error(['message' => 'Wait a minute before trying again.'], 429);
        }
        set_transient($rate_key, 1, MINUTE_IN_SECONDS);

        $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash((string) $_POST['name'])) : '';
        $shortcode_tags = Lumail_Form::parse_tags(
            isset($_POST['tags']) ? sanitize_text_field(wp_unslash((string) $_POST['tags'])) : ''
        );
        $tags = Lumail_Form::merge_tags(
            Lumail_Form::parse_tags(Lumail_Settings::get()['default_tags']),
            $shortcode_tags
        );

        $result = Lumail_Plugin::client()->create_subscriber(
            Lumail_Form::subscriber_payload([
                'email' => $email,
                'name' => $name,
                'tags' => $tags,
                'ip_address' => $ip,
            ])
        );

        if (! $result['ok']) {
            $status = $result['status'] >= 400 ? $result['status'] : 502;
            $message = $result['status'] === 401
                ? 'Lumail is not configured correctly.'
                : 'Could not subscribe right now.';
            wp_send_json_error(['message' => $message], $status);
        }

        wp_send_json_success(['message' => Lumail_Settings::get()['success_message']]);
    }

    public static function ping(): void
    {
        check_ajax_referer('lumail_ping', 'nonce');
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Forbidden.'], 403);
        }

        $result = Lumail_Plugin::client()->ping();
        if (! $result['ok']) {
            wp_send_json_error(
                ['message' => $result['message'] !== '' ? $result['message'] : 'Connection failed.'],
                $result['status'] >= 400 ? $result['status'] : 502
            );
        }

        wp_send_json_success(['message' => 'Connected. Token can read subscribers.']);
    }
}
