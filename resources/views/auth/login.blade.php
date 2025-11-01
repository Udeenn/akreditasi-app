<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Data Perpustakaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="{{ asset('img/logo4.png') }}" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <style>
        html,
        body {
            height: 100%;
        }

        body {
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            background-image: url('{{ asset('img/home1.webp') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }

        .login-card {
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1);
        }

        .login-card .row.g-0 {
            height: 100%;
        }

        .login-image-section {
            padding: 1rem;
            margin: 0;
            position: relative;
        }

        .login-image-section img {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            max-width: 80%;
            max-height: 80%;
            width: auto;
            height: auto;
            display: block;
        }

        .login-form-section {
            padding: 3rem;
        }

        .login-form-section .form-label {
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
            color: #6c757d;
        }

        .login-form-section .form-control {
            border-radius: 0.25rem;
            padding: 0.75rem 1rem;
            border: 1px solid #ced4da;
        }

        .login-form-section .form-control:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        .login-form-section .btn-signin {
            background-color: #4A69FF;
            border: none;
            padding: 0.75rem;
            font-weight: bold;
            color: #fff;
            width: 100%;
        }

        .login-form-section .btn-signin:hover {
            background-color: #2449fe;
        }

        .login-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            margin-top: 1rem;
            margin-bottom: 1.5rem;
            color: #6c757d;
        }

        .login-options a {
            color: #6c757d;
            text-decoration: none;
        }

        .login-options a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9 col-md-10">
                <div class="card login-card">
                    <div class="row g-0">
                        <div class="col-md-5 login-image-section d-none d-md-block">
                            <img src="{{ asset('img/logo4a.png') }}" alt="umslibrary">
                        </div>

                        <div class="col-md-7 login-form-section">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h3 class="fw-bold mb-0">Masuk Sebagai Staff Perpustakaan</h3>
                            </div>
                            @if ($errors->any())
                                <div class="error">
                                    {{ $errors->first() }}
                                </div>
                            @endif
                            <form method="POST" action="{{ route('login') }}">
                                @csrf
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input id="username" type="text" class="form-control" name="username"
                                        value="{{ old('username') }}" required autocomplete="username" autofocus
                                        placeholder="Masukkan username Anda">
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input id="password" type="password" class="form-control" name="password" required
                                        autocomplete="current-password" placeholder="Masukkan password Anda">
                                </div>
                                <div class="d-flex gap-2 mb-3">
                                    <button type="submit" class="btn btn-signin flex-grow-1" style="min-width:0;">
                                        Masuk
                                    </button>
                                    <button type="button" class="btn btn-outline-success" onclick="history.back()">
                                        Kembali
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
