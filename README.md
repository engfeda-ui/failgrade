# 🛑 Moodle Quiz Access Rule: Fail Grade (`quizaccess_failgrade`)

[![Moodle Compatibility](https://img.shields.io/badge/Moodle-4.0%20to%205.0%2B-orange.svg?style=flat-square)](https://moodle.org)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%20%7C%208.2%20%7C%208.3-blue.svg?style=flat-square)](https://php.net)
[![Database](https://img.shields.io/badge/Database-PostgreSQL%20%7C%20MySQL%20%7C%20MariaDB-purple.svg?style=flat-square)](https://docs.moodle.org)
[![License](https://img.shields.io/badge/License-GPL%20v3-green.svg?style=flat-square)](http://www.gnu.org/copyleft/gpl.html)

An essential Moodle quiz access rule plugin designed to enforce mastery-based learning. This plugin prevents students from starting new quiz attempts once they have achieved a passing grade, encouraging them to focus on other areas once competency is proven.

Based on and updating the legacy `Reattempt Checker` and `Pass Grade` access rules, this plugin simplifies integration by leveraging Moodle’s native **"Grade to pass"** configuration.

---

## ✨ Features

- **Automated Mastery Locking:** Restricts students from making additional attempts once they reach or exceed the passing grade.
- **Native Moodle Integration:** Leverages Moodle’s built-in **"Grade to pass"** (located under Grade settings) without requiring custom database fields.
- **Customizable Feedback:** Provides clear, professional notifications to students explaining that they have passed and why further attempts are locked.
- **Enterprise Standards:**
  - **Privacy Subsystem (GDPR):** Full compliance with Moodle's privacy requirements.
  - **Reliable Backup & Restore:** Integrates with Moodle's core course backup/restore pipeline.
- **Developer-Grade Quality:**
  - Standardized PHPUnit test coverage for verifying attempt locking logic.
  - GitHub Actions CI/CD workflows for continuous build and test validation.

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

1. Navigate to your course and select a **Quiz** (or create a new one).
2. Go to **Quiz Settings > Grade**:
   - Set **Attempts allowed** to more than 1 (e.g., Unlimited, or 3 attempts).
   - Enter a value for **Grade to pass** (e.g., `8.00`).
3. Expand **Extra restrictions on attempts**:
   - Check the option **"Lock attempts after passing"** (enabled by this access rule).
4. Save the quiz settings.
5. **How it works:** When a student takes the quiz and receives a score equal to or higher than `8.00`, any future attempts are automatically locked, and the student sees a friendly "You have already passed this quiz" message.

---

## 💻 Directory Structure

```text
failgrade/
├── classes/                # Autoloaded classes (Access rule logic)
│   └── privacy/            # GDPR Privacy provider implementation
├── db/                     # Database definitions (install.xml, access.php)
├── lang/                   # Language localization packs
│   ├── en/                 # English translations
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

## 📄 License & Credits

- **Copyright:** © 2026 Hakan Çiğci ([https://hakancigci.com.tr](https://hakancigci.com.tr))
- **License:** Licensed under the [GNU GPL v3 License](http://www.gnu.org/copyleft/gpl.html) (or later).