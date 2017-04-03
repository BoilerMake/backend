<?php

namespace App\Http\Controllers\API;

use Log;
use Auth;
use App\Models\User;
use App\Http\Controllers\Controller;

class SponsorController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    public function info()
    {
        Log::info(Auth::user()->roles()->get()->pluck('name'));
        if (! Auth::user()->hasRole('sponsor')) {//TODO middleware perhaps?
            return;
        }
        $hacker_info = '';
        if (Auth::user()->hasRole('sponsor-group-1')) {
            $hacker_info = 'No hacker information.';
        }
        if (Auth::user()->hasRole('sponsor-group-2')) {
            $hacker_info = 'Hacker information after BoilerMake.';
        }
        if (Auth::user()->hasRole('sponsor-group-3')) {
            $hacker_info = 'Hacker information before BoilerMake.';
        }

        return ['level'=>$hacker_info];
    }
}
