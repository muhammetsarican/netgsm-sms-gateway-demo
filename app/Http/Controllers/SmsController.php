<?php

namespace App\Http\Controllers;

use App\Actions\Sms\QueneSingleSms;
use App\Http\Requests\SendSmsRequest;
use App\Http\Resources\SmsMessageResource;
use App\Models\SmsMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SmsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function store(SendSmsRequest $request, QueneSingleSms $queueSingleSms): JsonResponse
    {
        //
        $message = $queueSingleSms->handle(
            phone: $request->phoneNumber(),
            body: $request->string('message')->toString(),
            sender: $request->input('sender'),
            idempotencyKey: $request->header('Idempotency-Key') ?? (string) Str::uuid(),
            callbackUrl: $request->input('callback_url')
        );

        return SmsMessageResource::make($message)->response()->setStatusCode(202)->header('Idempotent-Replayed', $message->wasRecentlyCreated ? 'false' : 'true');
    }

    /**
     * Display the specified resource.
     */
    public function show(SmsMessage $message): SmsMessageResource
    {
        //
        return SmsMessageResource::make($message);
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
