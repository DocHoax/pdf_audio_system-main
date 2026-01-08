# Digital Ocean App Platform Deployment Guide

## Prerequisites

- Digital Ocean account
- GitHub repository (already set up ✅)

## Deployment Steps

### 1. Create App on Digital Ocean

1. Go to https://cloud.digitalocean.com/apps
2. Click **"Create App"**
3. Choose **GitHub** as source
4. Authorize Digital Ocean to access your GitHub
5. Select repository: `DocHoax/pdf_audio_system-main`
6. Select branch: `main`
7. Click **Next**

### 2. Configure Resources

Digital Ocean will detect the `.do/app.yaml` config file automatically.

**Web Service:**

- Name: `web`
- Environment: PHP 8.x
- Instance Type: Basic (512MB RAM, 1 vCPU) - ~$5/month
- HTTP Port: 8080

**Database:**

- Engine: MySQL 8
- Plan: Basic (1GB RAM, 10GB storage, 1 node) - ~$15/month
- Name: `db`

### 3. Set Environment Variables

In the Digital Ocean dashboard, add these environment variables:

```
# Database (auto-populated from managed database)
DB_HOST=${db.HOSTNAME}
DB_NAME=${db.DATABASE}
DB_USER=${db.USERNAME}
DB_PASS=${db.PASSWORD}
DB_PORT=${db.PORT}

# API Keys (add manually)
YARNGPT_API_KEY=your_actual_yarngpt_api_key
GOOGLE_CLIENT_ID=your_actual_google_client_id
GOOGLE_CLIENT_SECRET=your_actual_google_client_secret

# App Config
APP_ENV=production
GOOGLE_REDIRECT_URI=https://your-app-name.ondigitalocean.app/google-callback.php
```

### 4. Initialize Database

After deployment, you need to import the database schema.

**Option A: Using DO Console**

1. Go to your Database in DO dashboard
2. Click "Users & Databases" → "Create Database" → Name it `echodoc_db`
3. Use the connection details to connect via MySQL client
4. Import `database/setup.sql`

**Option B: Using Connection String**

```bash
# Get connection string from DO dashboard
mysql -h <hostname> -u <username> -p<password> -P <port> echodoc_db < database/setup.sql
```

### 5. Update Google OAuth Redirect URI

1. Go to https://console.cloud.google.com/
2. Navigate to your OAuth credentials
3. Add authorized redirect URI:
   ```
   https://your-app-name.ondigitalocean.app/google-callback.php
   ```

### 6. Deploy

Click **"Create Resources"** and Digital Ocean will:

- Build your app
- Create managed MySQL database
- Deploy to production
- Provide a URL like: `https://echodoc-pdf-audio-xxxxx.ondigitalocean.app`

### 7. Post-Deployment Checks

- [ ] Test file upload functionality
- [ ] Test PDF to audio conversion
- [ ] Test Google OAuth login
- [ ] Test MP3 download feature
- [ ] Check database connection
- [ ] Monitor logs for errors

## Updating Your App

Push changes to GitHub `main` branch:

```bash
git add .
git commit -m "Your changes"
git push origin main
```

Digital Ocean will automatically redeploy (if `deploy_on_push: true`).

## Cost Estimate

- **Web Service (Basic):** ~$5/month
- **Managed MySQL:** ~$15/month
- **Bandwidth:** Included (1TB free)
- **Total:** ~$20/month

## Troubleshooting

**Issue: Database connection fails**

- Check environment variables are set correctly
- Verify database is running
- Check firewall rules

**Issue: File uploads fail**

- Increase upload size limits in PHP settings
- Check disk space
- Verify `uploads/` directory permissions

**Issue: Google OAuth fails**

- Update redirect URI in Google Console
- Check `GOOGLE_REDIRECT_URI` environment variable
- Verify client ID and secret are correct

## Alternative: Use Droplet (~$6/month)

If you prefer more control, deploy on a DO Droplet:

1. Create Ubuntu Droplet
2. Install LAMP stack
3. Clone repository
4. Configure Apache/Nginx
5. Import database
6. Set up SSL with Let's Encrypt

See `DEPLOYMENT_DROPLET.md` for detailed guide.
