@extends('tmi-cluster::layouts.app')

@section('content')
    <tmi-dashboard
            assets-url="{{ asset('vendor/tmi-cluster') }}"
            dashboard-url="{{ action([\GhostZero\TmiCluster\Http\Controllers\DashboardController::class, 'index']) }}">
    </tmi-dashboard>
@endsection
