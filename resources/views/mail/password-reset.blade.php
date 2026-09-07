<x-mail::message>
# Восстановление пароля

Ваш код: **{{ $code }}**

Код действует {{ config('escrow.reset_code_ttl_minutes') }} минут. Если вы не запрашивали сброс, проигнорируйте письмо.

С уважением,<br>
{{ config('app.name') }}
</x-mail::message>
