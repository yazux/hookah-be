@yield('emails.email_header')

<h3 style='text-align: center;'>Подтверждение e-mail</h3>

<div>Для подтверждения e-mail адреса, пожалуйста, пройдите по ссылке ниже.</div>

<h4 style='text-align: center;'>Учетные данные:</h4>

<div>E-mail: <span style='font-style: italic;'>{{$email}}</span></div>

<div>Код-подтверждения: <a href='{{$link}}'>{{$code}}</a></div>

<a href='{{$link}}'><h3 style='text-align: center;'>Подтвердить e-mail</h3></a>

@yield('emails.email_footer')