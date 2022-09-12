<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ResourceController;
use App\Http\Requests\Api\ReligionRequest;
use App\Models\Religion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ReligionController extends ResourceController
{
    public function index(Religion $religion, Request $request): JsonResource
    {
        return $this->all($religion, $request);
    }

    public function store(Religion $religion, ReligionRequest $request): JsonResource
    {
        $request->merge([
            'created_by' => $request->user()->id
        ]);
        return $this->post($religion, $request);
    }

    public function show(Religion $religion, Request $request): JsonResource
    {
        return $this->get($religion, $request);
    }

    public function update(Religion $religion, ReligionRequest $request): JsonResource
    {
        return $this->patch($religion, $request);
    }

    public function destroy(Religion $religion, Request $request): JsonResource
    {
        $request->merge([
            'deleted_by' => Auth::user()->id
        ]);
        return $this->delete($religion, $request);
    }
}
