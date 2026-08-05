# Changelog

All notable changes to Lineweb Commerce Suite are documented here.

## 0.6.0 — 2026-08-05

- Added manual Product Comparison for two to four catalog-visible products using one batched WooCommerce query, semantic responsive markup, product links, and differences-only filtering.
- Added variation-state updates to Delivery Estimate and Product Specifications for classic and block product forms.
- Added read-only diagnostics for profile confirmation, free-shipping rules, duplicate protection, unsupported product types, and actual placement locations.
- Added namespaced classic variation stock/backorder/virtual state, block-event reset behavior, and virtual measurement suppression.
- Ensured virtual Delivery Estimate blocks are visually hidden even when their block layout uses flex.

## 0.5.0 — 2026-08-05

- Added a global delivery profile for ranges, cutoff, ISO working days, holidays, backorder behavior, and stock/virtual suppression.
- Made automatic delivery fail closed until a merchant explicitly confirms the profile.
- Added global/local profile selection for manual blocks and store-currency editor previews.

## 0.4.1 — 2026-08-05

- Replaced the mark-only graphic with the official full vertical LineWeb logo lockup from the LineWeb brand source.
- Centered the complete logo, status, footer branding, company copy, and links in a symmetrical dark composition at desktop and mobile widths.

## 0.4.0 — 2026-08-05

- Added a branded WooCommerce → Lineweb Commerce administration home with live placement state, feature anchors, merchant workflow guidance, and direct destinations for settings, products, pages, and Cart review.
- Added a one-time welcome redirect after ordinary activation, plus Explore suite and Settings links on the Plugins screen.
- Bundled Lineweb branding and the scoped responsive administration stylesheet locally; the page makes no external asset request.

## 0.3.0 — 2026-08-05

- Added default-on automatic Delivery Estimate placement after the single-product add-to-cart area in modern block templates and classic WooCommerce templates.
- Added default-on automatic Free Shipping Progress placement before the official Cart checkout action, between Mini-Cart items and actions, and in classic cart/Mini-Cart widget surfaces, with a Cart-wrapper fallback for custom templates.
- Added WooCommerce-native settings toggles under Products → Lineweb Commerce, plus manual-block detection and Block Hooks removal support to prevent unwanted duplicates.
- Used the WordPress Block Hooks API, the Cart block render boundary, and documented WooCommerce actions instead of DOM selectors or copied templates.
- Extended the local-only demo seeder with a $200 WooCommerce free-shipping method and a reviewable storefront.

## 0.2.0 — 2026-08-05

- Added Product Specifications using WooCommerce-owned SKU, availability, weight, dimensions, and visible product attributes.
- Added Delivery Estimate with product-context resolution, stock/backorder handling, WordPress timezone, cutoff hour, bounded business-day range, weekend skipping, and ISO holiday exclusions.
- Added Free Shipping Progress with a manual threshold or matching-zone WooCommerce free-shipping detection, coupon requirement handling, displayed-subtotal/discount parity, namespaced Cart API `ExtendSchema` data for live zone changes, server-rendered fallback, and refreshes after block/classic cart events.
- Added Clean/Compact, Card/Inline, and Bar/Minimal styles with semantic definitions, labelled progress state, live announcements, reduced motion, and responsive mobile layouts.
- Added seven delivery/shipping unit tests and two real editor-to-frontend Playwright journeys, including guest Store API cart changes without reload.
- Hid Decision Room from the block inserter while retaining its runtime registration for saved-content compatibility.

## 0.1.0 — 2026-08-05

- Added the independent `lineweb-commerce` WooCommerce plugin and dependency metadata.
- Added Decision Room with real Woo product selection, live price/stock/image/link rendering, transparent weighted scoring, visible evidence, scenarios, strongest fit, and trade-offs.
- Added matching PHP and JavaScript score calculation, bounded server input, escaped dynamic output, local-only visitor interaction, Laboratory/Paper styles, accessibility behavior, and responsive container layouts.
- Declared HPOS and Cart/Checkout block compatibility without changing either system.
- Added seven scoring unit tests, one full editor-to-frontend Playwright journey, Plugin Check/release tooling, threat model, competitive product rationale, and idempotent local demo tools.
