<?php

declare(strict_types=1);

namespace App\Modules\Exhibition\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Modules\Exhibition\Models\Exhibitor;
use Illuminate\Database\Eloquent\Builder;

class ExhibitorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $event_id = $request->input('event_id');

        $query = Exhibitor::query();

        if ($event_id) {
            $query->where('event_id', $event_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $exhibitors = $query->with(['booths', 'members', 'leads.tags'])->get();

        return response()->json([
            'exhibitors' => $exhibitors,
        ]);
    }
}