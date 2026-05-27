# TSiSIP Frontend Guide

## Stack

- HTML5
- CSS3 (Custom Properties)
- JavaScript (ES6+)
- PHP 8.2
- Apache

## Structure

```
web/
├── common/      # Shared PHP
│   ├── header.php
│   ├── footer.php
│   └── config.php
├── tsisip/      # Assets
│   ├── css/
│   ├── js/
│   └── assets/
└── *.php        # Pages
```

## CSS

### Custom Properties
```css
:root {
    --tsisip-primary-blue: #2D5A8E;
    --tsisip-bg-primary: #F4F6F8;
    --tsisip-text-primary: #1E2A3A;
}
```

### Dark Mode
```css
[data-theme="dark"] {
    --tsisip-bg-primary: #0A1628;
    --tsisip-text-primary: #E8ECF1;
}
```

### Responsive
```css
@media (max-width: 768px) {
    .tsisip-dashboard-grid {
        grid-template-columns: 1fr;
    }
}
```

## JavaScript

### Modules
- theme-toggle.js
- sse-client.js
- notifications.js
- dashboard-widgets.js
- tour.js
- shortcuts.js

### API
```javascript
// Notifications
TSiSIPNotify.success('Done!');
TSiSIPNotify.error('Failed!');

// SSE
TSiSIPEvents.on('data', (data) => {
    console.log(data);
});
```

## Components

### Cards
```html
<div class="tsisip-dashboard-card">
    <div class="tsisip-card-header">
        <h3>Title</h3>
    </div>
    <div class="tsisip-card-body">
        Content
    </div>
</div>
```

### Tables
```html
<table class="tsisip-table dataTable">
    <thead>...</thead>
    <tbody>...</tbody>
</table>
```

### Forms
```html
<div class="tsisip-form-group">
    <label class="tsisip-form-label">Label</label>
    <input class="tsisip-input" type="text">
</div>
```

## Theming

### Presets
- default
- ocean
- forest
- sunset

### Custom
Edit `web/tsisip/css/tsisip-theme.css`.

## Assets

### Manifest
```json
{
    "assets": {
        "css": {
            "tsisip-theme": "tsisip-theme.abc123.css"
        }
    }
}
```

### Helper
```php
tsisiçip_asset('css/tsisip-theme.css');
```

## Accessibility

- ARIA labels
- Keyboard navigation
- Screen reader support
- High contrast support
- Reduced motion support

## Performance

- Minified assets
- Lazy loading
- Cache headers
- Gzip compression
- CDN (future)

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
