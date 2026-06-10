<?php declare(strict_types=1);
namespace Gratis\AI;

/**
 * WordPress 7.0 Abilities API integration.
 * Registers AI abilities that can be invoked by any AI agent, browser extension, or MCP client.
 */
final class AbilitiesIntegration
{
    public static function register(): void
    {
        if (!function_exists('wp_register_ability_category') || !function_exists('wp_register_ability')) {
            return;
        }

        add_action('wp_abilities_api_categories_init', [__CLASS__, 'registerCategories']);
        add_action('wp_abilities_api_init', [__CLASS__, 'registerAbilities']);
    }

    public static function registerCategories(): void
    {
        wp_register_ability_category('gratis-ai', [
            'label'       => 'Gratis AI',
            'description' => 'AI-powered content and site management abilities',
        ]);
    }

    public static function registerAbilities(): void
    {
        wp_register_ability('gratis-ai/generate-content', [
            'label'       => 'Generate Content',
            'description' => 'Generate blog post content from a topic or prompt',
            'category'    => 'gratis-ai',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'topic' => ['type' => 'string', 'description' => 'Topic or title'],
                    'type'  => ['type' => 'string', 'enum' => ['post', 'page', 'pattern']],
                ],
                'required' => ['topic'],
            ],
            'output_schema' => [
                'type' => 'object',
                'properties' => [
                    'content' => ['type' => 'string'],
                    'title'   => ['type' => 'string'],
                ],
            ],
            'callback'            => [__CLASS__, 'generateContent'],
            'permission_callback' => fn() => current_user_can('edit_posts'),
        ]);

        wp_register_ability('gratis-ai/analyze-seo', [
            'label'       => 'Analyze SEO',
            'description' => 'Get AI-powered SEO recommendations for a URL',
            'category'    => 'gratis-ai',
            'input_schema' => [
                'type' => 'object',
                'properties' => ['url' => ['type' => 'string']],
                'required' => ['url'],
            ],
            'callback'            => [__CLASS__, 'analyzeSEO'],
            'permission_callback' => fn() => current_user_can('manage_options'),
        ]);

        wp_register_ability('gratis-ai/cache-recommendations', [
            'label'       => 'Cache Recommendations',
            'description' => 'Get cache optimization recommendations based on detected capabilities',
            'category'    => 'gratis-ai',
            'callback'            => [__CLASS__, 'cacheRecommendations'],
            'permission_callback' => fn() => current_user_can('manage_options'),
        ]);
    }

    public static function generateContent(array $input): array
    {
        $provider = new Provider\OpenAICompatible();
        if (!$provider->isAvailable()) {
            return ['content' => '', 'title' => $input['topic'], 'error' => 'No AI provider configured'];
        }
        $content = $provider->complete("Generate a WordPress post about: {$input['topic']}. Use Gutenberg blocks.", ['max_tokens' => 1024]);
        return ['content' => $content, 'title' => $input['topic']];
    }

    public static function analyzeSEO(array $input): array
    {
        $provider = new Provider\OpenAICompatible();
        if (!$provider->isAvailable()) return ['score' => 0, 'recommendations' => ['Configure AI provider']];
        $html = wp_remote_retrieve_body(wp_remote_get($input['url'], ['timeout' => 10]));
        $result = $provider->complete("Analyze SEO for this content (first 2000 chars): " . substr(strip_tags($html), 0, 2000), ['max_tokens' => 500]);
        return ['score' => 75, 'recommendations' => explode("\n", $result)];
    }

    public static function cacheRecommendations(array $input): array
    {
        $recs = ['Enable page caching for anonymous visitors'];
        if (class_exists('VLT\CacheManager\Diagnostics\CapabilityDetector')) {
            $caps = \VLT\CacheManager\Diagnostics\CapabilityDetector::detect();
            if (!($caps['ext-redis'] ?? false)) $recs[] = 'Install Redis for object caching';
            if (!($caps['ext-igbinary'] ?? false)) $recs[] = 'Install igbinary for faster serialization';
        }
        return ['recommendations' => $recs];
    }
}
