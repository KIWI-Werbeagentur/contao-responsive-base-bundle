# KIWI. Contao Responsive Base

**Provides basic configuration to implement responsive CSS frameworks into your Contao CMS.**

## Table of contents
- [Supported Frameworks](#frameworks)
- [Features](#features)
- [Breakpoints in the frontend](#frontend)

## Supported Frameworks <a name="frameworks"></a>

- Bootstrap 5.2 (via [KIWI. Contao Bootstrap Bundle](https://github.com/KIWI-Werbeagentur/contao-bootstrap-bundle))

### Disclaimer
This bundles itself does not implement a framework and is not meant to be used as-is. It is a starting point for your own responsive CSS framework.

See our [KIWI. Contao Bootstrap Bundle](https://github.com/KIWI-Werbeagentur/contao-bootstrap-bundle) as an example on how to implement a framework.

If you implement a new framework based on this bundle, we would be happy to add it to the list of supported frameworks.

## Features <a name="features"></a>

- pre-configuration for breakpoints
- responsive BE widgets:
  - responsive
  - optionalResponsive
  - responsiveSubpalette
  - optionalResponsiveSubpalette
- the configured breakpoints in the frontend, in CSS and JS

## Breakpoints in the frontend <a name="frontend"></a>

Every page gets the configured breakpoints inline, so no request is needed and they are available
right away.

### CSS custom properties

The properties of the active breakpoint are set on `<body>`, via media queries:

```css
--responsive-key: lg;
--responsive-breakpoint: 992;
--responsive-modifier: -lg;
--responsive-container: 960px;
```

### JS helper: `window.KiwiResponsive`

Available synchronously to every script on the page, based on `matchMedia`, so changes are
reported exactly when a breakpoint is crossed, without listening to `resize`.

```js
KiwiResponsive.current()      // "lg"
KiwiResponsive.config()       // {breakpoint: 992, modifier: "-lg", container: "960px"}
KiwiResponsive.up('lg')       // true from lg upwards (like media-breakpoint-up)
KiwiResponsive.down('lg')     // true below lg (like media-breakpoint-down)
KiwiResponsive.breakpoints    // the whole configuration

// called whenever the active breakpoint changes, returns a function to remove the callback
const off = KiwiResponsive.onChange(({key, prevKey}) => { /* ... */ })

// also called right away (prevKey: null), for code that has to run now and on every change
KiwiResponsive.onChange(({key}) => { /* ... */ }, {immediate: true})
```

Use it instead of hard-coded widths like `window.innerWidth > 992`, so the JS follows the
breakpoint configuration. For measurements, prefer measuring the actual element (e.g. the
container's `getBoundingClientRect()`): the container can be `100%`, and the gutters come from CSS.

### Deprecated (to be removed in 2.0)

- `responsive.js` with `window.responsiveSize`, `window.KiwiBootstrap` and the `responsiveUpdate`
  event: use `KiwiResponsive` (`current()`, `config()`, `onChange()`) and the CSS custom properties
- `window.arrBreakpoints`: use `KiwiResponsive.breakpoints`
- the `/responsive/breakpoints.js` route: the breakpoints are now output inline on every page,
  the script no longer needs to be loaded