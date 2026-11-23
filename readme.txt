=== Gratis AI ===
Contributors: juslintek
Tags: ai, openai, mcp, content generation, assistant
Requires at least: 6.0
Tested up to: 6.7
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

No. Unless you explicitly configure an external service (such as a CDN or SMTP host), the plugin does not phone home or send analytics.

= Where do I get support? =

Open an issue on the plugin's GitHub repository.

== Third-Party Services ==

This plugin sends data to external AI providers when you configure an API key and use content generation features.

= OpenAI-compatible API providers =

When configured, your prompts and content context are sent to the configured endpoint (e.g. OpenAI, Anthropic, or a self-hosted model) for AI processing.

* OpenAI: [Terms of Use](https://openai.com/policies/terms-of-use) | [Privacy Policy](https://openai.com/policies/privacy-policy)
* Anthropic: [Terms of Service](https://www.anthropic.com/legal/consumer-terms) | [Privacy Policy](https://www.anthropic.com/legal/privacy)

No data is sent to any external service unless you explicitly configure an API endpoint and key in the plugin settings. The plugin does not phone home or transmit analytics.

== Changelog ==

= 2.0.0 =
* Initial public release.

== Upgrade Notice ==

= 2.0.0 =
Initial public release.
