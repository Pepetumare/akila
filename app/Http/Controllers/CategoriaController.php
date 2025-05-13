<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function index()
    {
        $categorias = Categoria::all();
        return view('categorias.index', compact('categorias'));
    }

    public function show($slug)
    {
        $categoria = Categoria::where('slug', $slug)->firstOrFail();
        return view('categorias.show', compact('categoria'));
    }
}
