<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\DataTransferObjects\Products\UpdateProductDto;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final readonly class UpdateProductAction
{
    public function __invoke(Product $product, UpdateProductDto $dto, User $updater): Product
    {
        return DB::transaction(function () use ($product, $dto, $updater): Product {
            $password = $product->password;

            if ($dto->visibility === \App\Enums\ProductVisibility::Password) {
                if (!in_array($dto->password, [null, '', '0'], true)) {
                    $password = Hash::make($dto->password);
                }
            } else {
                $password = null;
            }

            $product->update([
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

            // Sincronizar tiers de precios (recreación)
            $product->prices()->delete();

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
                ->causedBy($updater)
                ->useLog('product')
                ->log('updated');

            return $product->refresh()->load('prices');
        });
    }
}
