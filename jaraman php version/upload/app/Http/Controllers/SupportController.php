<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Support;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Requests\SupportRequest;
use App\Http\Resources\SupportResource;

class SupportController extends Controller
{
    public function store(SupportRequest $request)
    {
        try {
            
            $data = $request->validated();
            $data['user_id'] = auth()->id();
            $data['attachment'] = isset($data['attachment']) ? upload_image("supports", $data['attachment'], $data) : null;
            $support = Support::create($data);
            
            return response()->success('Support submitted successfully', new SupportResource($support), 201);
        } catch (Exception $e) {
            report($e);

            return response()->errorResponse($e->getMessage(), [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function index()
    {
        try {
            $supports = Support::where('user_id', auth()->id())->latest()->get();
            return response()->success('Support retrieved successfully', SupportResource::collection($supports), 201);
        } catch (Exception $e) {
            report($e);

            return response()->errorResponse($e->getMessage(), [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Support $support)
    {
        try {
            return response()->success('Support retrieved successfully', new SupportResource($support), 201);
        } catch (Exception $e) {
            report($e);

            return response()->errorResponse($e->getMessage(), [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}
