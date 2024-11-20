<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;


class LogoController extends Controller
{
    public function showForm()
    {
        $icons = Storage::disk('public')->files('icons/auto');
        $fonts = Storage::disk('public')->files('fonts');
        return view('page.logo-generate', compact('icons', 'fonts'));
    }
}
