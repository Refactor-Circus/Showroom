{{-- An asset tile's thumbnail and caption. --}}
<div class="flex aspect-square items-center justify-center overflow-hidden rounded-radius bg-surface-alt dark:bg-white/5">
    @if ($asset->isImage())
        <img src="{{ $asset->url() }}" alt="{{ $asset->label() }}" loading="lazy" class="size-full object-contain">
    @else
        <span class="font-mono text-xs uppercase opacity-70">{{ pathinfo($asset->filename, PATHINFO_EXTENSION) ?: $asset->mime_type }}</span>
    @endif
</div>
<span class="truncate text-xs">{{ $caption }}</span>
