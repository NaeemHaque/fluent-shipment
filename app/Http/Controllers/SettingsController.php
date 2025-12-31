<?php

namespace FluentShipment\App\Http\Controllers;

use FluentShipment\App\Services\EmailNotificationService;
use FluentShipment\App\Helpers\DateTimeHelper;
use FluentShipment\App\Models\Shipment;
use FluentShipment\Framework\Http\Request\Request;

class SettingsController extends Controller
{
    public function getEmailSettings()
    {
        $emailTypes = EmailNotificationService::getEmailTypes();
        $settings   = [];

        foreach ($emailTypes as $type => $label) {
            $settings[$type] = [
                'enabled' => get_option('fluentshipment_email_' . $type . '_enabled', true),
                'label'   => $label,
            ];
        }

        return [
            'success'  => true,
            'settings' => [
                'email_notifications' => $settings,
                'email_from'          => get_option('fluentshipment_email_from', get_bloginfo('admin_email')),
                'email_from_name'     => get_option('fluentshipment_email_from_name', get_bloginfo('name')),
            ],
        ];
    }

    public function updateEmailSettings(Request $request)
    {
        $request->validate([
            'email_notifications' => 'array',
            'email_from'          => 'email',
            'email_from_name'     => 'string|max:255',
        ]);

        $emailNotifications = $request->getSafe('email_notifications', 'fluentShipmentSanitizeArray', []);
        $emailFrom          = $request->getSafe('email_from', 'sanitize_email');
        $emailFromName      = $request->getSafe('email_from_name', 'sanitize_text_field');

        if ( ! empty($emailNotifications)) {
            foreach ($emailNotifications as $type => $settings) {
                if (isset($settings['enabled'])) {
                    $enabled = rest_sanitize_boolean($settings['enabled']);
                    update_option('fluentshipment_email_' . sanitize_key($type) . '_enabled', $enabled);
                }
            }
        }

        if ($emailFrom) {
            update_option('fluentshipment_email_from', $emailFrom);
        }

        if ($emailFromName) {
            update_option('fluentshipment_email_from_name', $emailFromName);
        }

        return [
            'success' => true,
            'message' => 'Email settings updated successfully',
        ];
    }

    public function getGeneralSettings()
    {
        return [
            'success'  => true,
            'settings' => [
                'default_estimated_delivery_days' => get_option('fluentshipment_default_delivery_days', 5),
                'auto_create_tracking_number'     => get_option('fluentshipment_auto_tracking_number', true),
                'tracking_number_prefix'          => get_option('fluentshipment_tracking_prefix', 'FS'),
                'enable_customer_tracking'        => get_option('fluentshipment_customer_tracking', true),
                'require_delivery_confirmation'   => get_option('fluentshipment_delivery_confirmation', false),
                'default_currency'                => get_option('fluentshipment_default_currency', 'USD'),
            ],
        ];
    }

    public function updateGeneralSettings(Request $request)
    {
        $request->validate([
            'default_estimated_delivery_days' => 'integer|min:1|max:365',
            'auto_create_tracking_number'     => 'boolean',
            'tracking_number_prefix'          => 'string|max:10',
            'enable_customer_tracking'        => 'boolean',
            'require_delivery_confirmation'   => 'boolean',
            'default_currency'                => 'string|max:3',
        ]);

        $settings = [
            'default_estimated_delivery_days' => $request->getSafe('default_estimated_delivery_days', 'intval'),
            'auto_create_tracking_number'     => $request->getSafe('auto_create_tracking_number', 'rest_sanitize_boolean'),
            'tracking_number_prefix'          => $request->getSafe('tracking_number_prefix', 'sanitize_text_field'),
            'enable_customer_tracking'        => $request->getSafe('enable_customer_tracking', 'rest_sanitize_boolean'),
            'require_delivery_confirmation'   => $request->getSafe('require_delivery_confirmation', 'rest_sanitize_boolean'),
            'default_currency'                => $request->getSafe('default_currency', 'sanitize_text_field'),
        ];

        foreach ($settings as $key => $value) {
            if ($value !== null) {
                update_option('fluentshipment_' . $key, $value);
            }
        }

        return [
            'success' => true,
            'message' => 'General settings updated successfully',
        ];
    }

