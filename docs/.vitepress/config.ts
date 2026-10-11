import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'Decimal Object',
  description: 'Immutable decimal value object for PHP, built on bcmath.',
  base: '/decimal-object/',
  srcExclude: ['README.md'],
  head: [['link', { rel: 'icon', type: 'image/svg+xml', href: '/decimal-object/favicon.svg' }]],
  themeConfig: {
    logo: '/logo.svg',
    nav: [
      { text: 'Installation', link: '/guide/installation' },
      { text: 'Getting started', link: '/guide/getting-started' },
      { text: 'API', link: '/reference/api' },
      {
        text: 'Ecosystem',
        items: [
          { text: 'Integrations', link: '/reference/integrations' },
          { text: 'CakePHP Decimal', link: 'https://dereuromark.github.io/cakephp-decimal/' },
          { text: 'Packagist', link: 'https://packagist.org/packages/php-collective/decimal-object' },
        ],
      },
    ],
    sidebar: [
      {
        text: 'Guide',
        items: [
          { text: 'Installation', link: '/guide/installation' },
          { text: 'Getting started', link: '/guide/getting-started' },
          { text: 'Creating decimals', link: '/guide/creating' },
          { text: 'Arithmetic', link: '/guide/arithmetic' },
          { text: 'Precision and scale', link: '/guide/scale' },
          { text: 'Rounding', link: '/guide/rounding' },
          { text: 'Comparison and checks', link: '/guide/comparison' },
          { text: 'Conversion', link: '/guide/conversion' },
        ],
      },
      {
        text: 'Reference',
        items: [
          { text: 'API', link: '/reference/api' },
          { text: 'Integrations', link: '/reference/integrations' },
          { text: 'Alternatives', link: '/reference/alternatives' },
          { text: 'Upgrading', link: '/reference/upgrading' },
        ],
      },
    ],
    search: { provider: 'local' },
    socialLinks: [{ icon: 'github', link: 'https://github.com/php-collective/decimal-object' }],
    editLink: { pattern: 'https://github.com/php-collective/decimal-object/edit/master/docs/:path' },
    footer: { message: 'Released under the MIT License.', copyright: 'Copyright The PHP Collective' },
  },
})
