<?php declare(strict_types=1);
namespace Gratis\AI\CLI;

use Gratis\AI\Provider\OpenAICompatible;
use Gratis\AI\ModelRouter;
use Gratis\AI\KeyVault;

final class AICommand
{
    /**
     * Show AI configuration and provider status.
     * @when after_wp_load
     */
    public function status($args, $assoc_args): void
    {
        $provider = ModelRouter::resolve();
        \WP_CLI::log("=== Gratis AI Status ===");
        \WP_CLI::log("Active provider: " . $provider->name());
        \WP_CLI::log("Available: " . ($provider->isAvailable() ? 'yes' : 'no'));
    }

    /**
     * Send a prompt to the AI model.
     * <prompt> : The prompt.
     * @when after_wp_load
     */
    public function ask($args, $assoc_args): void
    {
        $provider = ModelRouter::resolve();
        if (!$provider->isAvailable()) { \WP_CLI::error("No AI provider. Run: wp gratis-ai free-guide"); }
        \WP_CLI::log($provider->complete($args[0], ['max_tokens' => 1024]));
    }

    /**
     * Configure the primary AI provider.
     * [--endpoint=<url>] [--model=<model>] [--api-key=<key>]
     * @when after_wp_load
     */
    public function configure($args, $assoc_args): void
    {
        if (isset($assoc_args['endpoint'])) update_option('gratis_ai_endpoint', $assoc_args['endpoint']);
        if (isset($assoc_args['model'])) update_option('gratis_ai_model', $assoc_args['model']);
        if (isset($assoc_args['api-key'])) update_option('gratis_ai_api_key', $assoc_args['api-key']);
        \WP_CLI::success("AI provider configured.");
    }

    /**
     * Generate a blog post from a topic.
     * <topic> : Topic.
     * [--publish] : Publish immediately.
     * @subcommand generate-post
     * @when after_wp_load
     */
    public function generate_post($args, $assoc_args): void
    {
        $provider = ModelRouter::resolve();
        if (!$provider->isAvailable()) { \WP_CLI::error("No AI provider available."); }
        $content = $provider->complete("Write a WordPress blog post about: {$args[0]}. Use Gutenberg block format.", ['max_tokens' => 2048]);
        $status = isset($assoc_args['publish']) ? 'publish' : 'draft';
        $id = wp_insert_post(['post_title' => $args[0], 'post_content' => $content, 'post_status' => $status]);
        \WP_CLI::success("Post #{$id} created ({$status}).");
    }

    /**
     * Show available AI providers.
     * @when after_wp_load
     */
    public function providers($args, $assoc_args): void
    {
        $providers = ModelRouter::providers();
        foreach ($providers as $name => $info) {
            $icon = ($info['status'] === 'configured' || $info['status'] === 'key stored') ? '✓' : '○';
            \WP_CLI::log("{$icon} {$name}: {$info['model']} [{$info['status']}]");
            if (isset($info['signup'])) \WP_CLI::log("    {$info['signup']}");
        }
    }

    /**
     * Show free provider signup guide.
     * @subcommand free-guide
     * @when after_wp_load
     */
    public function free_guide($args, $assoc_args): void
    {
        \WP_CLI::log(ModelRouter::freeProviderGuide());
    }

    /**
     * Manage encrypted API keys.
     * <action> : set, list, delete, export.
     * [<name>] : Key name.
     * [<value>] : Key value (for set).
     * @when after_wp_load
     */
    public function key($args, $assoc_args): void
    {
        $action = $args[0] ?? 'list';
        if ($action === 'set') {
            if (empty($args[1]) || empty($args[2])) { \WP_CLI::error("Usage: key set <name> <value>"); }
            KeyVault::set($args[1], $args[2]);
            \WP_CLI::success("Key '{$args[1]}' stored (encrypted).");
        } elseif ($action === 'list') {
            $keys = KeyVault::list();
            if (empty($keys)) { \WP_CLI::log("No keys. Run: wp gratis-ai free-guide"); return; }
            foreach ($keys as $name => $masked) { \WP_CLI::log("  {$name}: {$masked}"); }
        } elseif ($action === 'delete') {
            KeyVault::delete($args[1] ?? '');
            \WP_CLI::success("Deleted.");
        } elseif ($action === 'export') {
            echo KeyVault::export();
        }
    }

    /**
     * Show MCP server connection info.
     * @subcommand mcp-info
     * @when after_wp_load
     */
    public function mcp_info($args, $assoc_args): void
    {
        $key = get_option('gratis_ai_mcp_key', '');
        if (!$key) { $key = wp_generate_password(32, false); update_option('gratis_ai_mcp_key', $key); }
        \WP_CLI::log("=== MCP Server ===");
        \WP_CLI::log("Tools: " . rest_url('gratis-ai/v1/mcp/tools'));
        \WP_CLI::log("Execute: " . rest_url('gratis-ai/v1/mcp/execute'));
        \WP_CLI::log("API Key: {$key}");
        \WP_CLI::log("\nHeader: Authorization: Bearer {$key}");
    }
}
