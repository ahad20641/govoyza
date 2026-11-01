# Security and privacy checklist

We hold passports, bank statements and medical records. A leak is the biggest risk to the business.
Run this checklist at task 1.32 and before every phase launch.

## Data
- [ ] Passport number, DOB, bank details and similar use encrypted casts; `APP_KEY` backed up safely offline
- [ ] Documents on the private `documents` disk only; served via policy-checked controller with signed URLs (≤ 10 min)
- [ ] No document path or partner identifier in public HTML, emails, PDFs or JS bundles
- [ ] Retention job: archive/delete documents N months after completion (configurable, default 12)
- [ ] Daily encrypted off-server backups of DB + documents; restore tested once per phase

## Access
- [ ] 2FA mandatory for admin and partner users
- [ ] Admin domain optional IP allowlist (`GOVOYZA_ADMIN_IPS`)
- [ ] Partners see only jobs assigned to them; access revoked on job close (policy test)
- [ ] Customer phone/email never visible to partners (test)
- [ ] Least-privilege roles: Owner / Operations / Finance; Finance cannot edit orders
- [ ] Audit log for every admin and partner write

## App
- [ ] Rate limiting on login, OTP, track-application, uploads, webhooks
- [ ] Webhook signatures/IPN validation for every gateway; idempotency keys
- [ ] Upload validation: mime sniffing, size limits, no executable types, images re-encoded
- [ ] CSRF, secure + httpOnly + SameSite cookies, separate session cookie per subdomain
- [ ] Security headers: HSTS, CSP, X-Frame-Options, Referrer-Policy; `X-Robots-Tag: noindex` on partner/admin
- [ ] `APP_DEBUG=false` and no Telescope/Debugbar on production
- [ ] Dependencies: `composer audit` and `npm audit` in CI

## Infrastructure
- [ ] `.env` only on server, permissions 600; never committed
- [ ] SSH key auth only for deploy; key used by GitHub Actions has no other use
- [ ] Cloudflare WAF + DDoS in front (recommended)
- [ ] Sentry for errors, uptime monitor on `/up`
