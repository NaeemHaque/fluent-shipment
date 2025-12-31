<?php

namespace FluentShipment\App\Services;

use FluentShipment\App\Models\Shipment;
use FluentShipment\App\Models\Rider;
use FluentShipment\App\Utils\Support\Mail;
use FluentShipment\App\Helpers\DateTimeHelper;

class EmailNotificationService
{
    const EMAIL_TYPE_PROCESSING = 'processing';
    const EMAIL_TYPE_DELIVERED = 'delivered';

    public static function sendShipmentNotification(Shipment $shipment, string $emailType): bool
    {
        if ( ! static::isEmailEnabled($emailType)) {
            return false;
        }

        if ( ! $shipment->customer_email) {
            return false;
        }

        try {
            switch ($emailType) {
                case static::EMAIL_TYPE_PROCESSING:
                    return static::sendProcessingNotification($shipment);

                case static::EMAIL_TYPE_DELIVERED:
                    return static::sendDeliveredNotification($shipment);

                default:
                    return false;
            }
        } catch (\Exception $e) {
            error_log('Fluent Shipment Email Error: ' . $e->getMessage());

            return false;
        }
    }

    public static function sendProcessingNotification(Shipment $shipment): bool
    {
        $emailData = static::prepareEmailData($shipment);

        $subject = static::getEmailSubject(static::EMAIL_TYPE_PROCESSING, $emailData);
        $body    = static::getEmailBody(static::EMAIL_TYPE_PROCESSING, $emailData);

        return static::sendEmail(
            $shipment->customer_email,
            $subject,
            $body
        );
    }

    public static function sendDeliveredNotification(Shipment $shipment): bool
    {
        $emailData = static::prepareEmailData($shipment);
        $subject   = static::getEmailSubject(static::EMAIL_TYPE_DELIVERED, $emailData);
        $body      = static::getEmailBody(static::EMAIL_TYPE_DELIVERED, $emailData);

        return static::sendEmail(
            $shipment->customer_email,
            $subject,
            $body
        );
    }

    protected static function prepareEmailData(Shipment $shipment): array
    {
        $rider      = $shipment->rider ? $shipment->rider : null;
        $senderInfo = $shipment->sender_info;

        return [
            'shipment'                => $shipment,
            'tracking_number'         => $shipment->tracking_number,
            'current_status'          => $shipment->status_label,
            'tracking_url'            => $shipment->getTrackingUrl(),
            'estimated_delivery'      => $shipment->estimated_delivery ? $shipment->estimated_delivery->format('M j, Y') : 'N/A',
            'shipped_at'              => $shipment->shipped_at ? $shipment->shipped_at->format('M j, Y g:i A') : null,
            'delivered_at'            => $shipment->delivered_at ? $shipment->delivered_at->format('M j, Y g:i A') : null,
            'delivery_address'        => $shipment->delivery_address,
            'shipping_address'        => $shipment->shipping_address,
            'package_info'            => $shipment->package_info,
            'formatted_shipping_cost' => $shipment->formatted_shipping_cost,
            'special_instructions'    => $shipment->special_instructions,
            'rider'                   => $rider ? [
                'name'         => $rider->rider_name,
                'phone'        => $rider->phone,
                'vehicle_info' => $rider->vehicle_type_label . ($rider->vehicle_number ? " ({$rider->vehicle_number})" : ''),
                'rating'       => $rider->formatted_rating,
            ] : null,
            'sender'                  => $senderInfo ? [
                'name'  => $senderInfo['name'] ?? '',
                'email' => $senderInfo['email'] ?? '',
                'phone' => $senderInfo['phone'] ?? '',
            ] : null,
            'site_name'               => get_bloginfo('name'),
            'site_url'                => get_site_url(),
        ];
    }

    protected static function getEmailSubject(string $type, array $data): string
    {
        $subjects = [
            static::EMAIL_TYPE_PROCESSING => sprintf(
                '[%s] Your order is being processed - %s',
                $data['site_name'],
                $data['tracking_number']
            ),
            static::EMAIL_TYPE_DELIVERED  => sprintf(
                '[%s] Your order has been delivered - %s',
                $data['site_name'],
                $data['tracking_number']
            ),
        ];

        return apply_filters('fluentshipment_email_subject_' . $type, $subjects[$type] ?? '', $data);
    }

