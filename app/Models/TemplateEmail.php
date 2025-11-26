<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateEmail extends Model
{
    protected $table = 'template_emails';

        protected $fillable = [
        'name',
        'subject',
        'title',
        'body',
        'attachments',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'attachments' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Obtener las imágenes ordenadas por el campo order
     */
    public function getOrderedAttachments()
    {
        if (!$this->attachments) {
            return [];
        }

        // Ordenar por el campo 'order'
        $attachments = collect($this->attachments);

        return $attachments->sortBy('order')->values()->toArray();
    }

    /**
     * Obtener solo las rutas de los archivos ordenadas
     */
    public function getOrderedAttachmentPaths()
    {
        $ordered = $this->getOrderedAttachments();

        return collect($ordered)->pluck('file')->filter()->toArray();
    }

    /**
     * Obtener la primera imagen (orden 1)
     */
    public function getFirstAttachment()
    {
        $ordered = $this->getOrderedAttachments();

        return $ordered[0] ?? null;
    }

    /**
     * Obtener la última imagen (mayor orden)
     */
    public function getLastAttachment()
    {
        $ordered = $this->getOrderedAttachments();

        return end($ordered) ?: null;
    }

    /**
     * Obtener una imagen específica por orden
     */
    public function getAttachmentByOrder($order)
    {
        $ordered = $this->getOrderedAttachments();

        return collect($ordered)->firstWhere('order', $order);
    }

    /**
     * Obtener todas las imágenes con su orden como array asociativo
     */
    public function getAttachmentsWithOrder()
    {
        return $this->getOrderedAttachments();
    }
}
