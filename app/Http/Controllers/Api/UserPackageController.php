<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserPackageRequest;
use App\Http\Resources\UserPackageResource;
use App\Models\UserPackage;
use App\Services\PackageValidationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @tags Mis Paquetes
 */
class UserPackageController extends Controller
{
    /**
     * Lista todos los paquetes del usuario autenticado
     *
     */




}
