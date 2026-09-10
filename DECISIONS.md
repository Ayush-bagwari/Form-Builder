# Architectural Decisions & Technical Trade-offs

This document outlines key technical decisions, schema architecture, hybrid AI integration strategies, and Part D differentiator entry details for the AI Form Builder project.

---

## 1. Core Architecture Decisions

### Framework & Stack Choices
- **Laravel 11 & PHP 8.2+**: Provides clean MVC structure, robust database ORM (Eloquent), built-in authentication, queue infrastructure, and database migrations.
- **Livewire 3 & Alpine.js**: Enables real-time reactive UI components (Form Canvas, AI Modal, Document Import Modal, Notification Bell) without complex single-page app (SPA) overhead or state sync issues.
- **Tailwind CSS**: Delivers responsive, custom, high-contrast UI design across all screen sizes.
- **MySQL & SQLite**: MySQL for local and production relational persistence; SQLite in-memory for lightning-fast isolated automated testing.

### Schema Storage Format
- **Raw JSON Schema (`schema`)**: Stored as structured JSON containing `sections`, `fields`, `type`, `key`, `label`, `placeholder`, `validation`, and `options`.
- **Rationale**: Allows 100% dynamic form rendering, instant drag-and-drop manipulation via jQuery `formBuilder`, raw schema code editing, and effortless schema refinement via AI prompts.

---

## 2. Hybrid Document Import Strategy (Part C)

### Problem
Word (`.docx`) and Excel (`.xlsx`) documents contain varied structures (headings, tables, question lists). Relying purely on AI parser is expensive and slow; relying purely on regex heuristics fails on ambiguous questions.

### Implementation
- **Deterministic Pre-pass**:
  - `DocxParser`: Uses `PhpWord` to extract Headings ➔ Sections, Question lines ending in `?`/`:` ➔ Fields, and Bullet points ➔ Radio/Checkbox Options.
  - `XlsxParser`: Uses `PhpSpreadsheet` to extract structured table headers (`Section`, `Label`, `Type`, `Options`, `Required`).
- **AI Enrichment Pass**:
  - Runs a local heuristic check, then calls Google Gemini API (`gemini-1.5-flash`) only on ambiguous fields to infer optimal field types (`email`, `phone`, `date`, `file`, `rating`, `signature`).
- **Interactive Mapping UI**:
  - Displays detected sections, fields, dropdown type selectors, required toggles, and unparseable blocks with `+ Convert to Field`.

### Trade-offs Accepted
- Small AI latency (~1-2s) during import enrichment to guarantee high type accuracy.

---

## 3. Part D Entry — In-App Real-Time Notification Center

### User Problem
Form creators need immediate visibility into application events—such as new public submissions, AI generation completions, and document imports—without manually refreshing pages or checking external channels.

### Implementation
- **Database Notifications**: Uses Laravel database notifications table storing event payloads.
- **Livewire Bell Component (`<livewire:notification-bell />`)**:
  - Navbar bell icon (`🔔`) with a traditional glowing red dot badge (`🔴`).
  - Interactive dropdown menu displaying relative timestamps ("2 mins ago"), custom event icons (`✨`, `📥`, `📄`), action links, and mark-as-read toggles.
  - Real-time updates via Livewire `wire:poll.5s`.

### Trade-offs Accepted
- Used database notifications and Livewire polling (`wire:poll.5s`) instead of WebSockets (Pusher/Soketi) to maintain 100% free deployment compatibility on Render without requiring paid external WebSocket servers.

### What We'd Do With More Time
- Implement WebSocket broadcasting (`Reverb` / Pusher) for instant push notifications without polling overhead.
- Add browser push notifications (Service Worker Web Push API).

---

## 4. Production Deployment & Cloud Infrastructure

### AWS Cloud Architecture (Free Tier Optimized)
- **Compute (AWS EC2)**:
  - `t2.micro` / `t3.micro` instance running Docker and Nginx + PHP-FPM 8.2.
  - Configured with 2GB swap space to ensure memory safety during Docker asset compilation and dependency resolution within the 1GB physical RAM limit.
- **Relational Persistence (AWS RDS MySQL)**:
  - Managed MySQL instance (`db.t4g.micro` / `db.t3.micro`) within the 20GB Free Tier allowance.
  - Isolated database networking with security group restricted to the EC2 security group.
- **Object Storage (AWS S3)**:
  - High-durability file uploads and document imports via `league/flysystem-aws-s3-v3` (`Storage::disk('s3')`).
  - Offloads user assets from EC2 ephemeral filesystem directly to cloud object storage.
- **Background Worker & Queue Strategy**:
  - Supervisor daemon running inside the container: `php /var/www/html/artisan queue:work --sleep=3 --tries=3 --max-time=3600 --no-interaction`.
  - Dynamically switches between `database` queue (default on AWS Free Tier to avoid running paid ElastiCache Redis clusters) and `redis` when external Redis is provided.
- **Automated CI/CD Pipeline (GitHub Actions)**:
  - Automated quality gate running PHP 8.2 tests (`php artisan test`) and frontend compilation (`npm run build`) on every push and PR.
  - Automated deployment on push to `main` via SSH into EC2, pulling latest code, rebuilding Docker image, and restarting the container with zero downtime.
