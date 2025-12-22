# ISPConfig Zabbix Monitoring

This project provides a monitoring ecosystem for ISPConfig using Zabbix 7.4. It includes scripts for autodiscovery and reading individual keys, along with templates for each module, starting with the websites module.

## Project Structure

```
ispconfig-zabbix-monitoring
├── src
│   ├── autodiscovery
│   │   ├── websites.php
│   │   ├── emails.php
│   │   └── mail_domains.php
│   ├── keys
│   │   ├── websites.php
│   │   ├── emails.php
│   │   └── mail_domains.php
│   └── lib
│       ├── ISPConfigClient.php
│       └── ZabbixHelper.php
├── templates
│   ├── websites
│   │   └── template_ispconfig_websites.yaml
│   └── email
│       ├── template_ispconfig_mail_accounts.yaml
│       └── template_ispconfig_mail_domains.yaml
├── config
│   ├── config.example.php
│   └── config.php
├── scripts
│   └── install.sh
├── composer.json
├── README.md
├── PROJECT_PLAN.md
└── LICENSE
```

## Installation

1. Clone the repository:
   ```
   git clone https://your-repo-url.git
   cd ispconfig-zabbix-monitoring
   ```

2. Install dependencies using Composer:
   ```
   composer install
   ```

3. Configure the project by copying the example configuration file:
   ```
   cp config/config.example.php config/config.php
   ```

   Edit `config/config.php` to add your ISPConfig API credentials.

4. Run the installation script to set up the environment:
   ```
   ./scripts/install.sh
   ```

## Usage

### Websites Module

The `src/autodiscovery/websites.php` script automatically discovers websites in ISPConfig and formats the data for Zabbix.

The `src/keys/websites.php` script retrieves metrics for individual websites:
```bash
php src/keys/websites.php <website_id> <key>
```

Available keys: `active`, `domain`, `server_id`, `document_root`, `php_version`, `ssl_enabled`, `traffic`, `disk_usage`, `hd_quota`

### Email Module

The `src/autodiscovery/emails.php` script discovers all email accounts in ISPConfig.

The `src/keys/emails.php` script retrieves metrics for individual email accounts:
```bash
php src/keys/emails.php <email_id> <key>
```

Available keys: `active`, `email`, `domain`, `quota`, `used`, `usage_percent`, `spamfilter_enabled`, `antivirus_enabled`, `mail_domain_id`, `server_id`, `homedir`

### Mail Domains Module

The `src/autodiscovery/mail_domains.php` script discovers all mail domains in ISPConfig.

The `src/keys/mail_domains.php` script retrieves metrics for mail domains:
```bash
php src/keys/mail_domains.php <domain_id> <key>
```

Available keys: `active`, `domain`, `server_id`, `mail_catchall`, `account_count`, `total_quota`, `total_used`

### Zabbix Templates

Import the following templates into Zabbix:
- `templates/websites/template_ispconfig_websites.yaml` - Websites monitoring
- `templates/email/template_ispconfig_mail_accounts.yaml` - Email accounts monitoring
- `templates/email/template_ispconfig_mail_domains.yaml` - Mail domains monitoring

## Contribution

Contributions are welcome! Please fork the repository and submit a pull request with your changes. Make sure to follow the coding standards and include tests for new features.

## License

This project is licensed under the MIT License. See the LICENSE file for more details.
