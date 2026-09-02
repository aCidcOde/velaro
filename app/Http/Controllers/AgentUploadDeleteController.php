<?php

namespace App\Http\Controllers;

use App\Models\AgentUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AgentUploadDeleteController extends Controller
{
    public function __invoke(Request $request, AgentUpload $upload): JsonResponse
    {
        abort_unless($upload->user_id === $request->user()?->id, 403);

        if ($upload->status !== AgentUpload::STATUS_FAILED) {
            return response()->json([
                'message' => 'Apenas uploads com falha podem ser apagados.',
            ], 422);
        }

        if (is_string($upload->local_path) && $upload->local_path !== '' && Storage::disk('local')->exists($upload->local_path)) {
            Storage::disk('local')->delete($upload->local_path);
        }

        $uploadId = $upload->id;
        $upload->delete();

        return response()->json([
            'ok' => true,
            'upload_id' => $uploadId,
        ]);
    }
}
