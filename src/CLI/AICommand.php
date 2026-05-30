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

    /**
     * Generate a blog post draft.
     *
     * ## OPTIONS
     * <topic>
     * : Topic or title for the post.
     * [--publish]
     * : Publish immediately instead of draft.
     * [--model=<model>]
     * : Override model.
     *
     * ## EXAMPLES
     *     wp gratis-ai generate-post "How to optimize WordPress database"
     *
     * @subcommand generate-post
     * @when after_wp_load
     */
    public function generate_post($args, $assoc_args): void
    {
        $provider = new OpenAICompatible();
        if (!$provider->isAvailable()) {
            \WP_CLI::error("No AI provider configured.");
        }

        $topic = $args[0];
        $prompt = "Write a WordPress blog post about: {$topic}\n\nFormat as WordPress block editor HTML (Gutenberg blocks). Include:\n- An engaging introduction paragraph\n- 2-3 H2 sections with content\n- A code block if relevant\n- A conclusion\n\nUse <!-- wp:paragraph --> and <!-- wp:heading --> block syntax. Keep it under 800 words.";

        \WP_CLI::log("Generating post about: {$topic}...");
        $content = $provider->complete($prompt, array_merge($assoc_args, ['max_tokens' => 2048]));

        $status = isset($assoc_args['publish']) ? 'publish' : 'draft';
        $post_id = wp_insert_post([
            'post_title'   => $topic,
            'post_content' => $content,
            'post_status'  => $status,
            'post_type'    => 'post',
        ]);

        if (is_wp_error($post_id)) {
            \WP_CLI::error("Failed to create post: " . $post_id->get_error_message());
        }

        \WP_CLI::success("Created post #{$post_id} ({$status}): " . get_permalink($post_id));
    }

    /**
     * Generate a block pattern.
     *
     * ## OPTIONS
     * <description>
     * : What the pattern should look like.
     * [--name=<name>]
     * : Pattern file name (without .php).
     *
     * ## EXAMPLES
     *     wp gratis-ai generate-pattern "testimonials grid with 3 cards, dark theme"
     *
     * @subcommand generate-pattern
     * @when after_wp_load
     */
    public function generate_pattern($args, $assoc_args): void
    {
        $provider = new OpenAICompatible();
        if (!$provider->isAvailable()) {
            \WP_CLI::error("No AI provider configured.");
        }

        $desc = $args[0];
        $prompt = "Generate a WordPress block pattern for: {$desc}\n\nOutput ONLY the PHP file content with the pattern header comment and block markup. Use this format:\n<?php\n/**\n * Title: [title]\n * Slug: gratis-theme/[slug]\n * Categories: gratis-builder\n */\n?>\n[block markup here]\n\nUse dark theme colors (#0d0f12 background, #ffffff text, #22C55E accent). Use proper WordPress block comments.";

        \WP_CLI::log("Generating pattern: {$desc}...");
        $content = $provider->complete($prompt, ['max_tokens' => 2048]);

        $name = $assoc_args['name'] ?? sanitize_title($desc);
        $path = get_stylesheet_directory() . "/patterns/{$name}.php";

        if (file_exists($path)) {
            \WP_CLI::error("Pattern already exists: {$path}");
        }

        file_put_contents($path, $content);
        \WP_CLI::success("Pattern saved: {$path}");
    }

    /**
     * Rewrite/improve existing content.
     *
     * ## OPTIONS
     * <post_id>
     * : Post ID to rewrite.
     * [--instruction=<instruction>]
     * : How to rewrite (default: improve clarity and SEO).
     *
     * ## EXAMPLES
     *     wp gratis-ai rewrite 42 --instruction="make it more concise"
     *
     * @when after_wp_load
     */
    public function rewrite($args, $assoc_args): void
    {
        $provider = new OpenAICompatible();
        if (!$provider->isAvailable()) {
            \WP_CLI::error("No AI provider configured.");
        }

        $post = get_post((int) $args[0]);
        if (!$post) {
            \WP_CLI::error("Post not found.");
        }

        $instruction = $assoc_args['instruction'] ?? 'Improve clarity, readability, and SEO. Keep the same structure and block format.';
        $prompt = "Rewrite this WordPress post content. Instruction: {$instruction}\n\nOriginal content:\n{$post->post_content}\n\nOutput the improved content in the same WordPress block format.";

        \WP_CLI::log("Rewriting post #{$post->ID}: {$post->post_title}...");
        $content = $provider->complete($prompt, ['max_tokens' => 3000]);

        wp_update_post(['ID' => $post->ID, 'post_content' => $content]);
        \WP_CLI::success("Post #{$post->ID} rewritten. Review at: " . get_edit_post_link($post->ID, 'raw'));
    }
}
