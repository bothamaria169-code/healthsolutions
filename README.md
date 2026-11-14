# Phola — health products, WhatsApp ordering and a patient health tracker

> `pholahealthsolutions.co.za` · built on the FairCart storefront (same PHP stack, design system and admin)

Customers browse health products and supplements with clear prices and **order on WhatsApp**
(+27 63 043 7385). After a sale, staff set the customer up as a **patient**: they sign in with their
**cellphone number and a 4-digit PIN**, get **email reminders at every dose time**, tick doses off,
log readings, and **report side effects**, which staff review and answer from the admin.

---

## Stack

PHP 8.1+ and MySQL (PDO), vanilla JS, Bootstrap 5.3, Font Awesome 6.5, Chart.js via CDN.
No Composer, no build step. PHPMailer is bundled in `lib/`.

## Local setup (XAMPP)

```bash
mysql -u root -e "CREATE DATABASE pholahealthsolut_database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root --default-character-set=utf8mb4 pholahealthsolut_database < database/database.sql
mysql -u root --default-character-set=utf8mb4 pholahealthsolut_database < database/seed.sql
cp config/secrets.example.php config/secrets.php   # then fill it in
```

Browse to `http://localhost/pholahealthsolutions/`.

| Account | Sign in with |
|---|---|
| Admin (staff) | `info@pholahealthsolutions.co.za` — password in `ADMIN-LOGIN.txt` on the build computer (not in git) |
| Demo patient | cellphone `082 000 0001`, PIN `1234` — **delete before launch** |

## Secrets — never in git

This repository is **public**. Database, mailbox and signing secrets live in `config/secrets.php`,
which is git-ignored. Upload it to the server once with cPanel File Manager; deploys never touch it.
`config/secrets.example.php` lists every key.

## What is where

```
index.php, shop.php, modules/shop/   storefront (FairCart, rebranded)
enquire.php                          logs an enquiry, then opens WhatsApp with the order typed in
cart.php                             the "enquiry list" — several products in one WhatsApp message
dose.php                             the "I took it" link in reminder emails (signed, confirm-to-log)
classes/Health.php                   patients: PIN sign-in, schedules, adherence, reminders, side effects
classes/Groq.php                     AI writing: product copy, captions, side-effect summaries
classes/SocialPoster.php             Facebook Page + Instagram posting through Meta's Graph API
dashboards/customer/                 the patient portal: today, medication, side effects, readings
dashboards/admin/                    admin: setup checklist, enquiries, patients, reminders, AI & social,
                                     plus FairCart's products, categories, banners, articles, newsletter
scripts/cron.php                     every 5 minutes: dose + refill reminders, daily social post
.cpanel.yml, .github/workflows/      deployment (see DEPLOY.md)
```

## Health products — a standing rule

Supplements here support health; they do not treat, cure or prevent disease, and they never replace
prescribed medication (ARVs, insulin, blood pressure tablets). The product pages, footer, terms, FAQ,
chat assistant and every AI prompt say so. Keep it that way when editing copy.

## Security notes

- PINs are bcrypt-hashed; five wrong PINs lock the account for 15 minutes (counted in the database,
  not the session). Unknown numbers and wrong PINs get the same answer and take the same time.
- Reminder links are HMAC-signed, expire after three days, and only log a dose on a POST.
- Staff notes and health data are only shown to the patient and admins. Deleting a patient deletes
  their medication, doses, reports and readings (POPIA).
- `config/`, `classes/`, `includes/`, `lib/`, `database/` and `scripts/` (except `cron.php`, which
  needs the cron key) are 404'd by `.htaccess`.

## Customer reviews, testimonials and news videos

The homepage has a customer-review section and a video-news section, each linking to a dedicated
page. Use these pages to check the public display:

- `testimonials.php` — review images, testimonial videos and published quotes.
- `news.php` — news and update videos.
- `stories.php` — longer customer stories and testimonial video links.
- `index.php` — homepage previews and links to the dedicated pages.

The Reviews and News links are also included in the site navigation, footer and sitemap.

### Upload a testimonial from Admin

1. Sign in to the admin dashboard and open **Testimonials & patient stories**.
2. Add a testimonial or edit an existing one. Enter a customer name, then upload one review image
   or MP4 video. A quote is optional when media is attached.
3. Optionally add a longer story or a YouTube, Facebook or TikTok video link.
4. Choose **Live** to publish it. Drafts remain hidden from public pages.

The upload control accepts JPEG, PNG and WebP images (up to 8 MB) and MP4 videos (up to 50 MB).
Uploaded testimonials are stored under `uploads/testimonials/`; admins can preview and remove
uploaded media while editing. Only publish customer material with their permission.

### Add media manually

Media can also be added directly to the site folders using the hosting file manager or FTP:

| Media | Folder | Formats | Display |
|---|---|---|---|
| Review images | `assets/img/testimonials/` | `.jpg`, `.jpeg`, `.png`, `.webp` | Up to three on the homepage; all on Testimonials |
| Testimonial videos | `assets/video/` | `.mp4` | Homepage preview and Testimonials page |
| News videos | `assets/video/news/` | `.mp4` | Homepage preview and News & updates page |

Use descriptive video filenames; the page derives the displayed title from the filename. The
website scans these folders automatically, so manually added files do not need database entries.
See `REVIEWS-NEWS-MEDIA-GUIDE.txt` for the short upload checklist.

### Deployment notes

Deploy the feature files and media folders together. The testimonial screenshot database column is
added automatically for an existing database by `includes/features.php`; it is also included in
`database/database.sql` for new installations. Do not overwrite the live `config/secrets.php`
during deployment.

For admin video uploads, configure PHP so `upload_max_filesize` is at least `50M` and
`post_max_size` is greater than `50M`. After deployment, test an image and MP4 upload as an admin,
then verify the homepage, Testimonials page and News & updates page.
