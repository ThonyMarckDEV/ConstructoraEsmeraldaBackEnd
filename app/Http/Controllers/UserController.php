<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function gerProjects()
    {
        $user = auth()->user();
        return response()->json($user->makeHidden(['password']));
    }



}