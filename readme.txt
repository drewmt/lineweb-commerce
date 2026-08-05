=== Lineweb Commerce Suite ===
Contributors: lineweb
Tags: woocommerce, product comparison, delivery estimate, free shipping, product specifications
Requires at least: 6.9
Tested up to: 7.0
Requires PHP: 8.3
Requires Plugins: woocommerce
WC requires at least: 10.8
WC tested up to: 10.9
Stable tag: 0.6.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Useful WooCommerce blocks for product facts, delivery, comparison, and free-shipping progress.

== Description ==

Lineweb Commerce Suite adds four focused Gutenberg blocks and places selected conversion-critical ones automatically:

* Product Specifications shows SKU, current availability, weight, dimensions, and visible WooCommerce attributes.
* Delivery Estimate uses a merchant-confirmed global profile or an explicit local override.
* Free Shipping Progress shows the live cart amount remaining before free shipping.
* Product Comparison shows two to four catalog-visible products with live prices, availability, dimensions, selected attributes, links, and a differences-only view.

Delivery Estimate is not shown automatically until a merchant reviews and confirms the global delivery profile. Free Shipping Progress can appear before checkout in the official Cart and between items and actions in the Mini-Cart when a matching WooCommerce rule exists. Existing placement selections survive upgrades, future placements default off, and manual blocks are respected to prevent duplicates.

Product data stays in WooCommerce. The product blocks can use a selected product or the current product in a product template. Free Shipping Progress can detect the matching WooCommerce free-shipping method or use a manual threshold.

The plugin uses semantic server-rendered HTML, responsive layouts, and a small Store API enhancement for live cart changes. It includes English and Greek interface translations. It adds no analytics, personal-data storage, pricing rule, shipping method, checkout field, payment integration, or order mutation.

== Installation ==

1. Install and activate WooCommerce.
2. Upload the `lineweb-commerce` plugin folder or install its ZIP.
3. Activate Lineweb Commerce Suite.
4. Open WooCommerce > Lineweb Commerce for the feature guide, live placement status, and quick actions.
5. Configure a WooCommerce free-shipping method with a minimum amount if you want automatic progress on Cart and Mini-Cart.
6. Review and confirm the global delivery profile, then review automatic placements under WooCommerce > Settings > Products > Lineweb Commerce.
7. Use the Lineweb Commerce block category only when you want a custom placement or a product-specific block in other content.

Product Specifications and Delivery Estimate can use the current product in a product template or an explicitly selected product in a post or page. Product Comparison can be added manually to a page, post, product, or buying guide.

== Frequently Asked Questions ==

= Where do specification values come from? =

From the selected WooCommerce product: SKU, stock state, weight, dimensions, and attributes marked visible on the product page. The block does not maintain a duplicate data source.

= Is the delivery date guaranteed? =

No. It is a clearly labelled estimate based on current stock, site timezone, configured business-day range, cutoff, weekends, and holidays. Cached pages can briefly show an older date until the cache refreshes.

= How is the free-shipping threshold selected? =

Use 0 to detect the free-shipping method in the matching WooCommerce shipping zone. You can also enter a manual amount. The calculation follows the displayed cart subtotal and the shipping method's discount setting.

= Does the progress block change the cart? =

No. It only reads the current cart. Normal WooCommerce add/remove/coupon events trigger a fresh Store API read.

= Does it collect visitor data? =

No. It adds no tracking, fingerprint, lead form, account, cookie of its own, or custom database table.

= What happens when a customer selects a variation? =

On supported classic and block product forms, Product Specifications and Delivery Estimate update from the selected variation data. Test the active theme and any customized product form before release.

== Privacy ==

The plugin reads the WooCommerce product and cart data needed for the visible feature. It does not create a visitor profile, send telemetry, add tracking cookies, or write orders, inventory, payments, shipping methods, fees, or discounts.

== Accessibility and Performance ==

Public output uses semantic server-rendered markup, focus styling, labelled states, responsive layouts, and controlled horizontal scrolling for comparison tables. Product Comparison reads the selected products together for each render rather than copying catalog data into plugin storage.

Free Shipping Progress uses the WooCommerce Store API and normal WooCommerce cart events to refresh its display. It does not mutate the cart. The active theme and customized WooCommerce templates can still affect layout and accessibility, so test the complete Product, Cart, and Mini-Cart flows.

== Screenshots ==

1. The Lineweb Commerce Suite administration hub with feature navigation, placement status, settings, and merchant guidance.
2. Live WooCommerce product specifications followed by a delivery estimate based on a confirmed merchant profile.
3. Product Comparison showing three catalog products in a responsive table with a differences-only control.
4. Free Shipping Progress placed in Cart totals immediately before the checkout action.

== Support and Security ==

Support covers reproducible plugin defects on the WordPress, WooCommerce, and PHP versions declared above. Before reporting an issue, reproduce it with a default block or classic theme and rule out unrelated browser-console errors or unsupported template overrides.

Report suspected vulnerabilities privately through https://lineweb.gr/contact/ and include the plugin name, affected version, reproduction steps, and impact. Do not post credentials, customer data, or a production database export.

== Changelog ==

= 0.6.0 =

* Added Product Comparison for two to four catalog-visible products with batched WooCommerce reads and a differences-only view.
* Added variation-aware delivery and specification updates for classic and block product forms.
* Added merchant diagnostics for delivery confirmation, free shipping, duplicate protection, product types, and actual placements.

= 0.5.0 =

* Added a global delivery profile with working days, holidays, cutoff, stock, backorder, and virtual-product behavior.
* Paused automatic Delivery Estimate output until a merchant explicitly confirms the profile.
* Added global/local rule selection to manual Delivery Estimate blocks and WooCommerce currency formatting to editor previews.

= 0.4.1 =

* Replaced the mark-only graphic with the official full vertical LineWeb logo lockup.
* Centered the complete logo, status, footer branding, company copy, and links in a symmetrical dark composition.

= 0.4.0 =

* Added a branded Lineweb Commerce admin home inside WooCommerce with feature navigation, live automatic-placement status, settings links, merchant workflow guidance, and Lineweb contact details.
* Added a one-time welcome redirect after activation plus Explore suite and Settings actions on the Plugins screen.
* Added locally bundled Lineweb branding with no external admin request.

= 0.3.0 =

* Added default-on Delivery Estimate placement after the product add-to-cart area.
* Added default-on Free Shipping Progress placement before Cart checkout and inside Mini-Cart, including classic WooCommerce templates/widgets.
* Added WooCommerce settings toggles and duplicate protection for manually placed blocks.
* Used WordPress Block Hooks and official WooCommerce render/action boundaries without DOM injection.

= 0.2.0 =

* Added Product Specifications with live WooCommerce product fields and visible attributes.
* Added Delivery Estimate with stock, timezone, cutoff, business-day, weekend, and holiday handling.
* Added Free Shipping Progress with matching-zone auto detection, manual goals, Woo subtotal parity, and live cart updates.
* Added responsive styles, labelled interactive states, deterministic unit tests, and Gutenberg/frontend/cart E2E coverage.
* Hid Decision Room from the inserter while preserving existing saved content.

= 0.1.0 =

* Added the original Decision Room block.
