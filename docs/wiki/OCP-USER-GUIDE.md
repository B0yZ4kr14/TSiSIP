# TSiSIP Control Panel — User Guide

## Getting Started

### Login
1. Navigate to `https://tsiapp.io/login.php`
2. Enter your username and password
3. Click **Sign In**

### First Login
If your account requires a password change, you will be redirected to the change password page.

## Dashboard

The dashboard is your home screen with:
- **System Status**: Real-time component health
- **System Management**: Links to administrative tools
- **Bookmarks**: Quick access to your favorite pages
- **Feature 002 Tools**: Advanced monitoring tools

### Customizing the Dashboard
1. Click **Customize** in the top right
2. Check/uncheck widgets to show/hide
3. Click **Save**

## Navigation

### Desktop
Use the top navigation bar to access all pages.

### Mobile
Tap the hamburger menu (☰) to open navigation.

## Pages Reference

| Page | Role | Description |
|------|------|-------------|
| Dashboard | All | Main landing page |
| System Health | devops | Component status overview |
| Gateway Health | devops | Dispatcher targets status |
| Call Queue | devops | Active calls/dialogs |
| RTPengine Status | devops | Media relay statistics |
| Subscriber Stats | devops | Subscriber counts |
| Audit Log | All | Activity history |
| System Reports | devops | Analytics and trends |
| Search | All | Global search |
| Profile | All | User preferences |
| API Docs | devops | Integration endpoints |
| Memory Status | All | Real-time OpenSIPS memory usage |
| Processes | All | OpenSIPS worker process list |
| USRLoc Live | All | Live subscriber location table |
| Blacklists | All | Runtime blacklist inspection |
| Version | All | OpenSIPS version and loaded modules |
| NAT Helper | All | NAT keepalive socket status |
| Topology Hiding | All | Topology-hidden dialog mapping |
| Hash Tables | All | Runtime hash table inspection |
| AVP Inspector | All | AVP attribute inspection |
| Pike Monitor | devops | Rate limiting and blocked IPs |
| Rate Limit | devops | Per-pipe rate limiting status |
| TCP Connections | devops | TCP/TLS connection inspector |
| Timers | devops | Internal timer schedule |
| Presence | devops | Presence server watchers |

## Understanding MI Errors

Pages that fetch data from OpenSIPS may display colored warning banners when something goes wrong.

- **Yellow warning banners** mean an OpenSIPS module is not loaded or initialized. The data for that page is unavailable. Check `opensips.cfg` to verify the module is loaded and its parameters are correct.
- **Red error banners** mean a connection failure or permission issue. This usually indicates OpenSIPS MI HTTP is unreachable, the network is down, or your role does not have access. Verify the OpenSIPS container is running, the MI URL is correct, and your account has the required role.
- Each banner includes a contextual **hint** to guide resolution. Read the hint carefully before escalating to the DevOps team.

## Theme and Language

### Change Theme
1. Click the moon/sun icon in the header
2. Or go to **Profile** > Theme

### Change Language
1. Click the language code (EN/ES/PT) in the header
2. Or go to **Profile** > Language

## Bookmarks

### Add a Bookmark
1. Navigate to any page
2. Click the star (☆) in the header
3. The star fills (★) when bookmarked

### Remove a Bookmark
1. Click the filled star (★)
2. The bookmark is removed

## Real-time Updates

Pages with live data show a connection indicator:
- **Green**: Connected (SSE active)
- **Yellow**: Reconnecting
- **Red**: Error
- **Gray**: Disconnected

## Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| `/` | Focus search (future) |
| `?` | Show help (future) |
| `Esc` | Close modals |

## Troubleshooting

### Cannot Login
- Verify caps lock is off
- Check if account is locked (5 failed attempts)
- Contact an admin to unlock

### Page Not Loading
- Check connection indicator
- Refresh the page
- Clear browser cache

### Data Not Updating
- Check if SSE is connected
- Refresh manually
- Check OpenSIPS MI connectivity

## Security

- Always log out when finished
- Use strong passwords
- Report suspicious activity via audit log
- Do not share your account

## Support

For technical support, contact the DevOps team or check the API Documentation page.
