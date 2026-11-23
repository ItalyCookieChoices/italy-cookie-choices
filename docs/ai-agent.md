# Configure Cookie Choices with an AI agent

[Leggi in italiano](it/agente-ai.md).

An AI assistant, like Claude, can set up Cookie Choices from start to end: it scans the site, blocks the vendors it finds, writes the texts and the links of the banner, imports the settings of another consent plugin and tells you what is still missing. It works with the [abilities](abilities.md) of the plugin, through the [MCP Adapter](https://github.com/WordPress/mcp-adapter) of WordPress.

## What you need

* WordPress 6.9 or later, with the Abilities API.
* The MCP Adapter plugin, installed and active.
* An administrator account: the abilities run as the user who connects, only with the `manage_options` capability.
* An AI client that supports MCP, like Claude Desktop or Claude Code.

## Connect the AI client

The MCP Adapter has a default server that shows the public abilities of the plugins. Connect your client in one of two ways, the [CLI usage guide](https://github.com/WordPress/mcp-adapter/blob/trunk/docs/guides/cli-usage.md) of the MCP Adapter has the configuration of each client:

* **On your computer, with WP-CLI**: the client runs `wp mcp-adapter serve --user=admin` in the folder of the site.
* **On a remote site, over HTTP**: the client runs the `@automattic/mcp-wordpress-remote` proxy with the address `https://example.com/wp-json/mcp/mcp-adapter-default-server`, your username and an [application password](https://make.wordpress.org/core/2020/11/05/application-passwords-integration-guide/).

The client finds the abilities of the **Cookie Choices** category with the `mcp-adapter/discover-abilities` tool, and runs them with `mcp-adapter/execute-ability`.

## An example prompt

```text
Configure Cookie Choices on my site:
1. Check get-status and tell me what is missing.
2. If list-migration-sources finds another consent plugin, show me what the import changes and wait for my OK.
3. Scan the home page and the pages with scan-site, then block the vendors it finds with switch-vendor.
4. For the unknown third parties, tell me which ones set cookies before adding them as custom vendors.
5. Set the policy link to /privacy-policy/ and turn on the consent log with update-settings.
6. Check get-status again and give me a short summary.
```

## Keep the control

* Read before you approve: the AI client asks before it runs an ability that changes the site, and the abilities say whether they only read or can replace what is saved.
* The changes are the same as on the settings page, checked in the same way: open the settings page to review them.
* `update-settings` and `import-settings` can replace the saved texts: export the settings first from **Tools**, to restore them if needed.
* The AI agent does not write your cookie policy and it is not legal advice: check with your consultant what your site needs.
