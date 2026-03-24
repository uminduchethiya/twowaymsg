<?php

use App\Events\DirectMessageSent;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Public chat room channels
Broadcast::channel('chat.{room}', function () {
    return true;
});

// Private DM channel — only the two participants may subscribe
// Channel name: dm.{minId}.{maxId}
Broadcast::channel('dm.{a}.{b}', function ($user, $a, $b) {
    $key = DirectMessageSent::dmChannelKey((int) $a, (int) $b);
    $myKey = DirectMessageSent::dmChannelKey($user->id, (int) $a === $user->id ? (int) $b : (int) $a);
    return $key === $myKey && ((int) $user->id === (int) $a || (int) $user->id === (int) $b);
});
