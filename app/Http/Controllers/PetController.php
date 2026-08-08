<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePetRequest;
use App\Http\Requests\UpdatePetRequest;
use App\Models\Pet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PetController extends Controller
{
    /**
     * Reading this in one place is the point: writes and URL generation used to
     * name their disks separately, which held together only because both
     * resolved to the same /storage path. On a bucket they would not.
     */
    private function disk(): string
    {
        return config('filesystems.photos');
    }

    public function index(): Response
    {
        return Inertia::render('Pets', [
            'pets' => auth()->user()->pets()->latest()->get()->map(fn (Pet $pet) => [
                'id' => $pet->id,
                'name' => $pet->name,
                'species' => $pet->species,
                'breed' => $pet->breed,
                'age' => $pet->age,
                'special_needs' => $pet->special_needs,
                'photo_url' => $pet->photo ? Storage::disk($this->disk())->url($pet->photo) : null,
            ]),
        ]);
    }

    public function store(StorePetRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('photo');

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->storePublicly('pet-photos', $this->disk());
        }

        $request->user()->pets()->create($data);

        return back()->with('success', 'Pet added.');
    }

    public function update(UpdatePetRequest $request, Pet $pet): RedirectResponse
    {
        $this->authorize('update', $pet);

        $data = $request->safe()->except('photo');

        if ($request->hasFile('photo')) {
            if ($pet->photo) {
                Storage::disk($this->disk())->delete($pet->photo);
            }
            $data['photo'] = $request->file('photo')->storePublicly('pet-photos', $this->disk());
        }

        $pet->update($data);

        return back()->with('success', 'Pet updated.');
    }

    public function destroy(Pet $pet): RedirectResponse
    {
        $this->authorize('delete', $pet);

        if ($pet->photo) {
            Storage::disk($this->disk())->delete($pet->photo);
        }

        $pet->delete();

        return back()->with('success', 'Pet removed.');
    }
}
