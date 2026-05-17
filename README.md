
# Moodle - Fail Grade Quiz Access Rule (quizaccess_failgrade)

## Description

The purpose of this plugin is to restrict extra attempts on Quiz after the user reach a passing grade.

Based on the [Reattempt Checker - a quiz access rule](https://moodle.org/plugins/quizaccess_reattemptchecker) and [Pass grade quiz access rule](https://moodle.org/plugins/quizaccess_passgrade), the main goal is:

 - update to support newer versions of Moodle
 - simplify the structure and use the already existing field "grade to pass"
 - add tests

# Instalation

Please refer to the official documentation: [Installing Plugins](https://docs.moodle.org/en/Installing_plugins)

## Requirements & Compatibility

- **Moodle Compatibility:** Moodle 4.0 up to 5.0+ (Fully compatible with Moodle 4.5 / `MOODLE_405_STABLE` and Moodle 5.0+).
- **PHP Compatibility:** PHP 8.1, 8.2, and 8.3.
- **Database:** PostgreSQL (13+) or MySQL/MariaDB.

# Status / Roadmap

- [X] Publish plugin on GitHub

- [X] Submit to [Moodle Plugins directory](https://moodle.org/plugins/)

- [X] GDPR

- [X] Unit tests

- [ ] Behat tests

- [ ] Translate to other languages

# Development

Please, use GitHub for issues.

## License

Licensed under the [GNU GPL License](http://www.gnu.org/copyleft/gpl.html)