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

### Debian/Ubuntu Package Installation

The repository with packages for Debian & Ubuntu is available:

```bash
sudo apt install lsb-release wget apt-transport-https
wget -qO- https://repo.vitexsoftware.com/keyring.gpg | sudo tee /etc/apt/trusted.gpg.d/vitexsoftware.gpg
echo "deb [signed-by=/etc/apt/trusted.gpg.d/vitexsoftware.gpg] https://repo.vitexsoftware.com $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/vitexsoftware.list
sudo apt update
sudo apt install ispconfig-zabbix-monitoring
```

During installation, you'll be prompted for:
- ISPConfig Remote API URL
- ISPConfig API username
- ISPConfig API password

The configuration will be saved to `/etc/ispconfig-zabbix-monitoring/config.php`.

To reconfigure after installation:
```bash
sudo dpkg-reconfigure ispconfig-zabbix-monitoring
```

### Manual Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/Spoje-NET/ispconfig-zabbix-monitoring.git
   cd ispconfig-zabbix-monitoring
   ```

2. Install dependencies using Composer:
   ```bash
   composer install
   ```

3. Configure the project by copying the example configuration file:
   ```bash
   cp config/config.example.php config/config.php
   ```

   Edit `config/config.php` to add your ISPConfig API credentials.

4. Copy the Zabbix agent configuration:
   ```bash
   sudo cp config/zabbix_agentd.d/ispconfig-monitoring.conf /etc/zabbix/zabbix_agentd.d/
   sudo systemctl restart zabbix-agent
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

## Zabbix Configuration

After installation, the Zabbix agent configuration is automatically installed to:
- `/etc/zabbix/zabbix_agentd.d/ispconfig-monitoring.conf` (Zabbix Agent 1)
- `/etc/zabbix/zabbix_agent2.d/ispconfig-monitoring.conf` (Zabbix Agent 2)

The following UserParameters are available:
- `ispconfig.websites.discovery` - Website autodiscovery
- `ispconfig.website[*]` - Website metrics
- `ispconfig.emails.discovery` - Email account autodiscovery
- `ispconfig.email[*]` - Email account metrics
- `ispconfig.maildomains.discovery` - Mail domain autodiscovery
- `ispconfig.maildomain[*]` - Mail domain metrics

## Testing

The project includes a comprehensive test suite:

```bash
composer install
vendor/bin/phpunit
```

42 tests with 82 assertions covering:
- ISPConfigClient configuration validation and error handling
- ZabbixHelper data formatting and discovery
- Value conversion (numeric, boolean, bytes, timestamps)
- LLD data validation

## License

This project is licensed under the MIT License. See the LICENSE file for more details.

## Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Add tests for new features
4. Submit a pull request

Follow the existing code style and ensure all tests pass before submitting.
