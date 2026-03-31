# SleepApneaBD — Bangladesh's First Sleep Lab

A complete, modern static website for **SleepApneaBD**, Bangladesh's pioneering sleep apnea clinic founded in 2005 by Prof. Dr. AKM Mosharraf Hossain.

🌐 **Live:** [sleepapneabd.com](https://sleepapneabd.com)

---

## Pages

| File | Description |
|------|-------------|
| `index.html` | Homepage — hero, stats, how it works, warning signs, doctor profile, testimonials, map |
| `sleep-apnea-assessment.html` | STOP-BANG risk assessment questionnaire (8 questions, scored result) |
| `articles.html` | Articles & resources with category filtering |
| `contact.html` | Contact form, clinic info, Google Maps embed |

---

## Features

- **Dark / light theme** toggle (persisted in `localStorage`)
- **STOP-BANG questionnaire** — interactive 8-question sleep apnea risk screener with Low / Moderate / High risk scoring
- **Article filter** — JS-powered category filtering with smooth animation
- **Contact form** — client-side validation + animated Bengali+English thank-you message
- **Animated background orbs** — CSS keyframe floating gradient orbs
- **Scroll-triggered fade-up** animations via `IntersectionObserver`
- **Animated stat counters** — count-up on scroll into view
- **Sticky navbar** with blur backdrop + mobile hamburger menu
- **Floating WhatsApp button** with pulse animation
- **Fully responsive** — mobile, tablet, and desktop layouts
- **Bilingual** — Bengali (UTF-8) + English throughout
- **SEO meta tags** and Open Graph tags on every page
- **Google Fonts** (Inter) via preconnect + link
- **No external CSS/JS** — all styles and scripts inline per page

---

## Design System

| Token | Dark | Light |
|-------|------|-------|
| Background | `#0a0e1a` | `#f8fafc` |
| Card | `rgba(255,255,255,0.05)` + blur | `rgba(255,255,255,0.7)` + blur |
| Text primary | `#f1f5f9` | `#0f172a` |
| Text secondary | `#94a3b8` | `#475569` |
| Accent gradient | `#06d6a0 → #3b82f6` | same |

---

## About the Clinic

SleepApneaBD was established in 2005 as **Bangladesh's first dedicated sleep laboratory**. Led by Prof. Dr. AKM Mosharraf Hossain, the clinic specialises in:

- Obstructive Sleep Apnea (OSA) diagnosis & treatment
- CPAP / BiPAP therapy setup and follow-up
- Snoring treatment
- Insomnia and other sleep disorders
- Polysomnography (overnight sleep studies)

Over 5,000 patients have been treated since founding.

---

## Deployment

This is a static site hosted via GitHub Pages with the custom domain `sleepapneabd.com` (configured via `CNAME`).

```
index.html
sleep-apnea-assessment.html
articles.html
contact.html
CNAME
README.md
```

---

© 2025 SleepApneaBD. All rights reserved.
