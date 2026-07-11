# Architecture — Santai AI Growth OS

## Repository Structure

```text
santai-ai-growth-os/
├── plugin/
│   └── santai-marketing-os/
│       ├── assets/
│       ├── includes/
│       ├── templates/
│       ├── modules/
│       └── santai-marketing-os.php
├── docs/
├── tests/
├── scripts/
├── .github/
├── PROJECT_RULES.md
├── ROADMAP.md
├── ARCHITECTURE.md
├── CHANGELOG.md
├── README.md
└── composer.json
```

## Core Modules

### Products
Menyimpan product knowledge, media, USP, pain point, target audience dan bahan pemasaran.

### Campaigns
Mengurus kempen, platform, angle, CTA, media dan status penerbitan.

### AI Engine
Menghasilkan copywriting berdasarkan product knowledge dan brand voice.

### Connectors
Setiap platform mempunyai connector berasingan.

```text
modules/
├── facebook/
├── telegram/
├── wordpress/
├── woocommerce/
├── shopee/
├── lazada/
└── tiktok/
```

## Standard Connector Interface

Setiap connector mesti menyediakan:

```text
connect()
test_connection()
get_health()
publish()
schedule()
get_logs()
```

## Facebook Connector v2

```text
User Access Token
↓
GET /me/accounts
↓
Discover Pages
↓
Simpan Page ID + Page Access Token
↓
Campaign pilih Page
↓
Publish / Schedule
↓
Log Post ID dan status
```

## Data Security

Jangan commit:
- API keys
- access tokens
- app secrets
- passwords
- production credentials

Credential mesti berada dalam WordPress options atau environment configuration.
