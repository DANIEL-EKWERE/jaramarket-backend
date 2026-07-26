<?php

namespace App\Http\Controllers;

use App\Filters\LgaFilter\Name as LgaFilterByName;
use App\Filters\LgaFilter\Search as LgaFilterSearch;
use App\Filters\LgaFilter\State as LgaFilterState;
use App\Http\Resources\StatesAndLga\LgaResource;
use App\Models\Lga;

class LgaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $lgas = Lga::with('state')->filterWithPipeline([
            LgaFilterState::class,
            LgaFilterByName::class,
            LgaFilterSearch::class,
        ])->get();
        $lga_collection = LgaResource::collection($lgas);

        return response()->success('successful', $lga_collection);
    }

    public function findLga(Lga $lga)
    {
        $state = $lga->load('state');
        $lga_collection = new LgaResource($state);

        return response()->success('successful', $lga_collection);
    }
}
