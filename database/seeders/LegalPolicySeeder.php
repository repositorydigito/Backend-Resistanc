<?php

namespace Database\Seeders;

use App\Models\LegalPolicy;
use Illuminate\Database\Seeder;

class LegalPolicySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $policies = LegalPolicy::insert([
            [
                'type' => 'privacy',
                'title' => 'Aviso de Privacidad y Tratamiento de Datos Personales',
                'content' => '<h2>1. Responsable del Tratamiento</h2>
<p>R STUDIO, con domicilio legal en [Dirección], es el responsable del tratamiento de los datos personales de sus usuarios y miembros.</p>

<h2>2. Finalidad del Tratamiento</h2>
<ul>
    <li>Gestionar reservas y membresías.</li>
    <li>Facilitar el acceso a clases presenciales, en vivo y grabadas.</li>
    <li>Realizar ventas de productos físicos y servicios digitales.</li>
    <li>Comunicar promociones, novedades y recordatorios.</li>
</ul>

<h2>3. Base Legal</h2>
<p>El tratamiento se realiza conforme a lo dispuesto por la Ley N.° 29733 – Ley de Protección de Datos Personales y su Reglamento.</p>

<h2>4. Derechos del Titular</h2>
<p>El usuario podrá ejercer en cualquier momento sus derechos de acceso, rectificación, cancelación y oposición (ARCO) enviando una solicitud a [correo de contacto].</p>

<h2>5. Transferencia de Datos</h2>
<p>R STUDIO no comparte datos personales con terceros sin el consentimiento expreso del titular, salvo obligación legal o requerimiento de autoridad competente.</p>

<h2>6. Conservación de Datos</h2>
<p>Los datos se conservarán mientras la relación contractual esté vigente y durante los plazos legales establecidos.</p>

<h2>7. Consentimiento Expreso</h2>
<p>El registro en la aplicación o la reserva de servicios implica la aceptación de esta política y el consentimiento para el tratamiento de datos conforme a las finalidades indicadas.</p>',
                'is_active' => true,
            ],

            [
                'type' => 'privacy',
                'title' => 'Inicio de Sesión a través de Facebook',
                'content' => '<p>Si eliges iniciar sesión en RSISTANC STUDIO mediante tu cuenta de Facebook, estarás autorizando que se comparta con nosotros determinada información personal asociada a tu perfil de esa red social. Esta funcionalidad te permite ingresar de forma rápida a nuestra plataforma sin necesidad de crear una nueva cuenta.</p>

<h2>¿Qué datos obtenemos de Facebook?</h2>
<p>Al utilizar esta opción, RSISTANC STUDIO podrá recibir, con tu consentimiento expreso, los siguientes datos:</p>
<ul>
    <li>Tu nombre completo</li>
    <li>Dirección de correo electrónico</li>
    <li>Foto de perfil</li>
    <li>ID de usuario de Facebook</li>
</ul>
<p>En ningún caso accederemos a tu contraseña ni a publicaciones, listas de amigos o cualquier otro dato no autorizado por ti ni ajeno a la finalidad del inicio de sesión.</p>

<h2>¿Cómo usamos esta información?</h2>
<p>La información obtenida se utiliza exclusivamente para:</p>
<ul>
    <li>Verificar tu identidad como usuario de RSISTANC STUDIO</li>
    <li>Crear o asociar tu cuenta dentro de nuestra plataforma</li>
    <li>Personalizar tu experiencia y mostrar tu nombre e imagen de perfil en tu cuenta</li>
</ul>
<p>No utilizamos esta información para fines publicitarios ni la compartimos con terceros sin tu autorización expresa.</p>

<h2>Revocación del acceso</h2>
<p>Puedes revocar en cualquier momento el acceso de nuestra plataforma a tu cuenta de Facebook desde la configuración de privacidad de dicha red social. Al hacerlo, ya no podrás iniciar sesión en RSISTANC STUDIO mediante Facebook, a menos que restablezcas el permiso.</p>

<h2>Protección y almacenamiento de datos</h2>
<p>Toda la información obtenida se almacena bajo estrictas medidas de seguridad técnicas y organizativas, conforme a lo establecido por la Ley N.º 29733 y su reglamento. Solo será utilizada mientras mantengas activa tu cuenta o hasta que solicites su eliminación.</p>',
                'is_active' => true,
            ],

            [
                'type' => 'term',
                'title' => 'Política de Servicios y Modalidades de R STUDIOk',
                'content' => '<h2>1. Objeto de la Política</h2>
<p>El presente documento tiene como finalidad informar a nuestros usuarios y miembros sobre las modalidades de entrenamiento que ofrece RSISTANC STUDIO (también denominado R STUDIO), así como el alcance de nuestros servicios presenciales y virtuales.</p>

<h2>2. Modalidades de Entrenamiento Disponibles</h2>
<ul>
    <li><strong>R Cycling (Indoor Cycling):</strong> Clases presenciales, en vivo y grabadas, con instructores certificados R Stars.</li>
    <li><strong>R Reformer (Pilates Reformer):</strong> Entrenamientos especializados con equipos de pilates reformer.</li>
    <li><strong>R Pilates (Pilates Mat):</strong> Sesiones en colchoneta orientadas a tonificación, flexibilidad y control postural.</li>
    <li><strong>R Box (Boxing):</strong> Clases de box adaptadas a todos los niveles, con enfoque en técnica y acondicionamiento físico.</li>
</ul>

<h2>3. Servicios Complementarios en Instalaciones</h2>
<ul>
    <li><strong>Toallas:</strong> Disponibles para todos los usuarios sin costo adicional.</li>
    <li><strong>Zapatos de Cycling:</strong> Disponibles con reserva previa y uso exclusivo en clases de R Cycling. Deben devolverse al finalizar la clase depositándolos en los contenedores designados para su higienización.</li>
</ul>

<h2>4. Servicios Digitales Complementarios</h2>
<ul>
    <li>Acceso a clases en vivo vía aplicación.</li>
    <li>Acceso a clases grabadas para entrenamiento en cualquier momento.</li>
</ul>

<h2>5. Uso de Datos Personales</h2>
<p>De conformidad con la Ley N.° 29733, la información personal solicitada será utilizada únicamente para la gestión de reservas, control de acceso y comunicación con el usuario.</p>',
                'is_active' => true,
            ],
            [
                'type' => 'term',
                'title' => 'Política de Venta y Distribución de Productos Físicos',
                'content' => '<h2>1. Objeto de la Política</h2>
<p>Establecer las condiciones de venta, entrega y beneficios asociados a la compra de productos físicos a través de nuestra aplicación móvil.</p>

<h2>2. Tipos de Productos Disponibles</h2>
<h3>Shakes (Batidos)</h3>
<ul>
    <li><strong>Detox:</strong> 1 g proteínas, 120 kcal, 400 ml. Ingredientes: Piña, manzana, espinaca, pepino, kion. Sin bebida base (extracto natural).</li>
    <li><strong>Proteico – Sabor Berry Recovery:</strong> 18 g proteínas, 320 kcal, 500 ml. Ingredientes: Frambuesa, mora, arándano, plátano, mantequilla de maní, proteína vegana, colágeno.</li>
    <li><strong>Proteico – Sabor Strawberry Matcha:</strong> 16 g proteínas, 290 kcal aprox., 500 ml. Ingredientes: Fresa, matcha, kéfir en polvo, proteína vegana, colágeno.</li>
    <li><strong>Bebidas base a escoger:</strong> Avena, almendra, soja, sin lactosa o agua.</li>
</ul>

<h3>Accesorios Deportivos</h3>
<p>Incluye productos propios de R STUDIO y productos de marcas asociadas.</p>

<h3>Enlaces a Tiendas Asociadas</h3>
<p>Algunos productos se adquirirán directamente a través de tiendas asociadas, con beneficios de descuento para miembros de R STUDIO.</p>

<h2>3. Condiciones de Compra y Entrega</h2>
<ul>
    <li>Los productos propios de R STUDIO se entregarán en nuestras instalaciones.</li>
    <li>Las compras a través de tiendas asociadas se regirán por las políticas y tiempos de entrega de cada proveedor.</li>
</ul>

<h2>4. Protección de Datos Personales</h2>
<p>La información personal utilizada para la compra será tratada exclusivamente para la gestión de la transacción.</p>',
                'is_active' => true,
            ],
            [
                'type' => 'term',
                'title' => 'Política de Reservas, Cancelaciones y Devoluciones',
                'content' => '<h2>1. Objeto de la Política</h2>
<p>Definir los procedimientos y condiciones aplicables a la reserva, modificación y cancelación de clases, así como a la devolución de créditos y productos.</p>

<h2>2. Casos de Devolución de Créditos por Clases</h2>
<ul>
    <li><strong>Cancelación por parte de R STUDIO:</strong> La clase no será cobrada ni descontada de la membresía.</li>
    <li><strong>Cambio de Instructor:</strong> Si el usuario decide no asistir, se devolverá el crédito para reservar una nueva clase.</li>
    <li><strong>Cancelación por parte del Usuario:</strong> Debe realizarse con un mínimo de 6 horas de anticipación.</li>
</ul>

<h2>3. Políticas de Lista de Espera</h2>
<ul>
    <li><strong>Ingreso tardío:</strong> Se permite el ingreso hasta la segunda canción.</li>
    <li><strong>Redistribución de bicicletas:</strong> A partir de la tercera canción, los presentes pueden ocupar bicicletas libres.</li>
    <li><strong>Lista de espera activa:</strong> El ingreso será posible a partir de la tercera canción, en cualquiera de las bicicletas libres.</li>
    <li><strong>Prioridad de asignación:</strong> Si hay lista de espera y se libera un asiento, se priorizará la redistribución interna antes de permitir nuevos ingresos.</li>
</ul>

<h2>4. Protección de Datos Personales</h2>
<p>Los datos recopilados en procesos de reserva, modificación o cancelación serán tratados conforme a la Ley N.° 29733.</p>',
                'is_active' => true,
            ],
        ]);
    }
}
