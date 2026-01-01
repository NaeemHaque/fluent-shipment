# Fluent Shipment

Fluent Shipment is a WordPress plugin for managing and tracking e-commerce shipments. It supports manual shipments, FluentCart imports, rider assignment, and a centralized dashboard—while automatically notifying customers by email at every stage of delivery.



![License](https://img.shields.io/badge/license-GPL--2.0-blue.svg)
![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-green.svg)
![WordPress](https://img.shields.io/badge/wordpress-%3E%3D5.0-blue.svg)
![Version](https://img.shields.io/badge/version-1.0.0-orange.svg)

## 🔗 Quick Access

[![Live Demo](https://img.shields.io/badge/Live_Demo-Try_Now-brightgreen?style=for-the-badge&logo=wordpress)](https://dev-ninjatables.pantheonsite.io/wp-admin/?wtlwp_token=10b43ef3005fafb162cd895bc2266f5b5719ff14b0dbce1b6a4fd1b4c0c7db93c512e9eab2c31a682ae7d56c3a2ab26847fd880f13b26573784c501a7da05899)
[![Video Demo](https://img.shields.io/badge/Video_Demo-Watch_Now-red?style=for-the-badge&logo=youtube)](https://youtu.be/VnFdJlhevGs)

## Features

### 🚀 Core Features

- **Fluent Cart Integration**: Automatically create shipments by importing orders from Fluent Cart
- **Real-Time Tracking**: Complete shipment tracking with 8 different status levels
- **Tracking Events**: Detailed event logging for each shipment stage
- **Public Tracking Page**: Beautiful public tracking page for customers
- **Rider Management**: Comprehensive rider assignment and management system with performance tracking
- **Dashboard Analytics**: Beautiful dashboard with interactive charts and statistics powered by ECharts
- **Email Notifications**: Automated customer notifications for important shipment updates
- **Advanced Search & Filtering**: Filter shipments by status, date range, tracking number, customer email, and more

### 🚀 Upcoming Features

- **Multi-Platform Support**: Seamlessly integrates with WooCommerce, Sure Cart, and supports manual/API shipment creation
- **Third-party integration**: Integration with major shipping carriers like UPS, FedEx, USPS, DHL, and more for automated label generation, real-time rates, and tracking


## Shipment Statuses

The plugin supports comprehensive shipment tracking with the following statuses:

| Status | Description |
|--------|-------------|
| `pending` | Shipment created but not yet processed |
| `processing` | Shipment is being prepared |
| `shipped` | Package has been shipped |
| `in_transit` | Package is on the way |
| `out_for_delivery` | Package is out for final delivery |
| `delivered` | Successfully delivered to customer |
| `failed` | Delivery attempt failed |
| `cancelled` | Shipment was cancelled |

## Requirements

- **PHP**: 7.4 or higher
- **WordPress**: 5.0 or higher
- **MySQL**: 5.6 or higher
- **Node.js**: 16+ (for development)
- **Composer**: Latest version

## Development Setup

For developers who want to contribute or customize the plugin:

1. **Clone Repository**
   ```bash
   git clone https://github.com/naeemHaque/fluent-shipment.git
   cd fluent-shipment
   ```

2. **Install All Dependencies**
   ```bash
   # Install PHP dependencies (including dev)
   composer install

   # Install Node.js dependencies
   npm install
   ```

3. **Development Workflow**
   ```bash
   # Start development server with hot reload
   npm run dev

   # Or watch for changes
   npm run watch

   # Build for production
   npm run build
   ```

## Configuration

### Database Setup

The plugin automatically creates the necessary database tables on activation:

- `fluent_shipments` - Main shipments table
- `fluent_shipment_tracking_events` - Tracking events log
- `fluent_shipment_riders` - Rider management


### Plugin Settings

Configure the plugin through:
1. WordPress Admin → Fluent Shipment → Settings 
2. Copy shortcode and create Tracking page via shortcode
3. Configure tracking page URL
4. Configure notification preferences

## License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

### Author

**Golam Sarwer Naeem**
- GitHub: [@naeemHaque](https://github.com/naeemHaque)
- Plugin URI: https://github.com/naeemHaque/fluent-shipment

---

Made with ❤️ for the WordPress community
