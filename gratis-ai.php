<?php
/**
 * Plugin Name: Gratis AI
 * Plugin URI: https://github.com/juslintek/gratis-ai
 * Description: Optional AI assistance for WordPress. BYOM (Bring Your Own Model) — use any OpenAI-compatible endpoint or local model.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * Author: Linas Jusys
 * Author URI: https://github.com/juslintek
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gratis-ai
 */

defined('ABSPATH') || exit;

require_once __DIR__ . '/autoload.php';

add_action('plugins_loaded', function () {
    if (defined('WP_CLI') && WP_CLI) {
        \WP_CLI::add_command('gratis-ai', \Gratis\AI\CLI\AICommand::class);
    }
});
