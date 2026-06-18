@include('partials.landing.header')
@include('partials.landing.navbar')

<div id="main-wrapper" class="main-wrapper overflow-hidden">
    @yield('content')
</div>

@include('partials.landing.footer')