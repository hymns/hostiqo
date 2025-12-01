# Cloudflare DNS Management Setup

Automatically create and manage DNS A records for your websites using Cloudflare API.

## Features

- **Auto DNS Record Creation**: Automatically creates DNS A records when you add a new website
- **Server IP Detection**: Automatically detects your server's public IP address
- **Manual Sync**: Manually sync/update DNS records anytime
- **Status Tracking**: View DNS status (pending, active, failed, none) in the dashboard
- **Error Handling**: Clear error messages when DNS operations fail
- **Auto Cleanup**: Automatically removes DNS records when websites are deleted

---

## Prerequisites

1. **Cloudflare Account** with your domain configured
2. **Cloudflare API Token** with DNS edit permissions

---

## Step 1: Get Cloudflare API Token

### Create API Token

1. Login to [Cloudflare Dashboard](https://dash.cloudflare.com/)
2. Go to **My Profile** → **API Tokens**
3. Click **Create Token**
4. Use the **Edit zone DNS** template or create custom token
5. Configure permissions:
   - **Zone** → **DNS** → **Edit**
   - **Zone Resources**: Include → Specific zone → Select your domain
6. Click **Continue to summary**
7. Click **Create Token**
8. **Copy the token** (you won't see it again!)

### Example Token Permissions

```
Permissions:
  Zone - DNS - Edit

Zone Resources:
  Include - Specific zone - yourdomain.com
```

---

## Step 2: Configure Application

### Add to `.env` file

```bash
# Cloudflare DNS Management
CLOUDFLARE_ENABLED=true
CLOUDFLARE_API_TOKEN=your_cloudflare_api_token_here
CLOUDFLARE_PROXIED=false
```

**Environment Variables:**

- **CLOUDFLARE_ENABLED**: Set to `true` to enable automatic DNS management
- **CLOUDFLARE_API_TOKEN**: Your Cloudflare API token with DNS edit permissions
- **CLOUDFLARE_PROXIED**: Set to `true` to enable Cloudflare proxy (orange cloud), `false` for DNS only

### Verify Configuration

```bash
# Test API token
curl -X GET "https://api.cloudflare.com/client/v4/user/tokens/verify" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## Step 3: How It Works

### Automatic DNS Creation

When you create a new website:

1. System detects server's public IP automatically
2. Finds Cloudflare zone ID for the domain
3. Creates DNS A record: `domain.com → server_ip`
4. Updates website DNS status to `active`

**Example Flow:**
```
Create Website: example.com
  ↓
Detect Server IP: 203.0.113.5
  ↓
Find Cloudflare Zone: example.com
  ↓
Create DNS A Record: example.com → 203.0.113.5
  ↓
Status: DNS Active ✓
```

### Manual DNS Sync

You can manually sync DNS records from the website detail page:

1. Go to **Websites** → Click on website
2. Click **Sync DNS** button
3. System will:
   - Update server IP if changed
   - Create record if doesn't exist
   - Update existing record if exists

---

## Step 4: Using DNS Management

### View DNS Status

**In Website List:**
- DNS column shows status badge (None, Pending, Active, Failed)
- Hover to see full status and IP address

**In Website Details:**
- DNS Status row shows current status
- Server IP address displayed when active
- Error messages shown if sync failed

### DNS Status Meanings

| Status | Description |
|--------|-------------|
| **None** | DNS not configured (Cloudflare disabled or not synced) |
| **Pending** | DNS record creation in progress |
| **Active** | DNS record successfully created and pointing to server |
| **Failed** | DNS operation failed (check error message) |

### Manual Operations

**Sync DNS Record:**
```
Websites → Select Website → Click "Sync DNS"
```

**Delete Website (Auto-removes DNS):**
```
Websites → Select Website → Delete
(DNS record automatically removed from Cloudflare)
```

---

## API Endpoints

The system provides these internal API endpoints:

```php
// Sync DNS record for website
POST /websites/{website}/dns-sync

// Remove DNS record
DELETE /websites/{website}/dns-remove

// Verify Cloudflare token
GET /cloudflare/verify-token

// Get server IP
GET /cloudflare/server-ip
```

---

## Troubleshooting

### DNS Status: Failed

**Common Issues:**

1. **"Cloudflare zone not found"**
   - Domain not added to your Cloudflare account
   - API token doesn't have access to the zone
   - Check domain spelling

2. **"Invalid API token"**
   - Token expired or revoked
   - Wrong token in `.env` file
   - Token missing DNS edit permission

3. **"Failed to detect server IP"**
   - Server has no internet connection
   - Firewall blocking outbound connections
   - All IP detection services are down

4. **"Record already exists"**
   - DNS record created manually in Cloudflare
   - Delete the record in Cloudflare and sync again

### Check Logs

```bash
# View Laravel logs
tail -f storage/logs/laravel.log | grep -i "dns\|cloudflare"
```

### Verify Token Manually

```bash
# Check if token is valid
php artisan tinker

>>> $cf = app(\App\Services\CloudflareService::class);
>>> $cf->verifyToken();
```

### Test Server IP Detection

```bash
# Check detected IP
curl https://api.ipify.org
curl https://icanhazip.com
```

---

## Advanced Configuration

### Use Cloudflare Proxy (Orange Cloud)

Enable Cloudflare's CDN and DDoS protection:

```bash
CLOUDFLARE_PROXIED=true
```

**Benefits:**
- Hide your server's real IP
- DDoS protection
- CDN caching
- SSL/TLS termination

**Note:** When proxied is enabled, DNS points to Cloudflare's IPs, not your server IP directly.

### Subdomain Support

The system supports both root domains and subdomains:

```
example.com       → Creates A record for example.com
www.example.com   → Creates A record for www.example.com
api.example.com   → Creates A record for api.example.com
```

**Note:** Ensure the root domain (e.g., `example.com`) exists in your Cloudflare account.

---

## Security Notes

### API Token Security

⚠️ **Never commit your API token to version control!**

- Store token only in `.env` file
- Add `.env` to `.gitignore` (already done in Laravel)
- Use environment-specific tokens (dev/staging/production)
- Rotate tokens periodically

### Least Privilege

✓ Use tokens with **minimum required permissions**:
- Only DNS Edit permission
- Scope to specific zones if possible
- Don't use Global API Key

### Token Storage

```bash
# Good: Environment variable
CLOUDFLARE_API_TOKEN=abc123...

# Bad: Hardcoded in code
$token = "abc123..."; // Never do this!
```

---

## Database Schema

New fields added to `websites` table:

```php
cloudflare_zone_id      // Cloudflare zone ID
cloudflare_record_id    // DNS record ID
server_ip               // Server's public IP address
dns_status              // Status: none, pending, active, failed
dns_error               // Error message if failed
dns_last_synced_at      // Last sync timestamp
```

---

## Disabling DNS Management

To disable automatic DNS management:

```bash
# In .env file
CLOUDFLARE_ENABLED=false
```

Existing DNS records in Cloudflare will remain unchanged. Websites will continue to work normally.

---

## Production Deployment

### Complete Setup Steps

1. **Get Cloudflare API token** (see Step 1)

2. **Add to production `.env`:**
   ```bash
   CLOUDFLARE_ENABLED=true
   CLOUDFLARE_API_TOKEN=your_production_token
   CLOUDFLARE_PROXIED=false
   ```

3. **Verify configuration:**
   ```bash
   php artisan tinker
   >>> config('services.cloudflare.enabled')
   => true
   >>> config('services.cloudflare.api_token')
   => "your_token..."
   ```

4. **Create test website** to verify DNS auto-creation

5. **Check logs** for any errors

---

## Example Workflow

### Scenario: Deploy New Website

1. **Add website in dashboard:**
   ```
   Name: My App
   Domain: myapp.com
   Type: PHP
   ```

2. **System automatically:**
   - Detects server IP: `203.0.113.5`
   - Finds Cloudflare zone for `myapp.com`
   - Creates DNS A record: `myapp.com → 203.0.113.5`
   - Creates Nginx config
   - Requests SSL certificate

3. **DNS propagates** (usually instant with Cloudflare)

4. **Website is live** at `https://myapp.com`

### All automated! 🎉

---

## Support

If you encounter issues:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Verify Cloudflare API token permissions
3. Test server connectivity
4. Check domain is added to Cloudflare account

For Cloudflare API documentation: https://api.cloudflare.com/
