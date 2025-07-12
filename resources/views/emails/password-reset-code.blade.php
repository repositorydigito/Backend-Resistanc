@component('mail::message')
# Código de Recuperación de Contraseña

¡Hola {{ $userName }}!

Has solicitado restablecer tu contraseña en **{{ config('app.name') }}**.

Tu código de verificación es:

<div style="text-align: center; margin: 30px 0;">
    <div style="display: inline-block; background: #f8f9fa; border: 2px solid #dee2e6; border-radius: 8px; padding: 20px; font-family: monospace; font-size: 24px; font-weight: bold; letter-spacing: 8px; color: #495057;">
        {{ $code }}
    </div>
</div>

**Importante:**
- Este código expirará en {{ $expireTime }} minutos
- No compartas este código con nadie
- Si no solicitaste este código, puedes ignorar este mensaje

@component('mail::button', ['url' => '#', 'color' => 'primary'])
Ingresar Código en la App
@endcomponent

Si tienes problemas con el botón, copia y pega el código manualmente en la aplicación.

Saludos,<br>
{{ config('app.name') }}

<small style="color: #6c757d;">
Este es un mensaje automático, por favor no respondas a este correo.
</small>
@endcomponent
