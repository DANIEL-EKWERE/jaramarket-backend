<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Country;
use Illuminate\Http\Response;
use App\Filters\CountryFilter\Name as CountryFilterName;
use App\Filters\CountryFilter\Search as CountryFilterSearch;
use App\Http\Resources\CountryResource;
use App\Http\Resources\StatesAndLga\StateResource;

class CountryController extends Controller
{
    public function index()
    {
        try{
            
            $country = Country::orderby('name','asc')->filterWithPipeline([
                CountryFilterName::class,
                CountryFilterSearch::class,
            ])->get();
           
            return response()->success('Countries retrieved successfully', CountryResource::collection($country));
        
        } catch (Exception $e) {
            report($e);

            return response()->errorResponse(['message' => "Server error"], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function states($countryId)
    {
        try{
            
            $country = Country::with('states')->findOrFail($countryId);
            return response()->success('States retrieved successfully', StateResource::collection($country->states));
        
        } catch (Exception $e) {
            report($e);

            return response()->errorResponse(['message' => "Server error"], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
