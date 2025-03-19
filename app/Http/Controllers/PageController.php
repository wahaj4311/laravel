<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    public function home()
    {
        return view('pages.home', [
            'title' => 'Home',
            'description' => 'Welcome to our Laravel Jenkins Practice Project'
        ]);
    }

    public function about()
    {
        return view('pages.about', [
            'title' => 'About',
            'description' => 'This is a practice project for Jenkins CI/CD integration'
        ]);
    }
}
