<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Discipline;
use App\Models\Membership;
use Illuminate\Http\Request;

class HomeController extends Controller
{


    public function index()
    {

        $membresias = Membership::all();
        $disciplines = Discipline::all();

        return view('client.home', compact('membresias', 'disciplines'));
    }

}
