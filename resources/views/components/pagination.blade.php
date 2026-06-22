@props(['paginator' => null])

@if ($paginator && $paginator->hasPages())
    <div {{ $attributes->merge(['class' => 'mt-4']) }}>
        {{ $paginator->onEachSide(1)->links() }}
    </div>
@endif
