<?php declare(strict_types=1);
namespace Gratis\AI;

use Gratis\AI\Provider\OpenAICompatible;

/**
 * Model router: tries user's configured model first, falls back to free providers.
 *
 * Priority:
 * 1. User's own model (BYOM - any OpenAI-compatible endpoint)
 * 2. Free tier providers (Groq free, Cerebras free, Together.ai free, HuggingFace)
 * 3. Local model (Ollama at localhost:11434)
 */
final class ModelRouter {

    /** @var array Free providers that offer API access without payment */
    private const FREE_PROVIDERS = [
        'groq' => [
            'endpoint' => 'https://api.groq.com/openai/v1',
            'model'    => 'llama-3.3-70b-versatile',
            'key_name' => 'groq_api_key',
            'signup'   => 'https://console.groq.com (free, no credit card)',
        ],
        'cerebras' => [
            'endpoint' => 'https://api.cerebras.ai/v1',
            'model'    => 'llama-3.3-70b',
            'key_name' => 'cerebras_api_key',
            'signup'   => 'https://cloud.cerebras.ai (free tier)',
        ],
        'together' => [
            'endpoint' => 'https://api.together.xyz/v1',
            'model'    => 'meta-llama/Llama-3.3-70B-Instruct-Turbo',
            'key_name' => 'together_api_key',
            'signup'   => 'https://api.together.xyz (free $5 credit)',
        ],
        'openrouter' => [
            'endpoint' => 'https://openrouter.ai/api/v1',
            'model'    => 'meta-llama/llama-3.3-70b-instruct:free',
            'key_name' => 'openrouter_api_key',
            'signup'   => 'https://openrouter.ai (free models available)',
        ],
    ];

    /** Get the best available provider. */
    public static function resolve(): OpenAICompatible {
        // 1. User's own configured endpoint
        $userEndpoint = get_option('gratis_ai_endpoint', '');
        $userKey = get_option('gratis_ai_api_key', '');
        if ($userEndpoint && $userKey) {
            return new OpenAICompatible($userEndpoint, $userKey, get_option('gratis_ai_model', 'gpt-4o-mini'));
        }

        // 2. Try free providers (check KeyVault for stored keys)
        foreach (self::FREE_PROVIDERS as $name => $config) {
            $key = KeyVault::get($config['key_name']);
            if ($key) {
                return new OpenAICompatible($config['endpoint'], $key, $config['model']);
            }
        }

        // 3. Local Ollama
        $localEndpoint = 'http://localhost:11434/v1';
        return new OpenAICompatible($localEndpoint, '', 'llama3');
    }

    /** Get info about available providers. */
    public static function providers(): array {
        $result = [];

        // User's own
        $userEndpoint = get_option('gratis_ai_endpoint', '');
        if ($userEndpoint) {
            $result['custom'] = ['endpoint' => $userEndpoint, 'model' => get_option('gratis_ai_model', ''), 'status' => 'configured'];
        }

        // Free providers
        foreach (self::FREE_PROVIDERS as $name => $config) {
            $key = KeyVault::get($config['key_name']);
            $result[$name] = [
                'endpoint' => $config['endpoint'],
                'model'    => $config['model'],
                'status'   => $key ? 'key stored' : 'no key',
                'signup'   => $config['signup'],
            ];
        }

        // Local
        $result['local'] = ['endpoint' => 'http://localhost:11434/v1', 'model' => 'llama3', 'status' => 'fallback'];

        return $result;
    }

    /** Get free provider signup instructions. */
    public static function freeProviderGuide(): string {
        $guide = "Free AI providers (no credit card required):\n\n";
        foreach (self::FREE_PROVIDERS as $name => $config) {
            $guide .= "  {$name}:\n";
            $guide .= "    Signup: {$config['signup']}\n";
            $guide .= "    Model: {$config['model']}\n";
            $guide .= "    Store key: wp gratis-ai key set {$config['key_name']} <your-key>\n\n";
        }
        return $guide;
    }
}
