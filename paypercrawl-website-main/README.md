# 🚀 PayPerCrawl – AI Content Monetization Platform

[![Production Status](https://img.shields.io/badge/status-production-green?style=flat-square)]()
[![Next.js](https://img.shields.io/badge/Next.js-15-black?style=flat-square&logo=next.js)]()
[![Tailwind CSS](https://img.shields.io/badge/TailwindCSS-v4-38B2AC?style=flat-square&logo=tailwind-css)]()
[![WordPress Plugin](https://img.shields.io/badge/WordPress-Plugin-21759B?style=flat-square&logo=wordpress)]()

> **Turn AI bot traffic into revenue.**  
> PayPerCrawl empowers website owners and developers to monetize AI-driven crawler traffic through a powerful SaaS platform and WordPress integration.

![PayPerCrawl Demo Banner](docs/assets/banner.png)

---

## ✨ Features at a Glance

### 💼 For Site Owners
- **Bot Monetization Engine** – Earn from AI crawlers.
- **One-Click WordPress Integration** – Works instantly with CrawlGuard plugin.
- **Secure API Keys** – Manage, revoke, and validate access.

### ⚙️ For Developers
- **REST API** – Easy integration with external services.
- **Next.js 15 + TailwindCSS v4** – Modern, high-performance frontend.
- **Prisma + PostgreSQL** – Robust database for scalability.

### 🛡 For Administrators
- **Invite-Only Dashboard** – Restrict beta access with tokens.
- **Bulk User Management** – Approve, revoke, and monitor users.
- **Real-Time Status Tracking** – Know who’s in and who’s not.

---

## 🖼 UI Showcase

| Dashboard – API Keys | Theme Customizer | WordPress Plugin |
|----------------------|------------------|------------------|
| ![Dashboard Screenshot](docs/assets/dashboard.png) | ![Theme Customizer](docs/assets/theme.png) | ![WP Plugin](docs/assets/plugin.png) |

---

## 🛠 Tech Stack

| Category         | Technology |
|------------------|------------|
| **Frontend**     | Next.js 15, Tailwind CSS v4, ShadCN UI |
| **Backend**      | Next.js API Routes, Prisma ORM |
| **Database**     | PostgreSQL (Neon) |
| **Email Service**| Resend API + DKIM |
| **Plugin**       | WordPress (PHP, JS, CSS) |
| **Hosting**      | Vercel / Hostinger |

---

## ⚡ Quick Start

```bash
# 1️⃣ Clone the repository
git clone https://github.com/YOUR_USERNAME/paypercrawl-website.git
cd paypercrawl-website

# 2️⃣ Install dependencies
npm install

# 3️⃣ Set environment variables
cp .env.example .env   # then edit your credentials

# 4️⃣ Run in development mode
npm run dev
```

Visit [http://localhost:3000](http://localhost:3000)

---

## 🔑 Environment Variables

```env
DATABASE_URL=postgresql://user:pass@host/db
RESEND_API_KEY=your_resend_api_key
NEXT_PUBLIC_APP_URL=https://your-domain.com
ADMIN_API_KEY=secure_admin_key
NODE_ENV=production
```

---

## 🚀 Deployment

### **Vercel (Recommended)**
1. Import your GitHub repo into Vercel.
2. Add environment variables.
3. Deploy instantly.

### **Hostinger (Cost-Saving Alternative)**
- Full guide: [HOSTINGER_DEPLOYMENT_GUIDE.md](HOSTINGER_DEPLOYMENT_GUIDE.md)
- Deployment scripts: `deploy-hostinger.sh` / `.bat`

---

## 📡 API Reference (Core Endpoints)

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/apikeys/generate` | POST | Generate new API key |
| `/api/apikeys/validate` | POST | Validate API key |
| `/api/plugin/download` | GET | Download WordPress plugin |
| `/api/waitlist/join` | POST | Join waitlist |
| `/api/waitlist/invite` | POST | Send invitation |

Full API docs: [docs/API_REFERENCE.md](docs/API_REFERENCE.md)

---

## 🎨 Theme System

- **Modes:** Light, Dark, GitHub Dark Dimmed
- **Customization:** Live preview, color presets, import/export
- **Visual Enhancements:** Glass morphism, glow effects, micro-interactions

---

## 🧪 Testing & QA

- **Dashboard Protection:** Token-based middleware
- **Plugin Integration:** Validates API keys
- **Email Delivery:** Verified via Resend API + DKIM
- **Browser Compatibility:** Chrome, Firefox, Safari, Edge

Testing guides:  
- [PRODUCTION_TESTING_GUIDE.md](PRODUCTION_TESTING_GUIDE.md)  
- [DASHBOARD_PROTECTION_TEST.md](DASHBOARD_PROTECTION_TEST.md)

---

## 📜 License
MIT License – See [LICENSE](LICENSE) for details.

---

## 📞 Support
- **Documentation:** `/docs` folder
- **Issues:** [GitHub Issues](../../issues)
- **Email:** admin@paypercrawl.tech

---

## ✅ Project Status
**Production Ready** – Fully functional with optional persistence and analytics enhancements available.

> 💡 *PayPerCrawl is designed to scale with your audience — from small blogs to enterprise-level AI traffic management.*
