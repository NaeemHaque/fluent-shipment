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
        $emailSettings = $this->getEmailSettingsData();
        $emailTypes    = EmailNotificationService::getEmailTypes();

        $notifications = [];
        foreach ($emailTypes as $type => $label) {
            $notifications[$type] = [
                'enabled' => $emailSettings['notifications'][$type] ?? true,
                'label'   => $label,
            ];
        }

        return [
            'success'  => true,
            'settings' => [
                'email_notifications' => $notifications,
                'email_from'          => $emailSettings['from_email'],
                'email_from_name'     => $emailSettings['from_name'],
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

        $emailSettings = $this->getEmailSettingsData();
        
        $emailNotifications = $request->getSafe('email_notifications', 'fluentShipmentSanitizeArray', []);
        $emailFrom          = $request->getSafe('email_from', 'sanitize_email');
        $emailFromName      = $request->getSafe('email_from_name', 'sanitize_text_field');

        if (!empty($emailNotifications)) {
            foreach ($emailNotifications as $type => $settings) {
                if (isset($settings['enabled'])) {
                    $emailSettings['notifications'][sanitize_key($type)] = rest_sanitize_boolean($settings['enabled']);
                }
            }
        }

        if ($emailFrom) {
            $emailSettings['from_email'] = $emailFrom;
        }

        if ($emailFromName) {
            $emailSettings['from_name'] = $emailFromName;
        }

        update_option('fluentshipment_email_settings', $emailSettings);

        return [
            'success' => true,
            'message' => 'Email settings updated successfully',
        ];
    }

    public function getGeneralSettings()
    {
        $generalSettings = $this->getGeneralSettingsData();
        
        return [
            'success'  => true,
            'settings' => $generalSettings,
        ];
    }

    public function updateGeneralSettings(Request $request)
    {
        $request->validate([
            'tracking_page_url' => 'nullable|string|max:500',
        ]);

        $generalSettings = $this->getGeneralSettingsData();

        if ($request->has('default_estimated_delivery_days')) {
            $generalSettings['default_estimated_delivery_days'] = $request->getSafe('default_estimated_delivery_days', 'intval');
        }
        if ($request->has('auto_create_tracking_number')) {
            $generalSettings['auto_create_tracking_number'] = $request->getSafe('auto_create_tracking_number', 'rest_sanitize_boolean');
        }
        if ($request->has('tracking_number_prefix')) {
            $generalSettings['tracking_number_prefix'] = $request->getSafe('tracking_number_prefix', 'sanitize_text_field');
        }
        if ($request->has('enable_customer_tracking')) {
            $generalSettings['enable_customer_tracking'] = $request->getSafe('enable_customer_tracking', 'rest_sanitize_boolean');
        }
        if ($request->has('require_delivery_confirmation')) {
            $generalSettings['require_delivery_confirmation'] = $request->getSafe('require_delivery_confirmation', 'rest_sanitize_boolean');
        }
        if ($request->has('default_currency')) {
            $generalSettings['default_currency'] = $request->getSafe('default_currency', 'sanitize_text_field');
        }
        if ($request->has('tracking_page_url')) {
            $generalSettings['tracking_page_url'] = $request->getSafe('tracking_page_url', 'sanitize_url');
        }

        update_option('fluentshipment_general_settings', $generalSettings);

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

        // Set the test email directly in attributes for the test
        $testShipment->setAttribute('customer_email', $testEmail);

        try {
            $success = EmailNotificationService::sendShipmentNotification($testShipment, $emailType);

            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Test email sent successfully to ' . $testEmail,
                ];
            } else {
                // Check if email notifications are enabled for this type
                $emailSettings = $this->getEmailSettingsData();
                if (!($emailSettings['notifications'][$emailType] ?? true)) {
                    return [
                        'success' => false,
                        'message' => 'Email notifications are disabled for ' . $emailType . ' type.',
                    ];
                }
                
                return [
                    'success' => false,
                    'message' => 'Failed to send test email. Please check your email configuration and WordPress mail settings.',
                ];
            }
        } catch (\Exception $e) {
            error_log('Test Email Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error sending test email: ' . $e->getMessage(),
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
                if ($this->tracking_number) {
                    $generalSettings = get_option('fluentshipment_general_settings', []);
                    $trackingPageUrl = $generalSettings['tracking_page_url'] ?? '';

                    if ($trackingPageUrl) {
                        return $trackingPageUrl . '?tracking=' . $this->tracking_number;
                    }
                }

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
        $smtpSettings = $this->getSmtpSettingsData();
        
        return [
            'success'  => true,
            'settings' => $smtpSettings,
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

        $smtpSettings = $this->getSmtpSettingsData();

        if ($request->has('smtp_enabled')) {
            $smtpSettings['enabled'] = $request->getSafe('smtp_enabled', 'rest_sanitize_boolean');
        }
        if ($request->has('smtp_host')) {
            $smtpSettings['host'] = $request->getSafe('smtp_host', 'sanitize_text_field');
        }
        if ($request->has('smtp_port')) {
            $smtpSettings['port'] = $request->getSafe('smtp_port', 'intval');
        }
        if ($request->has('smtp_encryption')) {
            $smtpSettings['encryption'] = $request->getSafe('smtp_encryption', 'sanitize_text_field');
        }
        if ($request->has('smtp_username')) {
            $smtpSettings['username'] = $request->getSafe('smtp_username', 'sanitize_text_field');
        }
        if ($request->has('smtp_password')) {
            $smtpSettings['password'] = $request->getSafe('smtp_password', 'sanitize_text_field');
        }
        if ($request->has('smtp_auth')) {
            $smtpSettings['auth'] = $request->getSafe('smtp_auth', 'rest_sanitize_boolean');
        }

        update_option('fluentshipment_smtp_settings', $smtpSettings);

        if ($smtpSettings['enabled']) {
            $this->configureWordPressSmtp($smtpSettings);
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
            $phpmailer->Host     = $settings['host'];
            $phpmailer->Port     = $settings['port'];
            $phpmailer->SMTPAuth = $settings['auth'];

            if ($settings['auth']) {
                $phpmailer->Username = $settings['username'];
                $phpmailer->Password = $settings['password'];
            }

            if ($settings['encryption'] !== 'none') {
                $phpmailer->SMTPSecure = $settings['encryption'];
            }
        });
    }

    private function getEmailSettingsData(): array
    {
        $defaults = [
            'notifications' => [
                'processing' => true,
                'delivered'  => true,
            ],
            'from_email'    => get_bloginfo('admin_email'),
            'from_name'     => get_bloginfo('name'),
        ];

        $settings = get_option('fluentshipment_email_settings', []);

        return wp_parse_args($settings, $defaults);
    }

    private function getGeneralSettingsData(): array
    {
        $defaults = [
            'default_estimated_delivery_days' => 5,
            'auto_create_tracking_number'     => true,
            'tracking_number_prefix'          => 'FS',
            'enable_customer_tracking'        => true,
            'require_delivery_confirmation'   => false,
            'default_currency'                => 'USD',
            'tracking_page_url'               => '',
        ];

        $settings = get_option('fluentshipment_general_settings', []);

        return wp_parse_args($settings, $defaults);
    }

    private function getSmtpSettingsData(): array
    {
        $defaults = [
            'enabled'    => false,
            'host'       => '',
            'port'       => 587,
            'encryption' => 'tls',
            'username'   => '',
            'password'   => '',
            'auth'       => true,
        ];

        $settings = get_option('fluentshipment_smtp_settings', []);

        return wp_parse_args($settings, $defaults);
    }
}
