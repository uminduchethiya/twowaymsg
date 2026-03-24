<?php

namespace App\Http\Controllers;

use App\Events\DirectMessageSent;
use App\Events\MessageSent;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function index(): View
    {
        return view('chat');
    }

    // ── Public room messages ──────────────────────────────────────────────────

    public function messages(Request $request): JsonResponse
    {
        $room = $request->query('room', 'general');

        $messages = Message::whereNull('receiver_id')
            ->where('room', $room)
            ->latest()
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        return response()->json($messages);
    }

    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
            'room' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9\-]+$/'],
        ]);

        $message = Message::create([
            'user_id' => Auth::id(),
            'room'    => $validated['room'],
            'body'    => $validated['body'],
        ]);

        broadcast(new MessageSent($message));

        return response()->json($message, 201);
    }

    // ── Direct messages ───────────────────────────────────────────────────────

    public function users(): JsonResponse
    {
        $users = User::where('id', '!=', Auth::id())
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($users);
    }

    public function directMessages(Request $request): JsonResponse
    {
        $request->validate([
            'with' => ['required', 'integer', 'exists:users,id'],
        ]);

        $messages = Message::conversation(Auth::id(), (int) $request->query('with'))
            ->latest()
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        return response()->json($messages);
    }

    public function sendDirect(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'body'        => ['required', 'string', 'max:1000'],
            'receiver_id' => ['required', 'integer', 'exists:users,id', 'different:' . Auth::id()],
        ]);

        $message = Message::create([
            'user_id'     => Auth::id(),
            'receiver_id' => $validated['receiver_id'],
            'room'        => 'dm',
            'body'        => $validated['body'],
        ]);

        broadcast(new DirectMessageSent($message));

        return response()->json($message, 201);
    }
}
