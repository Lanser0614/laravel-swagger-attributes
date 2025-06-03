# Changelog

All notable changes to the Laravel Swagger Attributes package will be documented in this file.

## [v4.0.0] - 2025-06-03

### Added
- Full support for Laravel API Resources in response schema generation
- New `resource` property in `ApiSwaggerResponse` attribute to specify Laravel API Resource classes
- Automatic schema extraction from API Resource PHPDoc annotations and method analysis
- Resource-specific schema generation with merging capabilities for complex responses
- Support for response types (SINGLE, COLLECTION, PAGINATED) with API Resources
- Advanced type extraction from Resource class `toArray` method
- Improved error handling for resource schema generation with graceful fallbacks

### Fixed
- Various bugs and inconsistencies in schema generation
- Type safety issues in HTTP method handling
- Improved error handling throughout the codebase

### Changed
- Enhanced documentation with API Resource usage examples
- Updated code structure to better support future extensions

## [v3.0.0] - 2024-12-15

### Added
- Support for Laravel 11
- Improved validation error schema documentation
- New attribute for dedicated validation error responses

### Changed
- Updated minimum PHP requirement to 8.0
- Enhanced OpenAPI schema generation for database types

## [v2.0.0] - 2024-05-22

### Added
- Support for query parameters with repeatable attributes
- Enhanced support for PostgreSQL database types
- Improved command-line tool for route scanning

### Fixed
- Various issues with schema generation
- Path normalization bugs

## [v1.0.0] - 2023-10-18

### Added
- Initial release with PHP 8 Attributes for Swagger/OpenAPI documentation
- Support for Laravel Form Request validation rules
- Exception documentation with status codes
- Response schema generation from Eloquent models
- Basic Swagger UI integration
