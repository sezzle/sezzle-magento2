# AGENTS.md

This file provides guidance to AI coding agents and assistants when working with code in this repository.

## Project Overview

Sezzle Payment Extension for Magento 2 (`Sezzle_Sezzlepay`) - a buy-now-pay-later payment gateway distributed via Magento Marketplace and GitHub.

## Development Setup

### Local Environment

Developed within a full Magento 2 installation using MAMP + OpenSearch:

1. Start required services:
   ```bash
   # Start OpenSearch container
   docker start opensearch

   # Open MAMP and start Apache/MySQL
   # Ensure Document Root is set to: /Applications/MAMP/htdocs/magento/248/pub
   ```
> Note: If the `opensearch` container has not been created yet, follow the OpenSearch setup steps in `DEVELOPERS.md`.

2. Development location:
   ```bash
   # Extension code is at:
   /Applications/MAMP/htdocs/magento/248/vendor/sezzle/sezzlepay
   # OR (manual install):
   /Applications/MAMP/htdocs/magento/248/app/code/Sezzle/Sezzlepay
   ```

3. After making changes:
   ```bash
   cd /Applications/MAMP/htdocs/magento/248
   php -d memory_limit=-1 bin/magento setup:upgrade  # database changes
   php -d memory_limit=-1 bin/magento setup:di:compile  # dependency changes
   php -d memory_limit=-1 bin/magento setup:static-content:deploy -f  # HTML/CSS/JS
   php -d memory_limit=-1 bin/magento indexer:reindex
   php -d memory_limit=-1 bin/magento cache:clean
   ```

### Testing Access

- Storefront: http://127.0.0.1:8888
- Admin Panel: http://127.0.0.1:8888/admin
  - Username: `admin`
  - Password: `admin123`

## Architecture

### Module Structure

**API Layer** (`Api/`):
- Service contracts for checkout, customer management, and settlement reports
- Supports authenticated (`CartManagementInterface`) and guest (`GuestCartManagementInterface`) operations

**Gateway Layer** (`Gateway/`):
- Implements Magento's Payment Gateway pattern
- `Command/`: Payment operations (authorize, capture, refund, release)
- `Request/`: Builds API request payloads for Sezzle API
- `Response/`: Handles and validates Sezzle API responses
- `Validator/`: Request/response validation
- `Http/`: HTTP client and authentication services

**Model Layer** (`Model/`):
- `Model/Checkout/`: Checkout session management and order creation
- `Model/Tokenize/`: Customer tokenization for returning customers
- `Model/Quote/`: Shopping cart integration
- `Model/SettlementReports`: Payout reconciliation functionality

**UI Components** (`Block/`, `view/`):
- `Block/Widget/`: Product page, cart, and installment widgets (Knockout.js)
- `view/frontend/`: Storefront templates and JavaScript
- `view/adminhtml/`: Admin panel configuration

**Configuration** (`etc/`):
- `di.xml`: Dependency injection and service wiring (extensive virtual types for payment gateway)
- `config.xml`: Default configuration values
- `webapi.xml`: REST API endpoint definitions
- `events.xml`: Event observers
- `payment.xml`: Payment method registration

### Payment Flow

1. Checkout → Session creation via `Model/Checkout/CheckoutManagement`
2. `Gateway/Command/InitializeCommand` builds request via `Gateway/Request/Session/*RequestBuilder`
3. Customer pays on Sezzle → Returns to `Controller/Payment/Complete.php`
4. `Gateway/Command/AuthorizeCommand` validates with Sezzle API
5. Invoice capture → `Gateway/Command/CaptureCommand`
6. Refunds → `Gateway/Command/RefundCommand`

### Key Integration Points

**Sezzle API Integration**:
- Base client: `Gateway/Http/Client.php`
- Authentication: `Gateway/Http/AuthenticationService.php`
- Two API versions: `Model/Api/V1.php` and `Model/Api/V2.php`

**Magento Integration**:
- Payment method adapter: Virtual type `SezzleFacade` in `di.xml`
- Configuration: `Gateway/Config/Config.php` reads from `Helper/Data.php`
- Observer pattern: `Observer/` for order/payment events
- Plugin pattern: `Plugin/` for extending core Magento functionality

## Release Process

1. Update version in `composer.json` and `CHANGELOG.md`
2. Create zip: `sezzle_sezzlepay-{version}.zip` (compress all contents)
3. Merge to `production` branch
4. Submit to Adobe Commerce Marketplace (see DEVELOPERS.md)
5. Create release tag on GitHub after mirror completes

## Important Notes

- **Two-Factor Auth**: Disabled by default for local development
- **API Keys**: Sandbox keys can be reused across test stores (validation only checks merchant exists)
- **Widget Configuration**: Generated widget UUID is based on API keys
- **Logging**: Extension logs to `/var/log/sezzlepay.log`
- **Memory Limits**: Always use `php -d memory_limit=-1` for bin/magento commands
- **Branch**: Primary branch is `production` (not main/master)
- **Mirror Setup**: GitLab project mirrors to GitHub for public distribution
