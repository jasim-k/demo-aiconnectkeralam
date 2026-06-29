# AI Connect Kerala Store — MCP + Telegram AI Assistant

The **AI-enabled** version of the AI Connect Kerala Store demo. It contains the full
storefront **plus** a [Laravel MCP](https://github.com/laravel/mcp) server that exposes the
store's capabilities as tools an AI assistant can call with natural language — and a
**conversational Telegram shopping assistant** built on the official
[Laravel AI SDK](https://github.com/laravel/ai).

Crucially, **no business logic was changed** to add AI — the tools are thin wrappers around
the existing `ProductService`, `CartService`, `CheckoutService` and `OrderService`. The
Telegram assistant **reuses the exact same MCP tools** (the AI SDK runs them in-process), so
there is a single source of truth for store behaviour.

## MCP tools

Defined in `app/Mcp/Tools/`, registered by `app/Mcp/Servers/StoreServer.php`:

| Category | Tools |
|---|---|
| **Product** | `search_products`, `get_product_details`, `compare_products` |
| **Cart** | `add_to_cart`, `update_cart_quantity`, `remove_from_cart`, `clear_cart`, `view_cart` |
| **Checkout** | `checkout`, `get_order_details` |
| **Notification** | `send_telegram_confirmation` |

The cart is shared across tool calls via a stable session id (`MCP_CART_SESSION`, default
`mcp-assistant`), namespaced per authenticated customer. Prices are formatted in INR (₹).

**Example flow:** *"Add an iPhone 17 Pro 256GB and two MagSafe chargers, then check out."*
→ `search_products` → `add_to_cart` → `view_cart` → `checkout` → `send_telegram_confirmation`.

## Setup

```bash
composer setup
php artisan migrate:fresh --seed     # Apple catalog + AI conversation tables
php artisan passport:keys            # OAuth signing keys (web MCP server, see below)
```

## Running the MCP server

The server is registered twice in `routes/ai.php`:

### Local (stdio) — for Claude Desktop

```bash
php artisan mcp:start store
```

Add to your `claude_desktop_config.json`:

```json
{
  "mcpServers": {
    "ai-connect-kerala-store": {
      "command": "php",
      "args": ["artisan", "mcp:start", "store"],
      "cwd": "/absolute/path/to/cart-mcp"
    }
  }
}
```

The local server is a trusted subprocess and is **not** behind OAuth.

### Web (HTTP) — for remote clients, protected by OAuth 2.1

```
POST /mcp/store        # protected by auth:api (Passport)
```

Unauthenticated requests receive `401` with a `WWW-Authenticate` header pointing at the
OAuth discovery document, so MCP clients can complete the flow automatically:

- Discovery: `/.well-known/oauth-authorization-server`, `/.well-known/oauth-protected-resource`
- Dynamic client registration: `POST /oauth/register`
- Authorize / token: `/oauth/authorize`, `/oauth/token`

OAuth is provided by **Laravel Passport** as a translation layer to the underlying user
(single `mcp:use` scope). Configure it via:

- `config/auth.php` — the `api` guard (`driver: passport`)
- `App\Models\User` — `HasApiTokens` + `OAuthenticatable`
- `AppServiceProvider` — `Passport::authorizationView('mcp.authorize')`

### Inspect / debug

```bash
php artisan mcp:inspector store        # local server
php artisan mcp:inspector store-web    # web server (supply a bearer token)
```

## Telegram AI shopping assistant

A customer can chat with the store directly inside Telegram. Messages are handled by an
agent (`app/Ai/Agents/TelegramShopAssistant.php`) built on the **Laravel AI SDK**. The agent
hands the model the **same MCP tools** as above — the SDK detects each Laravel MCP tool and
runs it in-process, so there is no second implementation and no HTTP round-trip.

**Authentication is the Telegram pairing**, not OAuth:

1. The customer generates a pairing code from **Settings → Telegram** (a QR code and an
   "Open in Telegram" deep link are shown alongside it).
2. They send `/pair <code>` (or scan the QR, which sends `/start <code>`) to the bot.
3. The bot links their `telegram_chat_id` to their account.
4. From then on, any free-text message is authenticated as that customer
   (`TelegramAssistantService` resolves the paired user and runs the agent on their behalf),
   so the assistant's cart and orders are scoped to them. Conversation context carries across
   messages via the AI SDK's `agent_conversations` tables.

### Configuration

```env
# Telegram
TELEGRAM_BOT_TOKEN=
TELEGRAM_BOT_USERNAME=         # required for the deep link + QR code
TELEGRAM_WEBHOOK_SECRET=
TELEGRAM_PAIR_CODE_TTL=15

# OpenAI (powers the assistant; provider is configured in config/ai.php)
OPENAI_API_KEY=
OPENAI_MODEL=gpt-4o-mini
OPENAI_BASE_URL=https://api.openai.com/v1
```

### Webhook

Telegram delivers updates to a public HTTPS endpoint:

```
POST /api/telegram/webhook        # telegram.webhook
```

Register it with the Bot API (uses `APP_URL`, attaches `TELEGRAM_WEBHOOK_SECRET`):

```bash
php artisan telegram:set-webhook                 # uses the telegram.webhook route
php artisan telegram:set-webhook --info          # show current webhook
php artisan telegram:set-webhook --delete        # remove it
```

`send_telegram_confirmation` sends the order summary to the paired chat after checkout.

## Testing

```bash
php artisan test                         # full suite
php artisan test tests/Feature/Mcp       # MCP tool + OAuth tests
php artisan test tests/Feature/Telegram  # pairing, webhook + AI assistant tests
```

MCP tools are tested with the built-in harness, e.g.:

```php
StoreServer::tool(SearchProductsTool::class, ['query' => 'iPhone 17'])
    ->assertOk()
    ->assertSee('iPhone 17 Pro');
```

The Telegram assistant is tested by faking the agent
(`TelegramShopAssistant::fake([...])`) and asserting the reply is delivered to the chat.

---

> Demo project for AI Connect Kerala. Not affiliated with Apple Inc.; product names and
> imagery © Apple.
