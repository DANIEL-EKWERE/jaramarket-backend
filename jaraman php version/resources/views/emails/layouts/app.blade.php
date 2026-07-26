<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title') | {{ company('site_name', config('app.name')) }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        /* Reset */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { border-collapse: collapse !important; }
        body { margin: 0 !important; padding: 0 !important; }

        /* Responsive */
        @media screen and (max-width: 600px) {
            .email-container { width: 100% !important; }
            .email-body { padding: 15px !important; }
            h1 { font-size: 20px !important; }
        }

        /* Light mode */
        body { background-color: #f8f9fa; color: #333; font-family: Arial, sans-serif; }
        .email-container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; }

        /* Dark mode (supported clients) */
        @media (prefers-color-scheme: dark) {
            body { background-color: #121212; color: #ddd; }
            .email-container { background-color: #1e1e1e; border: 1px solid #333; }
            .email-footer { background: #222; color: #aaa; }
            .btn { background: #0d6efd !important; }
        }

        /* Button */
        .btn {
            display: inline-block;
            background: #0d6efd;
            color: #fff !important;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 4px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        
        {{-- Header --}}
        @include('emails.partials.header')

        {{-- Body --}}
        <div class="email-body" style="padding: 20px;">
            @yield('content')
        </div>

        {{-- Footer --}}
        @include('emails.partials.footer')

    </div>
</body>
</html>
