# 🚀 AI Dynamic Form Builder & Management Platform

A modern, full-stack Laravel 11 + Livewire 3 + Alpine.js + Tailwind CSS application for building, editing, previewing, and analyzing dynamic interactive forms. Powered by an **AI Form Generation Engine (Google Gemini API & OpenAI)** with asynchronous queued processing.

---

## 🌟 Key Features

### 1. Phase 1 — Form Engine & Core Builder
- **Visual Drag & Drop Canvas**: Built with jQuery `formBuilder` integrated into Livewire state.
- **4-Step Wizard**: Details ➔ Visual Canvas / Raw JSON Editor ➔ Settings ➔ Publish & Share.
- **12+ Field Types**: Text, Email, Textarea, Number, Select, Radio, Checkbox, Date, File, Phone, Rating (Star Rating), and Digital Signature.
- **Public Form Endpoint**: `/f/{slug}` dynamically renders public forms, validates responses, enforces custom rate limits, and persists submissions.
- **Submissions Analytics**: Responses table, filter modals, response stats, and real-time CSV streaming export.

### 2. Phase 2 — AI Form Generation & Refinement (Part B)
- **Natural Language Creation**: Turn prompts like *"Internship application with education history, skills, and resume upload"* into complete, fully editable forms.
- **AI Form Editing (Refinement)**: Modify existing forms on the fly (*"Add emergency contact section"*, *"Make phone required"*, *"Translate labels to Hindi"*).
- **Asynchronous Non-Blocking Queued Jobs**: Long LLM calls run in the background via `GenerateAiFormJob` and `RefineAiFormJob`. Livewire dashboard polls progress in real-time (`wire:poll.3s`) without blocking HTTP requests.
- **Token & Latency Logging**: Persists LLM model name (`gemini-1.5-flash`), prompt tokens, completion tokens, total tokens, and latency (ms) in the `ai_generation_logs` table.

---

## 🧠 AI Prompt Strategy & Reliability System

### 1. System Prompt & Strict JSON Output Contract
The AI Form Generator ([AiFormGeneratorService.php](file:///c:/Users/user/Desktop/form-builder/app/Services/AiFormGeneratorService.php)) enforces a strict system prompt with JSON response format:

```json
{
  "title": "Clear Form Title",
  "description": "Form instructions...",
  "version": 1,
  "sections": [
    {
      "id": "sec_1",
      "title": "Section Name",
      "fields": [
        {
          "id": "field_1",
          "type": "text|email|textarea|number|select|radio|checkbox|file|date|phone|rating|signature",
          "key": "unique_snake_case_key",
          "label": "User Facing Label",
          "placeholder": "...",
          "required": true,
          "options": [{"label": "Option 1", "value": "option_1"}],
          "validation": {"max_length": 255}
        }
      ]
    }
  ]
}
```

### 2. JSON Repair Engine
To handle malformed or partial LLM JSON outputs gracefully:
- **Markdown Stripping**: Automatically strips triple-backtick markdown blocks (` ```json ... ``` `).
- **Trailing Comma Cleanup**: Removes trailing commas before closing braces/brackets (`,\s*}` or `,\s*]`).
- **Bracket Repair**: Gracefully handles missing outer braces before parsing.

### 3. Hallucinated Field Type Mapping
If the LLM hallucinates non-standard field types, `AiFormGeneratorService` automatically maps them:
- `dropdown` ➔ `select`
- `multiselect` / `checkboxes` ➔ `checkbox`
- `radio-group` / `radios` ➔ `radio`
- `tel` / `telephone` ➔ `phone`
- `star` / `starRating` ➔ `rating`
- `attach` / `upload` ➔ `file`
- `header` / `heading` ➔ `text`

### 4. Automatic Retries & Offline Fallback
- **Up to 3 Automated Retries**: If the LLM output fails schema validation, the service appends the error trace and retries the prompt with explicit correction guidance.
- **Offline Mock Generator**: If no `GEMINI_API_KEY` is set in `.env`, the service seamlessly falls back to an intelligent offline mock generator so developers and reviewers can evaluate the AI workflow offline without API charges.

---

## 🛠️ Configuration & Setup

1. **Clone & Install Dependencies**:
   ```bash
   composer install
   npm install
   ```

2. **Environment Setup**:
   Copy `.env.example` to `.env`:
   ```env
   GEMINI_API_KEY=your_gemini_api_key_here
   GEMINI_MODEL=gemini-1.5-flash
   ```

3. **Database & Migrations**:
   ```bash
   php artisan migrate
   ```

4. **Run Test Suite**:
   ```bash
   php vendor/bin/phpunit
   ```

5. **Run Local Server**:
   ```bash
   php artisan serve
   ```

---

## 📜 License
The project is open-source under the [MIT license](https://opensource.org/licenses/MIT).
