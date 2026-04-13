# Deployment Guide - GlowUp Beauty

## Overview
This project is configured for automatic deployment to Vercel with CI/CD pipelines.

## Prerequisites
- GitHub account with repository access
- Vercel account (free tier available)
- Node.js 18+ installed locally

## Setup Instructions

### 1. Connect to Vercel
```bash
# Install Vercel CLI
npm install -g vercel

# Login to Vercel
vercel login

# Link project to Vercel
vercel link
```

### 2. Configure GitHub Secrets
Add these secrets to your GitHub repository (Settings → Secrets and variables → Actions):

- `VERCEL_TOKEN`: Your Vercel authentication token
  - Get from: https://vercel.com/account/tokens
  
- `VERCEL_ORG_ID`: Your Vercel organization ID
  - Get from: `vercel whoami` or Vercel dashboard
  
- `VERCEL_PROJECT_ID`: Your Vercel project ID
  - Get from: `vercel project ls` or Vercel dashboard

### 3. Set Environment Variables in Vercel
In Vercel dashboard, add these environment variables:

**Production:**
- `DB_HOST`: Your production database host
- `DB_USER`: Database username
- `DB_PASSWORD`: Database password
- `DB_NAME`: Database name
- `SITE_URL`: Your production domain

**Preview/Development:**
- Same as above but with development database credentials

## Deployment Workflows

### Automatic Deployments

#### 1. **Pull Request Preview** (Automatic)
- Triggered on: PR to `main` or `develop`
- Creates preview URL for testing
- Comment posted with deployment status

#### 2. **Development Deploy** (Automatic)
- Triggered on: Push to `develop` branch
- Deploys to staging environment
- URL: `https://glowup-staging.vercel.app`

#### 3. **Production Deploy** (Automatic)
- Triggered on: Push to `main` branch
- Deploys to production
- URL: Your custom domain

#### 4. **Release Deploy** (Manual/Tag)
- Triggered on: Git tag `v*.*.*` or manual workflow dispatch
- Creates GitHub release
- Deploys to production
- Example: `git tag v1.0.0 && git push origin v1.0.0`

### Manual Deployment

```bash
# Deploy to preview
vercel

# Deploy to production
vercel --prod
```

## Monitoring Deployments

### GitHub Actions
- View workflow runs: Repository → Actions tab
- Check logs for each deployment step
- Automatic notifications on success/failure

### Vercel Dashboard
- Monitor deployments: https://vercel.com/dashboard
- View analytics and performance metrics
- Check environment variables and settings

## Rollback

### Quick Rollback
1. Go to Vercel dashboard
2. Select your project
3. Go to "Deployments" tab
4. Click "Promote to Production" on previous deployment

### Via Git
```bash
# Revert to previous commit
git revert HEAD
git push origin main

# Or reset to specific commit
git reset --hard <commit-hash>
git push origin main --force
```

## Database Migrations

For database schema changes:

1. Create migration file in `migrations/` folder
2. Update `database-setup.sql` if needed
3. Run migrations before deployment:
   ```bash
   php setup-database.php
   ```

## Environment-Specific Configuration

### Development (.develop branch)
- Uses development database
- Debug mode enabled
- Preview URLs generated

### Production (main branch)
- Uses production database
- Debug mode disabled
- Custom domain configured
- SSL/TLS enabled automatically

## Troubleshooting

### Deployment Fails
1. Check GitHub Actions logs
2. Verify environment variables in Vercel
3. Ensure database credentials are correct
4. Check PHP syntax: `php -l filename.php`

### Database Connection Issues
1. Verify `DB_HOST`, `DB_USER`, `DB_PASSWORD` in Vercel
2. Check database firewall allows Vercel IPs
3. Ensure database user has proper permissions

### Preview URLs Not Working
1. Check Vercel deployment logs
2. Verify `vercel.json` configuration
3. Ensure all PHP files are syntactically correct

## Performance Tips

1. **Optimize Images**: Compress product images before upload
2. **Database Indexing**: Add indexes to frequently queried columns
3. **Caching**: Enable Vercel edge caching for static assets
4. **CDN**: Use Vercel's built-in CDN for global distribution

## Security Best Practices

1. Never commit `.env` files
2. Use GitHub Secrets for sensitive data
3. Rotate database passwords regularly
4. Enable two-factor authentication on Vercel
5. Review deployment logs for errors
6. Keep dependencies updated

## Support

- Vercel Docs: https://vercel.com/docs
- GitHub Actions: https://docs.github.com/en/actions
- PHP on Vercel: https://vercel.com/docs/functions/serverless-functions/runtimes/php

## Quick Commands

```bash
# View deployment status
vercel status

# Check environment variables
vercel env ls

# View logs
vercel logs

# Deploy with specific environment
vercel deploy --prod --env DB_HOST=production.db.com
```
