# Database Management Permissions

## Required MySQL Privileges

For the Database Management feature to work, your MySQL user needs **GLOBAL** privileges (not just database-specific privileges).

> **Note:** The system automatically tests your MySQL permissions and caches the result for 10 minutes. If you grant new permissions, click the "Recheck" button (🔄) to force a retest.

### Required Privileges:
- ✅ **CREATE** (on `*.*`) - To create new databases
- ✅ **CREATE USER** - To create new MySQL users
- ✅ **GRANT OPTION** - To grant privileges to created users

## How to Grant Permissions

### Option 1: Grant ALL PRIVILEGES (Full Access)
```sql
-- Login as root user
mysql -u root -p

-- Grant all privileges (includes CREATE, CREATE USER, GRANT OPTION)
GRANT ALL PRIVILEGES ON *.* TO 'webhook'@'localhost' WITH GRANT OPTION;
FLUSH PRIVILEGES;
```

### Option 2: Grant Specific Privileges (Recommended)
```sql
-- Login as root user
mysql -u root -p

-- Grant only the required privileges
GRANT CREATE, CREATE USER ON *.* TO 'webhook'@'localhost' WITH GRANT OPTION;
FLUSH PRIVILEGES;
```

## Verify Your Permissions

Check current user's grants:
```sql
SHOW GRANTS FOR 'webhook'@'localhost';
```

Expected output should include:
```
GRANT CREATE, CREATE USER ON *.* TO `webhook`@`localhost` WITH GRANT OPTION
```

Or:
```
GRANT ALL PRIVILEGES ON *.* TO `webhook`@`localhost` WITH GRANT OPTION
```

## Why These Permissions Are Required

1. **CREATE (on \*.\*)**: 
   - Needed to create new databases
   - Must be GLOBAL privilege, not database-specific
   
2. **CREATE USER**:
   - Needed to create new MySQL users
   - Always a global privilege
   
3. **GRANT OPTION**:
   - Needed to grant privileges to newly created users
   - Allows the application to set up users with access to their databases

## Security Note

If you're concerned about security, you can:
- Create a separate MySQL admin user specifically for this application
- Grant only the minimum required privileges shown above
- Do NOT grant DROP, DELETE, or other destructive privileges unless needed

## Troubleshooting

### Error: "Access denied for user 'webhook'@'localhost' to database..."
This means your user doesn't have CREATE privilege on `*.*` (global level). 

**Solution**: Grant the privileges using the commands above.

### User has CREATE but only on specific database
If you see something like:
```
GRANT CREATE ON `webhook`.* TO 'webhook'@'localhost'
```

This is NOT sufficient. You need:
```
GRANT CREATE ON *.* TO 'webhook'@'localhost'
```

Note the `*.*` instead of `webhook.*` - this gives global CREATE privilege.
