<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Discipline;
use App\Models\LegalPolicy;
use App\Models\Membership;
use Illuminate\Http\Request;

class PrivacityController extends Controller
{


    public function privacy()
    {


        $privacies = LegalPolicy::where('type', 'privacy')->get();

        return view('client.privacity', compact('privacies'));
    }

    public function terms()
    {
        $terms = LegalPolicy::where('type', 'term')->get();

        return view('client.terms', compact('terms'));
    }
}
