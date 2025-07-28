# Dashboard SCSS Architecture

Complete modular SCSS architecture for the Happy Place Housing agent dashboard, designed with consistency, scalability, and maintainability in mind.

## 📁 File Structure

```
/components/dashboard/
├── dashboard-main.scss           # Main import file - import this in your theme
├── _dashboard-variables.scss     # CSS custom properties & theme variables
├── _dashboard-mixins.scss        # Utility mixins & functions
├── _dashboard-layout.scss        # Grid system & layout foundation
├── _dashboard-navigation.scss    # Sidebar nav, breadcrumbs, user profiles
├── _dashboard-header.scss        # Page headers, tabs, search, actions
├── _dashboard-cards.scss         # Card components with Listing Swipe Card feel
├── _dashboard-forms.scss         # Form styling with validation
├── _dashboard-tables.scss        # Data tables with sorting & pagination
├── _dashboard-modals.scss        # Modals, drawers, popups
├── _dashboard-stats.scss         # Charts, graphs, metrics
├── _dashboard-utilities.scss     # Utility classes
├── _dashboard-themes.scss        # Theme variations
└── sections/
    ├── _dashboard-overview.scss   # Main dashboard page
    ├── _dashboard-listings.scss   # Listing management
    ├── _dashboard-marketing.scss  # Marketing tools & flyer generator
    ├── _dashboard-analytics.scss  # Analytics & reporting
    └── _dashboard-settings.scss   # Settings & configuration
```

## 🎨 Design System

### Color Scheme
- **Primary**: HPH Primary Blues (`--hph-primary-*`)
- **Secondary**: HPH Secondary colors for accents
- **Status**: Success (green), Warning (yellow), Danger (red)
- **Neutrals**: Gray scale with proper contrast ratios

### Typography
- **Font Family**: Poppins (Primary), System fonts (Fallback)
- **Scale**: Consistent type scale from xs (12px) to 3xl (32px)
- **Weight**: Light (300) to Bold (700)

### Spacing
- **8px Grid**: All spacing uses 8px increments
- **Variables**: `--dashboard-spacing-xs` through `--dashboard-spacing-2xl`

### Responsive Breakpoints
- **Mobile**: max-width 767px
- **Tablet**: 768px - 1199px  
- **Desktop**: 1200px+

## 🧩 Component System

### Core Components

#### Cards (`_dashboard-cards.scss`)
Based on Listing Swipe Card feel with hover effects:
```scss
.hph-dashboard-card {
  // Base card styling
  &--stat { /* Statistics display */ }
  &--compact { /* Reduced padding */ }
  &--highlighted { /* Primary accent */ }
  &--empty { /* Empty state */ }
}
```

#### Forms (`_dashboard-forms.scss`)
Comprehensive form styling with validation:
```scss
.hph-dashboard-form {
  .form-control { /* Input fields */ }
  .form-check { /* Checkboxes/radios */ }
  .hph-toggle { /* Toggle switches */ }
  .hph-input-group { /* Input groups */ }
}
```

#### Tables (`_dashboard-tables.scss`)
Data tables with sorting, filtering, and pagination:
```scss
.hph-dashboard-table {
  &--compact { /* Reduced spacing */ }
  &--striped { /* Alternating rows */ }
  &--borderless { /* Clean layout */ }
}
```

### Layout Components

#### Navigation (`_dashboard-navigation.scss`)
- Sidebar navigation with active states
- Breadcrumb navigation
- User profile components
- Mobile-responsive hamburger menu

#### Grid System (`_dashboard-layout.scss`)
```scss
.hph-dashboard-grid {
  &--2-col { /* 2 column layout */ }
  &--3-col { /* 3 column layout */ }
  &--4-col { /* 4 column layout */ }
}
```

### Section-Specific Styling

#### Overview Page (`sections/_dashboard-overview.scss`)
- Welcome section with quick stats
- Recent activity feed
- Performance charts
- Quick actions sidebar

#### Listings Page (`sections/_dashboard-listings.scss`)
- Grid and list view toggles
- Advanced filtering
- Bulk actions
- Status badges and quick actions

#### Marketing Page (`sections/_dashboard-marketing.scss`)
- Marketing tool cards
- Flyer generator interface
- Campaign management
- Performance metrics

## 🎭 Theme System

### Available Themes
- **Light** (default): Clean, bright interface
- **Dark**: Dark mode with proper contrast
- **High Contrast**: Accessibility-focused
- **Compact**: Reduced spacing for power users
- **Comfortable**: Increased spacing for accessibility
- **Seasonal**: Spring, Summer, Autumn, Winter variants

### Theme Usage
```html
<div data-theme="dark" class="hph-dashboard">
  <!-- Dashboard content -->
</div>
```

### Custom Themes
Create custom themes by overriding CSS custom properties:
```scss
[data-theme="custom"] {
  --hph-primary-600: #your-color;
  --dashboard-background: #your-bg;
  // ... other variables
}
```

