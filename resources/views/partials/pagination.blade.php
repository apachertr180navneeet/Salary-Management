@if ($employees->hasPages())
    <div class="pagination-container">
        <div>
            Showing <strong class="salary-cell">{{ $employees->firstItem() ?? 0 }}</strong> to <strong class="salary-cell">{{ $employees->lastItem() ?? 0 }}</strong> of <strong class="salary-cell">{{ $employees->total() }}</strong> employees
        </div>
        <div class="pagination-links">
            {{-- Previous Page Link --}}
            @if ($employees->onFirstPage())
                <span class="page-link-btn page-link-arrow disabled"><i class="fa-solid fa-chevron-left"></i> Previous</span>
            @else
                <a href="javascript:void(0)" onclick="goToPage({{ $employees->currentPage() - 1 }})" class="page-link-btn page-link-arrow"><i class="fa-solid fa-chevron-left"></i> Previous</a>
            @endif

            {{-- Dynamic Page Numbers (Max 5 shown for high density UI) --}}
            @php
                $start = max(1, $employees->currentPage() - 2);
                $end = min($employees->lastPage(), $employees->currentPage() + 2);
            @endphp
            
            @if($start > 1)
                <a href="javascript:void(0)" onclick="goToPage(1)" class="page-link-btn">1</a>
                @if($start > 2)
                    <span class="page-link-btn disabled">...</span>
                @endif
            @endif

            @for ($page = $start; $page <= $end; $page++)
                @if ($page == $employees->currentPage())
                    <span class="page-link-btn active">{{ $page }}</span>
                @else
                    <a href="javascript:void(0)" onclick="goToPage({{ $page }})" class="page-link-btn">{{ $page }}</a>
                @endif
            @endfor

            @if($end < $employees->lastPage())
                @if($end < $employees->lastPage() - 1)
                    <span class="page-link-btn disabled">...</span>
                @endif
                <a href="javascript:void(0)" onclick="goToPage({{ $employees->lastPage() }})" class="page-link-btn">{{ $employees->lastPage() }}</a>
            @endif

            {{-- Next Page Link --}}
            @if ($employees->hasMorePages())
                <a href="javascript:void(0)" onclick="goToPage({{ $employees->currentPage() + 1 }})" class="page-link-btn page-link-arrow">Next <i class="fa-solid fa-chevron-right"></i></a>
            @else
                <span class="page-link-btn page-link-arrow disabled">Next <i class="fa-solid fa-chevron-right"></i></span>
            @endif
        </div>
    </div>
@else
    <div class="pagination-container">
        <div>
            Showing <strong class="salary-cell">{{ $employees->total() }}</strong> employees
        </div>
    </div>
@endif
