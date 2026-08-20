<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    /**
     * Switch the application language.
     */
    public function switch(Request $request, string $locale)
    {
        // Validate locale
        if (in_array($locale, ['en', 'ar'])) {
            Session::put('locale', $locale);
        }

        return redirect()->back();
    }
}
