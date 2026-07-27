<?php

namespace App\Http\Controllers;

use App\Mail\RenewalMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EmailsController extends Controller
{
    public function renewalEmail()
    {
        Mail::to('apao@gmail.com')->send(new RenewalMail());

        return 'Email sent successfully';
    }
}