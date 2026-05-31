<?php

namespace App\Enums;

/**
 * Shipment-level status for an individual fulfillment record.
 */
enum FulfillmentShipmentStatus: string
{
    case Pending = 'pending';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
}
