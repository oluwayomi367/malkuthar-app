<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Mail\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(): View
    {
        return view('storefront.contact');
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        $key = 'contact:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            return back()->with('error', 'Trop de messages envoyés. Réessayez dans quelques minutes.');
        }

        RateLimiter::hit($key, 600);

        $data = $request->validated();

        Mail::to(config('malkuthar.contact.email'))->queue(new ContactMessage(
            senderName: $data['name'],
            senderEmail: $data['email'],
            subjectLabel: $request->subjectLabel(),
            body: $data['message'],
        ));

        return back()->with('success', 'Votre message a bien été envoyé. Nous vous répondrons rapidement.');
    }
}
