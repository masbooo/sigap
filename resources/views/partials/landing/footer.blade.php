<!-- ------------------------------------- -->
<!-- Footer Start -->
<!-- ------------------------------------- -->
<footer>
    <div class="container-fluid">
        <div class="d-flex justify-content-between py-7 flex-md-nowrap flex-wrap gap-sm-0 gap-3">
            <div class="d-flex gap-3 align-items-center landing-footer-brand">
                <img src="{{ asset_url('assets/custom/images/logos/sigap32.svg') }}" alt="SIGAP" class="landing-footer-logo">
                <p class="fs-4 mb-0">Copyright &copy; {{ date('Y') }} - <b>BPKAD</b></p>
            </div>
            <div class="d-flex gap-3 align-items-center landing-footer-socials">
                <a
                    href="https://bpkad.surabaya.go.id/home"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="landing-footer-social-link"
                    data-bs-toggle="tooltip"
                    data-bs-title="Website"
                    aria-label="Website"
                >
                    <img src="{{ asset_url('assets/custom/images/frontend-pages/website16.svg') }}" alt="Website" class="landing-footer-social-icon">
                </a>
                <a
                    href="https://www.youtube.com/@bpkadsurabaya"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="landing-footer-social-link"
                    data-bs-toggle="tooltip"
                    data-bs-title="Youtube"
                    aria-label="Youtube"
                >
                    <img src="{{ asset_url('assets/custom/images/frontend-pages/youtube16.svg') }}" alt="Youtube" class="landing-footer-social-icon">
                </a>
                <a
                    href="https://www.instagram.com/bpkad.surabaya"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="landing-footer-social-link"
                    data-bs-toggle="tooltip"
                    data-bs-title="Instagram"
                    aria-label="Instagram"
                >
                    <img src="{{ asset_url('assets/custom/images/frontend-pages/instagram16.svg') }}" alt="Instagram" class="landing-footer-social-icon">
                </a>
            </div>
        </div>
    </div>
</footer>
<!-- ------------------------------------- -->
<!-- Footer End -->
<!-- ------------------------------------- -->

<!-- Scroll Top -->
<a href="javascript:void(0)" class="top-btn btn btn-primary d-flex align-items-center justify-content-center round-54 p-0 rounded-circle">
    <i class="ti ti-arrow-up fs-7"></i>
</a>

@php($status = (string) config('database.default'))

<div style="position:fixed; bottom:10px; right:10px; padding:6px 10px; border-radius:6px; font-size:12px;
background: {{ $status === 'cloud' ? '#16a34a' : '#dc2626' }}; color:white;">
    {{ strtoupper($status) }}
</div>

<!-- CORE LIBRARY -->
<script src="{{ asset_url('assets/custom/js/vendor.min.js') }}"></script>

<!-- BOOTSTRAP -->
<script src="{{ asset_url('assets/custom/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>

<!-- UI LIBRARY -->
<script src="{{ asset_url('assets/custom/libs/simplebar/dist/simplebar.min.js') }}"></script>
<script src="{{ asset_url('assets/custom/libs/owl.carousel/owl.carousel.min.js') }}"></script>

<!-- FULLCALENDAR -->
<script src="{{ asset_url('assets/custom/libs/fullcalendar/index.global.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/locales/id.global.min.js"></script>

<!-- LEAFLET -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- SWEET ALERT -->
<script src="{{ asset_url('assets/custom/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="{{ asset_url('assets/custom/js/forms/sweet-alert.init.js') }}"></script>

<!-- RATING -->
<script src="{{ asset_url('assets/custom/libs/jquery-raty-js/lib/jquery.raty.js') }}"></script>
<script src="{{ asset_url('assets/custom/js/plugins/rating-init.js') }}"></script>

<!-- THEME CORE -->
<script src="{{ asset_url('assets/custom/js/theme/app.init.js') }}" defer></script>
<script src="{{ asset_url('assets/custom/js/theme/theme.js') }}" defer></script>
<script src="{{ asset_url('assets/custom/js/theme/app.min.js') }}" defer></script>
<script src="{{ asset_url('assets/custom/js/frontend-landingpage/homepage.js') }}" defer></script>
<script src="{{ asset_url('assets/custom/libs/aos/dist/aos.js') }}" defer></script>
<script src="{{ asset_url('assets/custom/js/landingpage/landingpage.js') }}" defer></script>
{{-- <script src="{{ asset_url('assets/custom/js/widget/card-custom.js') }}" defer></script> --}}

<!-- APPS -->
<script src="{{ asset_url('assets/custom/js/apps/calendar-init.js') }}" defer></script>

<!-- JAVASCRIPT -->
<script src="{{ asset_url('assets/main/js/gedung.js') }}"></script>
<script src="{{ asset_url('assets/main/js/umkm.js') }}" defer></script>
<script src="{{ asset_url('assets/main/js/kontak.js') }}" defer></script>
<script src="{{ asset_url('assets/main/js/pjax.js') }}" defer></script>
<script src="{{ asset_url('assets/main/js/navbar-state.js') }}" defer></script>

<!-- ICONS -->
<script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>

</body>
</html>
