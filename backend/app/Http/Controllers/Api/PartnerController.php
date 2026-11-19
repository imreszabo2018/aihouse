<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    /**
     * List all partners.
     */
    public function index()
    {
        return response()->json(Partner::all());
    }

    /**
     * Show a single partner.
     */
    public function show(Partner $partner)
    {
        return response()->json($partner);
    }


    /**
     * Store a new partner.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tax_number' => 'nullable|string|max:50',
            'country_code' => 'nullable|string|size:2',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
        ]);

        $partner = Partner::create($validated);

        return response()->json($partner, 201);
    }


    /**
     * Update an existing partner.
     */
    public function update(Request $request, Partner $partner)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'tax_number' => 'sometimes|nullable|string|max:50',
            'country_code' => 'sometimes|nullable|string|size:2',
            'email' => 'sometimes|nullable|email|max:255',
            'address' => 'sometimes|nullable|string',
        ]);

        $partner->update($validated);

        return response()->json($partner);
    }


    /**
     * Delete an existing partner.
     */
    public function destroy(Partner $partner)
    {
        $partner->delete();

        return response()->json(null, 204);
    }

}
