<?php

namespace App\Http\Controllers;

use App\Enums\AdvertTypeEnum;
use Exception;
use App\Enums\StatusEnum;
use App\Filters\LgaFilter\State;
use App\Models\Ingredient;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Advertisement;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\AdvertisementRequest;
use App\Http\Resources\AdvertisementResource;

class AdvertisementController extends Controller
{
    public function fetch_adverts()
    {
        try {
            $ads = Advertisement::where('status',StatusEnum::ACTIVE())->latest()->get();
            return response()->success('Advertisement retrieved successfully', AdvertisementResource::collection($ads), 201);
        } catch (Exception $e) {
            return response()->json(['error' => 'Could not fetch advertisements', 'details' => $e->getMessage()], 500);
        }
    }

    public function index()
    {
        $advertisements = Advertisement::latest()->paginate(10);
        return view('advertisements.index', compact('advertisements'));
    }

    public function create()
    {
        $ingredients = Ingredient::limit(300)->get();
        return view('advertisements.create', compact('ingredients'));
    }

    public function store(AdvertisementRequest $request)
    {
            $data = $request->validated();
            try {
                  $ad = DB::transaction(function () use ($data) {
                    $imagePath = isset($data['image']) ? upload_image("advertisement", $data['image'], $data) : null;
                    $ad = Advertisement::create([
                        'image' => $imagePath,
                        'type' => $data['type'],
                        'value' => $data['value'] ?? null,
                        'ingredient_ids' => $data['ingredient_ids'] ?? null,
                        'status' => $data['status'] ?? 'active',
                    ]);

                    // Apply discount or off if required
                    if (in_array($data['type'], [AdvertTypeEnum::DISCOUNT(), AdvertTypeEnum::OFF()]) && !empty($data['ingredient_ids'])) {
                        $ingredients = Ingredient::whereIn('id', $data['ingredient_ids'])->get();

                        foreach ($ingredients as $ingredient) {
                            if ($data['type'] === AdvertTypeEnum::DISCOUNT()) {
                                $discounted = $ingredient->price - ($ingredient->price * ($data['value'] / 100));
                            } else { // 'off'
                                $discounted = $ingredient->price - $data['value'];
                            }

                            $ingredient->discounted_price = max(0, $discounted);
                            $ingredient->save();
                        }
                    }

                    return $ad;
                });

                return redirect()->route('advertisements.index')
                       ->with('success', 'Advertisement created successfully.');

            } catch (Exception $e) {
                redirect()->route('advertisements.index')
                       ->with('success', $e->getMessage());
            }
        }

        public function edit(Advertisement $advertisement)
        {
            $ingredients = Ingredient::limit(300)->get();
            return view('advertisements.edit', compact('ingredients', 'advertisement'));
        }


        public function update(AdvertisementRequest $request,Advertisement $advertisement)
        {
                $data = $request->validated();
                try {
                      $ad = DB::transaction(function () use ($data, $advertisement) {
                        $imagePath = isset($data['image']) ? upload_image("advertisement", $data['image'], $data) : $advertisement->image;
                        $ad = Advertisement::update([
                            'image' => $imagePath,
                            'type' => $data['type'],
                            'value' => $data['value'] ?? null,
                            'ingredient_ids' => $data['ingredient_ids'] ?? null
                        ]);
    
                        // Apply discount or off if required
                        if (in_array($data['type'], [AdvertTypeEnum::DISCOUNT(), AdvertTypeEnum::OFF()]) && !empty($data['ingredient_ids'])) {
                            $ingredients = Ingredient::whereIn('id', $data['ingredient_ids'])->get();
    
                            foreach ($ingredients as $ingredient) {
                                if ($data['type'] === AdvertTypeEnum::DISCOUNT()) {
                                    $discounted = $ingredient->price - ($ingredient->price * ($data['value'] / 100));
                                } else { // 'off'
                                    $discounted = $ingredient->price - $data['value'];
                                }
    
                                $ingredient->discounted_price = max(0, $discounted);
                                $ingredient->save();
                            }
                        }
    
                        return $ad;
                    });
    
                    return redirect()->route('advertisements.index')
                           ->with('success', 'Advertisement updated successfully.');
    
                } catch (Exception $e) {
                    redirect()->route('advertisements.index')
                           ->with('error', $e->getMessage());
                }
            }

        public function destroy($id)
        {
            try {
                $advertisement = Advertisement::findOrFail($id);
                DB::transaction(function () use ($advertisement) {
                    if (in_array($advertisement->type, ['discount', 'off']) && !empty($advertisement->ingredient_ids)) {
                        $ingredients = Ingredient::whereIn('id', $advertisement->ingredient_ids)->get();
                        foreach ($ingredients as $ingredient) {


                            if($advertisement->status == StatusEnum::ACTIVE()){
                                $ingredient->discounted_price = 0;
                                $ingredient->save();
                            } else {
                                if ($advertisement->type === AdvertTypeEnum::DISCOUNT()) {
                                    $discounted = $ingredient->price - ($ingredient->price * ($advertisement->value / 100));
                                } else { // 'off'
                                    $discounted = $ingredient->price - $advertisement->value;
                                }
    
                                $ingredient->discounted_price = max(0, $discounted);
                                $ingredient->save();
                            }
                        }
                    }

                    $status = $advertisement->status === StatusEnum::ACTIVE() ? StatusEnum::STOP() : StatusEnum::ACTIVE();
                    $advertisement->update(['status' => $status]);
                });

                return redirect()->route('advertisements.index')
                       ->with('success', 'Advertisement stopped successfully.');

            } catch (Exception $e) {
                redirect()->route('advertisements.index')
                       ->with('error', $e->getMessage());
            }
        }
}
