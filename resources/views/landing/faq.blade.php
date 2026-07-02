@extends('layouts.landing')

@section('content')

@php
    $regionOrder = ['Pusat', 'Timur', 'Selatan', 'Barat', 'Utara'];

    $regionIcons = [
        'Pusat'   => 'ti ti-medical-cross-circle fs-7 me-2',
        'Timur'   => 'ti ti-map-east fs-7 me-2',
        'Selatan' => 'ti ti-map-south fs-7 me-2',
        'Barat'   => 'ti ti-map-west fs-7 me-2',
        'Utara'   => 'ti ti-map-north fs-7 me-2',
    ];
@endphp

<section class="bg-primary-subtle py-14">
    <div class="container-fluid">
        <div class="text-center">
            <p class="text-primary fs-4 fw-bolder" data-aos="fade-up" data-aos-delay="200" data-aos-duration="1000">
                KONTAK
            </p>
            <h1 class="fw-bolder fs-12" data-aos="fade-up" data-aos-delay="400" data-aos-duration="1000">
                Siap Melayani Anda!
            </h1>
        </div>
    </div>
</section>

<div class="container-fluid" data-aos="fade-up" data-aos-delay="800" data-aos-duration="1000">
    <div class="row mt-5 justify-content-center">
        <div class="col-lg-9">
            <div class="text-center mb-7">
                <h3 class="fw-semibold">Pertanyaan yang sering diajukan</h3>
                <p class="fw-normal mb-0 fs-4">Panduan singkat untuk membantu memudahkan penggunaan aplikasi SIGAP</p>
            </div>

            <div class="accordion accordion-flush mb-5 card position-relative overflow-hidden" id="accordionUserFaq">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq-heading-1">
                        <button class="accordion-button collapsed fs-4 fw-semibold shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse-1" aria-expanded="false" aria-controls="faq-collapse-1">
                            Bagaimana cara melihat detail gedung serbaguna sebelum menyewa?
                        </button>
                    </h2>
                    <div id="faq-collapse-1" class="accordion-collapse collapse" aria-labelledby="faq-heading-1" data-bs-parent="#accordionUserFaq">
                        <div class="accordion-body fw-normal">
                            Buka menu <b>Gedung</b>, kemudian pilih Wilayah maka semua gedung yang berada di Wilayah tersebut akan muncul. Terlampir masing-masing detail gedung serbaguna yang berisi informasi seperti foto gedung, alamat, kapasitas, fasilitas dan tarif sewa.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq-heading-2">
                        <button class="accordion-button collapsed fs-4 fw-semibold shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse-2" aria-expanded="false" aria-controls="faq-collapse-2">
                            Apakah harus bekerjasama dengan UMKM ketika menyewa?
                        </button>
                    </h2>
                    <div id="faq-collapse-2" class="accordion-collapse collapse" aria-labelledby="faq-heading-2" data-bs-parent="#accordionUserFaq">
                        <div class="accordion-body fw-normal">
                            Ya. Untuk mendukung pertumbuhan ekonomi UMKM di sekitar gedung serbaguna, maka diharapkan agar dapat bekerjasama dengan UMKM ketika melakukan sewa gedung serbaguna 
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq-heading-3">
                        <button class="accordion-button collapsed fs-4 fw-semibold shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse-3" aria-expanded="false" aria-controls="faq-collapse-3">
                            Bagaimana cara melihat detail UMKM sebelum menyewa?
                        </button>
                    </h2>
                    <div id="faq-collapse-3" class="accordion-collapse collapse" aria-labelledby="faq-heading-3" data-bs-parent="#accordionUserFaq">
                        <div class="accordion-body fw-normal">
                            Buka menu <b>UMKM</b>, maka akan muncul seluruh Daftar UMKM. Gunakan filter Kategori, Wilayah, Lokasi untuk lebih mempersempit pilihan UMKM
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq-heading-4">
                        <button class="accordion-button collapsed fs-4 fw-semibold shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse-4" aria-expanded="false" aria-controls="faq-collapse-4">
                            Bagaimana cara mengetahui apakah gedung tersedia pada tanggal tertentu?
                        </button>
                    </h2>
                    <div id="faq-collapse-4" class="accordion-collapse collapse" aria-labelledby="faq-heading-4" data-bs-parent="#accordionUserFaq">
                        <div class="accordion-body fw-normal">
                            Buka menu <b>Jadwal</b>, lalu Pilih Lokasi (Wilayah, Kecamatan dan Gedung yang diinginkan). Kemudian silakan cek di bagian Kalender dengan klik tombol panah (<b><i class="ti ti-chevron-left"></i></b>) atau (<b><i class="ti ti-chevron-right"></i></b>) untuk memilih Bulan yang diinginkan. Apabila tanggal tertentu pada Kalender kosong, maka gedung dapat digunakan pada tanggal tertentu tersebut.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq-heading-5">
                        <button class="accordion-button collapsed fs-4 fw-semibold shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse-5" aria-expanded="false" aria-controls="faq-collapse-4">
                            Apakah ada batas waktu minimum pemesanan gedung?
                        </button>
                    </h2>
                    <div id="faq-collapse-5" class="accordion-collapse collapse" aria-labelledby="faq-heading-5" data-bs-parent="#accordionUserFaq">
                        <div class="accordion-body fw-normal">
                            Ya. Demi kelancaran administrasi, batas waktu minimum pemesanan gedung adalah H-14 atau 14 hari sebelum tanggal pelaksanaan kegiatan.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq-heading-6">
                        <button class="accordion-button collapsed fs-4 fw-semibold shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse-6" aria-expanded="false" aria-controls="faq-collapse-6">
                            Bagaimana cara melakukan pemesanan gedung?
                        </button>
                    </h2>
                    <div id="faq-collapse-6" class="accordion-collapse collapse" aria-labelledby="faq-heading-6" data-bs-parent="#accordionUserFaq">
                        <div class="accordion-body fw-normal">
                            Klik <b>MASUK</b> di pojok kanan atau klik (<b><i class="ti ti-menu-2"></i></b>) kemudian klik MASUK untuk masuk ke menu Login SIGAP. Klik Buat akun disini! untuk membuat akun kemudian masukkan Username dan Password untuk masuk ke menu User. Setelah itu klik menu Reservasi lalu klik Reservasi Sekarang.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card bg-primary-subtle rounded-2">
        <div class="card-body text-center">
            <div class="d-flex align-items-center justify-content-center mb-4 pt-8">
                <img src="{{ asset_url('assets/custom/images/profile/user-3.jpg') }}" class="rounded-circle me-n2 card-hover border border-2 border-white" width="44" height="44" alt="User 1">
                <img src="{{ asset_url('assets/custom/images/profile/user-2.jpg') }}" class="rounded-circle me-n2 card-hover border border-2 border-white" width="44" height="44" alt="User 2">
                <img src="{{ asset_url('assets/custom/images/profile/user-4.jpg') }}" class="rounded-circle me-n2 card-hover border border-2 border-white" width="44" height="44" alt="User 3">
            </div>
            <h3 class="fw-semibold">Butuh bantuan lanjutan?</h3>
            <p class="fw-normal mb-4 fs-4">Mulai dengan menghubungi Kecamatan sesuai lokasi gedung serbaguna pilihan Anda</p>
            <div class="d-flex align-items-center justify-content-center gap-3 flex-wrap mb-2">
                <a href="{{ base_url('kontak') }}" class="btn btn-outline-primary">Buka Kontak</a>
            </div>
        </div>
    </div>
</section>

<script>
    window.contactMapDefault = <?php echo json_encode($mapDefault ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    window.contactAllData = <?php echo json_encode($allContacts ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    window.contactGroupByRegion = <?php echo json_encode($contactGroup ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>

@endsection