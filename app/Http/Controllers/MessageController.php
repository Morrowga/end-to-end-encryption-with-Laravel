<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Message;
use phpseclib\Crypt\RSA;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\MessageRequest;
use App\Http\Resources\MessageResource;

class MessageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $messages = Message::where('recipient_id', Auth::id())->get();

            $decryptedMessages = new Collection();
            foreach ($messages as $message) {
                $encryptedMessage = base64_decode($message->message);

                $rsa = new RSA();
                $rsa->loadKey(Auth::user()->private_key);

                $decryptedMessage = $rsa->decrypt($encryptedMessage);

                $decryptedMessageArray = [
                    'id' => $message->id,
                    'sender_id' => $message->sender_id,
                    'recipient_id' => $message->recipient_id,
                    'message' => $decryptedMessage,
                ];

                $decryptedMessages->push($decryptedMessageArray);
            }

            return response()->json([
                'success' => true,
                'data' => MessageResource::collection($decryptedMessages),
            ]);
        } catch (\Exception $th) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching messages.',
                'error' => $e->getMessage(),
            ], 500);
        }

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MessageRequest $request)
    {
        try {
            DB::beginTransaction();

            $recipient = User::findOrFail($request->recipient_id);

            $rsa = new RSA();
            $rsa->loadKey($recipient->public_key);

            $encryptedMessage = base64_encode($rsa->encrypt($request->message));

            $message = Message::create([
                'sender_id' => Auth::user()->id,
                'recipient_id' => $recipient->id,
                'message' => $encryptedMessage,
            ]);

            DB::commit();

            return response()->json([
                "success" => true,
                "data" => new MessageResource($message)
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching messages.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
