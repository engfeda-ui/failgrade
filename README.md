# 🛑 Moodle Quiz Access Rule: Fail Grade (`quizaccess_failgrade`)

[![Moodle Compatibility](https://img.shields.io/badge/Moodle-4.0%20to%205.0%2B-orange.svg?style=flat-square)](https://moodle.org)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%20%7C%208.2%20%7C%208.3-blue.svg?style=flat-square)](https://php.net)
[![Database](https://img.shields.io/badge/Database-PostgreSQL%20%7C%20MySQL%20%7C%20MariaDB-purple.svg?style=flat-square)](https://docs.moodle.org)
[![License](https://img.shields.io/badge/License-GPL%20v3-green.svg?style=flat-square)](http://www.gnu.org/copyleft/gpl.html)

An essential Moodle quiz access rule plugin designed to enforce mastery-based learning. This plugin prevents students from starting new quiz attempts once they have proven their competency, encouraging them to focus on other areas once mastery is achieved.

It supports dual-mode locking: traditional **Grade-Based** locking and a brand new, highly customizable **Competency-Based** locking mode.

---

## ✨ Features

- **Dual-Mode Mastery Locking:**
  - **Grade-Based Mode:** Restricts students from making additional attempts once they reach or exceed the quiz's **"Grade to pass"**.
  - **Competency-Based Mode (NEW):** Prevents students from starting new attempts once they achieve mastery in all Moodle competencies mapped to the quiz.
- **Custom Per-Quiz Competency Thresholds (NEW):** Allows configuring a custom passing threshold (e.g., `60%`) for each individual quiz. If set to `0`, it falls back to the global report threshold.
- **Real-Time Competency Progress Table (NEW):** Automatically renders a clean, responsive HTML table for students on the quiz landing page. It lists each mapped competency, the required threshold, their current score, and a colorful status badge (**Passed** / **Needs Improvement**).
- **Native Moodle Integration:** Leverages Moodle’s built-in quickform API and database layers without requiring complex system dependencies.
- **Enterprise Standards:**
  - **Privacy Subsystem (GDPR):** Full compliance with Moodle's privacy requirements.
  - **Reliable Backup & Restore:** Integrates with Moodle's core course backup/restore pipeline.
- **Developer-Grade Quality:**
  - Standardized PHPUnit test coverage for verifying attempt locking logic.
  - Fully compliant with Moodle's strict `PHP_CodeSniffer` (Codechecker) specifications.

---

## 📋 Requirements

| Dependency | Required Version / Compatibility |
| :--- | :--- |
| **Moodle Framework** | Moodle 4.0 to 5.0+ (Tested against Moodle 4.5/5.0+ stable branches) |
| **PHP Runtime** | PHP 8.1, PHP 8.2, PHP 8.3 |
| **Database System** | PostgreSQL 13+, MySQL 8.0+, or MariaDB 10.5+ |

---

## 🚀 Installation

Follow these steps to install the plugin manually:

1. **Download & Extract:** Download the repository and extract the files.
2. **Directory Placement:** Copy the `failgrade` folder into your Moodle installation's quiz access rules directory:
   ```bash
   moodle/mod/quiz/accessrule/failgrade
   ```
3. **Run Moodle Upgrade:** Log in to your Moodle site as an Administrator and navigate to **Site administration > Notifications** to trigger the database upgrade and complete the installation.
4. **Alternative Install:** Alternatively, zip the directory and upload it via **Site administration > Plugins > Install plugins**.

---

## 🛠️ Usage & Configuration

Configuring the lock rule is extremely straightforward:

### A. Grade-Based Mode (الاعتماد على درجة النجاح)
1. Navigate to your course and select a **Quiz** (or create a new one).
2. Go to **Quiz Settings > Grade**:
   - Set **Attempts allowed** to more than 1 (e.g., Unlimited, or 3 attempts).
   - Enter a value for **Grade to pass** (e.g., `8.00`).
3. Expand **Extra restrictions on attempts**:
   - Set **"Block extra attempts if passing grade"** to **"Yes (Rely on passing grade)"**.
4. Save the quiz settings.

### B. Competency-Based Mode (الاعتماد على الجدارات)
1. Navigate to your course and select a **Quiz**.
2. Expand **Extra restrictions on attempts**:
   - Set **"Block extra attempts if passing grade"** to **"Yes (Rely on competencies)"**.
   - A new setting **"Competency success threshold (%)"** will appear. Enter a custom value (e.g., `60` for 60%) or leave it at `0` to use the global report settings.
3. Map competencies to this quiz's questions using `qbank_competency`.
4. **How it works:** Students can attempt the quiz. Once they achieve the required percentage threshold across the questions mapped to each competency inside this quiz, they see the success message and their attempt start button is blocked. The real-time progress table visually highlights their status badge by badge.

---

## 💻 Directory Structure

```text
failgrade/
├── classes/                # Autoloaded classes (Access rule logic)
│   └── privacy/            # GDPR Privacy provider implementation
├── db/                     # Database definitions (install.xml, upgrade.php)
├── lang/                   # Language localization packs
│   ├── en/                 # English translations
│   ├── ar/                 # Arabic translations (NEW)
│   └── tr/                 # Turkish translations
├── tests/                  # Automated test suites (PHPUnit)
├── .github/                # GitHub Action workflows
├── version.php             # Moodle plugin version and dependency definition
└── README.md               # Professional documentation
```

---

## 🔒 Security & Privacy (GDPR)

This plugin fully supports Moodle's Privacy Subsystem:
- It exports student attempt metadata in compliance with GDPR requests.
- It handles the safe deletion of user data upon request.
- Passwords are encrypted/stored securely in compliance with standard database practices.

---

## 🧪 Development & Testing

We maintain high code quality standards. Run automated tests using Moodle's PHPUnit framework:

```bash
# Initialize PHPUnit environment
php admin/tool/phpunit/cli/init.php

# Run tests for this plugin
vendor/bin/phpunit --group quizaccess_failgrade
```

---

## 🔒 Security & Code Compliance

This plugin has been audited and hardened according to Moodle's strict security and quality guidelines:

- **CSRF Protection:** Standard Moodle session key verification (`require_sesskey()`) is enforced on all state-mutating actions (such as queueing calculations).
- **SQL Injection Prevention:** Every query utilizes Moodle's Database API (`$DB`) with parameter bindings and named placeholders (`:named`), completely avoiding raw SQL interpolation and protecting against injection attacks.
- **Input Sanitization:** Direct superglobals (`$_GET`, `$_POST`, `$_REQUEST`) are strictly forbidden. Input retrieval uses standard Moodle validation helpers like `required_param()` and `optional_param()` with strict parameter type filters (`PARAM_INT`, `PARAM_BOOL`, etc.).
- **Capability Controls:** Page entry points verify course contexts with `require_login()` and validate explicit capabilities (e.g. `mod/quiz:viewreports`, `local_competency_report:viewreports`) via `require_capability()`.
- **Frankenstyle & Namespace Compliance:** Database tables and autoloaded classes are strictly prefixed and namespaced (e.g. `\local_competency_report\...` or `\quizaccess_failgrade\...`) preventing any class name or table name collisions.
- **Coding Standards:** Code is audited via `PHP_CodeSniffer` (PHPCS) enforcing clean syntax, proper DocBlocks, and standard Moodle conventions.

---

## 📄 License & Credits

- **Copyright:** © 2026 Mahmoud Salem
- **License:** Licensed under the [GNU GPL v3 License](http://www.gnu.org/copyleft/gpl.html) (or later).