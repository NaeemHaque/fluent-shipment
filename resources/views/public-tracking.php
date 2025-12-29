<?php
use FluentShipment\App\Shortcodes\TrackingShortcode;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="fluent-shipment-tracking" id="fluent-shipment-tracking">
    
    <!-- Header Section -->
    <div class="fs-tracking-header">
        <h2 class="fs-tracking-title"><?php echo esc_html($atts['title']); ?></h2>
    </div>

    <!-- Search Form -->
    <?php if ($atts['show_search'] === 'yes'): ?>
    <div class="fs-tracking-search">
        <form class="fs-search-form" id="fs-tracking-form" method="get">
            <div class="fs-search-input-group">
                <input 
                    type="text" 
                    name="tracking" 
                    id="fs-tracking-input"
                    class="fs-search-input" 
                    placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
                    value="<?php echo esc_attr($trackingNumber); ?>"
                    required
                >
                <button type="submit" class="fs-search-button">
                    <span class="fs-search-text"><?php echo esc_html($atts['button_text']); ?></span>
                    <span class="fs-search-loading" style="display: none;">
                        <span class="fs-spinner"></span>
                        Searching...
                    </span>
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Results Container -->
    <div class="fs-tracking-results" id="fs-tracking-results">
        
        <?php if ($trackingNumber && !$shipment): ?>
            <!-- No Results Found -->
            <div class="fs-tracking-error">
                <div class="fs-error-icon">📦</div>
                <h3>Shipment Not Found</h3>
                <p>No shipment found with tracking number: <strong><?php echo esc_html($trackingNumber); ?></strong></p>
                <p>Please check your tracking number and try again.</p>
            </div>
        <?php endif; ?>

        <?php if ($shipment): ?>
            <!-- Shipment Found - Display Results -->
            <div class="fs-tracking-success">
                
                <!-- Shipment Header -->
                <div class="fs-shipment-header">
                    <div class="fs-shipment-info">
                        <h3 class="fs-shipment-title">
                            Tracking Number: <span class="fs-tracking-number"><?php echo esc_html($shipment->tracking_number); ?></span>
                        </h3>
                        <div class="fs-current-status">
                            <span class="fs-status-label <?php echo esc_attr(TrackingShortcode::getStatusClass($shipment->current_status)); ?>">
                                <?php echo esc_html(TrackingShortcode::getStatusLabel($shipment->current_status)); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Shipment Details -->
                <div class="fs-shipment-details">
                    <div class="fs-detail-grid">
                        <?php if ($shipment->carrier): ?>
                        <div class="fs-detail-item">
                            <label>Carrier:</label>
                            <span><?php echo esc_html(ucfirst($shipment->carrier)); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($shipment->estimated_delivery): ?>
                        <div class="fs-detail-item">
                            <label>Estimated Delivery:</label>
                            <span><?php echo esc_html(date('M j, Y', strtotime($shipment->estimated_delivery))); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="fs-detail-item">
                            <label>Ship Date:</label>
                            <span><?php echo esc_html(date('M j, Y', strtotime($shipment->created_at))); ?></span>
                        </div>
                        
                        <?php if ($shipment->delivery_address): ?>
                        <div class="fs-detail-item fs-detail-full">
                            <label>Delivery Address:</label>
                            <span><?php 
                                $address = $shipment->delivery_address;
                                if (is_string($address)) {
                                    echo esc_html($address);
                                } elseif (is_array($address) || is_object($address)) {
                                    $addr = (array) $address;
                                    $parts = array_filter([
                                        $addr['address_1'] ?? '',
                                        $addr['city'] ?? '',
                                        $addr['state'] ?? '',
                                        $addr['postcode'] ?? '',
                                        $addr['country'] ?? '',
                                    ]);
                                    echo esc_html(implode(', ', $parts));
                                } else {
                                    echo 'N/A';
                                }
                            ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tracking Timeline -->
                <?php 
                $hasEvents = false;
                if (is_array($trackingEvents)) {
                    $hasEvents = !empty($trackingEvents);
                } elseif (is_object($trackingEvents) && method_exists($trackingEvents, 'count')) {
                    $hasEvents = $trackingEvents->count() > 0;
                } elseif (is_object($trackingEvents)) {
                    $hasEvents = count((array)$trackingEvents) > 0;
                }
                ?>
                <?php if ($hasEvents): ?>
                <div class="fs-tracking-timeline">
                    <h4 class="fs-timeline-title">Tracking History</h4>
                    
                    <div class="fs-timeline">
                        <?php foreach ($trackingEvents as $index => $event): ?>
                        <?php
                            // Handle both object and array formats safely
                            $eventStatus = is_object($event) ? $event->event_status : ($event['event_status'] ?? '');
                            $eventDate = is_object($event) ? $event->event_date : ($event['event_date'] ?? '');
                            $eventDescription = is_object($event) ? $event->event_description : ($event['event_description'] ?? '');
                            $eventLocation = is_object($event) ? $event->event_location : ($event['event_location'] ?? '');
                            $isMilestone = is_object($event) ? $event->is_milestone : ($event['is_milestone'] ?? false);
                        ?>
                        <div class="fs-timeline-item <?php echo $index === 0 ? 'fs-timeline-current' : ''; ?> <?php echo $isMilestone ? 'fs-timeline-milestone' : ''; ?>">
                            <div class="fs-timeline-marker">
                                <div class="fs-timeline-dot"></div>
                                <?php if ($index < count($trackingEvents) - 1): ?>
                                <div class="fs-timeline-line"></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="fs-timeline-content">
                                <div class="fs-timeline-header">
                                    <span class="fs-timeline-status <?php echo esc_attr(TrackingShortcode::getStatusClass($eventStatus)); ?>">
                                        <?php echo esc_html(TrackingShortcode::getStatusLabel($eventStatus)); ?>
                                    </span>
                                    <span class="fs-timeline-date">
                                        <?php 
                                        if ($eventDate) {
                                            echo esc_html(date('M j, Y g:i A', strtotime($eventDate)));
                                        }
                                        ?>
                                    </span>
                                </div>
                                
                                <?php if ($eventDescription): ?>
                                <div class="fs-timeline-description">
                                    <?php echo esc_html($eventDescription); ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($eventLocation): ?>
                                <div class="fs-timeline-location">
                                    📍 <?php echo esc_html($eventLocation); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        <?php endif; ?>

    </div>

    <!-- Loading State (Hidden by default) -->
    <div class="fs-tracking-loading" id="fs-tracking-loading" style="display: none;">
        <div class="fs-loading-content">
            <div class="fs-spinner-large"></div>
            <p>Searching for your shipment...</p>
        </div>
    </div>

    <!-- Powered By -->
    <div class="fs-tracking-footer">
        <p class="fs-powered-by">Powered by FluentShipment</p>
    </div>

</div>

<script type="text/javascript">
// Auto-focus on tracking input if no tracking number is present
jQuery(document).ready(function($) {
    if (!$('#fs-tracking-input').val()) {
        $('#fs-tracking-input').focus();
    }
});
</script>