    protected static function getEmailBody(string $type, array $data): string
    {
        $templates = [
            static::EMAIL_TYPE_PROCESSING => static::getProcessingEmailTemplate($data),
            static::EMAIL_TYPE_DELIVERED  => static::getDeliveredEmailTemplate($data),
        ];

        $body = $templates[$type] ?? '';

        return apply_filters('fluentshipment_email_body_' . $type, $body, $data);
    }

    protected static function getProcessingEmailTemplate(array $data): string
    {
        $addressString = static::formatAddressForEmail($data['delivery_address']);
        $packageItems  = static::formatPackageItems($data['package_info']);
        $senderInfo    = static::formatSenderInfo($data['sender']);

        $template = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9;">
            <div style="background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h2 style="color: #333; text-align: center; margin-bottom: 30px;">📦 Your Order is Being Processed</h2>
                
                <div style="background-color: #e8f5e8; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    <p style="margin: 0; font-weight: bold; color: #2c5530;">Great news! Your shipment is now being processed and will be shipped soon.</p>
                </div>

                <div style="border: 1px solid #ddd; border-radius: 5px; padding: 20px; margin-bottom: 20px;">
                    <h3 style="margin-top: 0; color: #333;">📋 Shipment Details</h3>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 8px 0; font-weight: bold;">Tracking Number:</td>
                            <td style="padding: 8px 0;">' . esc_html($data['tracking_number']) . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; font-weight: bold;">Status:</td>
                            <td style="padding: 8px 0; color: #007cba; font-weight: bold;">' . esc_html($data['current_status']) . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; font-weight: bold;">Estimated Delivery:</td>
                            <td style="padding: 8px 0;">' . esc_html($data['estimated_delivery']) . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; font-weight: bold;">Shipping Cost:</td>
                            <td style="padding: 8px 0;">' . esc_html($data['formatted_shipping_cost']) . '</td>
                        </tr>
                    </table>
                </div>

                ' . ($addressString ? '
                <div style="border: 1px solid #ddd; border-radius: 5px; padding: 20px; margin-bottom: 20px;">
                    <h3 style="margin-top: 0; color: #333;">🏠 Delivery Address</h3>
                    <p style="margin: 0; line-height: 1.5;">' . esc_html($addressString) . '</p>
                </div>
                ' : '') . '

                ' . ($packageItems ? '
                <div style="border: 1px solid #ddd; border-radius: 5px; padding: 20px; margin-bottom: 20px;">
                    <h3 style="margin-top: 0; color: #333;">📦 Package Contents</h3>
                    ' . $packageItems . '
                </div>
                ' : '') . '

                ' . ($senderInfo ? '
                <div style="border: 1px solid #ddd; border-radius: 5px; padding: 20px; margin-bottom: 20px;">
                    <h3 style="margin-top: 0; color: #333;">📤 Sender Information</h3>
                    ' . $senderInfo . '
                </div>
                ' : '') . '

                ' . ($data['special_instructions'] ? '
                <div style="border: 1px solid #ddd; border-radius: 5px; padding: 20px; margin-bottom: 20px;">
                    <h3 style="margin-top: 0; color: #333;">📝 Special Instructions</h3>
                    <p style="margin: 0; font-style: italic;">' . esc_html($data['special_instructions']) . '</p>
                </div>
                ' : '') . '

                ' . ($data['tracking_url'] ? '
                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . esc_url($data['tracking_url']) . '" style="display: inline-block; background-color: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">Track Your Shipment</a>
                </div>
                ' : '') . '

                <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 14px;">
                    <p>Thank you for your business!<br>
                    <a href="' . esc_url(
                        $data['site_url']
                    ) . '" style="color: #007cba; text-decoration: none;">' . esc_html($data['site_name']) . '</a></p>
                </div>
            </div>
        </div>';

