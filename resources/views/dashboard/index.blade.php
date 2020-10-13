@extends('tmi-cluster::layouts.app')

@push('tmi-cluster::meta')
    <meta http-equiv="refresh" content="15">
@endpush

@section('content')
    <div class="row">
        <div class="col-4">
            <div class="card text-white bg-info mb-3">
                <div class="card-body">
                    <h4 class="card-title">{{ $messages }}</h4>
                    <h5 class="card-text">Messages/s</h5>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card text-white bg-warning mb-3">
                <div class="card-body">
                    <h4 class="card-title">{{ $channels }}</h4>
                    <h5 class="card-text">Channels</h5>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card text-white bg-danger mb-3">
                <div class="card-body">
                    <h4 class="card-title">{{ $processes }}</h4>
                    <h5 class="card-text">Processes</h5>
                </div>
            </div>
        </div>
    </div>

    @foreach($supervisors as $supervisor)
    <div class="card mb-3">
        <table class="table mb-0">
            <thead class="thead-dark">
            <tr>
                <th scope="col" colspan="5">
                    Supervisor {{ $supervisor->name }}
                </th>
            </tr>
            </thead>
            <thead class="thead-light">
            <tr>
                <th scope="col"></th>
                <th scope="col">UUID</th>
                <th scope="col">State</th>
                <th scope="col">Last Ping</th>
                <th scope="col">Channels</th>
            </tr>
            </thead>
            <tbody>
            @foreach($supervisor->processes as $process)
                <tr>
                    <th scope="row" style="width: 30px; padding-right: 0;">
                        @if($process->state === \GhostZero\TmiCluster\Models\SupervisorProcess::STATE_CONNECTED)
                            <i class="far fa-check-circle text-success"></i>
                        @else
                            <i class="far fa-exclamation-triangle text-danger"></i>
                        @endif
                    </th>
                    <th scope="row">
                        {{ explode('-', $process->getKey())[0] }}
                    </th>
                    <td>
                        {{ $process->state }}
                    </td>
                    <td>
                        {{ $process->last_ping_at ? $process->last_ping_at->diffInSeconds() : 'N/A', }}
                        sec
                    </td>
                    <td>{{ count($process->channels) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endforeach
@endsection
