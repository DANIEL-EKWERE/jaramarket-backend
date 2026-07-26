<?php

namespace App\Http\Controllers;

use App\Filters\StateFilter\Lga as StateFilterLga;
use App\Filters\StateFilter\Name as StateFilterName;
use App\Filters\StateFilter\Search as StateFilterSearch;
use App\Http\Resources\StatesAndLga\StateResource;
use App\Models\State;

class StateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     *π
     */
    public function index()
    {
        $states = State::with('lgas')->filterWithPipeline([
            StateFilterLga::class,
            StateFilterName::class,
            StateFilterSearch::class,
        ])->get();
        $states_collection = StateResource::collection($states);

        return response()->success('successful', $states_collection);
    }

    public function findState(State $state)
    {
        $state = $state->load('lgas');
        $state_collection = new StateResource($state);

        return response()->success('successful', $state_collection);
    }
}
