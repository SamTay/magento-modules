# Development Progress
- Backend
  - If the only difference between original address and validated address is capitalization, move on to shipping method step automatically. Derp.
  - Add a ``verified`` attribute to shipping addresses so that if a customer picks a saved address that has already been validated, they automatically skip validation and proceed to shipping method step.
  - Modify the city/state system configuration to also allow auto ajax population (requires new usps api wrapper for Zipcode Lookup Tool)
- Frontend
	- Extend ``AddressValidation`` to ``MSAddressValidation`` (for multishipping)
- Bugs to squash
  - Navigating OP Checkout after the validated addresses form is presented, without hitting the "go back" button, will cause duplicated validated addresses forms because they are not deleted on any action other than "go back" clicks.

# API Support
- USPS for address validation
- Gmaps for visualization

# My Account
- [ToDo: determine if we need validation here at all]
- [ToDo: determine if we need validation for billing addresses]
- "SMEs": Archs, Thomas, Greg, Grady?

# Frontend Checkout Process
- Using a modal (Option A)
	- Modal pops up after user clicks continue on normal address entry step (regardless of correctness)
	- Modal displays suggestions and map displaying location of address as entered, and also automatically readjusts based on selected suggestion.
	- Modal provides option to continue to next checkout step OR reject suggestions (configurable in admin)
	- If user enters billing address and selects "Use As Shipping Address", trigger validation on the billing step (with warning about AVS vs. shipping address correctness), as normal shipping step will be skipped.
- Insert address validation form/map within existing checkout step (Option B)
	- Content slides in after user clicks continue on normal address entry step (regardless of correctness)
	- Step displays suggestions and map displaying location of address as entered, and also automatically readjusts based on selected suggestion.
	- User can continue to next checkout step OR reject suggestions (configurable in admin)
	- If user enters billing address and selects "Use As Shipping Address", trigger validation on the billing step (with warning about AVS vs. shipping address correctness), as normal shipping step will be skipped.

# Admin Integration
- Include AV content from Frontend (Option A or B) within a modal
- Include button in shipping address form trigger validation modal
	- Order create
	- Customer account, add address
	- Edit shipping address in shipping creation

# Multi-address Checkout
- [ToDo: Use time developing Frontend portion to finalize requirements/design for multi-address in Phase 2!]

# Additional Configuration Options
- Disable state/city (as API supports using zip-only)

# "Nice to haves" for Phase 1
- GeoIP lookup (for pre-selecting state?)
- Fill in address with HTML5 location

# Phase 2 Additions
- Multi-address checkout
- UPS/FedEx API Support
- Google Maps-driven autocomplete suggestions for individual address fields (with results prioritized by GeoIP results, configurable in admin)


ToDo:
1. Sam create repo and begin work on API integration
2. Rob to talk to not-SMEs about My Account, billing validation, and Option A vs. B
3. 
