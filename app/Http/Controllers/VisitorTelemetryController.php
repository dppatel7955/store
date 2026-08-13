<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Visitor;

class VisitorTelemetryController extends Controller
{
    public function update(Request $request)
    {
        $uuid = $request->cookie('saffron_visitor_uuid') ?: $request->input('visitor_uuid');

        if (! $uuid) {
            return response()->json(['status' => 'ignored'], 200);
        }

        $screen = $request->input('screen'); // e.g. "1920x1080"
        $timezone = $request->input('timezone'); // e.g. "Asia/Kolkata"
        $language = $request->input('language'); // e.g. "en-IN"
        $connection = $request->input('connection'); // e.g. "4g"

        $visitor = Visitor::where('visitor_uuid', $uuid)->first();
        if ($visitor) {
            $updateData = [];
            if ($screen && ! $visitor->screen_resolution) {
                $updateData['screen_resolution'] = substr($screen, 0, 35);
            }
            if ($timezone && ! $visitor->timezone) {
                $updateData['timezone'] = substr($timezone, 0, 55);
            }
            if ($language && (! $visitor->language || strlen($visitor->language) <= 2)) {
                $updateData['language'] = substr($language, 0, 25);
            }
            if ($connection && ! $visitor->connection_type) {
                $updateData['connection_type'] = substr(strtoupper($connection), 0, 30);
            }

            if (! empty($updateData)) {
                $visitor->update($updateData);
            }
        }

        return response()->json(['status' => 'success'], 200);
    }
}
