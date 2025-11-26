<?php

namespace App\Observers;

use App\Models\Order;

class OrderObserver
{
    public function creating(Order $order)
    {
        // Obtener el último número correlativo numérico
        $lastOrder = Order::orderByDesc('id')->first();

        // Extraer el número y aumentarlo
        $lastNumber = 0;
        if ($lastOrder && preg_match('/ORD-(\d+)/', $lastOrder->order_number, $matches)) {
            $lastNumber = (int) $matches[1];
        }

        $nextNumber = $lastNumber + 1;

        // Formatear como ORD-0001
        $order->order_number = 'ORD-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
