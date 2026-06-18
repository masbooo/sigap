@extends('layouts.error-admin')

@section('content')

<div class="position-relative min-vh-100 w-100 d-flex align-items-center justify-content-center">
    <div class="row justify-content-center w-100">
        <div class="col-lg-5">
            <div class="text-center">
                <img src="<?= base_url('assets/custom/images/backgrounds/errorimg.svg') ?>" alt="error-500" class="img-fluid mb-4" width="420">
                <h1 class="fw-semibold mb-3 fs-9">500</h1>
                <h4 class="fw-semibold mb-4">An internal server error has occurred in the admin panel.</h4>
                <a class="btn btn-primary" href="<?= base_url('admin/infografis') ?>" role="button">Back to Dashboard</a>
            </div>
        </div>
    </div>
</div>

@endsection