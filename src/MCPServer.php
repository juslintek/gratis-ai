<?php declare(strict_types=1);
namespace Gratis\AI;

/**
 * MCP (Model Context Protocol) Server.
 * Exposes WordPress site data as tools/resources for AI agents.
 * Compatible with Claude, Cursor, Kiro, and any MCP client.
 *
 * Endpoint: /wp-json/gratis-ai/v1/mcp
 */
final class MCPServer {

    public static function register(): void {
        register_rest_route('gratis-ai/v1', '/mcp/tools', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'listTools'],
            'permission_callback' => [__CLASS__, 'authenticate'],
        ]);

        register_rest_route('gratis-ai/v1', '/mcp/execute', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'executeTool'],
            'permission_callback' => [__CLASS__, 'authenticate'],
        ]);

        register_rest_route('gratis-ai/v1', '/mcp/resources', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'listResources'],
            'permission_callback' => [__CLASS__, 'authenticate'],
        ]);
    }

    public static function authenticate(\WP_REST_Request $request): bool {
        // Accept Bearer token or X-API-Key header
        $token = $request->get_header('Authorization');
        if ($token && str_starts_with($token, 'Bearer ')) {
            $token = substr($token, 7);
        } else {
            $token = $request->get_header('X-API-Key') ?? '';
        }
        $stored = get_option('gratis_ai_mcp_key', '');
        if (!$stored) {
            $stored = wp_generate_password(32, false);
            update_option('gratis_ai_mcp_key', $stored);
        }
        return $token && hash_equals($stored, $token);
    }

    public static function listTools(): \WP_REST_Response {
        return new \WP_REST_Response(['tools' => [
            ['name' => 'get_posts', 'description' => 'Get recent posts', 'inputSchema' => ['type' => 'object', 'properties' => ['count' => ['type' => 'integer'], 'type' => ['type' => 'string']]]],
            ['name' => 'create_post', 'description' => 'Create a new post', 'inputSchema' => ['type' => 'object', 'properties' => ['title' => ['type' => 'string'], 'content' => ['type' => 'string'], 'status' => ['type' => 'string']], 'required' => ['title']]],
            ['name' => 'get_site_info', 'description' => 'Get site information and health', 'inputSchema' => ['type' => 'object', 'properties' => []]],
            ['name' => 'search', 'description' => 'Search site content', 'inputSchema' => ['type' => 'object', 'properties' => ['query' => ['type' => 'string']], 'required' => ['query']]],
            ['name' => 'purge_cache', 'description' => 'Purge all caches', 'inputSchema' => ['type' => 'object', 'properties' => []]],
            ['name' => 'get_analytics', 'description' => 'Get analytics stats', 'inputSchema' => ['type' => 'object', 'properties' => ['days' => ['type' => 'integer']]]],
            ['name' => 'ai_generate', 'description' => 'Generate content using AI', 'inputSchema' => ['type' => 'object', 'properties' => ['prompt' => ['type' => 'string']], 'required' => ['prompt']]],
        ],]);
    }

    public static function executeTool(\WP_REST_Request $request): \WP_REST_Response {
        $tool = $request->get_param('name');
        $input = $request->get_param('input') ?? [];

        $result = match ($tool) {
            'get_posts'     => self::toolGetPosts($input),
            'create_post'   => self::toolCreatePost($input),
            'get_site_info' => self::toolSiteInfo(),
            'search'        => self::toolSearch($input),
            'purge_cache'   => self::toolPurgeCache(),
            'get_analytics' => self::toolAnalytics($input),
            'ai_generate'   => self::toolAIGenerate($input),
            default         => ['error' => "Unknown tool: {$tool}"],
        };

        return new \WP_REST_Response(['result' => $result]);
    }

    public static function listResources(): \WP_REST_Response {
        return new \WP_REST_Response(['resources' => [
            ['uri' => 'site://info', 'name' => 'Site Information', 'mimeType' => 'application/json'],
            ['uri' => 'site://posts', 'name' => 'Recent Posts', 'mimeType' => 'application/json'],
            ['uri' => 'site://plugins', 'name' => 'Active Plugins', 'mimeType' => 'application/json'],
        ],]);
    }

    private static function toolGetPosts(array $input): array {
        $posts = get_posts(['post_type' => $input['type'] ?? 'post', 'numberposts' => $input['count'] ?? 10, 'post_status' => 'publish']);
        return array_map(fn($p) => ['id' => $p->ID, 'title' => $p->post_title, 'date' => $p->post_date, 'url' => get_permalink($p)], $posts);
    }

    private static function toolCreatePost(array $input): array {
        $id = wp_insert_post(['post_title' => $input['title'], 'post_content' => $input['content'] ?? '', 'post_status' => $input['status'] ?? 'draft', 'post_type' => 'post']);
        return ['id' => $id, 'url' => get_permalink($id)];
    }

    private static function toolSiteInfo(): array {
        return [
            'name' => get_bloginfo('name'), 'url' => home_url(),
            'wordpress' => get_bloginfo('version'), 'php' => PHP_VERSION,
            'theme' => get_stylesheet(), 'plugins' => count(get_option('active_plugins', [])),
        ];
    }

    private static function toolSearch(array $input): array {
        if (class_exists('Gratis\Cache\Search\SearchIndex')) {
            $results = \Gratis\Cache\Search\SearchIndex::search($input['query'] ?? '', 10);
            return array_map(fn($r) => ['id' => $r['post_id'], 'title' => get_the_title($r['post_id']), 'url' => get_permalink($r['post_id'])], $results);
        }
        $posts = get_posts(['s' => $input['query'] ?? '', 'numberposts' => 10]);
        return array_map(fn($p) => ['id' => $p->ID, 'title' => $p->post_title, 'url' => get_permalink($p)], $posts);
    }

    private static function toolPurgeCache(): array {
        do_action('gratis_cache_purge_all', 'mcp');
        return ['purged' => true];
    }

    private static function toolAnalytics(array $input): array {
        if (!class_exists('Gratis\Analytics\Store')) return ['error' => 'Analytics not active'];
        $days = $input['days'] ?? 7;
        return \Gratis\Analytics\Store::stats(gmdate('Y-m-d 00:00:00', strtotime("-{$days} days")), gmdate('Y-m-d 23:59:59'));
    }

    private static function toolAIGenerate(array $input): array {
        $provider = ModelRouter::resolve();
        if (!$provider->isAvailable()) return ['error' => 'No AI provider available'];
        $result = $provider->complete($input['prompt'] ?? '', ['max_tokens' => 1024]);
        return ['content' => $result];
    }
}
