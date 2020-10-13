
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="René Preuß and TMI Cluster Contributors">
    <title>Dashboard &bull; TMI Cluster</title>
@stack('tmi-cluster::meta')

    <link rel="canonical" href="{{ request()->url() }}">

    <!-- Bootstrap core CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
    <link href="https://fa-cdn.bitinflow.com/releases/v5.13.0/css/all.min.css" rel="stylesheet" crossorigin="anonymous">

    <!-- Theme -->
    <meta name="theme-color" content="#343a40">

    @include('tmi-cluster::layouts.styles')
</head>
<body>
<div class="dashboard">
    <h1 class="text-center mb-3" style="font-weight: 900;">TMI Cluster</h1>

    @yield('content')

    <footer class="text-muted text-center">
        <small>
            Copyright &copy; {{ date('Y') }} René Preuß & Contributors
        </small>
    </footer>
</div>
</body>
</html>
