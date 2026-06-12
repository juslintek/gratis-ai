<?php declare(strict_types=1);
namespace Gratis\AI\Provider;

use Gratis\AI\Contracts\ModelProviderInterface;

/** Works with any OpenAI-compatible API: OpenAI, Anthropic via proxy, Ollama, LM Studio, vLLM, etc. */
final class OpenAICompatible implements ModelProviderInterface {

    private string $endpoint;
    private string $apiKey;
    private string $model;

    public function __construct(?string $endpoint = null, ?string $apiKey = null, ?string $model = null) {
        $this->endpoint = $endpoint ?? get_option('gratis_ai_endpoint', 'http://localhost:11434/v1');
        $this->apiKey = $apiKey ?? get_option('gratis_ai_api_key', '');
        $this->model = $model ?? get_option('gratis_ai_model', 'gpt-4o-mini');
    }

    public function name(): string {
 return 'openai-compatible'; }

    public function isAvailable(): bool {
        return !empty($this->endpoint);
    }

    public function complete(string $prompt, array $options = []): string {
        $body = wp_json_encode([
            'model'    => $options['model'] ?? $this->model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'max_tokens' => $options['max_tokens'] ?? 1024,
        ]);

        $headers = ['Content-Type: application/json'];
        if ($this->apiKey) $headers[] = 'Authorization: Bearer ' . $this->apiKey;

        $response = wp_remote_post(rtrim($this->endpoint, '/') . '/chat/completions', [
            'headers' => $this->wpHeaders($headers),
            'body'    => $body,
            'timeout' => $options['timeout'] ?? 30,
        ]);

        if (is_wp_error($response)) {
            return 'Error: ' . $response->get_error_message();
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        return $data['choices'][0]['message']['content'] ?? 'No response';
    }

    public function stream(string $prompt, array $options = []): \Generator {
        // Streaming requires direct curl for SSE — yield complete response for now
        yield $this->complete($prompt, $options);
    }

    private function wpHeaders(array $raw): array {
        $h = [];
        foreach ($raw as $line) {
            [$k, $v] = explode(': ', $line, 2);
            $h[$k] = $v;
        }
        return $h;
    }
}
