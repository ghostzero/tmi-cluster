<?php

namespace GhostZero\TmiCluster\Http\Controllers;

use GhostZero\TmiCluster\Contracts\ChannelManager;
use GhostZero\TmiCluster\Contracts\Invitable;
use GhostZero\TmiCluster\Models\Channel;
use GhostZero\TmiCluster\Repositories\DummyChannelManager;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InviteController extends Controller
{
    public function index(Request $request, ChannelManager $channelManager)
    {
        if ($channelManager instanceof DummyChannelManager) {
            throw new HttpException(409, 'This chatbot does not support invites.');
        }

        $user = $this->getInvitableUser($request);

        return view('tmi-cluster::invite.index', [
            'connections' => Channel::query()->count(),
            'bot_username' => config('tmi-cluster.tmi.identity.username'),
            'invited' => $channelManager->authorized([$user->getTwitchLogin()]),
            'user_username' => $user->getTwitchLogin(),
        ]);
    }

    public function store(Request $request, ChannelManager $channelManager)
    {
        $user = $this->getInvitableUser($request);

        if ($channelManager->authorized([$user->getTwitchLogin()])) {
            $channelManager->revokeAuthorization($user);
        } else {
            $channelManager->authorize($user, [
                'reconnect' => true,
            ]);
        }

        return back();
    }

    private function getInvitableUser(Request $request): Invitable
    {
        $user = $request->user();

        if (!($user instanceof Invitable) || !$user->getTwitchLogin()) {
            throw new HttpException(409, 'You cannot invite this bot into your channel.');
        }

        return $user;
    }
}
