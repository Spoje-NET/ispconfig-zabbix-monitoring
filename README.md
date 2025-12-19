# ISPConfig Zabbix Monitoring

This project provides a monitoring ecosystem for ISPConfig using Zabbix 7.4. It includes scripts for autodiscovery and reading individual keys, along with templates for each module, starting with the websites module.

## Project Structure

```
ispconfig-zabbix-monitoring
├── src
│   ├── autodiscovery
│   │   └── websites.php
│   ├── keys
│   │   └── websites.php
│   └── lib
│       ├── ISPConfigClient.php
│       └── ZabbixHelper.php
├── templates
│   └── websites
│       └── template_ispconfig_websites.yaml
├── config
│   └── config.example.php
├── scripts
│   └── install.sh
├── composer.json
└── README.md
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

- The `src/autodiscovery/websites.php` script will automatically discover websites in ISPConfig and format the data for Zabbix.
- The `src/keys/websites.php` file defines the keys for monitoring website metrics.
- The Zabbix template for monitoring websites can be found in `templates/websites/template_ispconfig_websites.yaml`.

## Contribution

Contributions are welcome! Please fork the repository and submit a pull request with your changes. Make sure to follow the coding standards and include tests for new features.

## License

This project is licensed under the MIT License. See the LICENSE file for more details.