<?php
/**
 * Settings → Lumail. Token stays server-side.
 *
 * @package Lumail
 */

if (! defined('ABSPATH')) {
    exit;
}

final class Lumail_Settings
{
    public const OPTION = 'lumail_settings';

    /**
     * @return array{
     *   api_token:string,
     *   api_base:string,
     *   default_tags:string,
     *   button_label:string,
     *   success_message:string,
     *   show_name:bool
     * }
     */
    public static function get(): array
    {
        $stored = get_option(self::OPTION, []);
        if (! is_array($stored)) {
            $stored = [];
        }

        return [
            'api_token' => is_string($stored['api_token'] ?? null) ? $stored['api_token'] : '',
            'api_base' => is_string($stored['api_base'] ?? null) && $stored['api_base'] !== ''
                ? rtrim($stored['api_base'], '/')
                : Lumail_Client::DEFAULT_BASE_URL,
            'default_tags' => is_string($stored['default_tags'] ?? null) ? $stored['default_tags'] : '',
            'button_label' => is_string($stored['button_label'] ?? null) && $stored['button_label'] !== ''
                ? $stored['button_label']
                : 'Subscribe',
            'success_message' => is_string($stored['success_message'] ?? null) && $stored['success_message'] !== ''
                ? $stored['success_message']
                : 'Check your inbox to confirm.',
            'show_name' => (bool) ($stored['show_name'] ?? true),
        ];
    }

    public static function token_hint(): string
    {
        $token = self::get()['api_token'];
        if ($token === '') {
            return '';
        }

        return 'lum_…' . substr($token, -4);
    }

    public static function register_menu(): void
    {
        add_options_page(
            'Lumail',
            'Lumail',
            'manage_options',
            'lumail',
            [self::class, 'render_page']
        );
    }

    public static function register_settings(): void
    {
        register_setting(
            'lumail',
            self::OPTION,
            [
                'type' => 'array',
                'sanitize_callback' => [self::class, 'sanitize'],
                'default' => self::get(),
            ]
        );
    }

    public static function enqueue(string $hook): void
    {
        if ($hook !== 'settings_page_lumail') {
            return;
        }

        wp_enqueue_style(
            'lumail-admin',
            LUMAIL_PLUGIN_URL . 'assets/admin.css',
            [],
            LUMAIL_VERSION
        );
        wp_enqueue_script(
            'lumail-admin',
            LUMAIL_PLUGIN_URL . 'assets/admin.js',
            [],
            LUMAIL_VERSION,
            true
        );
        wp_localize_script(
            'lumail-admin',
            'lumailAdmin',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('lumail_ping'),
            ]
        );
    }

    /**
     * @param mixed $input
     * @return array<string,mixed>
     */
    public static function sanitize($input): array
    {
        $current = self::get();
        if (! is_array($input)) {
            return $current;
        }

        $token = trim((string) ($input['api_token'] ?? ''));
        if ($token === '') {
            $token = $current['api_token'];
        } elseif (! preg_match('/^lum_[A-Za-z0-9]+$/', $token)) {
            $token = $current['api_token'];
            add_settings_error(
                self::OPTION,
                'lumail_token',
                'API token must start with lum_. The previous token was kept.',
                'error'
            );
        }

        $base = trim((string) ($input['api_base'] ?? ''));
        if ($base === '') {
            $base = Lumail_Client::DEFAULT_BASE_URL;
        }
        $base = esc_url_raw($base, ['https', 'http']);
        if ($base === '') {
            $base = Lumail_Client::DEFAULT_BASE_URL;
        }

        return [
            'api_token' => $token,
            'api_base' => rtrim($base, '/'),
            'default_tags' => implode(', ', Lumail_Form::parse_tags((string) ($input['default_tags'] ?? ''))),
            'button_label' => sanitize_text_field((string) ($input['button_label'] ?? 'Subscribe')),
            'success_message' => sanitize_text_field((string) ($input['success_message'] ?? '')),
            'show_name' => ! empty($input['show_name']),
        ];
    }

    public static function render_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $settings = self::get();
        $hint = self::token_hint();
        ?>
        <div class="wrap lumail-admin">
            <h1>Lumail</h1>
            <?php settings_errors(); ?>
            <p>Server-side subscribe form. Shortcode: <code>[lumail_form]</code></p>
            <form method="post" action="options.php">
                <?php settings_fields('lumail'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="lumail-api-token">API token</label></th>
                        <td>
                            <input
                                id="lumail-api-token"
                                class="regular-text"
                                type="password"
                                name="<?php echo esc_attr(self::OPTION); ?>[api_token]"
                                value=""
                                autocomplete="new-password"
                                placeholder="<?php echo esc_attr($hint !== '' ? $hint : 'lum_…'); ?>"
                            />
                            <p class="description">
                                Create one in Lumail → Settings → API Tokens. Needs the <code>subscribers</code> permission.
                                Leave blank to keep the current token.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lumail-api-base">API base URL</label></th>
                        <td>
                            <input
                                id="lumail-api-base"
                                class="regular-text"
                                type="url"
                                name="<?php echo esc_attr(self::OPTION); ?>[api_base]"
                                value="<?php echo esc_attr($settings['api_base']); ?>"
                            />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lumail-default-tags">Default tags</label></th>
                        <td>
                            <input
                                id="lumail-default-tags"
                                class="regular-text"
                                type="text"
                                name="<?php echo esc_attr(self::OPTION); ?>[default_tags]"
                                value="<?php echo esc_attr($settings['default_tags']); ?>"
                                placeholder="wordpress, newsletter"
                            />
                            <p class="description">Merged with <code>[lumail_form tags="blog"]</code>.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lumail-button">Button label</label></th>
                        <td>
                            <input
                                id="lumail-button"
                                class="regular-text"
                                type="text"
                                name="<?php echo esc_attr(self::OPTION); ?>[button_label]"
                                value="<?php echo esc_attr($settings['button_label']); ?>"
                            />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lumail-success">Success message</label></th>
                        <td>
                            <input
                                id="lumail-success"
                                class="regular-text"
                                type="text"
                                name="<?php echo esc_attr(self::OPTION); ?>[success_message]"
                                value="<?php echo esc_attr($settings['success_message']); ?>"
                            />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Name field</th>
                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="<?php echo esc_attr(self::OPTION); ?>[show_name]"
                                    value="1"
                                    <?php checked($settings['show_name']); ?>
                                />
                                Show the name input on the form
                            </label>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Save changes'); ?>
            </form>
            <p>
                <button type="button" class="button" id="lumail-ping">Test connection</button>
                <span id="lumail-ping-result" role="status"></span>
            </p>
            <h2>Shortcode</h2>
            <pre>[lumail_form title="Get the newsletter" tags="blog" button="Join" show_name="true"]</pre>
        </div>
        <?php
    }
}
