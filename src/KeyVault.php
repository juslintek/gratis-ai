<?php declare(strict_types=1);
namespace Gratis\AI;

/**
 * Encrypted API key storage.
 * Keys are encrypted at rest using WordPress AUTH_KEY as the encryption key.
 * Safe to version control the encrypted values.
 */
final class KeyVault {

    private const OPTION = 'gratis_ai_keys';

    public static function set(string $name, string $value): void {
        $keys = get_option(self::OPTION, []);
        $keys[$name] = self::encrypt($value);
        update_option(self::OPTION, $keys);
    }

    public static function get(string $name): string {
        $keys = get_option(self::OPTION, []);
        if (!isset($keys[$name])) return '';
        return self::decrypt($keys[$name]);
    }

    public static function list(): array {
        $keys = get_option(self::OPTION, []);
        return array_map(fn($v) => '***' . substr(self::decrypt($v), -4), $keys);
    }

    public static function delete(string $name): void {
        $keys = get_option(self::OPTION, []);
        unset($keys[$name]);
        update_option(self::OPTION, $keys);
    }

    /** Export encrypted keys (safe for version control). */
    public static function export(): string {
        return (string) wp_json_encode(get_option(self::OPTION, []), JSON_PRETTY_PRINT);
    }

    /** Import encrypted keys. */
    public static function import(string $json): int {
        $keys = json_decode($json, true);
        if (!is_array($keys)) return 0;
        update_option(self::OPTION, $keys);
        return count($keys);
    }

    private static function encrypt(string $value): string {
        $key = self::deriveKey();
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($value, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        // base64 is used to safely transport the binary IV+ciphertext, not to obfuscate code.
        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
        return base64_encode($iv . $encrypted);
    }

    private static function decrypt(string $value): string {
        $key = self::deriveKey();
        // base64 decodes the binary IV+ciphertext blob produced by encrypt(), not obfuscated code.
        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
        $data = base64_decode($value);
        if (strlen($data) < 17) {
            return '';
        }
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv) ?: '';
    }

    private static function deriveKey(): string {
        // Use WordPress AUTH_KEY as base, derive a proper key
        $base = defined('AUTH_KEY') ? AUTH_KEY : 'gratis-default-key-change-me';
        return hash('sha256', $base, true);
    }
}
