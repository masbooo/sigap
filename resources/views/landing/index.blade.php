@extends('layouts.landing')

@section('content')

@php
    $heroUmkmThumbnails = $heroUmkmThumbnails ?? [];
    $heroBadgeThumbnails = !empty($heroUmkmThumbnails)
        ? array_values($heroUmkmThumbnails)
        : [
            ['src' => '../assets/images/profile/user-1.jpg', 'alt' => 'UMKM SIGAP 1'],
            ['src' => '../assets/images/profile/user-2.jpg', 'alt' => 'UMKM SIGAP 2'],
            ['src' => '../assets/images/profile/user-3.jpg', 'alt' => 'UMKM SIGAP 3'],
        ];
    $landingImageMap = [
        '../assets/images/hero-img/bannerimg1.svg' => asset('assets/custom/images/hero-img/bannerimg1.svg'),
        '../assets/images/hero-img/bannerimg2.svg' => asset('assets/custom/images/hero-img/bannerimg2.svg'),
        '../assets/images/profile/user-1.jpg' => asset('assets/custom/images/profile/user-1.jpg'),
        '../assets/images/profile/user-2.jpg' => asset('assets/custom/images/profile/user-2.jpg'),
        '../assets/images/profile/user-3.jpg' => asset('assets/custom/images/profile/user-3.jpg'),
        '../assets/images/frontend-pages/page-homepage.jpg' => asset('assets/custom/images/frontend-pages/page-homepage.jpg'),
        '../assets/images/frontend-pages/page-about.jpg' => asset('assets/custom/images/frontend-pages/page-about.jpg'),
        '../assets/images/frontend-pages/page-portfolio.jpg' => asset('assets/custom/images/frontend-pages/page-portfolio.jpg'),
        '../assets/images/frontend-pages/page-pricing.jpg' => asset('assets/custom/images/frontend-pages/page-pricing.jpg'),
        '../assets/images/slider/slider-group.png' => asset('assets/custom/images/slider/slider-group.png'),
        '../assets/images/svgs/icon-star.svg' => asset('assets/custom/images/svgs/icon-star.svg'),
        '../assets/images/backgrounds/business-woman-checking-her-mail.png' => asset('assets/custom/images/backgrounds/business-woman-checking-her-mail.png'),
        '../assets/images/demos/demo-main.jpg' => asset('assets/custom/images/frontend-pages/template-1.png'),
        '../assets/images/demos/demo-dark.jpg' => asset('assets/custom/images/frontend-pages/template-2.png'),
        '../assets/images/demos/demo-horizontal.jpg' => asset('assets/custom/images/frontend-pages/template-3.png'),
        '../assets/images/demos/demo-minisidebar.jpg' => asset('assets/custom/images/frontend-pages/template-4.png'),
        '../assets/images/demos/demo-rtl.jpg' => asset('assets/custom/images/frontend-pages/screen.png'),
        '../assets/images/apps/app-calendar.jpg' => asset('assets/custom/images/frontend-pages/tabsimage.png'),
        '../assets/images/apps/app-chat.jpg' => asset('assets/custom/images/frontend-pages/design-collection.png'),
        '../assets/images/apps/app-email.jpg' => asset('assets/custom/images/frontend-pages/screen.png'),
        '../assets/images/apps/app-contact.jpg' => asset('assets/custom/images/frontend-pages/playframe.png'),
        '../assets/images/apps/app-invoice.jpg' => asset('assets/custom/images/frontend-pages/template-1.png'),
        '../assets/images/apps/modernize-bt-app-contact-list.jpg' => asset('assets/custom/images/frontend-pages/template-2.png'),
        '../assets/images/apps/app-user-profile.jpg' => asset('assets/custom/images/frontend-pages/page-about.jpg'),
        '../assets/images/apps/modernize-vue-app-blog.jpg' => asset('assets/custom/images/frontend-pages/page-portfolio.jpg'),
        '../assets/images/apps/modernize-vue-app-blog-detail.jpg' => asset('assets/custom/images/frontend-pages/blog-detail-banner.jpg'),
        '../assets/images/apps/modernize-vue-app-shop.jpg' => asset('assets/custom/images/frontend-pages/design-collection.png'),
        '../assets/images/apps/app-ecommerce-detail.jpg' => asset('assets/custom/images/frontend-pages/page-pricing.jpg'),
        '../assets/images/apps/app-ecommerce-list.jpg' => asset('assets/custom/images/frontend-pages/template-3.png'),
    ];

    ob_start();
@endphp

