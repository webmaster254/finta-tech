<?php

namespace App\Http\Controllers\v1;

use Log;
use Exception;
use Illuminate\Http\Request;
use Awcodes\Curator\Models\Media;
use App\Http\Controllers\Controller;

class MediaController extends Controller
{

        public function index(Request $request)
        {
            $id = $request->media_id;
            $media = Media::find($id);
            return response()->json(['media' => $media]);
}
}
