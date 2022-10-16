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
    <link rel="stylesheet" href="https://cdn.bitinflow.com/fontawesome/6.2.0/css/all.min.css"
          integrity="sha384-a/J8ePf6YlZ5aBQ6xmsPiCGV52z4VJMixEqX+4Mvoxi8flAWQA00ZFtk5qO5vyrP" crossorigin="anonymous">
    <link href="https://fonts.bunny.net/css2?family=Inter:wght@100;300;400;500;700;900&display=swap"
          rel="stylesheet" crossorigin="anonymous">
    <link rel="preload" as="font" href="https://cdn.bitinflow.com/fonts/JetBrainsMono-ExtraBold.woff2"
          integrity="sha384-O90Ey4hwu+amqcdiaazAUkVFmW9Rtvj7Muru0dYjEUGdiRYIRztSyIOwV5OmOfY9"
          type="font/woff2" crossorigin="anonymous">
    <link rel="preload" as="font" href="https://cdn.bitinflow.com/fonts/JetBrainsMono-Regular.woff2"
          integrity="sha384-QMyniVbTk4oi/X4p4HSbfku1TOppZPLcfrcflL8DwBuVOdMbBpodAfBr5Wa4AOMK"
          type="font/woff2" crossorigin="anonymous">
    <link href="{{ asset(mix('tmi-cluster.css', 'vendor/tmi-cluster')) }}" rel="stylesheet">

    <!-- Theme -->
    <meta name="theme-color" content="#6d00ff">
</head>
<body class="center">
<div id="app" class="app-center">
    <ul class="bg-bubbles">
        <li v-for="i in 10" :key="i"></li>
    </ul>
    @yield('content')
</div>

<script src="https://cdn.bitinflow.com/chart.js/2.9.3/Chart.min.js"
        integrity="sha384-i+dHPTzZw7YVZOx9lbH5l6lP74sLRtMtwN2XjVqjf3uAGAREAF4LMIUDTWEVs4LI"
        crossorigin="anonymous"></script>
<script src="{{ asset(mix('tmi-cluster.js', 'vendor/tmi-cluster')) }}"></script>
</body>
</html>
