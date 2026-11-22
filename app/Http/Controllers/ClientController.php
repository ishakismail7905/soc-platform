<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    /**
     * FR-14 reference pattern for every tenant-scoped page (alerts, cases, reports):
     * look the record up ONLY inside the signed-in user's client scope.
     * Another client's record and a missing one give the same 403 (wireframe 20).
     */
    public function show(Request $request, string $id): View
    {
        if (! ctype_digit($id)) {
            abort(403);
        }

        $client = Client::visibleTo($request->user())->find($id) ?? abort(403);

        return view('clients.show', ['client' => $client]);
    }
}