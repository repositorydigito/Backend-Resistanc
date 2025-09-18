<?php

namespace App\Observers;

use App\Models\FootwearLoan;
use Illuminate\Support\Facades\Auth;

class FootwearLoanObserver
{
    public function creating(FootwearLoan $footwearLoan)
    {
        // Si no se ha definido el user_id, asigna el usuario autenticado
        if (empty($footwearLoan->user_id) && Auth::check()) {
            $footwearLoan->user_id = Auth::id();
        }
    }
}
