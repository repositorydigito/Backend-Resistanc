@component('mail::message')
# 🔐 Código de Recuperación de Contraseña

¡Hola **{{ $userName }}**! 👋

Has solicitado restablecer tu contraseña en **{{ config('app.name') }}**.

## Tu código de verificación es:

<div style="text-align: center; margin: 40px 0; padding: 20px;">
    <div style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; padding: 30px 40px; box-shadow: 0 8px 25px rgba(0,0,0,0.15);">
        <div style="font-family: 'Courier New', monospace; font-size: 32px; font-weight: bold; letter-spacing: 12px; color: white; text-shadow: 0 2px 4px rgba(0,0,0,0.3);">
            {{ $code }}
        </div>
    </div>
</div>

## ⚠️ Información Importante:

- ⏰ **Expiración:** Este código expirará en **{{ $expireTime }} minutos**
- 🔒 **Seguridad:** No compartas este código con nadie
- 📱 **Uso:** Ingresa este código en la aplicación para continuar
- 🚫 **Si no lo solicitaste:** Puedes ignorar este mensaje de forma segura

## 🚀 ¿Cómo usar el código?

1. Abre la aplicación **{{ config('app.name') }}**
2. Ve a la sección de recuperación de contraseña
3. Ingresa el código: **{{ $code }}**
4. Crea tu nueva contraseña segura

@component('mail::button', ['url' => '#', 'color' => 'primary'])
📱 Abrir Aplicación
@endcomponent

---

<div style="background: #f8f9fa; border-left: 4px solid #007bff; padding: 15px; margin: 20px 0; border-radius: 4px;">
    <strong>💡 Consejo de Seguridad:</strong><br>
    Después de cambiar tu contraseña, asegúrate de cerrar sesión en todos los dispositivos donde tengas la aplicación abierta.
</div>

---

**Saludos, el equipo de {{ config('app.name') }}** 🎉

<small style="color: #6c757d; font-size: 12px;">
📧 Este es un mensaje automático de seguridad. Por favor no respondas a este correo.<br>
🛡️ Si tienes dudas sobre la seguridad de tu cuenta, contacta a nuestro equipo de soporte.
</small>
@endcomponent
