@props(['product'])

@php
    $discount = $product->discountPercent();
    $originalPrice = $product->price !== null
        ? \App\Models\Product::formatPriceValue($product->price).' €'
        : ($product->price_min !== null && $product->price_max !== null
            ? \App\Models\Product::formatPriceValue($product->price_min).'–'.\App\Models\Product::formatPriceValue($product->price_max).' €'
            : ($product->price_min !== null ? __('Dès :price €', ['price' => \App\Models\Product::formatPriceValue($product->price_min)]) : null));
@endphp

@if ($discount > 0 && $originalPrice !== null)
    <div class="flex flex-wrap items-center justify-end gap-1.5">
        <span class="text-[10px] font-normal text-zinc-400 line-through">{{ $originalPrice }}</span>
        <span class="rounded-full bg-bmlk-brand px-1.5 py-0.5 text-[10px] font-semibold text-white">−{{ $discount }}%</span>
    </div>
@endif
@if ($product->price !== null)
    {{ $product->formatted_price }} €
@elseif ($product->price_min !== null && $product->price_max !== null)
    {{ $product->formatted_price_min }}–{{ $product->formatted_price_max }} €
@elseif ($product->price_min !== null)
    {{ __('Dès :price €', ['price' => $product->formatted_price_min]) }}
@endif
