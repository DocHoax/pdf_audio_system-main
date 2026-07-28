# Upsun deployment guide

The deployment manifests live in [.platform.app.yaml](.platform.app.yaml), [.platform/routes.yaml](.platform/routes.yaml), and [.platform/services.yaml](.platform/services.yaml).

## 1. Prepare environment variables

Set these in your Upsun project:

- `APP_ENV=production`
- `APP_URL=https://your-domain.com`
- `YARNGPT_API_KEY=your_real_key`
- `ADMIN_EMAIL=you@example.com`

## 2. Deploy the app

Push the repository to your Upsun-connected Git remote and trigger a deployment.

## 3. Initialize the database

After the first deploy, connect to the MariaDB service and import the SQL files in the database folder:

```bash
mysql -h <host> -u <user> -p <database> < database/setup.sql
mysql -h <host> -u <user> -p <database> < database/stats_migration.sql
```

## 4. Verify the install

- Open the site URL.
- Confirm uploads work and files appear in the persistent storage mount.
- Check that the homepage loads and the API endpoints respond.
