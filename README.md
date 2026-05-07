# Heleket Payment integration for Magento 2

 - [Main Functionalities](#markdown-header-main-functionalities)
 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)
 - [Specifications](#markdown-header-specifications)
 - [Attributes](#markdown-header-attributes)


## Installation
\* = in production please use the `--keep-generated` option

### Type 1: Zip file

 - Unzip the zip file in `app/code/MageBrains`
 - Enable the module by running `php bin/magento module:enable MageBrains_Heleket`
 - Apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`

### Type 2: Composer

 - Make the module available in a composer repository for example:
 - 
 - Install the module composer by running `composer require magebrains/heleket-magento2`
 - enable the module by running `php bin/magento module:enable MageBrains_Heleket`
 - apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`


## Configuration

 - System Settings->Sales->Payment methods->Heleket
 - Set API key(Payment key) from Heleket merchant settings
 - Set Merchant UUID(Secret) from Heleket merchant settings 


## Specifications

 - Payment Method
	- heleket



