<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    // Mostrar vista con formulario editable
    public function edit()
    {
        return view('profile.edit', [
            'user' => Auth::user(),
        ]);
    }

    // Guardar cambios del formulario
    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email,' . $user->id,
            'phone'     => 'nullable|string|max:20',
            'address'   => 'nullable|string|max:255',
            'birthdate' => 'nullable|date',
            'password'  => 'nullable|string|min:8|confirmed',
            'avatar'    => 'nullable|image|max:2048', // máximo 2MB
        ]);

        if ($request->hasFile('avatar')) {
            // Elimina el avatar anterior si existe
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
        }

        $user->fill(collect($validated)->except('password')->toArray());


        if ($validated['password']) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return back()->with('success', 'Perfil actualizado con éxito.');
    }

    public function orders()
    {
        $orders = auth()->user()->orders()->latest()->get(); // si tienes relación definida
        return view('profile.pedidos', compact('orders'));
    }
}
