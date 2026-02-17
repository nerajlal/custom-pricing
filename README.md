# Custom Pricing & Loyalty App

## Executive Summary

This application is a comprehensive solution designed to empower Shopify merchants with advanced customer retention and B2B capabilities. It seamlessly integrates two powerful growth engines into a single platform: **Personalized Wholesale Pricing** and a **Rewards-based Loyalty Program**.

Designed for flexibility, this app allows store owners to treat their VIP and Wholesale customers differently from standard retail visitors, fostering deeper relationships and increasing lifetime value without needing separate storefronts or complex workarounds.

### Key Capabilities

*   **B2B & Wholesale Pricing Engine**
    Enable specific pricing for your most valuable customers. You can create "Tiers" (like Gold, Wholesale, or VIP) and assign customers to them. When these customers log in, they instantly see their special prices across the entire store—automatically replacing the standard retail price.

*   **Integrated Loyalty Rewards**
    Turn every purchase into an opportunity for future sales. Customers earn points for every dollar spent and can redeem them for automatic discounts. The system handles everything from tracking point balances to generating unique coupon codes for redemption.

*   **Seamless Customer Experience**
    The app works silently in the background. There are no pop-ups or intrusive widgets unless you want them. Prices update instantly, and loyalty points are tracked automatically, ensuring a smooth shopping experience that feels native to your brand.

---

> **Note for Developers & Technical Teams**
> The section below details the architecture, installation, and API structure required to maintain or extend this application.

---

## Technical Documentation

### System Architecture

This is a **Custom Shopify App** built on a monolithic **Laravel** framework (PHP). It differs from standard "App Bridge" apps by managing its own authentication and frontend injection logic, providing greater control over performance and security.

*   **Backend Core**: Laravel 10 handling data persistence, business logic, and Shopify API communication.
*   **Storefront Integration**: Uses **ScriptTag injection**. A lightweight, compiled JavaScript file is served to the storefront, which negotiates with the backend API to personalize the page for the logged-in user.
*   **Admin Interface**: Built with Blade Templates and Tailwind CSS for a fast, responsive backend experience without the complexity of a separate React frontend.

### Installation & Deployment

1.  **Clone Repository**
    Clone the codebase to your server or local environment.
    
    `git clone <repository-url>`

2.  **Dependencies**
    Install PHP and Node.js dependencies.
    
    `composer install`
    
    `npm install && npm run build`

3.  **Environment Setup**
    Configure your `.env` file with MySQL credentials and Shopify App keys (`Client ID`, `Client Secret`, `Scopes`).

4.  **Database Migration**
    Initialize the system tables.
    
    `php artisan migrate`

### internal API Reference

The application exposes several endpoints for the frontend script and admin actions.

### Frontend Architecture

The application uses a hybrid approach to ensure custom prices and loyalty components appear seamlessly on the storefront.

*   **Dynamic Script Injection**: The main entry point is a script loaded via the Shopify App Proxy (`/apps/custompicker/...`) or ScriptTag. This allows the script to be dynamically generated with server-side context (like the customer's ID and settings) using Blade templates.
*   **Price Flashing Prevention**: To prevent the "flash of original price" before custom prices load:
    *   **Global Hiding**: A global style (`metora-initial-hide`) is injected immediately to hide price elements on product and collection pages.
    *   **Cart-Specific Hiding**: A targeted style (`metora-cart-hide`) is used on the cart page to ensure original prices are hidden until the custom pricing calculation is complete.
*   **Mutation Observers**: The script uses `MutationObserver` to detect dynamic cart updates (e.g., side drawers, quantity changes) and re-apply custom pricing without requiring a page reload.

#### Storefront API (Public)

*   **GET /api/storefront/custom-price**
    Checks if the currently logged-in customer has a special price for the specific product variant being viewed. Returns the custom price and original price if a rule exists.

*   **POST /api/storefront/draft-order**
    Securely creates a Draft Order in Shopify. This is critical for B2B checkout flows to ensure the custom price is honored and cannot be tampered with by the user in the browser.

*   **GET /api/storefront/loyalty/search**
    Retrieves the point balance and active tier for a customer.

*   **POST /api/storefront/loyalty/redeem**
    Redeems a set number of points and responds with a valid, unique Shopify Discount Code.

#### Admin API (Authenticated)

All Admin routes are protected by custom middleware that verifies the request signature from Shopify.

*   **Customer Management**: Endpoints to search Shopify customers and assign them to Pricing Tiers.
*   **Price Overrides**: Endpoints to create, update, or delete specific price rules for a Variant + Customer combination.
*   **Loyalty Configuration**: Endpoints to adjust point values, setting earning rates (e.g., 10 points per $1), and managing manual point adjustments.

### Security Implementation

*   **Authentication**: Implements a custom OAuth2 flow to exchange authorization codes for permanent access tokens. Tokens are encrypted at rest.
*   **Request Validation**: All incoming Webhooks and Admin requests are verified using HMAC signatures to ensure they originate from Shopify.
*   **Frontend Security**: The storefront script treats all client-side data as untrusted. Final price calculations for checkout are always performed server-side via the Draft Order API to prevent manipulation.
