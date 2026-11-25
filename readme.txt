=== Gratis AI ===
Contributors: juslintek
Tags: ai, openai, mcp, content generation, assistant
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 2.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI assistant with bring-your-own-model, free model fallback, MCP server, and encrypted key storage.

== Description ==

AI assistant with bring-your-own-model, free model fallback, MCP server, and encrypted key storage.

Part of the Gratis suite — a collection of free, no-upsell WordPress plugins.

**Features:**

* Bring your own model (OpenAI-compatible providers)
* Free model fallback when no key is configured
* Built-in MCP server and WordPress Abilities API integration
* API keys encrypted at rest using site salts
* Content-generation REST endpoint for the page builder

**Philosophy:**

Everything is free. No "Pro" version, no locked features, no nag screens. Optional managed cloud services may be offered separately to cover infrastructure costs.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Configure it from its settings screen in the WordPress admin.

== Frequently Asked Questions ==

= Is this plugin really free? =

Yes. Everything is free — no "Pro" version, no locked features, and no nag screens. Optional managed cloud services may be offered separately to cover infrastructure costs.

= Does this plugin make external HTTP requests? =

Only when you configure an AI provider endpoint/key and invoke content generation. The plugin does not phone home or send analytics.

= Where do I get support? =

Open an issue on the plugin's GitHub repository.

== Third-Party Services ==

This plugin sends prompts and content context to external OpenAI-compatible AI providers only when you configure an API key and use content generation features.

= Supported OpenAI-compatible AI providers =

* OpenAI (`https://api.openai.com/v1`): [Terms of Use](https://openai.com/policies/terms-of-use) | [Privacy Policy](https://openai.com/policies/privacy-policy)
* Groq (`https://api.groq.com/openai/v1`): [Terms of Use](https://groq.com/terms-of-use/) | [Privacy Policy](https://groq.com/privacy-policy/)
* Cerebras (`https://api.cerebras.ai/v1`): [Terms of Service](https://cerebras.ai/terms-of-service) | [Privacy Policy](https://cerebras.ai/privacy-policy)
* Together AI (`https://api.together.xyz/v1`): [Terms of Service](https://www.together.ai/terms-of-service) | [Privacy Policy](https://www.together.ai/privacy)
* OpenRouter (`https://openrouter.ai/api/v1`): [Terms of Service](https://openrouter.ai/terms) | [Privacy Policy](https://openrouter.ai/privacy)
* Self-hosted OpenAI-compatible endpoints (Ollama, LM Studio, vLLM): governed by your own infrastructure policy.

No data is sent to any external service unless you explicitly configure an API endpoint and key in the plugin settings. The plugin does not phone home or transmit analytics.

== Changelog ==

= 2.0.0 =
* Initial public release.

== Upgrade Notice ==

= 2.0.0 =
Initial public release.
