# Plan: Mobile Responsive Design

## CSS Strategy

- CSS Grid + Flexbox with responsive breakpoints
- Mobile-first media queries
- Hamburger menu for sidebar on < 768px

## Breakpoints

- 320px: Phone portrait
- 768px: Tablet portrait
- 1024px: Desktop

## Components

- `web/tsisip/css/tsisip-theme.css` — Responsive rules
- `web/common/header.php` — Mobile hamburger toggle
- `web/common/role-nav.php` — Collapsible sidebar

## Testing

- `tests/integration/test-ocp-mobile-responsive.sh`
