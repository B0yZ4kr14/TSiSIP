# TSiSIP Tutorials

## Getting Started

### 1. Install
```bash
git clone https://github.com/B0yZ4kr14/TSiSIP.git
cd TSiSIP
bash scripts/install.sh
```

### 2. Login
- Open http://localhost
- Username: admin
- Password: admin123

### 3. Explore
- Dashboard
- System Health
- Audit Log

## Adding a User

### 1. Go to Users
### 2. Click Add
### 3. Fill form
### 4. Save

## Changing Theme

### 1. Go to Profile
### 2. Click Theme
### 3. Select Dark
### 4. Save

## Adding Bookmark

### 1. Go to page
### 2. Click star
### 3. Verify in dashboard

## Exporting Data

### 1. Go to Audit Log
### 2. Click Export
### 3. Select format
### 4. Download

## API Usage

### 1. Login
```bash
curl -X POST http://localhost/login.php -d "username=admin&password=admin123" -c cookies.txt
```

### 2. Call API
```bash
curl -b cookies.txt http://localhost/health.php
```

## Backup

### 1. Run backup
```bash
bash scripts/backup-db.sh
```

### 2. Verify
```bash
ls backups/
```

## Monitoring

### 1. Run monitor
```bash
bash scripts/monitor.sh
```

### 2. Check logs
```bash
cat logs/alerts.log
```

## More

See docs for more tutorials.
