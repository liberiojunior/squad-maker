@props([
    'paginator'
])

@if ($paginator->hasPages())

    @php
        $paginaAtual = $paginator->currentPage();
        $ultimaPagina = $paginator->lastPage();

        if ($paginaAtual <= 3) {

            $inicio = 2;
            $fim = min(4, $ultimaPagina - 1);

        } elseif ($paginaAtual >= $ultimaPagina - 2) {

            $inicio = max(2, $ultimaPagina - 3);
            $fim = $ultimaPagina - 1;

        } else {

            $inicio = $paginaAtual - 1;
            $fim = $paginaAtual + 1;

        }
    @endphp

    <div class="squad-pagination">

        <div class="squad-pagination-summary">
            Mostrando

            <strong>
                {{ $paginator->firstItem() }}
            </strong>

            a

            <strong>
                {{ $paginator->lastItem() }}
            </strong>

            de

            <strong>
                {{ $paginator->total() }}
            </strong>

            resultados
        </div>

        <nav aria-label="Paginação">

            <ul class="pagination">

                <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">

                    @if ($paginator->onFirstPage())

                        <span
                            class="page-link"
                            aria-hidden="true"
                        >
                            ‹
                        </span>

                    @else

                        <a
                            class="page-link"
                            href="{{ $paginator->previousPageUrl() }}"
                            rel="prev"
                            aria-label="Página anterior"
                        >
                            ‹
                        </a>

                    @endif

                </li>

                <li class="page-item {{ $paginaAtual === 1 ? 'active' : '' }}">

                    @if ($paginaAtual === 1)

                        <span class="page-link">
                            1
                        </span>

                    @else

                        <a
                            class="page-link"
                            href="{{ $paginator->url(1) }}"
                        >
                            1
                        </a>

                    @endif

                </li>

                @if ($inicio > 2)

                    <li class="page-item disabled pagination-ellipsis">

                        <span class="page-link">
                            …
                        </span>

                    </li>

                @endif

                @for ($pagina = $inicio; $pagina <= $fim; $pagina++)

                    <li class="page-item {{ $paginaAtual === $pagina ? 'active' : '' }}">

                        @if ($paginaAtual === $pagina)

                            <span class="page-link">
                                {{ $pagina }}
                            </span>

                        @else

                            <a
                                class="page-link"
                                href="{{ $paginator->url($pagina) }}"
                            >
                                {{ $pagina }}
                            </a>

                        @endif

                    </li>

                @endfor

                @if ($fim < $ultimaPagina - 1)

                    <li class="page-item disabled pagination-ellipsis">

                        <span class="page-link">
                            …
                        </span>

                    </li>

                @endif

                @if ($ultimaPagina > 1)

                    <li class="page-item {{ $paginaAtual === $ultimaPagina ? 'active' : '' }}">

                        @if ($paginaAtual === $ultimaPagina)

                            <span class="page-link">
                                {{ $ultimaPagina }}
                            </span>

                        @else

                            <a
                                class="page-link"
                                href="{{ $paginator->url($ultimaPagina) }}"
                            >
                                {{ $ultimaPagina }}
                            </a>

                        @endif

                    </li>

                @endif

                <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">

                    @if ($paginator->hasMorePages())

                        <a
                            class="page-link"
                            href="{{ $paginator->nextPageUrl() }}"
                            rel="next"
                            aria-label="Próxima página"
                        >
                            ›
                        </a>

                    @else

                        <span
                            class="page-link"
                            aria-hidden="true"
                        >
                            ›
                        </span>

                    @endif

                </li>

            </ul>

        </nav>

    </div>

@endif
