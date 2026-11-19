<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Partner;

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
}
