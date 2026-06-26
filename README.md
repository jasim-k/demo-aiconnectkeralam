# AI Connect Apple Store — Phase 1 (Storefront)

An AI-Powered Apple Store demo for **AI Connect Kerala**, built to show how a clean,
conventional Laravel application is structured before being AI-enabled.

This repository is **Phase 1**: a complete, AI-free e-commerce storefront. The
**Phase 2** companion (`../cart-mcp`) exposes this same business logic to an AI
assistant over the Model Context Protocol (MCP).

## Tech stack

- **Backend:** Laravel 13, PHP 8.3+, SQLite (MySQL-ready)
- **Frontend:** React 19 + TypeScript, Inertia.js v3, Tailwind CSS v4
- **Auth:** Laravel Fortify (login, registration, profile, 2FA scaffolding)
- **Tooling:** Pest, Larastan (level 7), Pint, ESLint, Prettier, Wayfinder

## Features

- **Home** — hero, featured products, latest arrivals, "shop by series" tiles
- **Catalog** — full-text search, filters (series / storage / colour), sorting, pagination
- **Product detail** — gallery, variant switching (storage & colour), stock status, quantity selector
- **Cart** — session-based; add / update / remove / clear, live grand total, navbar badge
- **Checkout** — **requires login**; validates cart & stock, creates the order, deducts stock, clears the cart (atomic transaction)
- **Order success** — order number, summary, session-guarded

The catalog seeds **61 real Apple products** (iPhone 15 / 16 / 17 lines + accessories)
with genuine product imagery and INR pricing.

## Architecture

Business logic lives in a **service layer**; controllers stay thin.

```
app/
├── Models/         Product, Cart, CartItem, Order, OrderItem
├── Services/       ProductService, CartService, CheckoutService, OrderService
└── Http/
    ├── Controllers/  Home, Product, Cart, Checkout
    └── Requests/      AddToCart, UpdateCart, Checkout
resources/js/
├── pages/store/    home, products, product, cart, checkout, order-success
├── layouts/        store-layout (storefront), auth-layout, settings
└── components/store/  product-card, product-image
```

## Getting started

```bash
# One-shot setup (install, env, key, migrate, build)
composer setup

# Seed the Apple catalog
php artisan migrate:fresh --seed

# Run everything (server + Vite + queue + logs)
composer dev
```

Then visit the URL printed by `php artisan serve` (default `http://localhost:8000`).

> SQLite is used by default (`database/database.sqlite`). To use MySQL, set the
> `DB_*` values in `.env` and re-run `php artisan migrate:fresh --seed`.

## Testing & quality

```bash
php artisan test          # Pest feature tests
composer lint             # Pint (format)
composer types:check      # Larastan (PHP static analysis)
npm run types:check       # TypeScript
npm run lint              # ESLint
```

## Branding & theme

The store is themed for **AI Connect Kerala** — brand blue (`#1877f2`), the AI Connect
logo, and a light-only theme. The storefront is intentionally Apple-inspired (clean,
rounded, generous whitespace).

## Phase 2 — AI enablement

See **`../cart-mcp`** for the MCP server that exposes search, cart, checkout, and order
tools to an AI assistant (e.g. Claude Desktop) — without modifying any of the business
logic in this project.

---

> Demo project for AI Connect Kerala. Not affiliated with Apple Inc.; product names and
> imagery © Apple.
