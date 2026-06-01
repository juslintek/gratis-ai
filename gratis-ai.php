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
 * Text Domain: gratis-ai
 */

defined('ABSPATH') || exit;

require_once __DIR__ . '/autoload.php';

add_action('plugins_loaded', function () {
    // WordPress 7.0 Abilities API
    \Gratis\AI\AbilitiesIntegration::register();

    if (defined('WP_CLI') && WP_CLI) {
        \WP_CLI::add_command('gratis-ai', \Gratis\AI\CLI\AICommand::class);
    }
});

// MCP Server REST endpoints
add_action('rest_api_init', [\Gratis\AI\MCPServer::class, 'register']);
