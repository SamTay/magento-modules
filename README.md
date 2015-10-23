# Development Progress
Look in the issues for new features to build or bugs to squash.

# API Support
- US Address Validation
	- USPS
	- Fedex
- International Address Validation
	- StrikeIron
- US City & State Auto Completion
	- USPS

# Available Magento Areas
- Onepage Checkout
- Account Dashboard

# Configuration per Magento Area
- Presentation
    - Modal
    - Slide (replace form content)
- Auto-fill City/State Fields (US only)
- Skip Validation for Equivalent Addresses
	- Skips validation step if the only difference in validated address is capitalization.
- Display Errors
	- Displays error message in the case that no enabled APIs could verify address or make suggestions.
- Error Message
	- Customizable HTML message to display to user if Display Errors is enabled

# Phase 3
- Admin Area
    - Include AV content within a modal
    - Include button in shipping address form trigger validation modal
- Skip validation for saved addresses that have been previously verified by an API

# Phase 4
- Multi-shipping Checkout Area
- Gmaps API Support (visualization)
	- Map displaying location of address as entered, and also automatically readjusts based on selected suggestion.
    - Google Maps-driven autocomplete suggestions for individual address fields (with results prioritized by GeoIP results, configurable in admin)
- GeoIP lookup
- Fill in address with HTML5 location

