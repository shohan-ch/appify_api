@component('mail::message')
# Introduction

The body of your message.

@component('mail::button', ['url' => ''])
Verify Code

@php

echo $user->verify_code

@endphp


@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent