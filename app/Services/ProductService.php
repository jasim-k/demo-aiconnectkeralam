<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class ProductService
{
    /**
     * Allowed sort options mapped to [column, direction].
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const SORTS = [
        'price_asc' => ['price', 'asc'],
        'price_desc' => ['price', 'desc'],
        'newest' => ['created_at', 'desc'],
        'name' => ['name', 'asc'],
    ];

    /**
     * Paginated, filtered and sorted catalog listing.
     *
     * @param  array{search?: string|null, series?: string|null, storage?: string|null, color?: string|null, price_min?: int|null, price_max?: int|null, sort?: string|null}  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function catalog(array $filters): LengthAwarePaginator
    {
        $query = Product::query();

        $this->applyFilters($query, $filters);

        [$column, $direction] = self::SORTS[$filters['sort'] ?? ''] ?? self::SORTS['newest'];
        $query->orderBy($column, $direction);

        return $query->paginate(12)->withQueryString();
    }

    /**
     * Free-text search used by the catalog and the Phase 2 MCP tool.
     *
     * @return Collection<int, Product>
     */
    public function search(string $term, int $limit = 20): Collection
    {
        return Product::query()
            ->where(function (Builder $query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('model', 'like', "%{$term}%")
                    ->orWhere('series', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('color', 'like', "%{$term}%");
            })
            ->orderBy('price')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    public function featured(int $limit = 4): Collection
    {
        return Product::query()->featured()->latest()->limit($limit)->get();
    }

    /**
     * @return Collection<int, Product>
     */
    public function latest(int $limit = 8): Collection
    {
        return Product::query()->latest()->limit($limit)->get();
    }

    /**
     * The cheapest variant of a given model, used as a hero/spotlight product.
     */
    public function firstByModel(string $model): ?Product
    {
        return Product::query()->where('model', $model)->orderBy('price')->first();
    }

    /**
     * Distinct filter option values available for the catalog sidebar.
     *
     * @return array{series: SupportCollection<int, string>, storage: SupportCollection<int, string>, color: SupportCollection<int, string>, price_min: int, price_max: int}
     */
    public function filterOptions(): array
    {
        return [
            'series' => Product::query()->distinct()->orderBy('series')->pluck('series'),
            'storage' => Product::query()->whereNotNull('storage')->distinct()->pluck('storage'),
            'color' => Product::query()->whereNotNull('color')->distinct()->orderBy('color')->pluck('color'),
            'price_min' => (int) Product::query()->min('price'),
            'price_max' => (int) Product::query()->max('price'),
        ];
    }

    /**
     * @param  Builder<Product>  $query
     * @param  array{search?: string|null, series?: string|null, storage?: string|null, color?: string|null, price_min?: int|null, price_max?: int|null}  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhere('series', 'like', "%{$search}%");
                });
            })
            ->when($filters['series'] ?? null, fn (Builder $query, string $series) => $query->where('series', $series))
            ->when($filters['storage'] ?? null, fn (Builder $query, string $storage) => $query->where('storage', $storage))
            ->when($filters['color'] ?? null, fn (Builder $query, string $color) => $query->where('color', $color))
            ->when($filters['price_min'] ?? null, fn (Builder $query, int $min) => $query->where('price', '>=', $min))
            ->when($filters['price_max'] ?? null, fn (Builder $query, int $max) => $query->where('price', '<=', $max));
    }
}