    public function testEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'type'  => 'required|string|in:processing,delivered',
        ]);

        $testEmail = $request->getSafe('email', 'sanitize_email');
        $emailType = $request->getSafe('type', 'sanitize_text_field');

        $testShipment = $this->createTestShipment();

        // Temporarily override customer email for testing
        $originalEmail                = $testShipment->customer_email;
        $testShipment->customer_email = $testEmail;

        $success = EmailNotificationService::sendShipmentNotification($testShipment, $emailType);

        // Restore original email
        $testShipment->customer_email = $originalEmail;

        if ($success) {
            return [
                'success' => true,
                'message' => 'Test email sent successfully to ' . $testEmail,
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to send test email. Please check your email configuration.',
            ];
        }
    }

    private function createTestShipment()
    {
        return new class extends \FluentShipment\App\Models\Shipment {
            public function __construct()
            {
                $this->attributes = [
                    'id'                   => 999999,
                    'tracking_number'      => 'FSA20241231TEST001',
                    'current_status'       => 'processing',
                    'customer_email'       => '',
                    'estimated_delivery'   => DateTimeHelper::daysFromNow(5),
                    'shipped_at'           => DateTimeHelper::now(),
                    'delivered_at'         => DateTimeHelper::now(),
                    'shipping_cost'        => 1599,
                    'special_instructions' => 'This is a test email notification.',
                    'delivery_address'     => json_encode([
                        'name'      => 'John Doe (Test)',
                        'address_1' => '123 Test Street',
                        'city'      => 'Test City',
                        'state'     => 'TC',
                        'postcode'  => '12345',
                        'country'   => 'Test Country',
                    ]),
                    'shipping_address'     => json_encode([
                        'name'      => 'John Doe (Test)',
                        'address_1' => '123 Test Street',
                        'city'      => 'Test City',
                        'state'     => 'TC',
                        'postcode'  => '12345',
                        'country'   => 'Test Country',
                    ]),
                    'package_info' => json_encode([
                        'items'          => [
                            [
                                'name'     => 'Test Product 1',
                                'quantity' => 2,
                                'weight'   => 1.5,
                            ],
                            [
                                'name'     => 'Test Product 2',
                                'quantity' => 1,
                                'weight'   => 0.8,
                            ],
                        ],
                        'total_items'    => 2,
                        'total_quantity' => 3,
                    ]),
                    'meta' => json_encode([
                        'sender' => [
                            'name'  => 'Test Store',
                            'email' => 'store@test.com',
                            'phone' => '+1-555-TEST',
                        ]
                    ]),
                ];

                $this->exists = true;
            }

            public function getTrackingUrl(): ?string
            {
                return 'https://example.com/track/' . $this->tracking_number;
            }

            public function rider()
            {
                return null;
            }

            public function getRiderAttribute()
            {
                return null;
            }
        };
    }

    public function getSmtpSettings()
    {
        return [
            'success'  => true,
            'settings' => [
                'smtp_enabled'    => get_option('fluentshipment_smtp_enabled', false),
                'smtp_host'       => get_option('fluentshipment_smtp_host', ''),
                'smtp_port'       => get_option('fluentshipment_smtp_port', 587),
                'smtp_encryption' => get_option('fluentshipment_smtp_encryption', 'tls'),
                'smtp_username'   => get_option('fluentshipment_smtp_username', ''),
                'smtp_password'   => get_option('fluentshipment_smtp_password', ''),
                'smtp_auth'       => get_option('fluentshipment_smtp_auth', true),
            ],
        ];
    }

    public function updateSmtpSettings(Request $request)
    {
        $request->validate([
            'smtp_enabled'    => 'boolean',
            'smtp_host'       => 'string|max:255',
            'smtp_port'       => 'integer|min:1|max:65535',
            'smtp_encryption' => 'string|in:none,ssl,tls',
            'smtp_username'   => 'string|max:255',
            'smtp_password'   => 'string|max:255',
            'smtp_auth'       => 'boolean',
        ]);

        $settings = [
            'smtp_enabled'    => $request->getSafe('smtp_enabled', 'rest_sanitize_boolean'),
            'smtp_host'       => $request->getSafe('smtp_host', 'sanitize_text_field'),
            'smtp_port'       => $request->getSafe('smtp_port', 'intval'),
            'smtp_encryption' => $request->getSafe('smtp_encryption', 'sanitize_text_field'),
            'smtp_username'   => $request->getSafe('smtp_username', 'sanitize_text_field'),
            'smtp_password'   => $request->getSafe('smtp_password', 'sanitize_text_field'),
            'smtp_auth'       => $request->getSafe('smtp_auth', 'rest_sanitize_boolean'),
        ];

        foreach ($settings as $key => $value) {
            if ($value !== null) {
                update_option('fluentshipment_' . $key, $value);
            }
        }

        if ($settings['smtp_enabled']) {
            $this->configureWordPressSmtp($settings);
        }

        return [
            'success' => true,
            'message' => 'SMTP settings updated successfully',
        ];
    }

    private function configureWordPressSmtp(array $settings)
    {
        add_action('phpmailer_init', function ($phpmailer) use ($settings) {
            $phpmailer->isSMTP();
            $phpmailer->Host     = $settings['smtp_host'];
            $phpmailer->Port     = $settings['smtp_port'];
            $phpmailer->SMTPAuth = $settings['smtp_auth'];

            if ($settings['smtp_auth']) {
                $phpmailer->Username = $settings['smtp_username'];
                $phpmailer->Password = $settings['smtp_password'];
            }

            if ($settings['smtp_encryption'] !== 'none') {
                $phpmailer->SMTPSecure = $settings['smtp_encryption'];
            }
        });
    }
}
