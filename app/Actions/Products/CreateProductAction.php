<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\DataTransferObjects\Products\CreateProductDto;
use App\Models\Event;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final readonly class CreateProductAction
{
    public function __invoke(Event $event, CreateProductDto $dto, User $creator): Product
    {
        return DB::transaction(function () use ($event, $dto, $creator): Product {
            // El evento manda sobre el organizer_id
            $organizerId = $event->organizer_id;

            $password = null;

            if ($dto->visibility === \App\Enums\ProductVisibility::Password && !in_array($dto->password, [null, '', '0'], true)) {
                $password = Hash::make($dto->password);
            }

            /** @var Product $product */
            $product = Product::query()->create([
                'event_id' => $event->event_id,
                'organizer_id' => $organizerId,
                'title' => $dto->title,
                'slug' => $dto->slug,
                'description' => $dto->description,
                'type' => $dto->type,
                'pricing_mode' => $dto->pricing_mode,
                'status' => $dto->status,
                'visibility' => $dto->visibility,
                'password' => $password,
                'min_qty' => $dto->min_qty,
                'max_qty' => $dto->max_qty,
                'sort_order' => $dto->sort_order,
            ]);

            // Crear tiers de precios
            foreach ($dto->prices as $priceData) {
                $product->prices()->create([
                    'name' => $priceData['name'],
                    'price' => $priceData['price'],
                    'capacity' => $priceData['capacity'] ?? null,
                    'quantity_sold' => 0,
                    'sales_start_at' => $priceData['sales_start_at'] ?? null,
                    'sales_end_at' => $priceData['sales_end_at'] ?? null,
                ]);
            }

            activity()
                ->performedOn($product)
                ->causedBy($creator)
                ->useLog('product')
                ->log('created');

            return $product->load('prices');
        });
    }
}
