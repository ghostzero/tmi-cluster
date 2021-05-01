@extends('tmi-cluster::layouts.astro')

@section('content')
    <main class="form-signin">
        <div class="card text-white bg-primary border-0 mb-3">
            <form method="post"
                  action="{{ action([\GhostZero\TmiCluster\Http\Controllers\InviteController::class, 'store']) }}">
                @csrf
                <div class="card-body">
                    <div class="d-flex justify-content-center my-4">
                        <img class="rounded-circle"
                             src="https://sfresolvers.b-cdn.net/avatars/twitch/{{ $bot_username }}" alt="" width="80">
                        <i class="fas fa-ellipsis-h align-self-center px-4 opacity-40"></i>
                        <img class="rounded-circle align-self-center"
                             src="https://sfresolvers.b-cdn.net/avatars/twitch/{{ $user_username }}" alt="" width="80">
                    </div>
                    <div class="text-center">
                        <h1 class="h3 fw-normal">
                            {{ config('tmi-cluster.tmi.identity.username') }}
                            <i class="fas fa-robot"></i>
                        </h1>
                        @if($invited)
                            <h2 class="h6 mb-3">
                                Is now in your Twitch chat
                            </h2>
                        @else
                            <h2 class="h6 mb-3">
                                Likes to join your Twitch chat
                            </h2>
                        @endif
                        <p class="small opacity-70">
                            Signed in as {{ $user_username }}
                        </p>
                    </div>
                    <hr/>
                    <div class="text-center small opacity-50">
                        Used in {{ $connections }} channels<br><br>
                        This bot <b>cannot</b> read your private messages or send messages as you.
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between bg-dark">
                    <a href="#" class="align-self-center text-decoration-none">Cancel</a>
                    @if($invited)
                        <button class="btn my-1 btn-link text-danger text-decoration-none" type="submit">
                            Revoke Authorization
                        </button>
                    @else
                        <button class="btn my-1 btn-primary" type="submit">
                            Authorize
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </main>
@endsection
