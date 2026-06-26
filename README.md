# AI Connect Apple Store — Phase 2 (MCP)

The **AI-enabled** version of the AI Connect Apple Store demo. It contains the full
Phase 1 storefront **plus** a [Laravel MCP](https://github.com/laravel/mcp) server that
exposes the store's capabilities as tools an AI assistant (e.g. Claude Desktop) can call
with natural language.

Crucially, **no business logic was changed** to add AI — the MCP tools are thin wrappers
around the existing `ProductService`, `CartService`, `CheckoutService` and `OrderService`.

> For the storefront itself (catalog, cart, checkout, service-layer architecture, setup,
> branding), see the Phase 1 README in `../cart`. This README focuses on the MCP layer.

## MCP tools

Defined in `app/Mcp/Tools/`, registered by `app/Mcp/Servers/StoreServer.php`:

| Category | Tools |
|---|---|
| **Product** | `search_products`, `get_product_details`, `compare_products` |
| **Cart** | `add_to_cart`, `update_cart_quantity`, `remove_from_cart`, `clear_cart`, `view_cart` |
| **Checkout** | `checkout`, `get_order_details` |
| **Notification** | `send_telegram_confirmation` |

The cart is shared across tool calls via a stable session id (`MCP_CART_SESSION`, default
`mcp-assistant`). Prices are formatted in INR (₹).

**Example flow:** *"Add an iPhone 17 Pro 256GB and two MagSafe chargers, then check out."*
→ `search_products` → `add_to_cart` → `view_cart` → `checkout` → `send_telegram_confirmation`.

## Setup

```bash
composer setup
php artisan migrate:fresh --seed     # 61 Apple products
php artisan passport:keys            # OAuth signing keys (web server, see below)
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
    "ai-connect-store": {
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

## Telegram notifications

`send_telegram_confirmation` sends an order summary via the Telegram Bot API. Set the
credentials in `.env`:

```env
TELEGRAM_BOT_TOKEN=
TELEGRAM_CHAT_ID=
```

If they are unset, the tool returns the message it *would* send (so the demo still flows).

## Testing

```bash
php artisan test                         # full suite
php artisan test tests/Feature/Mcp       # MCP tool + OAuth tests
```

MCP tools are tested with the built-in harness, e.g.:

```php
StoreServer::tool(SearchProductsTool::class, ['query' => 'iPhone 17'])
    ->assertOk()
    ->assertSee('iPhone 17 Pro');
```

---

> Demo project for AI Connect Kerala. Not affiliated with Apple Inc.; product names and
> imagery © Apple.
