<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $orderDate = $this->faker->dateTimeBetween('-6 months', 'now');
        $status = $this->faker->randomElement(['pending', 'confirmed', 'processing', 'preparing', 'ready', 'delivered', 'cancelled', 'refunded']);

        // Generate order number
        $orderNumber = 'RST-' . $orderDate->format('Y') . '-' . str_pad((string) $this->faker->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT);

        // Calculate amounts
        $subtotal = $this->faker->randomFloat(2, 50.00, 1500.00);
        $discountAmount = $this->faker->boolean(30) ? $this->faker->randomFloat(2, 5.00, $subtotal * 0.3) : 0;
        $taxAmount = ($subtotal - $discountAmount) * 0.18; // IGV 18%
        $shippingCost = $subtotal >= 150 ? 0 : $this->faker->randomFloat(2, 15.00, 35.00);
        $totalAmount = $subtotal - $discountAmount + $taxAmount + $shippingCost;

        return [
            'user_id' => User::factory(),
            'order_number' => $orderNumber,
            'order_type' => $this->faker->randomElement(['purchase', 'booking_extras', 'subscription', 'gift']),
            'subtotal_soles' => $subtotal,
            'tax_amount_soles' => $taxAmount,
            'shipping_amount_soles' => $shippingCost,
            'discount_amount_soles' => $discountAmount,
            'total_amount_soles' => $totalAmount,
            'currency' => 'PEN',
            'status' => $status,
            'payment_status' => $this->getPaymentStatus($status),
            'delivery_method' => $this->faker->randomElement(['pickup', 'delivery', 'digital']),
            'delivery_date' => $this->calculateDeliveryDate($orderDate, $status),
            'delivery_time_slot' => $this->faker->randomElement(['09:00-12:00', '12:00-15:00', '15:00-18:00', '18:00-21:00']),
            'delivery_address' => $this->generateDeliveryAddress(),
            'special_instructions' => $this->generateOrderNotes(),
            'promocode_used' => $this->faker->boolean(20) ? $this->generatePromocode() : null,
            'notes' => $this->generateInternalNotes(),
            'discount_code_id' => null,
            'created_at' => $orderDate,
            'updated_at' => $this->faker->dateTimeBetween($orderDate, 'now'),
        ];
    }

    /**
     * Get payment status based on order status.
     */
    private function getPaymentStatus(string $orderStatus): string
    {
        return match ($orderStatus) {
            'pending' => 'pending',
            'cancelled' => 'failed',
            'refunded' => 'refunded',
            default => 'paid',
        };
    }

    /**
     * Generate payment reference.
     */
    private function generatePaymentReference(): string
    {
        return 'PAY-' . strtoupper($this->faker->bothify('??##??##??'));
    }

    /**
     * Generate shipping address.
     */
    private function generateShippingAddress(): array
    {
        return [
            'name' => $this->faker->name(),
            'phone' => '+51 9' . $this->faker->numerify('########'),
            'address_line' => $this->faker->streetAddress(),
            'district' => $this->faker->randomElement(['Miraflores', 'San Isidro', 'Surco', 'La Molina', 'Barranco', 'San Borja']),
            'city' => 'Lima',
            'postal_code' => $this->faker->numerify('#####'),
            'country' => 'PE',
            'reference' => $this->faker->randomElement(['Casa azul', 'Edificio Torre', 'Al lado del parque', 'Frente a farmacia']),
        ];
    }

    /**
     * Generate delivery address.
     */
    private function generateDeliveryAddress(): ?array
    {
        // 30% don't need delivery address (pickup orders)
        if ($this->faker->boolean(30)) {
            return null;
        }

        return [
            'name' => $this->faker->name(),
            'phone' => '+51 ' . $this->faker->numerify('#########'),
            'address_line' => $this->faker->streetAddress(),
            'district' => $this->faker->randomElement([
                'Miraflores', 'San Isidro', 'Surco', 'La Molina', 'Barranco',
                'San Borja', 'Magdalena', 'Jesús María', 'Lince', 'Pueblo Libre'
            ]),
            'city' => 'Lima',
            'postal_code' => $this->faker->postcode(),
            'country' => 'PE',
            'reference' => $this->faker->randomElement([
                'Casa blanca con portón negro',
                'Edificio Torre',
                'Al lado del parque',
                'Frente a la farmacia',
                'Casa esquina',
            ]),
        ];
    }

    /**
     * Generate billing address.
     */
    private function generateBillingAddress(): array
    {
        // 70% same as shipping, 30% different
        if ($this->faker->boolean(70)) {
            return $this->generateShippingAddress();
        }

        return [
            'name' => $this->faker->name(),
            'document_type' => $this->faker->randomElement(['DNI', 'RUC']),
            'document_number' => $this->faker->numerify('########'),
            'address_line' => $this->faker->streetAddress(),
            'district' => $this->faker->randomElement(['Miraflores', 'San Isidro', 'Surco', 'La Molina']),
            'city' => 'Lima',
            'postal_code' => $this->faker->numerify('#####'),
            'country' => 'PE',
        ];
    }

    /**
     * Generate order notes.
     */
    private function generateOrderNotes(): ?string
    {
        $notes = [
            'Entregar en horario de oficina',
            'Llamar antes de llegar',
            'Dejar en portería',
            'Es un regalo - incluir tarjeta',
            'Cliente frecuente',
            'Primera compra',
            'Entrega urgente',
            'Verificar productos antes de entregar',
            'Cliente prefiere contacto por WhatsApp',
            'Dirección de difícil acceso',
        ];

        return $this->faker->boolean(25) ? $this->faker->randomElement($notes) : null;
    }

    /**
     * Generate internal notes.
     */
    private function generateInternalNotes(): ?string
    {
        $notes = [
            'Cliente VIP - prioridad alta',
            'Verificar stock antes de confirmar',
            'Aplicar descuento especial',
            'Coordinar entrega con almacén',
            'Cliente solicitó factura',
            'Pedido corporativo',
            'Revisar dirección - puede estar incorrecta',
            'Cliente reportó problema anterior',
            'Entrega coordinada con recepción',
            'Seguimiento especial requerido',
        ];

        return $this->faker->boolean(20) ? $this->faker->randomElement($notes) : null;
    }

    /**
     * Generate promocode.
     */
    private function generatePromocode(): string
    {
        return $this->faker->randomElement(['WELCOME10', 'BLACKFRIDAY', 'STUDENT15', 'FIRST20', 'LOYALTY25']);
    }

    /**
     * Calculate estimated delivery date.
     */
    private function calculateDeliveryDate($orderDate, string $status): ?\DateTime
    {
        if (in_array($status, ['cancelled', 'refunded'])) {
            return null;
        }

        $deliveryDays = $this->faker->numberBetween(1, 7);
        return (new \DateTime($orderDate->format('Y-m-d')))->modify("+{$deliveryDays} days");
    }

    /**
     * Get shipped date based on status.
     */
    private function getShippedDate(string $status, $orderDate): ?\DateTime
    {
        if (in_array($status, ['ready', 'delivered'])) {
            return $this->faker->dateTimeBetween($orderDate, 'now');
        }

        return null;
    }

    /**
     * Get delivered date based on status.
     */
    private function getDeliveredDate(string $status, $orderDate): ?\DateTime
    {
        if ($status === 'delivered') {
            return $this->faker->dateTimeBetween($orderDate, 'now');
        }

        return null;
    }

    /**
     * Generate cancellation reason.
     */
    private function generateCancellationReason(): string
    {
        return $this->faker->randomElement([
            'Cliente solicitó cancelación',
            'Producto sin stock',
            'Error en la dirección',
            'Problema con el pago',
            'Cliente no disponible',
            'Producto defectuoso',
            'Cambio de opinión del cliente',
            'Error en el pedido',
            'Demora en la entrega',
            'Solicitud de reembolso',
        ]);
    }

    /**
     * Indicate that the order is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);
    }

    /**
     * Indicate that the order is confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
    }

    /**
     * Indicate that the order is delivered.
     */
    public function delivered(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'delivered',
                'payment_status' => 'paid',
            ];
        });
    }

    /**
     * Indicate that the order is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'cancelled',
                'payment_status' => 'failed',
                'notes' => $this->generateCancellationReason(),
            ];
        });
    }

    /**
     * Create a recent order.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Create a high-value order.
     */
    public function highValue(): static
    {
        return $this->state(function (array $attributes) {
            $subtotal = $this->faker->randomFloat(2, 800.00, 3000.00);
            $discountAmount = $this->faker->randomFloat(2, 50.00, $subtotal * 0.2);
            $taxAmount = ($subtotal - $discountAmount) * 0.18;
            $shippingCost = 0; // Free shipping for high value orders
            $totalAmount = $subtotal - $discountAmount + $taxAmount + $shippingCost;

            return [
                'subtotal_soles' => $subtotal,
                'discount_amount_soles' => $discountAmount,
                'tax_amount_soles' => $taxAmount,
                'shipping_amount_soles' => $shippingCost,
                'total_amount_soles' => $totalAmount,
                'notes' => 'Pedido de alto valor - seguimiento especial',
            ];
        });
    }
}