<section class="hero-section position-relative overflow-hidden mb-0 mb-lg-5">
    <div class="container">
		<div class="row align-items-center">
			<div class="col-xl-6">
				<div class="hero-content my-5 my-xl-0">
					<h6 class="d-flex align-items-center gap-2 fs-4 fw-semibold mb-3" data-aos="fade-up" data-aos-delay="200" data-aos-duration="1000">
						<i class="ti ti-building text-primary fs-6"></i>Wujudkan acara impian Anda bersama kami
					</h6>
					<h1 class="fw-bolder mb-7 fs-13" data-aos="fade-up" data-aos-delay="400" data-aos-duration="1000">
						{{-- Most powerful & --}}
						<span class="text-primary"> Nyaman Acaranya,</span>
						Ringan Biayanya
					</h1>
					<p class="fs-5 mb-5 text-dark fw-normal" data-aos="fade-up" data-aos-delay="600" data-aos-duration="1000">
					Nikmati fasilitas gedung serbaguna Pemerintah Kota Surabaya dengan akses mudah dan harga terjangkau   </p>
					<div class="d-flex align-items-stretch gap-2 gap-sm-3 flex-nowrap" data-aos="fade-up" data-aos-delay="600" data-aos-duration="1000">
						<a class="btn btn-primary px-3 px-sm-5 py-6 btn-hover-shadow d-inline-flex align-items-center justify-content-center text-center text-nowrap" href="{{ url('gedung') }}" data-no-pjax style="flex: 0 1 220px; max-width: calc(50% - 0.25rem);">LIHAT GEDUNG</a>
						<a class="btn btn-outline-primary d-inline-flex align-items-center justify-content-center scroll-link px-3 px-sm-5 py-6 text-center text-nowrap" href="{{ url('jadwal') }}" style="flex: 0 1 220px; max-width: calc(50% - 0.25rem);">LIHAT JADWAL</a>
					</div>
				</div>
			</div>
			<div class="col-xl-6 d-none d-xl-block">
				<div class="hero-img-slide position-relative bg-primary-subtle p-4 rounded-0">
					<div class="d-flex flex-row">
						<div class="">
							<div class="banner-img-1 slideup">
								<img src="../assets/images/hero-img/bannerimg1.svg" class="img-fluid" />
							</div>
							<div class="banner-img-1 slideup">
								<img src="../assets/images/hero-img/bannerimg1.svg" class="img-fluid" />
							</div>
						</div>
						<div class="">
							<div class="banner-img-2 slideDown">
								<img src="../assets/images/hero-img/bannerimg2.svg" class="img-fluid" />
							</div>
							<div class="banner-img-2 slideDown">
								<img src="../assets/images/hero-img/bannerimg2.svg" class="img-fluid" />
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
    </div>
</section>

