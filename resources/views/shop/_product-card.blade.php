<a href="{{ route('catalog.show', $product->slug) }}" class="product-card">
    <div class="product-media">
        @if ($product->image_url)
            <img src="{{ $product->image_url }}" alt="{{ $product->brand->name }} {{ $product->name }}" loading="lazy">
        @else
            <div class="product-placeholder" aria-hidden="true"><span>{{ mb_substr($product->name, 0, 1) }}</span></div>
        @endif
        <span class="product-tag">{{ $product->category->name }}</span>
    </div>
    <div class="product-body">
        <div class="product-brand">{{ $product->brand->name }}</div>
        <div class="product-name">{{ $product->name }}</div>
        <div class="product-price">Mulai {{ \App\Support\Rupiah::format((int) $product->min_price) }}</div>
    </div>
</a>
