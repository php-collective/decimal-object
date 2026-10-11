---
layout: home
hero:
  name: Decimal Object
  text: Exact decimal arithmetic for PHP
  tagline: An immutable value object on top of bcmath. No custom extension, no float rounding error.
  image:
    src: /logo.svg
    alt: Decimal Object logo
  actions:
    - theme: brand
      text: Getting started
      link: /guide/getting-started
    - theme: alt
      text: Installation
      link: /guide/installation
    - theme: alt
      text: API reference
      link: /reference/api
features:
  - title: Exact results
    details: Values are stored and calculated as decimal strings, so 0.1 plus 0.2 equals 0.3.
  - title: Immutable
    details: Every operation returns a new object. A value you passed around cannot change behind your back.
  - title: Keeps your scale
    details: Trailing zeros are kept until you trim them. 19.90 stays 19.90.
  - title: Readable API
    details: add(), subtract(), greaterThan(), isZero(). No abbreviations to look up.
  - title: Any size
    details: Works with numbers far beyond the integer and float range of PHP.
  - title: Framework integrations
    details: Use it as the decimal column type in the CakePHP ORM through the cakephp-decimal plugin.
---