<section class="production pb-5 pb-md-5" id="production-template">
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-lg-8" data-aos="fade-up" data-aos-delay="200" data-aos-duration="1000">
				<div class="d-sm-flex align-items-center text-center gap-2 justify-content-center mb-7">
				<ul class="list-unstyled d-flex align-items-center justify-content-center justify-content-sm-start mb-2 mb-sm-0">
					@foreach ($heroBadgeThumbnails as $thumbnailIndex => $thumbnail)
					@php
						$thumbnailItemClass = $thumbnailIndex > 0 ? 'ms-n2' : '';
						$thumbnailAlt = $thumbnail['alt'] ?? 'UMKM SIGAP';
					@endphp
					<li class="{{ $thumbnailItemClass }}">
						<a class="d-block" href="javascript:void(0)" aria-label="{{ $thumbnailAlt }}">
						<img src="{{ $thumbnail['src'] }}" alt="{{ $thumbnailAlt }}" class="d-block border rounded-circle border-white" width="32" height="32" style="width: 32px; height: 32px; object-fit: cover;" />
						</a>
					</li>
					@endforeach
				</ul>
				<p class="mb-0 fw-semibold fs-4 text-dark">
					<span>125+</span> UMKM sudah bersinergi untuk tumbuh bersama SIGAP  
				</p>
				</div>
				<h2 class="text-center mb-0 fs-9 fw-bolder">
				Solusi Digital yang Siap Mendukung Pertumbuhan UMKM
				</h2>
			</div>
		</div>
		<div class="domo-contect position-relative" data-aos="fade-up" data-aos-delay="400" data-aos-duration="1000">
			<div class="demos-view pt-lg-5">
				<div class="row justify-content-center">
					<div class="col-sm-6 col-lg-4 mb-7">
						<div class="border d-block rounded-1 mb-2 position-relative lp-demos-box overflow-hidden">
							<img src="../assets/images/demos/demo-main.jpg" class="img-fluid" />
							<a target="_blank" href="../main/index.html" class="btn btn-primary lp-demos-btn fs-3 px-7 py-1 rounded position-absolute top-50 start-50 translate-middle">Live
								Preview</a>
						</div>
						<h6 class="mb-0 text-center fs-3">Main</h6>
					</div>
					<div class="col-sm-6 col-lg-4 mb-7">
						<div class="border d-block rounded-1 mb-2 position-relative lp-demos-box overflow-hidden">
							<img src="../assets/images/demos/demo-dark.jpg" class="img-fluid" />
							<a target="_blank" href="../dark/index2.html" class="btn btn-primary lp-demos-btn fs-3 px-7 py-1 rounded position-absolute top-50 start-50 translate-middle">Live
								Preview</a>
						</div>
						<h6 class="mb-0 text-center fs-3">Dark</h6>
					</div>
					<div class="col-sm-6 col-lg-4 mb-7">
						<div class="border d-block rounded-1 mb-2 position-relative lp-demos-box overflow-hidden">
							<img src="../assets/images/demos/demo-horizontal.jpg" class="img-fluid" />
							<a target="_blank" href="../horizontal/index3.html" class="btn btn-primary lp-demos-btn fs-3 px-7 py-1 rounded position-absolute top-50 start-50 translate-middle">Live
								Preview</a>
						</div>
						<h6 class="mb-0 text-center fs-3">Horizontal</h6>
					</div>
					<div class="col-sm-6 col-lg-4 mb-7">
						<div class="border d-block rounded-1 mb-2 position-relative lp-demos-box overflow-hidden">
							<img src="../assets/images/demos/demo-minisidebar.jpg" class="img-fluid" />
							<a target="_blank" href="../minisidebar/index4.html" class="btn btn-primary lp-demos-btn fs-3 px-7 py-1 rounded position-absolute top-50 start-50 translate-middle">Live
								Preview</a>
						</div>
						<h6 class="mb-0 text-center fs-3">Minisidebar</h6>
					</div>
					<div class="col-sm-6 col-lg-4 mb-7">
						<div class="border d-block rounded-1 mb-2 position-relative lp-demos-box overflow-hidden">
							<img src="../assets/images/demos/demo-rtl.jpg" class="img-fluid" />
							<a target="_blank" href="../rtl/index5.html" class="btn btn-primary lp-demos-btn fs-3 px-7 py-1 rounded position-absolute top-50 start-50 translate-middle">Live
								Preview</a>
						</div>
						<h6 class="mb-0 text-center fs-3">RTL</h6>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<section class="text-bg-light" data-aos="fade-up" data-aos-delay="200" data-aos-duration="1000">
	<div class="py-md-5">
		<div class="container-fluid">
			<div class="row justify-content-center">
				<div class="col-lg-8 col-xxl-8">
					<h2 class="fs-9 text-center mb-5 fw-bolder">
						Increase speed of your development and launch quickly with
						Modernize
					</h2>
				</div>
			</div>
		</div>
		<div class="sliding-wrapper position-relative overflow-hidden" data-aos="fade" data-aos-delay="400" data-aos-duration="1000">
			<div class="slide-background d-flex flex-row w-100">
				<div class="slide">
					<img src="../assets/images/slider/slider-group.png" alt="slide" height="100%" />
				</div>
				<div class="slide">
					<img src="../assets/images/slider/slider-group.png" alt="slide" height="100%" />
				</div>
				<div class="slide">
					<img src="../assets/images/slider/slider-group.png" alt="slide" height="100%" />
				</div>
			</div>
		</div>
	</div>
</section>

