<?php declare(strict_types=1);

namespace Gratis\AI;

defined('ABSPATH') || exit;

/**
 * Content generation REST endpoint.
 *
 * Exposes a simple {@see POST /wp-json/gratis-ai/v1/generate} route that the
 * Gratis Builder (and other clients) can call to generate post/page/pattern
 * content through the configured {@see ModelRouter} provider.
 */
final class ContentGenerator {

    /** Register the REST route. */
    public static function register(): void {
        register_rest_route(
            'gratis-ai/v1',
            '/generate',
            [
                'methods'             => 'POST',
                'callback'            => [__CLASS__, 'handle'],
                'permission_callback' => [__CLASS__, 'can_generate'],
                'args'                => [
                    'prompt'     => [
                        'type'              => 'string',
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ],
                    'type'       => [
                        'type'              => 'string',
                        'default'           => 'post',
                        'sanitize_callback' => 'sanitize_key',
                    ],
                    'max_tokens' => [
                        'type'              => 'integer',
                        'default'           => 1024,
                        'sanitize_callback' => 'absint',
                    ],
                ],
            ]
        );
    }

    /** Only users who can author content may generate it. */
    public static function can_generate(): bool {
        return current_user_can('edit_posts');
    }

    /**
     * Generate content from a prompt.
     */
    public static function handle(\WP_REST_Request $request): \WP_REST_Response {
        $prompt     = (string) $request->get_param('prompt');
        $type       = (string) $request->get_param('type');
        $max_tokens = (int) $request->get_param('max_tokens');
        $max_tokens = $max_tokens > 0 ? min($max_tokens, 4096) : 1024;

        if (!in_array($type, ['post', 'page', 'pattern'], true)) {
            $type = 'post';
        }

        $provider = ModelRouter::resolve();
        if (!$provider->isAvailable()) {
            return new \WP_REST_Response(
                ['error' => __('No AI provider is configured. Add a provider in Gratis AI settings.', 'gratis-ai')],
                503
            );
        }

        $instruction = sprintf(
            'Generate a WordPress %s using Gutenberg block markup for the following request: %s',
            $type,
            $prompt
        );
        $content = $provider->complete($instruction, ['max_tokens' => $max_tokens]);

        return new \WP_REST_Response(
            [
                'type'    => $type,
                'title'   => wp_trim_words($prompt, 12, ''),
                'content' => $content,
            ],
            200
        );
    }
}
