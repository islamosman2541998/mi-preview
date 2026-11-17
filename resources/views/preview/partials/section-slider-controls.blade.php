@php
    $previousLabel = $locale->value === 'ar' ? 'العناصر السابقة' : __('preview.slider.previous');
    $nextLabel = $locale->value === 'ar' ? 'العناصر التالية' : __('preview.slider.next');
@endphp
<div class="section-slider__controls" data-carousel-controls>
    <button type="button" class="section-slider__button section-slider__button--prev" data-carousel-prev aria-label="{{ $previousLabel }}">
        <x-preview.icon name="arrow" size="19" />
    </button>
    <button type="button" class="section-slider__button section-slider__button--next" data-carousel-next aria-label="{{ $nextLabel }}">
        <x-preview.icon name="arrow" size="19" />
    </button>
</div>
