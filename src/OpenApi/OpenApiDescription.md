# Introduction

The v4 API is a multi-capable web API based on the [API Platform](https://api-platform.com/) framework, built to support the future generation of the [Goteo](https://goteo.org) crowdfunding platform, aiming to be it's new underlying engine.

v4 exposes a REST API with predictable and resource-oriented URLs, accepts request bodies (payloads) encoded in multiple open standard formats such as JSON, including [JSON-LD](https://json-ld.org/) and [Hydra](https://www.hydra-cg.com/), returns responses encoded in the same formats, and uses standard HTTP verbs and status codes.

This API is documented in the OpenApi initiative [Specification v3.1.0](https://spec.openapis.org/oas/v3.1.0), which allows v4 to be easily understood by API development suites, documentation generators and SDK generators for the convenience of developers and API users. Most of the spec is generated automatically as changes are introduced, but some additional content such as this introduction and other sections might not be updated as regularly.

<span class="hl-yellow">
This API is still in early development and is not set to have backward compatibility with the Goteo API v1 (https://api.goteo.org/v1/). Major changes are to be expected.
</span>

# Authentication

The v4 API uses [OAuth 2.0](https://oauth.net/2/) to authenticate requests from consumer applications.

Auth clients are issued on a case-by-case basis. You may reach out to us in order to obtain client credentials to initiate auth flows.

The following paths are available for authentication:
- **`POST /oauth/token`** to obtain Access Tokens
- **`GET /oauth/authorize`** to initiate *Authorization Code Grant* flows

Please notice these paths do not have the `/v4` path prefix.

# Localization

The v4 API accepts localization of content. Resources such as Projects can have owner-submitted data (title, description, etc) in different languages, the extent of which is subject to the owner. Resources with localized content versions will expose a `locales` property listing the available localizations.

Retrieval of content in different locales is performed via standard HTTP [content negotiation](https://developer.mozilla.org/en-US/docs/Web/HTTP/Content_negotiation). When the request supplies an `Accept-Language` header, the API will retrieve localized versions of the content where available, falling back to the default locale of the API instance, and finally to the first available localization of the content regardless of the request preferences or the API defaults if it cannot find suitable localizations.

Submission of localized content is performed over standard REST content submission with the addition of a `Content-Language` header to a POST, PUT or PATCH request. Only the first tag will be used to determine the submitted localization. When a localizable resource is created with a non-localized request (a request without a `Content-Language` header), the default locale of the API instance is used.

Removal of localized content is performed over standard REST content deletion with the addition of a `Content-Language` header to a DELETE request. A delete request for localized content will act partially and remove only the requested localizations, not the entire resource. To remove the resource you can send a non-localized request. Unlike on submission v4 will process all language tags included in the header and remove every matching localization.

Localizations are assumed to be one per language globally. Language tags will be parsed to match to an [ISO 639](https://en.wikipedia.org/wiki/List_of_ISO_639_language_codes) two-letter languade code, e.g: a value of "en_US, en_GB" will be converted to "en", ignoring the "US" and "GB" subtags.
