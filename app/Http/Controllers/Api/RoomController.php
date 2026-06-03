<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\{JsonResponse, Request};

class RoomController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Room::where('is_active', true)->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'              => 'required|string|max:60',
            'building'          => 'nullable|string|max:60',
            'capacity'          => 'required|integer|min:1',
            'type'              => 'required|in:lecture,lab,seminar',
            'wifi_bssid'        => 'nullable|string|max:60',
            'latitude'          => 'nullable|numeric|between:-90,90',
            'longitude'         => 'nullable|numeric|between:-180,180',
            'gps_radius_meters' => 'nullable|integer|min:10|max:500',
        ]);
        return response()->json(Room::create([...$data, 'department_id' => $request->user()->department_id]), 201);
    }

    public function update(Request $request, Room $room): JsonResponse
    {
        $data = $request->validate([
            'name'              => 'sometimes|string|max:60',
            'capacity'          => 'sometimes|integer|min:1',
            'wifi_bssid'        => 'nullable|string|max:60',
            'latitude'          => 'nullable|numeric',
            'longitude'         => 'nullable|numeric',
            'gps_radius_meters' => 'nullable|integer|min:10|max:500',
            'is_active'         => 'sometimes|boolean',
        ]);
        $room->update($data);
        return response()->json($room);
    }

    public function destroy(Room $room): JsonResponse
    {
        $room->update(['is_active' => false]);
        return response()->json(['message' => 'Room deactivated.']);
    }
}
