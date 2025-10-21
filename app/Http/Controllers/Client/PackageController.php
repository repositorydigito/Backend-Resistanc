<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\Package;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index()
    {
        // Obtener membresías para la sección de beneficios
        $membreships = Membership::with(['discipline'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Obtener estadísticas generales
        $stats = [
            'total_packages' => Package::where('buy_type', 'affordable')
                ->where('status', 'active')
                ->where('is_membresia', false)
                ->count(),
            'total_membreships' => Package::where('buy_type', 'affordable')
                ->where('status', 'active')
                ->where('is_membresia', true)
                ->count(),
        ];

        return view('client.packages', compact('membreships', 'stats'));
    }
}