        return $template;
    }

    protected static function getDeliveredEmailTemplate(array $data): string
    {
        $addressString = static::formatAddressForEmail($data['delivery_address']);
        $riderInfo     = static::formatRiderInfo($data['rider']);

        $template = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9;">
            <div style="background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h2 style="color: #333; text-align: center; margin-bottom: 30px;">🎉 Your Order Has Been Delivered!</h2>
                
                <div style="background-color: #e8f5e8; padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center;">
                    <p style="margin: 0; font-weight: bold; color: #2c5530; font-size: 16px;">✅ Successfully delivered on ' . esc_html($data['delivered_at']) . '</p>
                </div>

                <div style="border: 1px solid #ddd; border-radius: 5px; padding: 20px; margin-bottom: 20px;">
                    <h3 style="margin-top: 0; color: #333;">📋 Delivery Details</h3>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 8px 0; font-weight: bold;">Tracking Number:</td>
                            <td style="padding: 8px 0;">' . esc_html($data['tracking_number']) . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; font-weight: bold;">Status:</td>
                            <td style="padding: 8px 0; color: #28a745; font-weight: bold;">' . esc_html($data['current_status']) . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; font-weight: bold;">Delivered At:</td>
                            <td style="padding: 8px 0;">' . esc_html($data['delivered_at']) . '</td>
                        </tr>
                    </table>
                </div>

                ' . ($addressString ? '
                <div style="border: 1px solid #ddd; border-radius: 5px; padding: 20px; margin-bottom: 20px;">
                    <h3 style="margin-top: 0; color: #333;">🏠 Delivered To</h3>
                    <p style="margin: 0; line-height: 1.5;">' . esc_html($addressString) . '</p>
                </div>
                ' : '') . '

                ' . ($riderInfo ? '
                <div style="border: 1px solid #ddd; border-radius: 5px; padding: 20px; margin-bottom: 20px;">
                    <h3 style="margin-top: 0; color: #333;">🚚 Delivered By</h3>
                    ' . $riderInfo . '
                </div>
                ' : '') . '

                <div style="background-color: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;">
                    <p style="margin: 0; color: #856404;"><strong>📞 Need help?</strong> If you have any questions about your delivery or need assistance, please contact us.</p>
                </div>

                <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 14px;">
                    <p>Thank you for choosing us for your delivery needs!<br>
                    <a href="' . esc_url(
                        $data['site_url']) . '" style="color: #007cba; text-decoration: none;">' . esc_html($data['site_name']) . '</a></p>
                </div>
            </div>
        </div>';

        return $template;
    }

    protected static function formatAddressForEmail(array $address): string
    {
        if (empty($address)) {
            return '';
        }

        $parts = [];

        if ( ! empty($address['name'])) {
            $parts[] = $address['name'];
        }

        if ( ! empty($address['address_1'])) {
            $parts[] = $address['address_1'];
        }

        if ( ! empty($address['address_2'])) {
            $parts[] = $address['address_2'];
        }

        $cityStateZip = array_filter([
            $address['city'] ?? '',
            $address['state'] ?? '',
            $address['postcode'] ?? '',
        ]);

        if ( ! empty($cityStateZip)) {
            $parts[] = implode(', ', $cityStateZip);
        }

        if ( ! empty($address['country'])) {
            $parts[] = $address['country'];
        }

        return implode('<br>', array_filter($parts));
    }

    protected static function formatPackageItems(array $packageInfo): string
    {
        if (empty($packageInfo['items'])) {
            return '<p style="margin: 0;">No package details available.</p>';
        }

        $html = '<ul style="margin: 0; padding-left: 20px;">';

        foreach ($packageInfo['items'] as $item) {
            $html .= '<li style="margin-bottom: 5px;">';
            $html .= '<strong>' . esc_html($item['name'] ?? 'Unknown Item') . '</strong>';

            if ( ! empty($item['quantity'])) {
                $html .= ' - Quantity: ' . esc_html($item['quantity']);
            }

            if ( ! empty($item['weight'])) {
                $html .= ' - Weight: ' . esc_html($item['weight']) . 'kg';
            }

            $html .= '</li>';
        }

        $html .= '</ul>';

        if ( ! empty($packageInfo['total_items'])) {
            $html .= '<p style="margin-top: 10px; margin-bottom: 0;"><strong>Total Items: ' . esc_html(
                    $packageInfo['total_items']
                ) . '</strong></p>';
        }

        return $html;
    }

    protected static function formatSenderInfo(array $sender = null): string
    {
        if (empty($sender)) {
            return '<p style="margin: 0;">Sender information not available.</p>';
        }

        $html = '<div>';

        if ( ! empty($sender['name'])) {
            $html .= '<p style="margin: 0 0 5px 0;"><strong>Name:</strong> ' . esc_html($sender['name']) . '</p>';
        }

        if ( ! empty($sender['email'])) {
            $html .= '<p style="margin: 0 0 5px 0;"><strong>Email:</strong> ' . esc_html($sender['email']) . '</p>';
        }

        if ( ! empty($sender['phone'])) {
            $html .= '<p style="margin: 0 0 5px 0;"><strong>Phone:</strong> ' . esc_html($sender['phone']) . '</p>';
        }

        $html .= '</div>';

        return $html;
    }

    protected static function formatRiderInfo(array $rider = null): string
    {
        if (empty($rider)) {
            return '<p style="margin: 0;">Delivery agent information not available.</p>';
        }

        $html = '<div>';

        if ( ! empty($rider['name'])) {
            $html .= '<p style="margin: 0 0 5px 0;"><strong>Name:</strong> ' . esc_html($rider['name']) . '</p>';
        }

        if ( ! empty($rider['phone'])) {
            $html .= '<p style="margin: 0 0 5px 0;"><strong>Contact:</strong> ' . esc_html($rider['phone']) . '</p>';
        }

        if ( ! empty($rider['vehicle_info'])) {
            $html .= '<p style="margin: 0 0 5px 0;"><strong>Vehicle:</strong> ' . esc_html(
                    $rider['vehicle_info']
                ) . '</p>';
        }

        if ( ! empty($rider['rating'])) {
            $html .= '<p style="margin: 0 0 5px 0;"><strong>Rating:</strong> ⭐ ' . esc_html($rider['rating']) . '</p>';
        }

        $html .= '</div>';

        return $html;
    }

    protected static function sendEmail(string $to, string $subject, string $body): bool
    {
        $fromEmail = static::getFromEmail();
        $fromName  = static::getFromName();

        try {
            return Mail::make()
                       ->to($to)
                       ->from($fromEmail, $fromName)
                       ->subject($subject)
                       ->body($body)
                       ->contentType('text/html')
                       ->send();
        } catch (\Exception $e) {
            error_log('Fluent Shipment Email Send Error: ' . $e->getMessage());

            return false;
        }
    }

    protected static function getFromEmail(): string
    {
        return apply_filters(
            'fluentshipment_email_from',
            get_option('fluentshipment_email_from', get_bloginfo('admin_email'))
        );
    }

    protected static function getFromName(): string
    {
        return apply_filters(
            'fluentshipment_email_from_name',
            get_option('fluentshipment_email_from_name', get_bloginfo('name'))
        );
    }

    protected static function isEmailEnabled(string $type): bool
    {
        $option = 'fluentshipment_email_' . $type . '_enabled';

        return (bool)get_option($option, true);
    }

    public static function getEmailTypes(): array
    {
        return [
            static::EMAIL_TYPE_PROCESSING => 'Processing Notification',
            static::EMAIL_TYPE_DELIVERED  => 'Delivery Confirmation',
        ];
    }

    public static function enableEmail(string $type, bool $enabled = true): bool
    {
        $option = 'fluentshipment_email_' . $type . '_enabled';

        return update_option($option, $enabled);
    }

    public static function isAnyEmailEnabled(): bool
    {
        foreach (array_keys(static::getEmailTypes()) as $type) {
            if (static::isEmailEnabled($type)) {
                return true;
            }
        }

        return false;
    }
}
