# Putting Phola online

The same steps live in the admin under **Setup checklist**, where you can tick them off.

## 1. Database (cPanel → MySQL® Databases)

1. Create database `pholahealthsolut_database` and user `database_nexus` with the password you were given.
   cPanel may add your account prefix (e.g. `pholahealthsolut_database_nexus`) — if so, use the full
   names in `config/secrets.php`.
2. **Add User To Database** → ALL PRIVILEGES.
3. cPanel → **phpMyAdmin** → select the database → **Import** `database/database.sql`, then
   `database/seed.sql`. If you only imported `database.sql`, the site loads the starter catalogue,
   the admin account and the demo patient from `seed.sql` by itself on the first page view.

## 2. Code from GitHub

Repository: <https://github.com/bothamaria169-code/healthsolutions>

**Option A — cPanel Git™ Version Control (recommended)**

> **Push the code to GitHub before you clone it in cPanel.** Cloning an empty repository leaves cPanel
> with "No checked-out branch", the Deploy button greyed out, and a folder that blocks a second clone.
> If that happens: remove the repository in cPanel and clone into a new, empty folder name.

1. cPanel → **Git™ Version Control** → **Create**.
2. Clone URL `https://github.com/bothamaria169-code/healthsolutions.git`,
   repository path `/home/pholahealthsolut/repositories/healthsolutions`, name `healthsolutions`.
3. Open `.cpanel.yml` and check the cPanel username in `DEPLOYPATH` matches yours.
4. **Manage → Pull or Deploy → Deploy HEAD Commit.** Repeat **Update from Remote → Deploy HEAD
   Commit** after every change pushed to GitHub.

**Option B — automatic on every push (GitHub Actions)**

GitHub → repository → **Settings → Secrets and variables → Actions → New repository secret**:
`FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD` (cPanel → FTP Accounts; the account's folder must be
`public_html`). From then on every push to `main` is uploaded automatically. Until the secrets
exist the workflow does nothing, so pushing is always safe.

## 3. Secrets (once)

Upload `config/secrets.php` from the build computer to `public_html/config/` with File Manager.
It holds the database password, the `info@pholahealthsolutions.co.za` mailbox password (SMTP
`mail.pholahealthsolutions.co.za`, port 465) and the signing keys. It is not in GitHub and deploys never
overwrite it.

## 4. HTTPS

cPanel → **SSL/TLS Status** → **Run AutoSSL**. Once https works, uncomment the HSTS line in
`.htaccess`.

## 5. Cron (reminders + daily social post)

cPanel → **Cron Jobs** → once per five minutes:

```
/usr/local/bin/php /home/USER/public_html/scripts/cron.php >/dev/null 2>&1
```

No cron on your plan? Admin → **Reminders** shows a private URL to call every 5 minutes from a
free URL-cron service instead.

## 6. In the admin

1. Sign in at `/modules/auth/login.php?mode=staff` and change the admin password.
   Can't sign in? Add `'admin_password' => 'a-new-password',` to `public_html/config/secrets.php`,
   open the staff sign-in page once, and sign in with that password. That sets the `admin_email`
   account's password, and creates the account if it is missing. Delete the line again afterwards.
2. **Reminders → Send a test email** to confirm the mailbox works.
3. **AI & social media**: paste the Groq API key; create the Meta app and **Connect with Facebook**.
4. **Products**: check prices and photos. **Patients**: delete the demo patient.

## Updating later

Edit locally → `git add -A && git commit -m "…" && git push`. Then either it deploys by itself
(Option B) or press **Update from Remote → Deploy HEAD Commit** in cPanel (Option A).
Database changes are not deployed by git: import new `.sql` files in phpMyAdmin.
