@yield('emails.email_header')

<h3 style='text-align: center;'>Восстановление пароля</h3>
<div>По Вашему запросу был сгенерирован новый пароль к личному кабинету. Вы можете авторизоваться на площадке по указанным ниже учетным данным:</div>
<h4 style='text-align: center;'>Учетные данные:</h4>
<div>Логин: <span style='font-style: italic;'>{{$email}}</span></div>
<div>Пароль: <span style='font-style: italic;'>{{$password}}</span></div>
<a href="{{$url}}/login/"><h3 style='text-align: center;'>Войти на площадку</h3></a>

@yield('emails.email_footer')