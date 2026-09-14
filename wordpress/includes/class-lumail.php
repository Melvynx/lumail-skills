<?php
/**
 * Plugin bootstrap.
 *
 * @package Lumail
 */

if (! defined('ABSPATH')) {
    exit;
}

final class Lumail_Plugin
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        if (! self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function boot(): void
    {
        add_action('init', [$this, 'register_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_action('admin_enqueue_scripts', [Lumail_Settings::class, 'enqueue']);
        add_action('admin_menu', [Lumail_Settings::class, 'register_menu']);
        add_action('admin_init', [Lumail_Settings::class, 'register_settings']);
        add_action('wp_ajax_lumail_subscribe', [Lumail_Ajax::class, 'subscribe']);
        add_action('wp_ajax_nopriv_lumail_subscribe', [Lumail_Ajax::class, 'subscribe']);
        add_action('wp_ajax_lumail_ping', [Lumail_Ajax::class, 'ping']);
        add_filter('plugin_action_links_' . plugin_basename(LUMAIL_PLUGIN_FILE), [$this, 'settings_link']);
    }

    /**
     * @param array<int,string> $links
     * @return array<int,string>
     */
    public function settings_link(array $links): array
    {
        $url = admin_url('options-general.php?page=lumail');
        array_unshift($links, '<a href="' . esc_url($url) . '">Settings</a>');

        return $links;
    }

    public function register_shortcode(): void
    {
        add_shortcode('lumail_form', [$this, 'shortcode']);
    }

    public function register_assets(): void
    {
        wp_register_style(
            'lumail-form',
            LUMAIL_PLUGIN_URL . 'assets/form.css',
            [],
            LUMAIL_VERSION
        );
        wp_register_script(
            'lumail-form',
            LUMAIL_PLUGIN_URL . 'assets/form.js',
            [],
            LUMAIL_VERSION,
            true
        );
    }

    /**
     * @param array<string,string>|string $atts
     */
    public function shortcode($atts): string
    {
        $atts = shortcode_atts(
            [
                'title' => '',
                'button' => Lumail_Settings::get()['button_label'],
                'show_name' => Lumail_Settings::get()['show_name'] ? 'true' : 'false',
                'tags' => '',
                'form_id' => uniqid('lumail-', false),
            ],
            is_array($atts) ? $atts : [],
            'lumail_form'
        );

        wp_enqueue_style('lumail-form');
        wp_enqueue_script('lumail-form');
        wp_localize_script(
            'lumail-form',
            'lumailForm',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'success' => Lumail_Settings::get()['success_message'],
            ]
        );

        return Lumail_Form::render(
            $atts,
            wp_create_nonce('lumail_subscribe'),
            admin_url('admin-ajax.php')
        );
    }

    public static function client(): Lumail_Client
    {
        $settings = Lumail_Settings::get();

        return new Lumail_Client($settings['api_token'], $settings['api_base']);
    }

    public static function client_ip(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'] as $key) {
            $value = $_SERVER[$key] ?? '';
            if (is_string($value) && filter_var($value, FILTER_VALIDATE_IP)) {
                return $value;
            }
        }

        return '';
    }
}
