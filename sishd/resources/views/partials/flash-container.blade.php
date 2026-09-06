@if (session('status') || $errors->any())
    <div class="container mt-3">
        @include('partials.flash')
    </div>
@endif
