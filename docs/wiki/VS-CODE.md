# VS Code Setup

## Extensions

### Recommended
- PHP Intelephense
- Prettier
- ESLint
- Docker

### Optional
- GitLens
- Markdown All in One
- Rainbow CSV

## Settings

```json
{
    "editor.tabSize": 4,
    "editor.rulers": [120],
    "files.trimTrailingWhitespace": true
}
```

## Debugging

### Xdebug
1. Install Xdebug in container
2. Configure path mappings
3. Start debugging session

## Tasks

### Build
```json
{
    "label": "Build",
    "type": "shell",
    "command": "make build"
}
```

### Test
```json
{
    "label": "Test",
    "type": "shell",
    "command": "make test"
}
```

## Snippets

### PHP
```json
{
    "TSiSIP Page": {
        "prefix": "tsisip-page",
        "body": [
            "<?php",
            "require_once __DIR__ . '/common/config.php';",
            "requireAuth();",
            "require_once __DIR__ . '/common/header.php';",
            "?>",
            "<div id=\"content\" class=\"tsisip-dashboard\">",
            "    <h1><?php echo _('$1'); ?></h1>",
            "</div>",
            "<?php require_once __DIR__ . '/common/footer.php'; ?>"
        ]
    }
}
```

## Shortcuts

- `Ctrl+Shift+B`: Build
- `Ctrl+Shift+T`: Test
- `F5`: Debug
