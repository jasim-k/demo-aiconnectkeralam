<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Definition of each phone line: storage => price (INR), available colors,
     * whether it should be surfaced as featured, and a marketing description.
     *
     * @var list<array{model: string, series: string, featured: bool, description: string, storages: array<string, int>, colors: list<string>}>
     */
    private array $phones = [
        // iPhone 15 Series
        [
            'model' => 'iPhone 15', 'series' => 'iPhone 15', 'featured' => false,
            'description' => 'A total powerhouse with the Dynamic Island, a 48MP Main camera, and USB-C — all in a durable, colour-infused glass and aluminium design.',
            'storages' => ['128GB' => 79900, '256GB' => 89900],
            'colors' => ['Black', 'Blue', 'Pink'],
        ],
        [
            'model' => 'iPhone 15 Plus', 'series' => 'iPhone 15', 'featured' => false,
            'description' => 'All the brilliance of iPhone 15 on a bigger 6.7-inch display with all-day battery life.',
            'storages' => ['128GB' => 89900, '256GB' => 99900],
            'colors' => ['Black', 'Green'],
        ],
        [
            'model' => 'iPhone 15 Pro', 'series' => 'iPhone 15', 'featured' => false,
            'description' => 'Forged in titanium with the powerful A17 Pro chip, a customisable Action button, and the most powerful iPhone camera system ever.',
            'storages' => ['128GB' => 134900, '256GB' => 144900],
            'colors' => ['Natural Titanium', 'Blue Titanium'],
        ],
        [
            'model' => 'iPhone 15 Pro Max', 'series' => 'iPhone 15', 'featured' => false,
            'description' => 'The ultimate iPhone 15, with a 5x Telephoto camera and our largest Pro display.',
            'storages' => ['256GB' => 159900, '512GB' => 179900],
            'colors' => ['Natural Titanium', 'Black Titanium'],
        ],

        // iPhone 16 Series
        [
            'model' => 'iPhone 16', 'series' => 'iPhone 16', 'featured' => false,
            'description' => 'Built for Apple Intelligence. Featuring the A18 chip, Camera Control, and a more durable design.',
            'storages' => ['128GB' => 79900, '256GB' => 89900],
            'colors' => ['Ultramarine', 'Teal', 'Black'],
        ],
        [
            'model' => 'iPhone 16 Plus', 'series' => 'iPhone 16', 'featured' => false,
            'description' => 'A bigger 6.7-inch display, the A18 chip, and remarkable all-day battery life.',
            'storages' => ['128GB' => 89900, '256GB' => 99900],
            'colors' => ['Ultramarine', 'White'],
        ],
        [
            'model' => 'iPhone 16 Pro', 'series' => 'iPhone 16', 'featured' => true,
            'description' => 'A larger 6.3-inch Super Retina XDR display, the A18 Pro chip, and a 4K 120 fps Dolby Vision camera system in titanium.',
            'storages' => ['128GB' => 119900, '256GB' => 129900],
            'colors' => ['Desert Titanium', 'Natural Titanium'],
        ],
        [
            'model' => 'iPhone 16 Pro Max', 'series' => 'iPhone 16', 'featured' => true,
            'description' => 'The largest 6.9-inch Pro display, the A18 Pro chip, and the most advanced iPhone camera system.',
            'storages' => ['256GB' => 144900, '512GB' => 164900],
            'colors' => ['Desert Titanium', 'Black Titanium'],
        ],

        // iPhone 17 Series
        [
            'model' => 'iPhone 17', 'series' => 'iPhone 17', 'featured' => true,
            'description' => 'The all-new iPhone 17 with the A19 chip, a brighter ProMotion display, and a dual 48MP camera system.',
            'storages' => ['256GB' => 82900, '512GB' => 102900],
            'colors' => ['Sage', 'Black'],
        ],
        [
            'model' => 'iPhone 17 Air', 'series' => 'iPhone 17', 'featured' => true,
            'description' => 'Impossibly thin. Astonishingly light. The A19 Pro chip in the most portable iPhone ever made.',
            'storages' => ['256GB' => 119900, '512GB' => 139900],
            'colors' => ['Sky Blue', 'Silver'],
        ],
        [
            'model' => 'iPhone 17 Pro', 'series' => 'iPhone 17', 'featured' => true,
            'description' => 'A unibody aluminium design, the A19 Pro chip, and a triple 48MP Pro camera system with an 8x Telephoto.',
            'storages' => ['256GB' => 132900, '512GB' => 152900],
            'colors' => ['Cosmic Orange', 'Deep Blue', 'Silver'],
        ],
        [
            'model' => 'iPhone 17 Pro Max', 'series' => 'iPhone 17', 'featured' => true,
            'description' => 'The ultimate iPhone. The biggest Pro display, the best battery life ever in an iPhone, and the A19 Pro chip.',
            'storages' => ['256GB' => 149900, '512GB' => 169900, '1TB' => 189900],
            'colors' => ['Cosmic Orange', 'Deep Blue'],
        ],
    ];

    /**
     * Accessories sold as single-variant products.
     *
     * @var list<array{name: string, price: int, description: string, color: string|null, stock: int}>
     */
    private array $accessories = [
        ['name' => 'AirPods Pro', 'price' => 24900, 'color' => 'White', 'stock' => 60, 'description' => 'AirPods Pro with up to 2x more Active Noise Cancellation, Adaptive Audio, and a USB-C charging case.'],
        ['name' => 'Apple Watch Ultra', 'price' => 89900, 'color' => 'Titanium', 'stock' => 18, 'description' => 'The most rugged and capable Apple Watch, with a bright 3000-nit display and up to 36 hours of battery life.'],
        ['name' => 'MagSafe Charger', 'price' => 4900, 'color' => 'White', 'stock' => 120, 'description' => 'Faster wireless charging up to 15W with perfectly aligned magnets that snap to your iPhone.'],
        ['name' => 'Apple FineWoven Case', 'price' => 5900, 'color' => 'Black', 'stock' => 80, 'description' => 'A durable FineWoven case with a soft microtwill finish and built-in magnets for MagSafe.'],
        ['name' => 'USB-C 35W Adapter', 'price' => 4900, 'color' => 'White', 'stock' => 100, 'description' => 'Dual USB-C Port Power Adapter to charge two devices at once with up to 35W.'],
    ];

    public function run(): void
    {
        $stockCycle = [40, 32, 25, 12, 6, 3];
        $index = 0;

        foreach ($this->phones as $phone) {
            foreach ($phone['storages'] as $storage => $price) {
                foreach ($phone['colors'] as $color) {
                    Product::create([
                        'name' => "{$phone['model']} {$storage}",
                        'sku' => $this->sku($phone['model'], $storage, $color),
                        'model' => $phone['model'],
                        'description' => $phone['description'],
                        'series' => $phone['series'],
                        'storage' => $storage,
                        'color' => $color,
                        'price' => $price,
                        'stock' => $stockCycle[$index % count($stockCycle)],
                        'image' => $this->image("{$phone['model']} {$color}"),
                        // Feature one representative variant per featured model.
                        'is_featured' => $phone['featured'] && $storage === array_key_first($phone['storages']) && $color === $phone['colors'][0],
                    ]);
                    $index++;
                }
            }
        }

        foreach ($this->accessories as $accessory) {
            Product::create([
                'name' => $accessory['name'],
                'sku' => $this->sku($accessory['name'], 'STD', $accessory['color'] ?? 'NA'),
                'model' => $accessory['name'],
                'description' => $accessory['description'],
                'series' => 'Accessories',
                'storage' => null,
                'color' => $accessory['color'],
                'price' => $accessory['price'],
                'stock' => $accessory['stock'],
                'image' => $this->image($accessory['name']),
                'is_featured' => false,
            ]);
        }
    }

    private function sku(string $model, string $storage, string $color): string
    {
        return Str::upper(
            collect([$model, $storage, $color])
                ->map(fn (string $part): string => Str::of($part)->replaceMatches('/[^A-Za-z0-9]/', '')->substr(0, 4))
                ->implode('-')
        ).'-'.Str::upper(Str::random(3));
    }

    /**
     * Resolve the local path to a product's real Apple image. The label is the
     * model and colour (or accessory name); files live in public/images/products.
     */
    private function image(string $label): string
    {
        return '/images/products/'.Str::slug($label).'.jpg';
    }
}
