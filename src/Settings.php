<?php declare(strict_types=1);

namespace Gratis\AI;

defined('ABSPATH') || exit;

/**
 * Admin settings screen for Gratis AI.
 *
 * Configures the primary (BYOM) provider, stores encrypted free-provider API
 * keys via {@see KeyVault}, surfaces provider status, and shows MCP server
 * connection info.
 */
final class Settings {

    private const GROUP = 'gratis_ai_settings_group';
    private const PAGE  = 'gratis-ai';

    private const API_KEY_MASK = '********';

    /** Free providers that accept a stored key, keyed by KeyVault name. */
    private const FREE_KEYS = [
        'groq_api_key'       => 'Groq',
        'cerebras_api_key'   => 'Cerebras',
        'together_api_key'   => 'Together.ai',
        'openrouter_api_key' => 'OpenRouter',
    ];

    /** Register the Settings submenu page. */
    public static function register_menu(): void {
        add_options_page(
            __('Gratis AI', 'gratis-ai'),
            __('Gratis AI', 'gratis-ai'),
            'manage_options',
            self::PAGE,
            [__CLASS__, 'render_page']
        );
    }

    /** Register the primary provider options. */
    public static function register_settings(): void {
        register_setting(self::GROUP, 'gratis_ai_endpoint', ['type' => 'string', 'sanitize_callback' => 'esc_url_raw', 'default' => '']);
        register_setting(self::GROUP, 'gratis_ai_model', ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => 'gpt-4o-mini']);
        register_setting(self::GROUP, 'gratis_ai_api_key', ['type' => 'string', 'sanitize_callback' => [__CLASS__, 'sanitize_api_key'], 'default' => '']);
    }

    /** Keep the existing key when the masked placeholder is submitted. */
    public static function sanitize_api_key($value): string {
        $value = (string) $value;
        if (self::API_KEY_MASK === $value) {
            return (string) get_option('gratis_ai_api_key', '');
        }
        return sanitize_text_field($value);
    }

    /** Register the admin-post handlers (free-provider key + MCP key regen). */
    public static function register_actions(): void {
        add_action('admin_post_gratis_ai_store_key', [__CLASS__, 'handle_store_key']);
        add_action('admin_post_gratis_ai_regen_mcp', [__CLASS__, 'handle_regen_mcp']);
    }

    /** Store an encrypted free-provider API key (nonce + capability gated). */
    public static function handle_store_key(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to do that.', 'gratis-ai'));
        }
        check_admin_referer('gratis_ai_store_key');

        $name  = isset($_POST['key_name']) ? sanitize_key(wp_unslash($_POST['key_name'])) : '';
        $value = isset($_POST['key_value']) ? sanitize_text_field(wp_unslash($_POST['key_value'])) : '';

        if (isset(self::FREE_KEYS[$name]) && '' !== $value) {
            KeyVault::set($name, $value);
        }

        wp_safe_redirect(add_query_arg(['page' => self::PAGE, 'gratis_ai_msg' => 'key_saved'], admin_url('options-general.php')));
        exit;
    }

    /** Regenerate the MCP server API key (nonce + capability gated). */
    public static function handle_regen_mcp(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to do that.', 'gratis-ai'));
        }
        check_admin_referer('gratis_ai_regen_mcp');

        update_option('gratis_ai_mcp_key', wp_generate_password(32, false));
        wp_safe_redirect(add_query_arg(['page' => self::PAGE, 'gratis_ai_msg' => 'mcp_regen'], admin_url('options-general.php')));
        exit;
    }

    /** Render the settings screen. */
    public static function render_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'gratis-ai'));
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display flag.
        $msg = isset($_GET['gratis_ai_msg']) ? sanitize_key(wp_unslash($_GET['gratis_ai_msg'])) : '';

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Gratis AI', 'gratis-ai') . '</h1>';
        echo '<p>' . esc_html__('Bring your own model or use a free provider. Keys are encrypted at rest.', 'gratis-ai') . '</p>';

        if ('key_saved' === $msg) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('API key stored (encrypted).', 'gratis-ai') . '</p></div>';
        } elseif ('mcp_regen' === $msg) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('MCP server key regenerated.', 'gratis-ai') . '</p></div>';
        }

        self::render_primary_form();
        self::render_provider_status();
        self::render_free_key_form();
        self::render_mcp_info();

        echo '</div>';
    }

    /** Primary (BYOM) provider config form. */
    private static function render_primary_form(): void {
        $endpoint = (string) get_option('gratis_ai_endpoint', '');
        $model    = (string) get_option('gratis_ai_model', 'gpt-4o-mini');
        $has_key  = '' !== (string) get_option('gratis_ai_api_key', '');

        echo '<h2>' . esc_html__('Primary provider', 'gratis-ai') . '</h2>';
        echo '<form method="post" action="options.php">';
        settings_fields(self::GROUP);
        echo '<table class="form-table" role="presentation"><tbody>';

        echo '<tr><th scope="row"><label for="ga_endpoint">' . esc_html__('API endpoint', 'gratis-ai') . '</label></th><td>';
        echo '<input type="url" id="ga_endpoint" class="regular-text" name="gratis_ai_endpoint" value="' . esc_attr($endpoint) . '" placeholder="https://api.openai.com/v1">';
        echo '<p class="description">' . esc_html__('Any OpenAI-compatible endpoint (OpenAI, Ollama, LM Studio, vLLM, …).', 'gratis-ai') . '</p>';
        echo '</td></tr>';

        echo '<tr><th scope="row"><label for="ga_model">' . esc_html__('Model', 'gratis-ai') . '</label></th><td>';
        echo '<input type="text" id="ga_model" class="regular-text" name="gratis_ai_model" value="' . esc_attr($model) . '">';
        echo '</td></tr>';

        echo '<tr><th scope="row"><label for="ga_key">' . esc_html__('API key', 'gratis-ai') . '</label></th><td>';
        echo '<input type="password" id="ga_key" class="regular-text" name="gratis_ai_api_key" value="' . esc_attr($has_key ? self::API_KEY_MASK : '') . '" autocomplete="new-password">';
        echo '<p class="description">' . esc_html__('Leave the masked value to keep the current key; clear it to remove.', 'gratis-ai') . '</p>';
        echo '</td></tr>';

        echo '</tbody></table>';
        submit_button();
        echo '</form>';
    }

    /** Provider availability table. */
    private static function render_provider_status(): void {
        $providers = ModelRouter::providers();
        echo '<h2>' . esc_html__('Provider status', 'gratis-ai') . '</h2>';
        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>' . esc_html__('Provider', 'gratis-ai') . '</th>';
        echo '<th>' . esc_html__('Model', 'gratis-ai') . '</th>';
        echo '<th>' . esc_html__('Status', 'gratis-ai') . '</th>';
        echo '</tr></thead><tbody>';
        foreach ($providers as $name => $info) {
            echo '<tr>';
            echo '<td>' . esc_html((string) $name) . '</td>';
            echo '<td><code>' . esc_html((string) ($info['model'] ?? '')) . '</code></td>';
            echo '<td>' . esc_html((string) ($info['status'] ?? '')) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }

    /** Free-provider key entry form. */
    private static function render_free_key_form(): void {
        echo '<h2>' . esc_html__('Free provider keys', 'gratis-ai') . '</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('gratis_ai_store_key');
        echo '<input type="hidden" name="action" value="gratis_ai_store_key">';
        echo '<select name="key_name">';
        foreach (self::FREE_KEYS as $key_name => $label) {
            $stored = '' !== KeyVault::get($key_name);
            echo '<option value="' . esc_attr($key_name) . '">' . esc_html($label) . ($stored ? esc_html__(' (stored)', 'gratis-ai') : '') . '</option>';
        }
        echo '</select> ';
        echo '<input type="password" name="key_value" class="regular-text" autocomplete="new-password" placeholder="' . esc_attr__('Paste API key', 'gratis-ai') . '"> ';
        submit_button(__('Store key', 'gratis-ai'), 'secondary', 'submit', false);
        echo '</form>';
    }

    /** MCP server connection info + key regeneration. */
    private static function render_mcp_info(): void {
        $key = (string) get_option('gratis_ai_mcp_key', '');

        echo '<h2>' . esc_html__('MCP server', 'gratis-ai') . '</h2>';
        echo '<table class="form-table" role="presentation"><tbody>';
        echo '<tr><th scope="row">' . esc_html__('Tools endpoint', 'gratis-ai') . '</th><td><code>' . esc_html(rest_url('gratis-ai/v1/mcp/tools')) . '</code></td></tr>';
        echo '<tr><th scope="row">' . esc_html__('Generate endpoint', 'gratis-ai') . '</th><td><code>' . esc_html(rest_url('gratis-ai/v1/generate')) . '</code></td></tr>';
        echo '<tr><th scope="row">' . esc_html__('API key', 'gratis-ai') . '</th><td>';
        echo '' !== $key ? '<code>' . esc_html($key) . '</code>' : '<span class="description">' . esc_html__('Not generated yet.', 'gratis-ai') . '</span>';
        echo '</td></tr>';
        echo '</tbody></table>';

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('gratis_ai_regen_mcp');
        echo '<input type="hidden" name="action" value="gratis_ai_regen_mcp">';
        submit_button(__('Regenerate MCP key', 'gratis-ai'), 'secondary', 'submit', false);
        echo '</form>';
    }
}
