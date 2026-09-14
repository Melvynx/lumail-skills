<?php
/**
 * Subscribe form: validation, tags, shortcode markup.
 *
 * @package Lumail
 */

if (! defined('ABSPATH') && ! defined('LUMAIL_TEST')) {
    exit;
}

final class Lumail_Form
{
    public const HONEYPOT_FIELD = 'website';

    /**
     * @return list<string>
     */
    public static function parse_tags(string $raw): array
    {
        $parts = preg_split('/[,\n]+/', $raw) ?: [];
        $tags = [];
        foreach ($parts as $part) {
            $tag = strtolower(trim($part));
            if ($tag === '' || strlen($tag) > 64) {
                continue;
            }
            $tags[] = $tag;
        }

        return array_values(array_unique($tags));
    }

    /**
     * @param list<string> $a
     * @param list<string> $b
     * @return list<string>
     */
    public static function merge_tags(array $a, array $b): array
    {
        return array_values(array_unique(array_merge($a, $b)));
    }

    public static function is_valid_email(string $email): bool
    {
        return (bool) filter_var(trim($email), FILTER_VALIDATE_EMAIL);
    }

    public static function honeypot_triggered(string $value): bool
    {
        return trim($value) !== '';
    }

    /**
     * Public forms never skip double opt-in and never silently resubscribe.
     *
     * @param array{
     *   email:string,
     *   name?:string,
     *   tags?:list<string>,
     *   ip_address?:string
     * } $input
     * @return array<string,mixed>
     */
    public static function subscriber_payload(array $input): array
    {
        $payload = [
            'email' => strtolower(trim($input['email'])),
            'tags' => $input['tags'] ?? [],
            'resubscribe' => false,
            'triggerWorkflows' => true,
            'skipDoubleOptIn' => false,
        ];

        $name = trim($input['name'] ?? '');
        if ($name !== '') {
            $payload['name'] = $name;
        }

        $ip = trim($input['ip_address'] ?? '');
        if ($ip !== '') {
            $payload['ipAddress'] = $ip;
        }

        return $payload;
    }

    /**
     * @param array<string,string> $atts
     */
    public static function render(array $atts, string $nonce, string $ajax_url): string
    {
        $title = $atts['title'] ?? '';
        $button = $atts['button'] !== '' ? $atts['button'] : 'Subscribe';
        $show_name = ($atts['show_name'] ?? 'true') !== 'false';
        $tags = $atts['tags'] ?? '';
        $form_id = $atts['form_id'] ?? uniqid('lumail-', false);

        $html = '<form class="lumail-form" method="post" action="' . self::esc($ajax_url) . '" data-lumail-form novalidate>';
        $html .= '<input type="hidden" name="action" value="lumail_subscribe" />';
        $html .= '<input type="hidden" name="nonce" value="' . self::esc($nonce) . '" />';
        $html .= '<input type="hidden" name="form_id" value="' . self::esc($form_id) . '" />';
        $html .= '<input type="hidden" name="tags" value="' . self::esc($tags) . '" />';
        $html .= '<div class="lumail-form__honeypot" aria-hidden="true">';
        $html .= '<label>Website <input type="text" name="' . self::HONEYPOT_FIELD . '" value="" tabindex="-1" autocomplete="off" /></label>';
        $html .= '</div>';

        if ($title !== '') {
            $html .= '<p class="lumail-form__title">' . self::esc($title) . '</p>';
        }

        if ($show_name) {
            $html .= '<label class="lumail-form__field">';
            $html .= '<span>Name</span>';
            $html .= '<input type="text" name="name" autocomplete="name" maxlength="120" />';
            $html .= '</label>';
        }

        $html .= '<label class="lumail-form__field">';
        $html .= '<span>Email</span>';
        $html .= '<input type="email" name="email" autocomplete="email" required maxlength="254" />';
        $html .= '</label>';

        $html .= '<button type="submit" class="lumail-form__submit">' . self::esc($button) . '</button>';
        $html .= '<p class="lumail-form__message" role="status" aria-live="polite" hidden></p>';
        $html .= '</form>';

        return $html;
    }

    private static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
