<!DOCTYPE html>
<html lang="en" dir="ltr" data-bs-theme="light" data-color-theme="Blue_Theme" data-layout="vertical">

<head>
    <!-- Required meta tags -->
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Preload -->
    <link rel="preload" as="image" href="{{ asset('assets/custom/images/logos/logotxt_sigap_b.svg') }}">
    <link rel="preload" as="image" href="{{ asset('assets/custom/images/logos/logotxt_sigap_w.svg') }}">

    <!-- Favicon icon -->
    <link rel="shortcut icon" type="image/png" href="{{ asset('assets/custom/images/logos/sigap32.svg') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css" />

    <style>
        .header-fp .logo-img img {
            max-width: 120px;
            height: auto;
        }
    </style>
    <!-- Core Css -->
    <link rel="stylesheet" href="{{ asset('assets/custom/css/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/main/css/styles.css') }}">

    <!-- FullCalendar -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/main.min.css">

    <!-- Owl Carousel -->
    <link rel="stylesheet" href="{{ asset('assets/custom/libs/owl.carousel/owl.carousel.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/custom/libs/aos/dist/aos.css') }}" />

    <!-- Rating -->
    <link rel="stylesheet" href="{{ asset('assets/custom/libs/jquery-raty-js/lib/jquery.raty.css') }}">

    <!-- Sweet Alert -->
    <link rel="stylesheet" href="{{ asset('assets/custom/libs/sweetalert2/sweetalert2.min.css') }}">

    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <title>{{ $title ?? 'SIGAP' }}</title>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.body.classList.remove('preload');
        });
    </script>
</head>

<body class="preload">
