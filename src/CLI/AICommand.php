<?php declare(strict_types=1);
namespace Gratis\AI\CLI;

use Gratis\AI\Provider\OpenAICompatible;

/** WP-CLI commands: wp gratis-ai <subcommand> */
final class AICommand
{
    /**
     * Show AI configuration and provider status.
     *
     * ## EXAMPLES
     *     wp gratis-ai status
     *
     * @when after_wp_load
     */
    public function status($args, $assoc_args): void
    {
        $provider = new OpenAICompatible();
        \WP_CLI::log("=== Gratis AI Status ===");
        \WP_CLI::log("Provider: " . $provider->name());
        \WP_CLI::log("Endpoint: " . get_option('gratis_ai_endpoint', 'http://localhost:11434/v1'));
        \WP_CLI::log("Model: " . get_option('gratis_ai_model', 'gpt-4o-mini'));
        \WP_CLI::log("API key: " . (get_option('gratis_ai_api_key') ? '***configured***' : 'not set'));
        \WP_CLI::log("Available: " . ($provider->isAvailable() ? 'yes' : 'no'));
    }

    /**
     * Send a prompt to the configured AI model.
     *
     * ## OPTIONS
     * <prompt>
     * : The prompt to send.
     *
     * [--model=<model>]
     * : Override the model.
     *
     * ## EXAMPLES
     *     wp gratis-ai ask "Explain my cache hit ratio"
     *
     * @when after_wp_load
     */
    public function ask($args, $assoc_args): void
    {
        $provider = new OpenAICompatible();
        if (!$provider->isAvailable()) {
            \WP_CLI::error("No AI provider configured. Set endpoint: wp option update gratis_ai_endpoint <url>");
        }

        $prompt = $args[0];
        \WP_CLI::log("Sending to {$provider->name()}...");
        $response = $provider->complete($prompt, $assoc_args);
        \WP_CLI::log("");
        \WP_CLI::log($response);
    }

    /**
     * Configure the AI provider.
     *
     * ## OPTIONS
     * [--endpoint=<url>]
     * : API endpoint URL.
     *
     * [--model=<model>]
     * : Model name.
     *
     * [--api-key=<key>]
     * : API key.
     *
     * ## EXAMPLES
     *     wp gratis-ai configure --endpoint=https://api.openai.com/v1 --api-key=sk-... --model=gpt-4o-mini
     *     wp gratis-ai configure --endpoint=http://localhost:11434/v1 --model=llama3
     *
     * @when after_wp_load
     */
    public function configure($args, $assoc_args): void
    {
        if (isset($assoc_args['endpoint'])) {
            update_option('gratis_ai_endpoint', $assoc_args['endpoint']);
            \WP_CLI::log("Endpoint: " . $assoc_args['endpoint']);
        }
        if (isset($assoc_args['model'])) {
            update_option('gratis_ai_model', $assoc_args['model']);
            \WP_CLI::log("Model: " . $assoc_args['model']);
        }
        if (isset($assoc_args['api-key'])) {
            update_option('gratis_ai_api_key', $assoc_args['api-key']);
            \WP_CLI::log("API key: configured");
        }
        \WP_CLI::success("AI provider configured.");
    }

    /**
     * Analyze site health and provide recommendations.
     *
     * ## EXAMPLES
     *     wp gratis-ai analyze
     *
     * @when after_wp_load
     */
    public function analyze($args, $assoc_args): void
    {
        $provider = new OpenAICompatible();
        if (!$provider->isAvailable()) {
            \WP_CLI::error("No AI provider configured.");
        }

        // Gather site context
        $context = $this->gatherSiteContext();
        $prompt = "You are a WordPress performance expert. Analyze this site data and provide 3-5 actionable recommendations:\n\n" . $context;

        \WP_CLI::log("Analyzing site...");
        $response = $provider->complete($prompt, ['max_tokens' => 2048]);
        \WP_CLI::log("");
        \WP_CLI::log($response);
    }

    private function gatherSiteContext(): string
    {
        $lines = [];
        $lines[] = "WordPress: " . get_bloginfo('version');
        $lines[] = "PHP: " . PHP_VERSION;
        $lines[] = "Theme: " . get_stylesheet();
        $lines[] = "Active plugins: " . count(get_option('active_plugins', []));

        if (class_exists('VLT\CacheManager\Diagnostics\CapabilityDetector')) {
            $caps = \VLT\CacheManager\Diagnostics\CapabilityDetector::detect();
            $lines[] = "Cache backend: " . \VLT\CacheManager\Diagnostics\CapabilityDetector::bestVolatileBackend();
            $lines[] = "Persistent store: " . \VLT\CacheManager\Diagnostics\CapabilityDetector::bestPersistentStore();
            $available = array_keys(array_filter($caps));
            $lines[] = "Extensions: " . implode(', ', $available);
        }

        if (class_exists('VLT\CacheManager\Log\Logger')) {
            $logger = new \VLT\CacheManager\Log\Logger();
            $stats = $logger->getTodayStats();
            $total = $stats['hits'] + $stats['misses'];
            $ratio = $total > 0 ? round($stats['hits'] / $total * 100, 1) : 0;
            $lines[] = "Cache hit ratio: {$ratio}% ({$stats['hits']} hits, {$stats['misses']} misses)";
        }

        return implode("\n", $lines);
    }
}
