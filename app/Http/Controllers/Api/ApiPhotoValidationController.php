<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\KimiVisionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ApiPhotoValidationController extends Controller
{
    public function __invoke(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|max:10240',
            'type' => 'required|in:nameplate,dashboard',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'valid' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $path = $request->file('photo')->store('temp/validation', 'local');

        try {
            $fullPath = Storage::disk('local')->path($path);
            $result = app(KimiVisionService::class)->validatePhoto($fullPath, $request->input('type'));

            return response()->json($result);
        } finally {
            if (! empty($path)) {
                Storage::disk('local')->delete($path);
            }
        }
    }
}
