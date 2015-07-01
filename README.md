# Development Progress
- Backend
  - Add a ``verified`` attribute to shipping addresses so that if a customer picks a saved address that has already been validated, they automatically skip validation and proceed to shipping method step.
  - Create a block to render the form for selecting an address (same for modal or checkout step) and include the html in the ajax response.
  - Cherry-pick the previous ``allowance`` system configuration field and source model, but adapt it to be a setting for strictness level in terms of when to display errors/warnings to the customer about unverified addresses, instead of the original, bad idea of restricting checkout.
- Frontend
	- Wrap the ``Billing.save`` method to check for ``'use_for_shipping'`` flag. Most likely, the easiest way to handle this is to uncheck this flag here and
		- If it was flagged: save billing normally, and manually match the shipping fields and submit the shipping address form, triggering the address validation
		- If it was not flagged: return parent billing save with normal execution.
	- Finish building base ``AddressValidation`` class with modal methods
	- Conditionally modify checkout depending on the ``presentation`` config value
		- This will likely mean declaring dependency on *MageJsConfig* and using modman
	- Extend ``AddressValidation`` to ``MSAddressValidation`` (for multishipping)
	- Unobtrusively display the Zipcode 5 + 4 without modifying shipping form
	

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
