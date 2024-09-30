# WP Funnel Custom Order Greetings Email Sender Plugin

When a customer checks out through WP Funnels, this plugin stores the customer information and sends a simple greeting email when the total order count reaches 100.

## Prerequisite Plugins

To use this plugin, please install the WP Funnels and WooCommerce plugins, and import/add products.

## Installation 

Upload this addon/plugin, then install and activate it. You can also upload the plugin manually. Go to the wp-content/plugins folder, create a folder named wpf-order-greetings, and then create a file named wpf-order-greetings.php inside that folder.

## WorkFlow

 When you place an order via the WP Funnels template page, the order-related customer information will be stored in the database. When the total order count reaches 100, a greetings email will be sent to each customer. Once the email has been successfully sent to 100 customers, this plugin will remove those customer records from the database.