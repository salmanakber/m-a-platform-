@if ($paginator->hasPages())
    <nav class="pager" role="navigation" aria-label="Seiten">
        <p class="pager__meta">
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}
            von {{ $paginator->total() }}
        </p>

        <ul class="pager__list">
            @if ($paginator->onFirstPage())
                <li class="is-disabled"><span aria-hidden="true">‹</span></li>
            @else
                <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Zurück">‹</a></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="is-ellipsis"><span>{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="is-active" aria-current="page"><span>{{ $page }}</span></li>
                        @else
                            <li><a href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Weiter">›</a></li>
            @else
                <li class="is-disabled"><span aria-hidden="true">›</span></li>
            @endif
        </ul>
    </nav>
@endif
