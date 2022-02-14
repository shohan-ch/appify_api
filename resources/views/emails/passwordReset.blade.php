@component('mail::message')
# Introduction

The body of your message.

@component('mail::button', ['url' => $redirect_url])
Password Reset Link
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent