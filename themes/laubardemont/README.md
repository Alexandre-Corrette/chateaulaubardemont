# Hugo Starter Venue

A Hugo theme for wedding venues, event spaces, and luxury properties. Built with Tailwind CSS, includes a PHP contact form backend, Google reviews integration, hero sliders, photo galleries, and Schema.org structured data.

## Features

- Responsive design with Tailwind CSS 3.4 (stone/amber palette)
- Hero image slider with autoplay
- Photo gallery with lightbox
- Contact form with PHP backend (SMTP or native mail)
- Google Reviews integration
- Schema.org structured data (LocalBusiness, EventVenue, BreadcrumbList)
- SEO-ready with Open Graph and meta tags
- Honeypot + CSRF + rate limiting on contact form
- Optional BelEvent API integration for lead capture

## Requirements

- Hugo v0.116.0+
- Node.js (for Tailwind CSS build)
- PHP 8.0+ (for contact form backend)

## Installation

### As a Git submodule

```bash
git submodule add https://github.com/Alexandre-Corrette/hugo-starter-venue.git themes/starter-venue
```

### Manual

Clone or download this repo into your Hugo site's `themes/` directory.

## Tailwind CSS

```bash
cd themes/starter-venue
npm install
npm run dev    # Watch mode
npm run build  # Production build
```

## Configuration

Add these params to your `hugo.toml`:

```toml
theme = 'starter-venue'

[params]
  propertyName = "Your Venue Name"
  location = "Your Location"
  footerDescription = "A short description for the footer."
  heroImage = "images/hero.jpg"
  googleMapsUrl = "https://www.google.com/maps/place/Your+Venue"
  priceRange = "€€€"
  maximumAttendeeCapacity = 100
  ctaText = "Contact Us"
  ctaHref = "/contact/"
  logo = "/images/logo/logo.png"
  google_rating = "4.9"

[params.contact]
  address = "123 Street<br>City"
  phone = "01 23 45 67 89"
  email = "contact@yourvenue.com"

[params.social]
  instagram = "https://instagram.com/yourvenue"
  facebook = "https://facebook.com/yourvenue"

[params.address]
  street = "123 Street"
  city = "City"
  postalCode = "12345"
  region = "Region"
  country = "FR"

[params.geo]
  latitude = 48.8566
  longitude = 2.3522
```

### PHP Contact Form

Set environment variables (in `.env` or server config):

```env
CONTACT_TO=contact@yourvenue.com
MAIL_FROM=form@yourvenue.com
ALLOWED_HOSTS=yourvenue.com,www.yourvenue.com
MAILER_DSN=              # empty = native mail(), or smtp://host:port
BELEVENT_API_URL=        # optional
BELEVENT_API_KEY=        # optional
BELEVENT_VENUE_SLUG=     # optional
```

### Google Reviews

Place a `google_reviews.json` file in your `data/` directory with this structure:

```json
{
  "rating": 4.9,
  "total_reviews": 42,
  "reviews": [
    { "author": "Name", "rating": 5, "date": "2024-01-01", "text": "Review text" }
  ]
}
```

### Menus

Define menus in your `hugo.toml`:

```toml
[[menu.main]]
  name = "Home"
  url = "/"
  weight = 1

[[menu.footer]]
  name = "Contact"
  url = "/contact/"
  weight = 1
```

## Page Layouts

| Layout | File | Description |
|--------|------|-------------|
| Homepage | `home.html` | Hero slider, steps, info, reviews |
| Sections | `sections.html` | Multi-section pages with galleries |
| Contact | `contact.html` | Contact form + info |
| Gallery | `galerie.html` | Photo gallery with lightbox |
| FAQ | `faq.html` | FAQ page |
| History | `histoire.html` | Timeline/story page |
| Partners | `partenaires.html` | Partner listings |
| Legal | `mentions-legales.html` | Legal notices |

## Content Format

All content uses **TOML frontmatter** (`+++` delimiters). See the example site for frontmatter structure.

## License

MIT