<section class="review-section">
	<div class="container pt-md-5">
		<div class="row justify-content-center">
			<div class="col-lg-8">
				<h2 class="fs-9 text-center mb-4 mb-lg-5 fw-bolder" data-aos="fade-up" data-aos-delay="200" data-aos-duration="1000">
				Don’t just take our words for it, See what developers like you
				are saying
				</h2>
			</div>
		</div>
		<div class="review-slider" data-aos="fade-up" data-aos-delay="400" data-aos-duration="1000">
			<div class="owl-carousel">
				<div class="item">
					<div class="card">
						<div class="card-body p-4">
						<div class="d-flex justify-content-between mb-4">
							<div class="d-flex align-items-center">
							<img src="../assets/images/profile/user-1.jpg" class="w-auto me-3 rounded-circle" width="40" height="40" />
							<div>
								<h6 class="fs-4 mb-1 fw-semibold">Joni Watson</h6>
								<p class="mb-0 text-dark">Features avaibility</p>
							</div>
							</div>
							<div>
							<ul class="list-unstyled d-flex align-items-center justify-content-end gap-1 mb-0">
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
							</ul>
							</div>
						</div>
						<p class="fs-4 mb-0 text-dark">
							The dashboard template from adminmart has helped me
							provide a clean and sleek look to my dashboard and made
							it look exactly the way I wanted it to, mainly without
							having.
						</p>
						</div>
					</div>
				</div>
				<div class="item">
					<div class="card">
						<div class="card-body p-4">
						<div class="d-flex justify-content-between mb-4">
							<div class="d-flex align-items-center">
							<img src="../assets/images/profile/user-2.jpg" class="w-auto me-3 rounded-circle" width="40" height="40" />
							<div>
								<h6 class="fs-4 mb-1 fw-semibold">Pingsan Cui</h6>
								<p class="mb-0 text-dark">Features avaibility</p>
							</div>
							</div>
							<div>
							<ul class="list-unstyled d-flex align-items-center justify-content-end gap-1 mb-0">
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
							</ul>
							</div>
						</div>
						<p class="fs-4 text-dark mb-0">
							The quality of design is excellent, customizability and
							flexibility much better than the other products
							available in the market. I strongly recommend the
							AdminMart to other buyers.
						</p>
						</div>
					</div>
				</div>
				<div class="item">
					<div class="card">
						<div class="card-body p-4">
						<div class="d-flex justify-content-between mb-4">
							<div class="d-flex align-items-center">
							<img src="../assets/images/profile/user-3.jpg" class="w-auto me-3 rounded-circle" width="40" height="40" />
							<div>
								<h6 class="fs-4 mb-1 fw-semibold">
								Emison Mendoan
								</h6>
								<p class="mb-0 fw-normal">Features avaibility</p>
							</div>
							</div>
							<div>
							<ul class="list-unstyled d-flex align-items-center justify-content-end gap-1 mb-0">
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
							</ul>
							</div>
						</div>
						<p class="fs-4 text-dark mb-0">
							This template is great, UI-rich and up-to-date. Although
							it is pretty much complete, I suggest to improve a bit
							of documentation. Thanks & Highly recomended!
						</p>
						</div>
					</div>
				</div>
				<div class="item">
					<div class="card">
						<div class="card-body p-4">
						<div class="d-flex justify-content-between mb-4">
							<div class="d-flex align-items-center">
							<img src="../assets/images/profile/user-1.jpg" class="w-auto me-3 rounded-circle" width="40" height="40" />
							<div>
								<h6 class="fs-4 mb-1 fw-semibold">Agen Salmon</h6>
								<p class="mb-0 text-dark">Features avaibility</p>
							</div>
							</div>
							<div>
							<ul class="list-unstyled d-flex align-items-center justify-content-end gap-1 mb-0">
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
							</ul>
							</div>
						</div>
						<p class="fs-4 mb-0 text-dark">
							The dashboard template from adminmart has helped me
							provide a clean and sleek look to my dashboard and made
							it look exactly the way I wanted it to, mainly without
							having.
						</p>
						</div>
					</div>
				</div>
				<div class="item">
					<div class="card">
						<div class="card-body p-4">
						<div class="d-flex justify-content-between mb-4">
							<div class="d-flex align-items-center">
							<img src="../assets/images/profile/user-2.jpg" class="w-auto me-3 rounded-circle" width="40" height="40" />
							<div>
								<h6 class="fs-4 mb-1 fw-semibold">Arisan Yuk</h6>
								<p class="mb-0 text-dark">Features avaibility</p>
							</div>
							</div>
							<div>
							<ul class="list-unstyled d-flex align-items-center justify-content-end gap-1 mb-0">
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
							</ul>
							</div>
						</div>
						<p class="fs-4 text-dark mb-0">
							The quality of design is excellent, customizability and
							flexibility much better than the other products
							available in the market. I strongly recommend the
							AdminMart to other buyers.
						</p>
						</div>
					</div>
				</div>
				<div class="item">
					<div class="card">
						<div class="card-body p-4">
						<div class="d-flex justify-content-between mb-4">
							<div class="d-flex align-items-center">
							<img src="../assets/images/profile/user-3.jpg" class="w-auto me-3 rounded-circle" width="40" height="40" />
							<div>
								<h6 class="fs-4 mb-1 fw-semibold">
								Rudi Bawel
								</h6>
								<p class="mb-0 fw-normal">Features avaibility</p>
							</div>
							</div>
							<div>
							<ul class="list-unstyled d-flex align-items-center justify-content-end gap-1 mb-0">
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
							</ul>
							</div>
						</div>
						<p class="fs-4 text-dark mb-0">
							This template is great, UI-rich and up-to-date. Although
							it is pretty much complete, I suggest to improve a bit
							of documentation. Thanks & Highly recomended!
						</p>
						</div>
					</div>
				</div>
				<div class="item">
					<div class="card">
						<div class="card-body p-4">
						<div class="d-flex justify-content-between mb-4">
							<div class="d-flex align-items-center">
							<img src="../assets/images/profile/user-1.jpg" class="w-auto me-3 rounded-circle" width="40" height="40" />
							<div>
								<h6 class="fs-4 mb-1 fw-semibold">Jenny Blackpink</h6>
								<p class="mb-0 text-dark">Features avaibility</p>
							</div>
							</div>
							<div>
							<ul class="list-unstyled d-flex align-items-center justify-content-end gap-1 mb-0">
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
							</ul>
							</div>
						</div>
						<p class="fs-4 mb-0 text-dark">
							The dashboard template from adminmart has helped me
							provide a clean and sleek look to my dashboard and made
							it look exactly the way I wanted it to, mainly without
							having.
						</p>
						</div>
					</div>
				</div>
				<div class="item">
					<div class="card">
						<div class="card-body p-4">
						<div class="d-flex justify-content-between mb-4">
							<div class="d-flex align-items-center">
							<img src="../assets/images/profile/user-2.jpg" class="w-auto me-3 rounded-circle" width="40" height="40" />
							<div>
								<h6 class="fs-4 mb-1 fw-semibold">Cui Mie</h6>
								<p class="mb-0 text-dark">Features avaibility</p>
							</div>
							</div>
							<div>
							<ul class="list-unstyled d-flex align-items-center justify-content-end gap-1 mb-0">
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
							</ul>
							</div>
						</div>
						<p class="fs-4 text-dark mb-0">
							The quality of design is excellent, customizability and
							flexibility much better than the other products
							available in the market. I strongly recommend the
							AdminMart to other buyers.
						</p>
						</div>
					</div>
				</div>
				<div class="item">
					<div class="card">
						<div class="card-body p-4">
						<div class="d-flex justify-content-between mb-4">
							<div class="d-flex align-items-center">
							<img src="../assets/images/profile/user-3.jpg" class="w-auto me-3 rounded-circle" width="40" height="40" />
							<div>
								<h6 class="fs-4 mb-1 fw-semibold">
								Seorang Pendoza
								</h6>
								<p class="mb-0 fw-normal">Features avaibility</p>
							</div>
							</div>
							<div>
							<ul class="list-unstyled d-flex align-items-center justify-content-end gap-1 mb-0">
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
							</ul>
							</div>
						</div>
						<p class="fs-4 text-dark mb-0">
							This template is great, UI-rich and up-to-date. Although
							it is pretty much complete, I suggest to improve a bit
							of documentation. Thanks & Highly recomended!
						</p>
						</div>
					</div>
				</div>
				<div class="item">
					<div class="card">
						<div class="card-body p-4">
						<div class="d-flex justify-content-between mb-4">
							<div class="d-flex align-items-center">
							<img src="../assets/images/profile/user-1.jpg" class="w-auto me-3 rounded-circle" width="40" height="40" />
							<div>
								<h6 class="fs-4 mb-1 fw-semibold">Jenny Jenny</h6>
								<p class="mb-0 text-dark">Features avaibility</p>
							</div>
							</div>
							<div>
							<ul class="list-unstyled d-flex align-items-center justify-content-end gap-1 mb-0">
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
							</ul>
							</div>
						</div>
						<p class="fs-4 mb-0 text-dark">
							The dashboard template from adminmart has helped me
							provide a clean and sleek look to my dashboard and made
							it look exactly the way I wanted it to, mainly without
							having.
						</p>
						</div>
					</div>
				</div>
				<div class="item">
					<div class="card">
						<div class="card-body p-4">
						<div class="d-flex justify-content-between mb-4">
							<div class="d-flex align-items-center">
							<img src="../assets/images/profile/user-2.jpg" class="w-auto me-3 rounded-circle" width="40" height="40" />
							<div>
								<h6 class="fs-4 mb-1 fw-semibold">Mishan Makhanan</h6>
								<p class="mb-0 text-dark">Features avaibility</p>
							</div>
							</div>
							<div>
							<ul class="list-unstyled d-flex align-items-center justify-content-end gap-1 mb-0">
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
							</ul>
							</div>
						</div>
						<p class="fs-4 text-dark mb-0">
							The quality of design is excellent, customizability and
							flexibility much better than the other products
							available in the market. I strongly recommend the
							AdminMart to other buyers.
						</p>
						</div>
					</div>
				</div>
				<div class="item">
					<div class="card">
						<div class="card-body p-4">
						<div class="d-flex justify-content-between mb-4">
							<div class="d-flex align-items-center">
							<img src="../assets/images/profile/user-3.jpg" class="w-auto me-3 rounded-circle" width="40" height="40" />
							<div>
								<h6 class="fs-4 mb-1 fw-semibold">
								Eminson Nosnime
								</h6>
								<p class="mb-0 fw-normal">Features avaibility</p>
							</div>
							</div>
							<div>
							<ul class="list-unstyled d-flex align-items-center justify-content-end gap-1 mb-0">
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
							</ul>
							</div>
						</div>
						<p class="fs-4 text-dark mb-0">
							This template is great, UI-rich and up-to-date. Although
							it is pretty much complete, I suggest to improve a bit
							of documentation. Thanks & Highly recomended!
						</p>
						</div>
					</div>
				</div>
				<div class="item">
					<div class="card">
						<div class="card-body p-4">
						<div class="d-flex justify-content-between mb-4">
							<div class="d-flex align-items-center">
							<img src="../assets/images/profile/user-1.jpg" class="w-auto me-3 rounded-circle" width="40" height="40" />
							<div>
								<h6 class="fs-4 mb-1 fw-semibold">Jenda Wilkas</h6>
								<p class="mb-0 text-dark">Features avaibility</p>
							</div>
							</div>
							<div>
							<ul class="list-unstyled d-flex align-items-center justify-content-end gap-1 mb-0">
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
							</ul>
							</div>
						</div>
						<p class="fs-4 mb-0 text-dark">
							The dashboard template from adminmart has helped me
							provide a clean and sleek look to my dashboard and made
							it look exactly the way I wanted it to, mainly without
							having.
						</p>
						</div>
					</div>
				</div>
				<div class="item">
					<div class="card">
						<div class="card-body p-4">
						<div class="d-flex justify-content-between mb-4">
							<div class="d-flex align-items-center">
							<img src="../assets/images/profile/user-2.jpg" class="w-auto me-3 rounded-circle" width="40" height="40" />
							<div>
								<h6 class="fs-4 mb-1 fw-semibold">Choipan Enak</h6>
								<p class="mb-0 text-dark">Features avaibility</p>
							</div>
							</div>
							<div>
							<ul class="list-unstyled d-flex align-items-center justify-content-end gap-1 mb-0">
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
							</ul>
							</div>
						</div>
						<p class="fs-4 text-dark mb-0">
							The quality of design is excellent, customizability and
							flexibility much better than the other products
							available in the market. I strongly recommend the
							AdminMart to other buyers.
						</p>
						</div>
					</div>
				</div>
				<div class="item">
					<div class="card">
						<div class="card-body p-4">
						<div class="d-flex justify-content-between mb-4">
							<div class="d-flex align-items-center">
							<img src="../assets/images/profile/user-3.jpg" class="w-auto me-3 rounded-circle" width="40" height="40" />
							<div>
								<h6 class="fs-4 mb-1 fw-semibold">
								Bukan Pendoza
								</h6>
								<p class="mb-0 fw-normal">Features avaibility</p>
							</div>
							</div>
							<div>
							<ul class="list-unstyled d-flex align-items-center justify-content-end gap-1 mb-0">
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
							</ul>
							</div>
						</div>
						<p class="fs-4 text-dark mb-0">
							This template is great, UI-rich and up-to-date. Although
							it is pretty much complete, I suggest to improve a bit
							of documentation. Thanks & Highly recomended!
						</p>
						</div>
					</div>
				</div>
				<div class="item">
					<div class="card">
						<div class="card-body p-4">
						<div class="d-flex justify-content-between mb-4">
							<div class="d-flex align-items-center">
							<img src="../assets/images/profile/user-1.jpg" class="w-auto me-3 rounded-circle" width="40" height="40" />
							<div>
								<h6 class="fs-4 mb-1 fw-semibold">Natasha Wilsona</h6>
								<p class="mb-0 text-dark">Features avaibility</p>
							</div>
							</div>
							<div>
							<ul class="list-unstyled d-flex align-items-center justify-content-end gap-1 mb-0">
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
							</ul>
							</div>
						</div>
						<p class="fs-4 mb-0 text-dark">
							The dashboard template from adminmart has helped me
							provide a clean and sleek look to my dashboard and made
							it look exactly the way I wanted it to, mainly without
							having.
						</p>
						</div>
					</div>
				</div>
				<div class="item">
					<div class="card">
						<div class="card-body p-4">
						<div class="d-flex justify-content-between mb-4">
							<div class="d-flex align-items-center">
							<img src="../assets/images/profile/user-2.jpg" class="w-auto me-3 rounded-circle" width="40" height="40" />
							<div>
								<h6 class="fs-4 mb-1 fw-semibold">Barisan Cuiii</h6>
								<p class="mb-0 text-dark">Features avaibility</p>
							</div>
							</div>
							<div>
							<ul class="list-unstyled d-flex align-items-center justify-content-end gap-1 mb-0">
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
							</ul>
							</div>
						</div>
						<p class="fs-4 text-dark mb-0">
							The quality of design is excellent, customizability and
							flexibility much better than the other products
							available in the market. I strongly recommend the
							AdminMart to other buyers.
						</p>
						</div>
					</div>
				</div>
				<div class="item">
					<div class="card">
						<div class="card-body p-4">
						<div class="d-flex justify-content-between mb-4">
							<div class="d-flex align-items-center">
							<img src="../assets/images/profile/user-3.jpg" class="w-auto me-3 rounded-circle" width="40" height="40" />
							<div>
								<h6 class="fs-4 mb-1 fw-semibold">
								Ederson Moraes
								</h6>
								<p class="mb-0 fw-normal">Features avaibility</p>
							</div>
							</div>
							<div>
							<ul class="list-unstyled d-flex align-items-center justify-content-end gap-1 mb-0">
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
								<li>
								<a href="">
									<img src="../assets/images/svgs/icon-star.svg" class="img-fluid" />
								</a>
								</li>
							</ul>
							</div>
						</div>
						<p class="fs-4 text-dark mb-0">
							This template is great, UI-rich and up-to-date. Although
							it is pretty much complete, I suggest to improve a bit
							of documentation. Thanks & Highly recomended!
						</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<section class="features-section py-5">
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-lg-6">
				<div class="text-center" data-aos="fade-up" data-aos-delay="600" data-aos-duration="1000">
					<small class="text-primary fw-bold mb-2 d-block fs-3">ALMOST COVERED EVERYTHING</small>
					<h2 class="fs-9 text-center mb-4 mb-lg-5 fw-bolder">
						Other Amazing Features & Flexibility Provided
					</h2>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-6 col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="800" data-aos-duration="1000">
				<div class="text-center mb-5">
					<i class="d-block ti ti-wand text-primary fs-10"></i>
					<h5 class="fs-5 fw-semibold mt-8">6 Theme Colors</h5>
					<p class="mb-0 text-dark">
						We have included 6 pre-defined Theme Colors with Modernize
						Admin.
					</p>
				</div>
			</div>
		<div class="col-sm-6 col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="800" data-aos-duration="1000">
			<div class="text-center mb-5">
				<i class="d-block ti ti-layout-sidebar text-primary fs-10"></i>
				<h5 class="fs-5 fw-semibold mt-8">Dark & Light Sidebar</h5>
				<p class="mb-0 text-dark">
					Included Dark and Light Sidebar for getting desire look and
					feel.
				</p>
			</div>
		</div>
		<div class="col-sm-6 col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="800" data-aos-duration="1000">
			<div class="text-center mb-5">
				<i class="d-block ti ti-archive text-primary fs-10"></i>
				<h5 class="fs-5 fw-semibold mt-8">425+ Page Templates</h5>
				<p class="mb-0 text-dark">
					Yes, we have 5 demos & 79+ Pages per demo to make it easier.
				</p>
			</div>
		</div>
		<div class="col-sm-6 col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="800" data-aos-duration="1000">
			<div class="text-center mb-5">
				<i class="d-block ti ti-adjustments text-primary fs-10"></i>
				<h5 class="fs-5 fw-semibold mt-8">150+ UI Components</h5>
				<p class="mb-0 text-dark">
					Almost 150+ UI Components being given with Modernize Admin
					Pack.
				</p>
			</div>
		</div>
		<div class="col-sm-6 col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="800" data-aos-duration="1000">
			<div class="text-center mb-5">
				<i class="d-block ti ti-presentation text-primary fs-10"></i>
				<h5 class="fs-5 fw-semibold mt-8">4+ Frontend Pages</h5>
				<p class="mb-0 text-dark">
					We have added useful frontend pages with Modernize Admin
				</p>
			</div>
		</div>
		<div class="col-sm-6 col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="1000" data-aos-duration="1000">
			<div class="text-center mb-5">
				<i class="d-block ti ti-tag text-primary fs-10"></i>
				<h5 class="fs-5 fw-semibold mt-8">Bootstrap 5x</h5>
				<p class="mb-0 text-dark">
					Its been made with Bootstrap 5 and full responsive layout.
				</p>
			</div>
		</div>
		<div class="col-sm-6 col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="1000" data-aos-duration="1000">
			<div class="text-center mb-5">
				<i class="d-block ti ti-diamond text-primary fs-10"></i>
				<h5 class="fs-5 fw-semibold mt-8">200+ Font Icons</h5>
				<p class="mb-0 text-dark">
					Lots of Icon Fonts are included here in the package of
					Modernize Admin.
				</p>
			</div>
		</div>
		<div class="col-sm-6 col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="1000" data-aos-duration="1000">
			<div class="text-center mb-5">
				<i class="d-block ti ti-device-desktop text-primary fs-10"></i>
				<h5 class="fs-5 fw-semibold mt-8">Fully Responsive</h5>
				<p class="mb-0 text-dark">
					All the layout of Modernize Admin is Fully Responsive and
					widely tested.
				</p>
			</div>
		</div>
		<div class="col-sm-6 col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="1000" data-aos-duration="1000">
			<div class="text-center mb-5">
				<i class="d-block ti ti-database text-primary fs-10"></i>
				<h5 class="fs-5 fw-semibold mt-8">SassBase CSS</h5>
				<p class="mb-0 text-dark">
					Our Css is written Sass Base to make your life easier.
				</p>
			</div>
		</div>
		<div class="col-sm-6 col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="1200" data-aos-duration="1000">
			<div class="text-center mb-5">
				<i class="d-block ti ti-arrows-shuffle text-primary fs-10"></i>
				<h5 class="fs-5 fw-semibold mt-8">Easy to Customize</h5>
				<p class="mb-0 text-dark">
					Customization will be easy as we understand your pain.
				</p>
			</div>
		</div>
		<div class="col-sm-6 col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="1200" data-aos-duration="1000">
			<div class="text-center mb-5">
				<i class="d-block ti ti-chart-pie text-primary fs-10"></i>
				<h5 class="fs-5 fw-semibold mt-8">Lots of Chart Options</h5>
				<p class="mb-0 text-dark">
					You name it and we have it, Yes lots of variations for
					Charts.
				</p>
			</div>
		</div>
		<div class="col-sm-6 col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="1200" data-aos-duration="1000">
			<div class="text-center mb-5">
				<i class="d-block ti ti-layers-intersect text-primary fs-10"></i>
				<h5 class="fs-5 fw-semibold mt-8">Lots of Table Examples</h5>
				<p class="mb-0 text-dark">
					Data Tables are initial requirement and we added them.
				</p>
			</div>
		</div>
		<div class="col-sm-6 col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="1200" data-aos-duration="1000">
			<div class="text-center mb-5">
				<i class="d-block ti ti-refresh text-primary fs-10"></i>
				<h5 class="fs-5 fw-semibold mt-8">Regular Updates</h5>
				<p class="mb-0 text-dark">
					We are constantly updating our pack with new features.
				</p>
			</div>
		</div>
		<div class="col-sm-6 col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="1400" data-aos-duration="1000">
			<div class="text-center mb-5">
				<i class="d-block ti ti-book text-primary fs-10"></i>
				<h5 class="fs-5 fw-semibold mt-8">Detailed Documentation</h5>
				<p class="mb-0 text-dark">
					We have made detailed documentation, so it will easy to use.
				</p>
			</div>
		</div>
		<div class="col-sm-6 col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="1400" data-aos-duration="1000">
			<div class="text-center mb-5">
				<i class="d-block ti ti-calendar text-primary fs-10"></i>
				<h5 class="fs-5 fw-semibold mt-8">Calendar Design</h5>
				<p class="mb-0 text-dark">
					Calendar is available with our package & in nice design.
				</p>
			</div>
		</div>
		<div class="col-sm-6 col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="1400" data-aos-duration="1000">
			<div class="text-center mb-5">
				<i class="d-block ti ti-brand-wechat text-primary fs-10"></i>
				<h5 class="fs-5 fw-semibold mt-8">Dedicated Support</h5>
				<p class="mb-0 text-dark">
					We believe in supreme support is key and we offer that.
				</p>
			</div>
		</div>
		</div>
	</div>
