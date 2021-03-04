<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="René Preuß and TMI Cluster Contributors">
    <title>TMI Cluster</title>
    @stack('tmi-cluster::meta')

    <link rel="canonical" href="{{ request()->url() }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('vendor/tmi-cluster/favicon.ico') }}">

    <!-- Fonts & Core CSS -->
    <link href="https://fa-cdn.bitinflow.com/releases/v5.13.0/css/all.min.css" rel="stylesheet"
          crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;300;400;500;700;900&display=swap"
          rel="stylesheet" crossorigin="anonymous">
    <link rel="preload" as="font" href="https://fa-cdn.bitinflow.com/fonts/JetBrainsMono-ExtraBold.woff2" type="font/woff2"
          crossorigin="anonymous">
    <link rel="preload" as="font" href="https://fa-cdn.bitinflow.com/fonts/JetBrainsMono-Regular.woff2" type="font/woff2"
          crossorigin="anonymous">
    <link href="{{ asset(mix('tmi-cluster.css', 'vendor/tmi-cluster')) }}" rel="stylesheet">

    <!-- Theme -->
    <meta name="theme-color" content="#6d00ff">
</head>
<body>
<div id="app">
    @yield('content')

    <footer class="text-muted text-center mb-5">
        <small>
            Copyright &copy; {{ date('Y') }} René Preuß & Contributors
        </small>
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
<script src="{{ asset(mix('tmi-cluster.js', 'vendor/tmi-cluster')) }}"></script>
</body>
</html>
