## WP Funnel Custom Order Greetings Email Sender Plugin

 When a customer checks out through WP Funnels, this plugin stores the customer information and sends a simple greeting email when the total order count reaches 100.

### Note

 I did not write code inside the <b>wpfunnels/public/modules/checkout/class-wpfnl-checkout.php</b> file because it's not standard practice to modify a plugin directly. Instead, I created a separate plugin <b>(wpf-order-greetings)</b> and used the WP Funnels plugin hook <b>[wpfunnels/funnel_order_placed]</b>, which is triggered in the save_checkout_fields function.

### Installation 

 Zip this plugin and upload this addon/plugin, then install and activate it. You can also upload the plugin <b>manually</b>. Go to the <b>wp-content/plugins</b> folder, create a folder named <b>wpf-order-greetings</b>, and then create a file named <b>wpf-order-greetings.php</b> inside that folder. This plugin is dependent with Woocommerce and WP Funnels plugin, So those plugins is required.

### WorkFlow

 When you place an order via the WP Funnels template page, the order-related customer information will be stored in the database. When the total order count reaches 100, a greetings email will be sent to each customer. Once the email has been successfully sent to 100 customers, this plugin will remove those customer records from the database.


### Any Query 
#### shahnaouzrazu91@gmail.com