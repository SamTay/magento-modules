# Development Progress
Look in the issues for new features to build or bugs to squash.

# API Support
- USPS for address validation
- Fedex for address validation

# My Account
- [Still needs integration - see issues]

# Onepage Checkout
- Using a modal (Option A)
	- Modal pops up after user clicks continue on normal address entry step
	- Modal displays suggestions
	- Modal provides option to continue to next checkout step OR reject suggestions (configurable in admin)
- Insert address validation form within existing checkout step (Option B)
	- Content slides in after user clicks continue on normal address entry step (regardless of correctness)
	- Step displays suggestions
	- User can continue to next checkout step OR reject suggestions (configurable in admin)

# Admin
- Include AV content within a modal
- Include button in shipping address form trigger validation modal
	- Order create
	- Customer account, add address
	- Edit shipping address in shipping creation

# Additional Configuration Options
- Presentation options: modal or onepage checkout step
- Disable state/city (as API supports using zip-only)
- Skip validation step depending on equivalence of request address and API output address
- Skip validation for saved addresses that have been previously verified by an API
- Error verbosity
- Custom error message

# "Nice to haves" for Phase 1
- GeoIP lookup (for pre-selecting state?)
- Fill in address with HTML5 location

# Phase 2 Additions
- Multi-address checkout
- UPS API Support (validation)
- Gmaps API Support (visualization)
	- Map displaying location of address as entered, and also automatically readjusts based on selected suggestion.
- Google Maps-driven autocomplete suggestions for individual address fields (with results prioritized by GeoIP results, configurable in admin)
