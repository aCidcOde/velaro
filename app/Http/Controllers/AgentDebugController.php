<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgentDebugRequest;
use Illuminate\Http\JsonResponse;

class AgentDebugController extends Controller
{
    public function __invoke(AgentDebugRequest $request): JsonResponse
    {
        $message = trim((string) $request->input('message', 'Teste de conectividade.'));

        return response()->json([
            'ok' => true,
            'message' => 'Diagnostico local concluido.',
            'echo' => $message,
            'queue_connection' => config('queue.default'),
        ]);
    }
}