</section>

{{-- <section class="py-md-5 mb-5">
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-lg-6">
				<div class="card c2a-box" data-aos="fade-up" data-aos-delay="1600" data-aos-duration="1000">
					<div class="card-body text-center p-4 pt-7">
						<h3 class="fs-7 fw-semibold pt-6">
						Haven't found an answer to your question?
						</h3>
						<p class="mb-7 pb-2 text-dark">
						Connect with us either on discord or email us
						</p>
						<div class="d-sm-flex align-items-center justify-content-center gap-3 mb-4">
							<a href="https://discord.com/invite/eMzE8F6Wqs" target="_blank" class="btn btn-primary d-block mb-3 mb-sm-0 btn-hover-shadow px-7 py-6" type="button">Ask on
								Discord</a>
							<a href="https://adminmart.com/support" target="_blank" class="btn btn-outline-secondary d-block px-7 py-6" type="button">Submit Ticket</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section> --}}

<section class="bg-primary pt-5 pb-8">
	<div class="container">
		<div class="row justify-content-between align-items-lg-center">
			<div class="col-lg-6 d-flex align-items-lg-center">
				<div class="card c2a-box w-100" data-aos="fade-up" data-aos-delay="1600" data-aos-duration="1000">
					<div class="card-body text-center p-4 pt-7">
						<h3 class="fs-7 fw-semibold pt-6">
						Haven't found an answer to your question?
						</h3>
						<p class="mb-7 pb-2 text-dark">
						Connect with us either on discord or email us
						</p>
						<div class="d-sm-flex align-items-center justify-content-center gap-3 mb-4">
							<a href="https://discord.com/invite/eMzE8F6Wqs" target="_blank" class="btn btn-primary d-block mb-3 mb-sm-0 btn-hover-shadow px-7 py-6" type="button">Ask on
								Discord</a>
							<a href="https://adminmart.com/support" target="_blank" class="btn btn-outline-secondary d-block px-7 py-6" type="button">Submit Ticket</a>
						</div>
					</div>
				</div>
			</div>
			<div class="col-lg-4 col-xl-5">
				<div class="text-center text-lg-end">
					<img src="../assets/images/backgrounds/business-woman-checking-her-mail.png" class="img-fluid" />
				</div>
			</div>
		</div>
	</div>
</section>

@php
    echo strtr(ob_get_clean(), $landingImageMap);
@endphp

@endsection
