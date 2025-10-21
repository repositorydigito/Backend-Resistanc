<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Discipline;
use App\Models\Faq;
use App\Models\Membership;
use App\Models\Service;
use Illuminate\Http\Request;

class HomeController extends Controller
{


    public function index()
    {

        $company = Company::first();
        $membresias = Membership::all();
        $disciplines = Discipline::orderBy('sort_order', 'asc')->get();
        $services = Service::orderBy('order', 'asc')->where('is_active', true)->get();
        $faqs = Faq::orderBy('order', 'asc')->where('is_active', true)->get();

        return view('web.home', compact('membresias', 'disciplines', 'company', 'services','faqs'));
    }

}
