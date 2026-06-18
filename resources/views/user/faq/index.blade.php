@extends('layouts.user')

@section('content')
<div class="container-fluid">
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">FAQ User</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <span class="text-muted">Pengaturan</span>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">
                                FAQ
                            </li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3">
                    <div class="text-center mb-n5">
                        <img src="{{ base_url('assets/custom/images/breadcrumb/ChatBc.png') }}" class="img-fluid mb-n4" alt="Breadcrumb">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="text-center mb-7">
                <h3 class="fw-semibold">Pertanyaan yang sering diajukan</h3>
                <p class="fw-normal mb-0 fs-4">Panduan singkat untuk membantu penggunaan menu user pada aplikasi SIGAP.</p>
            </div>

            <div class="accordion accordion-flush mb-5 card position-relative overflow-hidden" id="accordionUserFaq">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq-heading-one">
                        <button class="accordion-button collapsed fs-4 fw-semibold shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse-one" aria-expanded="false" aria-controls="faq-collapse-one">
                            Bagaimana cara melengkapi profil saya?
                        </button>
                    </h2>
                    <div id="faq-collapse-one" class="accordion-collapse collapse" aria-labelledby="faq-heading-one" data-bs-parent="#accordionUserFaq">
                        <div class="accordion-body fw-normal">
                            Buka menu <b>Dasbor</b>, lalu lengkapi biodata yang diminta seperti NIK, nama, alamat, kecamatan, kelurahan, dan nomor HP. Beberapa fitur SIGAP baru dapat dipakai setelah profil lengkap.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq-heading-two">
                        <button class="accordion-button collapsed fs-4 fw-semibold shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse-two" aria-expanded="false" aria-controls="faq-collapse-two">
                            Bagaimana cara membuat reservasi?
                        </button>
                    </h2>
                    <div id="faq-collapse-two" class="accordion-collapse collapse" aria-labelledby="faq-heading-two" data-bs-parent="#accordionUserFaq">
                        <div class="accordion-body fw-normal">
                            Masuk ke menu <b>Reservasi</b>, pilih jadwal dan kebutuhan penggunaan, lalu kirim permohonan. Status pengajuan akan tampil pada daftar reservasi Anda.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq-heading-three">
                        <button class="accordion-button collapsed fs-4 fw-semibold shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse-three" aria-expanded="false" aria-controls="faq-collapse-three">
                            Kapan menu pembayaran bisa digunakan?
                        </button>
                    </h2>
                    <div id="faq-collapse-three" class="accordion-collapse collapse" aria-labelledby="faq-heading-three" data-bs-parent="#accordionUserFaq">
                        <div class="accordion-body fw-normal">
                            Menu <b>Pembayaran</b> menampilkan data reservasi yang sudah disetujui. Jika belum ada data, artinya pengajuan Anda masih diproses atau belum lolos verifikasi.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq-heading-four">
                        <button class="accordion-button collapsed fs-4 fw-semibold shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse-four" aria-expanded="false" aria-controls="faq-collapse-four">
                            Mengapa saya tidak bisa membuka menu rating?
                        </button>
                    </h2>
                    <div id="faq-collapse-four" class="accordion-collapse collapse" aria-labelledby="faq-heading-four" data-bs-parent="#accordionUserFaq">
                        <div class="accordion-body fw-normal">
                            Menu rating memerlukan profil aktif dan lengkap. Selain itu, rating hanya tersedia ketika ada data reservasi yang memang menunggu ulasan dari Anda.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq-heading-five">
                        <button class="accordion-button collapsed fs-4 fw-semibold shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse-five" aria-expanded="false" aria-controls="faq-collapse-five">
                            Di mana saya bisa melihat ringkasan akun saya?
                        </button>
                    </h2>
                    <div id="faq-collapse-five" class="accordion-collapse collapse" aria-labelledby="faq-heading-five" data-bs-parent="#accordionUserFaq">
                        <div class="accordion-body fw-normal">
                            Buka menu <b>Profil</b> dari sidebar atau dari dropdown akun di kanan atas. Di halaman tersebut Anda bisa melihat biodata yang tersimpan pada akun user SIGAP Anda.
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
            <p class="fw-normal mb-4 fs-4">Mulai dari dasbor untuk melengkapi profil, lalu lanjutkan ke reservasi sesuai kebutuhan Anda.</p>
            <div class="d-flex align-items-center justify-content-center gap-3 flex-wrap mb-2">
                <a href="{{ base_url('user/dasbor') }}" class="btn btn-primary">Buka Dasbor</a>
                <a href="{{ base_url('user/reservasi') }}" class="btn btn-outline-primary">Buka Reservasi</a>
            </div>
        </div>
    </div>
</div>
@endsection
