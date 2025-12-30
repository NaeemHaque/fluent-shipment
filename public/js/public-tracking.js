/*!
 * Fluent Shipment Public Tracking
 * Version: 1.0.0
 */

(function($) {
    'use strict';

    // Main tracking object
    const FluentShipmentTracking = {
        
        // Initialize the tracking functionality
        init: function() {
            this.bindEvents();
            this.checkUrlParams();
        },

        // Bind all event handlers
        bindEvents: function() {
            // Form submission
            $('#fs-tracking-form').on('submit', this.handleFormSubmit.bind(this));
            
            // Real-time search as user types (debounced)
            // $('#fs-tracking-input').on('input', this.debounce(this.handleRealtimeSearch.bind(this), 500));
            
            // Enter key handling
            $('#fs-tracking-input').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $('#fs-tracking-form').trigger('submit');
                }
            });
        },

        // Check if tracking number is in URL parameters
        checkUrlParams: function() {
            const urlParams = new URLSearchParams(window.location.search);
            const tracking = urlParams.get('tracking');
            
            if (tracking && tracking.trim()) {
                $('#fs-tracking-input').val(tracking.trim());
                // Don't auto-submit, let the PHP handle the display
            }
        },

        // Handle form submission
        handleFormSubmit: function(e) {
            e.preventDefault();
            
            const trackingNumber = $('#fs-tracking-input').val().trim();
            
            if (!trackingNumber) {
                this.showError('Please enter a tracking number');
                return;
            }

            // Update URL and reload page for better SEO and sharing
            const newUrl = this.updateUrlParam(window.location.href, 'tracking', trackingNumber);
            window.location.href = newUrl;
        },

        // Handle real-time search (optional enhancement)
        handleRealtimeSearch: function() {
            const trackingNumber = $('#fs-tracking-input').val().trim();
            
            // Only search if we have a complete-looking tracking number
            if (trackingNumber.length < 6) {
                return;
            }
            
            this.performAjaxSearch(trackingNumber);
        },

        // Perform AJAX search
        performAjaxSearch: function(trackingNumber) {
            // Show loading state
            this.showLoading();
            
            $.ajax({
                url: fluentShipmentPublic.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'fluent_shipment_track',
                    tracking_number: trackingNumber,
                    nonce: fluentShipmentPublic.nonce
                },
                success: this.handleAjaxSuccess.bind(this),
                error: this.handleAjaxError.bind(this),
                complete: this.hideLoading.bind(this)
            });
        },

        // Handle successful AJAX response
        handleAjaxSuccess: function(response) {
            if (response.success) {
                this.displayResults(response.data);
            } else {
                this.showError(response.data.message || fluentShipmentPublic.strings.no_results);
            }
        },

        // Handle AJAX error
        handleAjaxError: function(xhr, status, error) {
            console.error('Tracking AJAX Error:', error);
            this.showError(fluentShipmentPublic.strings.error);
        },

        // Display search results
        displayResults: function(data) {
            const resultsHtml = this.buildResultsHtml(data);
            $('#fs-tracking-results').html(resultsHtml).show();
            
            // Scroll to results
            $('html, body').animate({
                scrollTop: $('#fs-tracking-results').offset().top - 20
            }, 500);
        },

        // Build HTML for tracking results
        buildResultsHtml: function(data) {
            const shipment = data.shipment;
            const events = data.events;
            
            let html = '<div class="fs-tracking-success">';
            
            // Shipment Header
            html += `
                <div class="fs-shipment-header">
                    <div class="fs-shipment-info">
                        <div class="fs-shipment-title">
                            Tracking Number: <span class="fs-tracking-number">${this.escapeHtml(shipment.tracking_number)}</span>
                        </div>
                        <div class="fs-current-status">
                            <span class="fs-status-label ${this.getStatusClass(shipment.current_status)}">
                                ${this.getStatusLabel(shipment.current_status)}
                            </span>
                        </div>
                    </div>
                </div>
            `;
            
            // Shipment Details
            html += '<div class="fs-shipment-details"><div class="fs-detail-grid">';
            
            if (shipment.carrier) {
                html += `
                    <div class="fs-detail-item">
                        <label>Carrier:</label>
                        <span>${this.escapeHtml(this.capitalizeFirst(shipment.carrier))}</span>
                    </div>
                `;
            }
            
            if (shipment.estimated_delivery) {
                html += `
                    <div class="fs-detail-item">
                        <label>Estimated Delivery:</label>
                        <span>${this.formatDate(shipment.estimated_delivery)}</span>
                    </div>
                `;
            }
            
            html += `
                <div class="fs-detail-item">
                    <label>Ship Date:</label>
                    <span>${this.formatDate(shipment.created_at)}</span>
                </div>
            `;
            
            if (shipment.delivery_address) {
                html += `
                    <div class="fs-detail-item fs-detail-full">
                        <label>Delivery Address:</label>
                        <span>${this.escapeHtml(shipment.delivery_address)}</span>
                    </div>
                `;
            }
            
            html += '</div></div>';
            
            // Tracking Timeline
            if (events && events.length > 0) {
                html += '<div class="fs-tracking-timeline">';
                html += '<h4 class="fs-timeline-title">Tracking History</h4>';
                html += '<div class="fs-timeline">';
                
                events.forEach((event, index) => {
                    const isFirst = index === 0;
                    const isLast = index === events.length - 1;
                    const isMilestone = event.is_milestone;
                    
                    html += `
                        <div class="fs-timeline-item ${isFirst ? 'fs-timeline-current' : ''} ${isMilestone ? 'fs-timeline-milestone' : ''}">
                            <div class="fs-timeline-marker">
                                <div class="fs-timeline-dot"></div>
                                ${!isLast ? '<div class="fs-timeline-line"></div>' : ''}
                            </div>
                            <div class="fs-timeline-content">
                                <div class="fs-timeline-header">
                                    <span class="fs-timeline-status ${this.getStatusClass(event.status)}">
                                        ${this.getStatusLabel(event.status)}
                                    </span>
                                    <span class="fs-timeline-date">
                                        ${event.formatted_date}
                                    </span>
                                </div>
                    `;
                    
                    if (event.description) {
                        html += `<div class="fs-timeline-description">${this.escapeHtml(event.description)}</div>`;
                    }
                    
                    if (event.location) {
                        html += `<div class="fs-timeline-location">📍 ${this.escapeHtml(event.location)}</div>`;
                    }
                    
                    html += '</div></div>';
                });
                
                html += '</div></div>';
            }
            
            html += '</div>';
            
            return html;
        },

        // Show loading state
        showLoading: function() {
            $('#fs-tracking-loading').show();
            $('#fs-tracking-results').hide();
            
            // Update button state
            const $button = $('.fs-search-button');
            $button.prop('disabled', true);
            $button.find('.fs-search-text').hide();
            $button.find('.fs-search-loading').show();
        },

        // Hide loading state
        hideLoading: function() {
            $('#fs-tracking-loading').hide();
            
            // Reset button state
            const $button = $('.fs-search-button');
            $button.prop('disabled', false);
            $button.find('.fs-search-text').show();
            $button.find('.fs-search-loading').hide();
        },

        // Show error message
        showError: function(message) {
            const errorHtml = `
                <div class="fs-tracking-error">
                    <div class="fs-error-icon">📦</div>
                    <h3>Error</h3>
                    <p>${this.escapeHtml(message)}</p>
                </div>
            `;
            
            $('#fs-tracking-results').html(errorHtml).show();
        },

        // Utility: Debounce function
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        // Utility: Update URL parameter
        updateUrlParam: function(url, param, paramVal) {
            let newAdditionalURL = "";
            let tempArray = url.split("?");
            let baseURL = tempArray[0];
            let additionalURL = tempArray[1];
            let temp = "";
            
            if (additionalURL) {
                tempArray = additionalURL.split("&");
                for (let i = 0; i < tempArray.length; i++) {
                    if (tempArray[i].split('=')[0] != param) {
                        newAdditionalURL += temp + tempArray[i];
                        temp = "&";
                    }
                }
            }
            
            let rowsText = temp + "" + param + "=" + paramVal;
            return baseURL + "?" + newAdditionalURL + rowsText;
        },

        // Utility: Escape HTML
        escapeHtml: function(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        },

        // Utility: Capitalize first letter
        capitalizeFirst: function(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        },

        // Utility: Format date
        formatDate: function(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        },

        // Get status label
        getStatusLabel: function(status) {
            const labels = {
                'pending': 'Pending',
                'processing': 'Processing',
                'shipped': 'Shipped',
                'in_transit': 'In Transit',
                'out_for_delivery': 'Out for Delivery',
                'delivered': 'Delivered',
                'failed': 'Delivery Failed',
                'cancelled': 'Cancelled',
                'returned': 'Returned',
                'exception': 'Exception'
            };
            
            return labels[status] || this.capitalizeFirst(status.replace('_', ' '));
        },

        // Get status CSS class
        getStatusClass: function(status) {
            const classes = {
                'pending': 'status-pending',
                'processing': 'status-processing',
                'shipped': 'status-shipped',
                'in_transit': 'status-transit',
                'out_for_delivery': 'status-delivery',
                'delivered': 'status-delivered',
                'failed': 'status-failed',
                'cancelled': 'status-cancelled',
                'returned': 'status-returned',
                'exception': 'status-exception'
            };
            
            return classes[status] || 'status-default';
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        // Only initialize if the tracking container exists
        if ($('#fluent-shipment-tracking').length) {
            FluentShipmentTracking.init();
        }
    });

    // Expose to global scope for external access
    window.FluentShipmentTracking = FluentShipmentTracking;

})(jQuery);
