# Team Development Workflow with Auto-Deploy

This guide covers best practices for team collaboration using the Hostiqo Manager with automatic deployments.

## Table of Contents
1. [Feature Branch Workflow (Recommended)](#feature-branch-workflow-recommended)
2. [Single Master Branch Workflow](#single-master-branch-workflow)
3. [Webhook Configuration](#webhook-configuration)
4. [Best Practices](#best-practices)
5. [Common Scenarios](#common-scenarios)

---

## Feature Branch Workflow (Recommended)

This is the **recommended approach** for team development with auto-deploy.

### Initial Setup

#### 1st Developer (Project Lead)
```bash
# Initial commit and deploy
git init
git add .
git commit -m "Initial commit"
git branch -M master
git remote add origin git@github.com:username/project.git
git push -u origin master
```

**Setup Webhook in Manager:**
- Branch: `master`
- Auto-deploy: Enabled
- Only `master` branch triggers deployment

#### 2nd Developer (Team Member)
```bash
# Clone the project
git clone git@github.com:username/project.git
cd project

# Create feature branch
git checkout -b feature/user-authentication

# Make changes
# ... edit files ...

# Commit and push to feature branch
git add .
git commit -m "Add user authentication"
git push origin feature/user-authentication
```

**Important:** Feature branch **TIDAK** trigger auto-deploy! ✅

### Development Cycle

#### While 2nd Dev Works on Feature Branch:

**1st Developer (continues on master):**
```bash
# Work on master branch
git checkout master

# Make changes
# ... edit files ...

# Commit and push
git add .
git commit -m "Fix homepage layout"
git push origin master
```
✅ **Auto-deploys to production**

**2nd Developer (on feature branch):**
```bash
# Stay on feature branch
git checkout feature/user-authentication

# Sync with latest master (important!)
git fetch origin
git rebase origin/master

# Continue development
# ... edit files ...

# Commit to feature branch
git add .
git commit -m "Complete authentication logic"
git push origin feature/user-authentication
```
❌ **Does NOT auto-deploy** (safe for development)

### Merging Feature to Master

#### Option A: Via Pull Request (Recommended)
```bash
# 2nd Developer creates Pull Request on GitHub/GitLab
# Title: "Feature: User Authentication"
# Base: master
# Compare: feature/user-authentication

# 1st Developer reviews the code
# Approve and merge via GitHub/GitLab UI
```
✅ **After merge, auto-deploys to production**

#### Option B: Manual Merge (Local)
```bash
# 1st Developer (or authorized team member)
git checkout master
git pull origin master
git merge feature/user-authentication
git push origin master
```
✅ **Auto-deploys to production**

### Cleanup
```bash
# Delete feature branch after merge
git branch -d feature/user-authentication
git push origin --delete feature/user-authentication
```

---

## Single Master Branch Workflow

⚠️ **Not Recommended for Production** - Use for solo development or small projects only.

### Setup
```bash
# All developers work directly on master
git clone git@github.com:username/project.git
cd project
```

### Development Pattern

**Developer 1:**
```bash
# Always pull first!
git pull origin master

# Make changes
# ... edit files ...

# Commit and push
git add .
git commit -m "Update feature X"
git push origin master
```
✅ **Auto-deploys immediately**

**Developer 2:**
```bash
# Always pull first to avoid conflicts!
git pull origin master

# Make changes
# ... edit files ...

# Commit and push
git add .
git commit -m "Update feature Y"
git push origin master
```
✅ **Auto-deploys immediately**

### ⚠️ Problems with This Approach:
1. **No Testing Before Production** - Every push goes live immediately
2. **Conflict Risk** - Multiple developers can cause merge conflicts
3. **No Code Review** - No PR review process
4. **Hard to Rollback** - Difficult to isolate which commit broke production
5. **Risky Deploys** - Untested code goes to production instantly

---

## Webhook Configuration

### Production Environment
```
Repository: git@github.com:username/project.git
Branch: master
Local Path: /var/www/project.com
Deploy User: www-data
Active: ✅ Yes

Pre-Deploy Script:
#!/bin/bash
php artisan down

Post-Deploy Script:
#!/bin/bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm install --production
npm run build
php artisan up
```

### Staging Environment (Optional but Recommended)
```
Repository: git@github.com:username/project.git
Branch: develop
Local Path: /var/www/staging.project.com
Deploy User: www-data
Active: ✅ Yes

Post-Deploy Script:
#!/bin/bash
composer install --optimize-autoloader
php artisan migrate --force
npm install
npm run build
```

**Workflow:**
- `develop` branch → Staging server (test here first)
- `master` branch → Production server (only after testing)

---

## Best Practices

### 1. Branch Strategy

#### ✅ Recommended: Git Flow
```
master          → Production (protected, auto-deploy)
develop         → Staging (integration, auto-deploy to staging)
feature/*       → Feature development (NO auto-deploy)
hotfix/*        → Emergency fixes (NO auto-deploy)
```

**Branch Protection Rules:**
- `master`: Require PR approval, no direct push
- `develop`: Allow direct push from team
- `feature/*`: Personal branches, no restrictions

### 2. Commit Messages

**Use Conventional Commits:**
```bash
feat: add user authentication
fix: resolve login redirect issue
docs: update API documentation
style: format code with prettier
refactor: simplify database queries
test: add unit tests for auth
chore: update dependencies
```

### 3. Development Workflow

```bash
# Step 1: Create feature branch
git checkout master
git pull origin master
git checkout -b feature/my-feature

# Step 2: Develop and commit (multiple commits OK)
git add .
git commit -m "feat: implement feature X"

# Step 3: Keep feature branch updated with master
git fetch origin
git rebase origin/master

# Step 4: Push feature branch
git push origin feature/my-feature

# Step 5: Create Pull Request
# - GitHub/GitLab UI
# - Request review from team

# Step 6: After approval and merge
# - Auto-deploys to production ✅
# - Delete feature branch
```

### 4. Code Review Checklist

Before merging to `master`:
- [ ] Code follows project conventions
- [ ] Tests pass (if applicable)
- [ ] No console.log / dd() / var_dump()
- [ ] Database migrations are reversible
- [ ] Environment variables documented
- [ ] README updated (if needed)
- [ ] No sensitive data in commits

### 5. Deployment Safety

#### Use Pre-Deploy Script for Maintenance
```bash
#!/bin/bash
# Put site in maintenance mode
php artisan down --retry=60
```

#### Use Post-Deploy Script for Updates
```bash
#!/bin/bash
# Install dependencies
composer install --no-dev --optimize-autoloader

# Run migrations (use --force for production)
php artisan migrate --force

# Clear and cache configs
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Build frontend assets
npm ci --production
npm run build

# Bring site back online
php artisan up
```

### 6. Environment-Specific Configuration

**Never commit `.env` file!**

```bash
# Add to .gitignore
.env
.env.backup
.env.production
```

**Document required variables in `.env.example`:**
```bash
APP_NAME="Project Name"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://project.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=project_db
DB_USERNAME=project_user
DB_PASSWORD=
```

### 7. Handling Merge Conflicts

```bash
# On feature branch, sync with master
git checkout feature/my-feature
git fetch origin
git rebase origin/master

# If conflicts occur
# 1. Fix conflicts in your editor
# 2. Mark as resolved
git add .
git rebase --continue

# Force push (feature branch only!)
git push origin feature/my-feature --force-with-lease
```

⚠️ **Never force push to `master` or `develop`!**

---

## Common Scenarios

### Scenario 1: Multiple Features in Progress

**Developer A:**
```bash
git checkout -b feature/payment-gateway
# ... work on payment integration
git push origin feature/payment-gateway
```

**Developer B:**
```bash
git checkout -b feature/email-notifications
# ... work on email system
git push origin feature/email-notifications
```

**Developer C:**
```bash
git checkout -b feature/admin-dashboard
# ... work on dashboard
git push origin feature/admin-dashboard
```

**Project Lead:**
```bash
# Review and merge features one by one
# Each merge triggers deployment
git checkout master
git merge feature/payment-gateway
git push origin master  # ✅ Deploy with payment only

# Later, after testing
git merge feature/email-notifications
git push origin master  # ✅ Deploy with payment + email

# Later, after testing
git merge feature/admin-dashboard
git push origin master  # ✅ Deploy all features
```

### Scenario 2: Hotfix in Production

```bash
# Create hotfix branch from master
git checkout master
git pull origin master
git checkout -b hotfix/critical-bug

# Fix the bug
# ... edit files ...

# Commit and push
git add .
git commit -m "fix: resolve critical payment bug"
git push origin hotfix/critical-bug

# Merge directly to master (emergency)
git checkout master
git merge hotfix/critical-bug
git push origin master  # ✅ Immediate deploy

# Also merge to develop if you have one
git checkout develop
git merge hotfix/critical-bug
git push origin develop
```

### Scenario 3: Rollback Failed Deployment

**Option 1: Revert Last Commit**
```bash
git revert HEAD
git push origin master  # ✅ Deploys previous working version
```

**Option 2: Reset to Previous Commit**
```bash
# Find working commit
git log --oneline

# Reset to that commit
git reset --hard abc1234
git push origin master --force  # ⚠️ Dangerous! Use carefully
```

**Option 3: Manual Deployment Trigger**
```bash
# In Hostiqo, click "Manual Deployment" button
# This re-deploys the current master branch
```

### Scenario 4: Testing Before Production

**Setup Staging Environment:**
1. Create `develop` branch
2. Create separate webhook for `develop` → staging server
3. Create separate webhook for `master` → production server

**Workflow:**
```bash
# Merge features to develop first
git checkout develop
git merge feature/new-feature
git push origin develop  # ✅ Auto-deploy to staging

# Test on staging: https://staging.project.com

# If OK, merge develop to master
git checkout master
git merge develop
git push origin master  # ✅ Auto-deploy to production
```

---

## Quick Reference

### Safe Commands (Always OK)
```bash
git pull origin master
git checkout -b feature/name
git add .
git commit -m "message"
git push origin feature/name
git rebase origin/master
```

### Dangerous Commands (Think Twice!)
```bash
git push --force              # Can overwrite others' work
git reset --hard             # Can lose uncommitted changes
git push origin master       # Triggers auto-deploy!
git merge feature/x          # On master, triggers deploy!
```

### Daily Developer Workflow
```bash
# Morning routine
git checkout master
git pull origin master
git checkout feature/my-feature
git rebase origin/master

# End of day
git add .
git commit -m "feat: progress on feature X"
git push origin feature/my-feature
```

### Team Lead Workflow
```bash
# Review pull requests on GitHub/GitLab
# Merge approved PRs
# Monitor deployments in Hostiqo
# Check deployment logs if issues occur
```

---

## SSH Key Management for Team

Each developer should have their own SSH key, but for webhook deployments, use a **dedicated deploy key**:

### Generate Deploy Key (Project Lead)
```bash
# In Hostiqo, use "Generate SSH Key" button
# This creates a unique SSH key for this webhook
```

### Add to GitHub/GitLab
1. Copy the public key from webhook details
2. Go to Repository Settings → Deploy Keys
3. Add the public key
4. ✅ Enable "Allow write access" if needed for git operations

**Benefits:**
- Separate keys for developers and deployments
- Revoke deploy key without affecting developers
- Audit trail for automated deployments

---

## Summary

### ✅ Recommended Setup
- **Production:** `master` branch with auto-deploy
- **Staging:** `develop` branch with auto-deploy (optional)
- **Development:** Feature branches (`feature/*`) without auto-deploy
- **Process:** PR → Review → Merge → Auto-deploy

### ⚠️ Not Recommended
- Single `master` branch for all development
- Direct commits to production branch
- No code review process
- Multiple developers on same feature branch

### 🎯 Key Principles
1. **Protection:** Protect `master` branch from direct pushes
2. **Review:** All changes go through PR review
3. **Testing:** Test on staging before production
4. **Isolation:** Each feature in separate branch
5. **Communication:** Document changes and notify team
6. **Rollback Plan:** Always know how to revert

---

**Need Help?**
- Check deployment logs in Hostiqo
- Review recent commits: `git log --oneline -10`
- Check current branch: `git branch`
- See uncommitted changes: `git status`
