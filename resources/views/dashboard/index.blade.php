@extends('tmi-cluster::layouts.app')

@section('content')
    <tmi-dashboard statistics-url="{{ action([\GhostZero\TmiCluster\Http\Controllers\DashboardController::class, 'statistics']) }}"></tmi-dashboard>
@endsection
