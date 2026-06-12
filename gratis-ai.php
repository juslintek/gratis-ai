<?php
/**
 * Plugin Name: Gratis AI
 * Plugin URI: https://github.com/juslintek/gratis-ai
 * Description: AI assistant with BYOM, free model fallback, MCP server, WP 7.0 Abilities API. Encrypted key storage.
 * Version: 2.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * Author: Linas Jusys
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gratis-ai
 */

defined('ABSPATH') || exit;

define('GRATIS_AI_VERSION', '2.0.0');
define('GRATIS_AI_FILE', __FILE__);
define('GRATIS_AI_DIR', plugin_dir_path(__FILE__));
define('GRATIS_AI_URL', plugin_dir_url(__FILE__));

require_once __DIR__ . '/autoload.php';

add_action('plugins_loaded', function () {
    // WordPress 7.0 Abilities API
    \Gratis\AI\AbilitiesIntegration::register();

    if (defined('WP_CLI') && WP_CLI) {
        \WP_CLI::add_command('gratis-ai', \Gratis\AI\CLI\AICommand::class);
    }
});

// MCP Server + content generation REST endpoints
add_action('rest_api_init', [\Gratis\AI\MCPServer::class, 'register']);
add_action('rest_api_init', [\Gratis\AI\ContentGenerator::class, 'register']);

// Admin settings page + key-management actions.
add_action('admin_menu', [\Gratis\AI\Settings::class, 'register_menu']);
add_action('admin_init', [\Gratis\AI\Settings::class, 'register_settings']);
\Gratis\AI\Settings::register_actions();
