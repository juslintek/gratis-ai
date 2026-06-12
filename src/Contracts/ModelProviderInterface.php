<?php declare(strict_types=1);
namespace Gratis\AI\Contracts;

interface ModelProviderInterface {

    public function name(): string;
    public function isAvailable(): bool;
    public function complete(string $prompt, array $options = []): string;
    public function stream(string $prompt, array $options = []): \Generator;
}
