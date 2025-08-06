# Happy Place Handbook Component Library

## Core Design System

### Colors
```css
--hph-color-primary: #18587A;
--hph-color-primary-light: #4D92B6;
--hph-color-primary-dark: #163E58;
--hph-color-secondary: #F3DD6D;
--hph-color-secondary-light: #F9EDB1;
--hph-color-secondary-dark: #E5C732;
--hph-color-accent: #DA7A4A;
```

### Typography
```css
--hph-font-primary: 'Poppins', -apple-system, system-ui, sans-serif;
--hph-font-size-xs: 0.75rem;    /* 12px */
--hph-font-size-sm: 0.875rem;   /* 14px */
--hph-font-size-base: 1rem;     /* 16px */
--hph-font-size-lg: 1.125rem;   /* 18px */
--hph-font-size-xl: 1.25rem;    /* 20px */
--hph-font-size-2xl: 1.5rem;    /* 24px */
--hph-font-size-3xl: 1.875rem;  /* 30px */
--hph-font-size-4xl: 2.25rem;   /* 36px */
```

## Layout Components

### Header
The main template header with flexible content alignment.

```html
<header class="hph-template-header">
    <div class="hph-template-header-content">
        <h1 class="hph-template-title">Page Title</h1>
        <!-- Additional header content -->
    </div>
</header>
```

### Split View
Two-column layout for listings and map views.

```html
<div class="hph-split-view">
    <aside class="hph-sidebar">
        <!-- Sidebar content -->
    </aside>
    <main class="hph-main-content">
        <!-- Main content -->
    </main>
</div>
```

## Property Components

### Property Hero
Full-width hero section with image and overlay.

```html
<div class="hph-listing-hero">
    <img src="hero.jpg" class="hph-listing-hero-image" alt="Property">
    <div class="hph-listing-hero-overlay">
        <div class="hph-listing-hero-price">$1,250,000</div>
        <div class="hph-listing-hero-address">123 Main Street</div>
        <div class="hph-listing-hero-stats">
            <span>4 beds</span>
            <span>3 baths</span>
            <span>2,500 sq ft</span>
        </div>
    </div>
</div>
```

### Property Gallery
Grid-based photo gallery with modal viewer.

```html
<div class="hph-gallery">
    <div class="hph-gallery-grid">
        <div class="hph-gallery-item hph-gallery-main">
            <img src="main.jpg" alt="Main View">
        </div>
        <div class="hph-gallery-item">
            <img src="photo2.jpg" alt="Interior">
        </div>
        <!-- Additional gallery items -->
    </div>
</div>

<!-- Gallery Modal -->
<div class="hph-gallery-modal">
    <div class="hph-gallery-modal-content">
        <img class="hph-gallery-modal-image" src="" alt="">
        <button class="hph-gallery-nav hph-gallery-prev">
            <i class="fas fa-chevron-left"></i>
        </button>
        <button class="hph-gallery-nav hph-gallery-next">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>
</div>
```

### Property Features
Grid layout for property features and amenities.

```html
<div class="hph-features-grid">
    <div class="hph-feature-item">
        <div class="hph-feature-icon">
            <i class="fas fa-bed"></i>
        </div>
        <div class="hph-feature-content">
            <div class="hph-feature-label">Bedrooms</div>
            <div class="hph-feature-value">4</div>
        </div>
    </div>
    <!-- Additional features -->
</div>
```

### Property Card
Compact property card for listing grids.

```html
<div class="hph-listing-grid">
    <div class="hph-listing-card">
        <div class="hph-listing-image">
            <img src="property.jpg" alt="Property">
        </div>
        <div class="hph-listing-content">
            <div class="hph-listing-price">$500,000</div>
            <div class="hph-listing-address">123 Main St</div>
            <div class="hph-listing-details">
                <span class="hph-listing-detail">3 beds</span>
                <span class="hph-listing-detail">2 baths</span>
            </div>
        </div>
    </div>
</div>
```

## Filter Components

### Filter Section
Container for filter groups.

```html
<div class="hph-filter-section">
    <h3 class="hph-filter-title">Filter Title</h3>
    <!-- Filter content -->
</div>
```

### Filter Chips
Interactive filter buttons.

```html
<div class="hph-filter-chips">
    <button class="hph-filter-chip">Any</button>
    <button class="hph-filter-chip active">2+</button>
    <button class="hph-filter-chip">3+</button>
</div>
```

### Price Range Input
Price input fields with currency formatting.

```html
<div class="hph-price-range">
    <div class="hph-price-input">
        <input type="text" placeholder="Min">
    </div>
    <span class="price-separator">to</span>
    <div class="hph-price-input">
        <input type="text" placeholder="Max">
    </div>
</div>
```

## Map Components

### Map Container
Container for Google Maps with controls.

```html
<div class="hph-map-container">
    <div id="map"></div>
    <div class="hph-map-overlay">
        <button class="map-button active">Map</button>
        <button class="map-button">Satellite</button>
    </div>
</div>
```

## Description Components

### Property Description
Clean, readable property descriptions.

```html
<div class="hph-property-description">
    <div class="hph-description-content">
        <p>Detailed property description...</p>
    </div>
</div>
```

## Best Practices

1. Always use the `hph-` prefix for our custom classes
2. Maintain consistent spacing using CSS variables
3. Use semantic HTML elements
4. Include proper ARIA attributes for accessibility
5. Follow BEM-like naming for modifiers (e.g., `active`, `primary`)
6. Use responsive design patterns
7. Maintain consistent typography scale

## Responsive Breakpoints

- Desktop: 1024px and above
- Tablet: 768px to 1023px
- Mobile: Below 768px

## Class Consolidation Notes

1. Combined similar padding/margin patterns into shared utility classes
2. Standardized transition timings using CSS variables
3. Unified border-radius and shadow values
4. Consolidated color values into semantic variables
5. Standardized spacing units across components

## JavaScript Integration

For components requiring JavaScript (gallery, filters, etc.), always:

1. Use data attributes for JS hooks (e.g., `data-gallery-item`)
2. Maintain separation of concerns
3. Use event delegation where appropriate
4. Include proper error handling
5. Support keyboard navigation
6. Ensure proper touch support for mobile

## Accessibility Features

All components include:

1. Proper ARIA labels
2. Keyboard navigation support
3. Focus management
4. Screen reader compatibility
5. Sufficient color contrast
6. Touch target sizes

## Performance Considerations

1. Use CSS Grid and Flexbox for layouts
2. Optimize images with proper sizing and formats
3. Minimize DOM depth
4. Use CSS transitions instead of JavaScript when possible
5. Implement lazy loading for images
6. Use efficient selectors