## 🚀 Implementation Guide

### 1. Basic Setup
Import the main file in your theme's SCSS:
```scss
@import 'components/dashboard/dashboard-main';
```

### 2. HTML Structure
```html
<div class="hph-dashboard" data-theme="light">
  <div class="hph-dashboard-container">
    <nav class="hph-dashboard-sidebar">
      <!-- Navigation content -->
    </nav>
    <main class="hph-dashboard-main">
      <header class="hph-dashboard-header">
        <!-- Page header -->
      </header>
      <div class="hph-dashboard-content">
        <!-- Page content with cards -->
      </div>
    </main>
  </div>
</div>
```

### 3. Card Examples
```html
<!-- Basic Card -->
<div class="hph-dashboard-card">
  <div class="hph-dashboard-card-header">
    <h3 class="card-title">Card Title</h3>
  </div>
  <div class="hph-dashboard-card-body">
    <!-- Card content -->
  </div>
</div>

<!-- Stat Card -->
<div class="hph-dashboard-card hph-dashboard-card--stat">
  <div class="hph-stat-icon">
    <i class="icon-home"></i>
  </div>
  <div class="hph-stat-content">
    <div class="hph-stat-value">42</div>
    <div class="hph-stat-label">Active Listings</div>
  </div>
</div>
```

## 🔧 Utility Classes

### Spacing
```scss
.hph-m-lg    // margin: var(--dashboard-spacing-lg)
.hph-p-md    // padding: var(--dashboard-spacing-md)
.hph-mt-xl   // margin-top: var(--dashboard-spacing-xl)
```

### Display
```scss
.hph-flex           // display: flex
.hph-grid           // display: grid
.hph-hide-mobile    // hide on mobile
.hph-show-tablet    // show on tablet+
```

### Colors
```scss
.hph-text-primary   // Primary text color
.hph-bg-success     // Success background
.hph-border-danger  // Danger border
```

## 📱 Responsive Behavior

### Mobile-First Approach
All components are designed mobile-first with progressive enhancement:

```scss
// Mobile (default)
.component { /* mobile styles */ }

// Tablet and up
@include dashboard-tablet {
  .component { /* tablet styles */ }
}

// Desktop and up
@include dashboard-desktop {
  .component { /* desktop styles */ }
}
```

### Grid Responsiveness
- **Desktop**: Full grid layouts
- **Tablet**: Reduced columns, maintained spacing
- **Mobile**: Single column, optimized touch targets

## ♿ Accessibility Features

### Focus Management
- Visible focus rings on all interactive elements
- Keyboard navigation support
- Skip links for screen readers

### Color Contrast
- WCAG AA compliant color combinations
- High contrast theme available
- Status indicators with icons, not just color

### Screen Reader Support
- Proper ARIA labels and roles
- Semantic HTML structure
- Hidden text for context

## 🧪 Development Tips

### Custom Properties
Always use CSS custom properties for consistency:
```scss
// Good
color: var(--dashboard-text);

// Avoid
color: #333;
```

### Mixins Usage
Leverage provided mixins for common patterns:
```scss
// Responsive
@include dashboard-mobile { /* styles */ }

// Button states
@include dashboard-button-state(hph-primary);

// Card styling
@include dashboard-card;
```

### Component Extension
Extend base components for variations:
```scss
.custom-card {
  @extend .hph-dashboard-card;
  // Custom modifications
}
```

## 🐛 Troubleshooting

### Common Issues

1. **Variables not found**: Ensure `_dashboard-variables.scss` is imported first
2. **Mixins not available**: Import `_dashboard-mixins.scss` before using
3. **Responsive not working**: Check if breakpoint mixins are available
4. **Theme not applying**: Verify `data-theme` attribute on wrapper element

### Debug Mode
Add debug class to visualize layout:
```html
<div class="hph-dashboard debug">
  <!-- Shows grid lines and spacing -->
</div>
```

## 📈 Performance Considerations

### CSS Architecture
- Modular imports prevent unused code
- Utility classes reduce repeated declarations
- CSS custom properties enable runtime theming

### File Size
- Each file is ~5-15KB when compiled
- Import only needed sections for smaller builds
- Utility classes are generated on-demand

## 🔄 Maintenance

### Adding New Components
1. Create new `_component-name.scss` file
2. Import in `dashboard-main.scss`
3. Follow established naming conventions
4. Document component API

### Theme Updates
1. Update variables in `_dashboard-variables.scss`
2. Test across all theme variations
3. Verify accessibility compliance
4. Update documentation

## 📚 Related Documentation

- [Listing Swipe Card Component](../cards/listing-swipe-card.md)
- [Global Design System](../../abstracts/README.md)
- [Accessibility Guidelines](../../../docs/accessibility.md)
- [Component Library](../../../docs/components.md)

---

*This dashboard system is designed to grow with your needs while maintaining consistency and performance. For questions or contributions, please refer to the project's contribution guidelines.*
