<x-mail::message>

@if ($company && $company->logo_url)
<div class="" style="background: rgb(136, 136, 136); display:grid; place-content: center; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
<img src="https://rsistancstudio.com/image/logos/resistance-logo-two-white.png" alt="{{ $company->name ?? 'Resistanc Studio' }}" style="max-width: 200px; height: auto; margin-bottom: 20px;">
</div>
@endif

¡Bienvenido/a a la comunidad
<img src="" alt="">
¡Hola **{{ $user->name }}**!

{{ $data->body }}

## 🔗 Verifica tu cuenta:

<div style="text-align: center; margin: 40px 0; padding: 20px;">
<div style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; padding: 30px 40px; box-shadow: 0 8px 25px rgba(0,0,0,0.15);">
<div style="font-family: 'Arial', sans-serif; font-size: 18px; font-weight: bold; color: white; text-shadow: 0 2px 4px rgba(0,0,0,0.3);">Haz clic en el botón para verificar </div>
</div>
</div>

<x-mail::button :url="$verificationUrl" color="primary">
🔐 Verificar mi cuenta
</x-mail::button>

## ⚠️ Información Importante:

- ⏰ **Expiración:** Este enlace expirará en **60 minutos**
- 🔒 **Seguridad:** No compartas este enlace con nadie
- 📱 **Uso:** Haz clic en el botón de arriba para verificar tu cuenta
- 🚫 **Si no te registraste:** Puedes ignorar este mensaje de forma segura

## 🚀 ¿Cómo verificar tu cuenta?

1. Haz clic en el botón **"Verificar mi cuenta"** de arriba
2. Serás redirigido a nuestra plataforma
3. Tu cuenta quedará verificada automáticamente
4. ¡Ya podrás disfrutar de todos nuestros servicios!

---

<div style="background: #f8f9fa; border-left: 4px solid #007bff; padding: 15px; margin: 20px 0; border-radius: 4px;">
<strong>💡 Consejo de Seguridad:</strong>
<br>
Si el botón no funciona, puedes copiar y pegar el siguiente enlace en tu navegador:
</div>

<div style="background: #f1f3f4; padding: 15px; border-radius: 8px; margin: 20px 0; word-break: break-all; font-family: monospace; font-size: 12px; color: #333;"> {{ $verificationUrl }} </div>

---

<div style="background: #e8f4fd; border: 1px solid #bee5eb; border-radius: 8px; padding: 20px; margin: 30px 0;">
<strong>💡 Consejo de Seguridad:</strong>
<br>
Después de verificar tu cuenta, asegúrate de mantener tu información de contacto actualizada.
</div>

---

@if ($company && $company->signature_image)
<div style="text-align: center; margin: 30px 0;">
<img src="{{ asset('storage/' . $company->signature_image) }}" alt="Firma {{ $company->name ?? 'Resistanc Studio' }}" style="max-width: 300px; height: auto;">
</div>
@endif

**Saludos, el equipo de {{ $company->name ?? 'Resistanc Studio' }}** 🎉

<small style="color: #6c757d; font-size: 12px;">📧 Este es un mensaje automático de verificación. Por favor no respondas a este correo.
<br>
🛡️ Si tienes dudas sobre tu cuenta, contacta a nuestro equipo de soporte.
</small>
</x-mail::message>
