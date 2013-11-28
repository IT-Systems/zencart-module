# Zen Cart - Svea payment module
## Version 4.1
* Supports Zen Cart version 1.5.1 and 1.3.9

This module supports Svea invoice and payment plan payments in Sweden, Finland, Norway, Denmark, Netherlands and Germany, as well as creditcard and direct bank payments from all countries. It is also possible to perform basic Svea order administration actions by setting the order status in the Zen Cart order administration interface. The supported Svea administration actions are: Deliver and Cancel invoice and payment plan orders and Credit invoice orders.

The module has been tested with Zen Cart and any pre-installed checkout, coupon, voucher and shipping modules, including the Svea invoice fee. The module has been updated to make use of the latest payment systems at Svea, and builds upon the included Svea php integration package.

**NOTE**: If you are upgrading from the previous version 3.x of this module, please contact Svea support before installing the version 4.0 module, your account settings may require updating. Also, all payment methods should be uninstalled and then re-installed when upgrading (please make note of your previous configuration, as you'll have to re-enter your settings after upgrading 4.0). This ensures that all settings are initialised correctly in the new module. 

As always, we strongly recommend that you have a test environment set up, and make a backup of your existing site, database and settings before upgrading.

If you experience technical issues with this module, or if you have feature suggestions, please submit an issue on the Github issue list.

#Installation instructions

##Basic installation example using the Svea Invoice payment method

The following example assumes that you have already downloaded and installed Zen Cart as described in the [Zen Cart documentation](http://www.zen-cart.com/).

This guide covers how to install the Svea Zen Cart module and install various payment methods in your Zen Cart shop, as well as the various localisation settings you need to make to ensure that the module works properly.

### Install the Zen Cart Svea payment module files

* Download or clone the contents of [this repository from github](https://github.com/sveawebpay/zencart-module). Unless instructed otherwise by Svea support, we recommend that you use only the default master branch of the repository, which contain the latest stable and tested module release.

* Copy the contents of the src folder to your ZenCart root folder.

* Make sure to merge the files and folders from the module with the ones in your Zen Cart installation, replacing any previously installed files with updated versions.

* This module depends on the Svea php integration package, which is included under the "svea" folder. (There should be no need to upgrade the integration package separately from the zencart module, unless instructed to do so by Svea support.)

### Configure the payment modules in the Zen Cart admin panel
In this example we'll first configure the Svea invoice payment method, instructions for other payment methods then follows below.

#### Svea Invoice configuration

* Log in to your Zen Cart admin panel.

* Browse to _Modules -> Payment_ where the various Svea payment methods should appear in the list.

* Select the Svea payment method (here: Svea Invoice), it should now show the module settings in the right hand panel.

* Click the _install_ link of the payment method you want to install. For now, select install the Svea Invoice payment method.

![Invoice payment settings] (https://github.com/sveawebpay/zencart-module/raw/develop/docs/image/install_payment_method.PNG "Installing payment method")

* You will now see a view of current payment method settings. Select the _edit_ button to the modify the payment method settings. 

* _Enable Svea Invoice Module_: if set to false, the module is disabled and won't show up in the customer list of available payment methods on checkout.

* _Svea Username_, _Svea Password_ and _Svea Client no_: enter the username and password that corresponds to your client number for the country in question. You can only accept invoice payments from countries for which you have entered credentials, other country fields should be left empty. Test credentials will be provided to you by your Svea integration manager upon request. 

![Invoice payment settings] (https://github.com/sveawebpay/zencart-module/raw/develop/docs/image/invoice_settings_1.PNG "Method invoice settings 1")

* _Transaction mode_: Determines whether payments using this method go to Svea's test or production servers. Until you have been giving the go ahead by Svea, this should be set to Test. Then, in order to receive payments for production orders, this should be switched over to its Production setting.

* _Accepted Currencies_: The list of currencies which you accept as payment. These must all be defined in your Zen Cart settings, see "Localisation and additional Zen Cart configuration requirements" below.

* _Default Currency_: If the customer ha an unsupported currency selected it will be converted to the default currency upon customer checkout. The default currency must also be present in the _Accepted Currencies_ list (above).

* _Set Order Status_: The Zen Cart order status given to orders after the customer has completed checkout.

* _Auto Deliver Order_: Order invoices will be delivered (sent out) to the customer by Svea if an order's status is set to this status. This may be done manually through the Zen Cart order admin, or you may use the Set Over Status (above) to this and the order will be autodelivered upon creation.

![Auto Deliver order settings] (https://github.com/sveawebpay/zencart-module/raw/develop/docs/image/autodeliver.PNG "Auto deliver settings")

* _Invoice Distribution Type_: If _Auto Deliver Order_ (above) is set to true, this setting must match the corresponding setting in Svea's admin interface. Ask your Svea integration manager is unsure.

* _Ignore OT list_: if you experience problems with i.e. incompatible order total modules, the module name(s) may be entered here and will then be ignored by the invoice payment module.

* _Payment Zone_: if a zone is selected here, invoice payments will only be accepted from within that zone. See "Localisation and additional Zen Cart configuration requirements" below.

* _Sort order of display_: determines the order in which payment methods are presented to the customer on checkout. The method are listed in ascending order on the payment method selection page.

* Finally, remember to _save_ your settings.

![Invoice payment settings] (https://github.com/sveawebpay/zencart-module/raw/develop/docs/image/invoice_settings_2.PNG "Method invoice settings 2")

#### Next we set up the Svea Invoice handling fee (used by Svea Invoice payment method )

* Browse to _Modules -> Order Total_.

* Select _Svea Invoice handling fee_ in the list, choose _install_ and then _edit_:

* _This module is installed_: Yep. So it is.

* _Tax class_: Select the tax class that will be applied to the invoice fee.

* _Sort order_ determines where in the order total stack the invoice fee will be displayed upon checkout.

* _Fee_: The fee can either be set to a specific amount, i.e. "5.00", or set to a percentage of the order sub-total, by ensuring the last character of the fee is a '%', i.e. "5.00%". Note that the fee always should be specified excluding tax. Also, make sure to use the correct decimal point notation, i.e. a dot (.) when specifying the fee.

![Invoice fee settings] (https://github.com/sveawebpay/zencart-module/raw/develop/docs/image/invoice_fee_settings.PNG "Invoice fee settings")

### Other payment methods
For the other Svea payment methods (payment plan, card payment and direct bank payment), see below.

#### Svea Payment Plan configuration

* In Zen Cart admin panel, go to _Modules -> Payment_.

* Locate _Svea Payment Plan_ in the list, _install_ and then _edit_ the module setting:

* _Enable Svea Payment Plan Module_: if set to false, the module is disabled and won't show up in the customer list of available payment methods on checkout

* _Svea Username <Country>_, _Svea Password <Country>_ and _Svea Client no <Country>_: enter the username and password that corresponds to your client number for the country in question. You can only accept invoice payments from countries for which you have entered credentials, other country fields should be left empty. Test credentials will be provided to you by your Svea integration manager upon request. 

* _Min amount for <Country> in <Currency>_ and _Max amount for <Country> in <Currency>_: The minimum and maximum amount for the various campaigns. Use the minimum and maximum value over the set of all active campaigns. Ask your Svea integration manager if unsure.

* _Transaction mode_: Determines whether payments using this method go to Svea's test or production servers. Until you have been giving the go ahead by Svea, this should be set to Test. Then, in order to receive payments for production orders, this should be switched over to its Production setting.

* _Accepted Currencies_: The list of currencies which you accept as payment. These must all be defined in your Zen Cart settings, see "Localisation and additional Zen Cart configuration requirements" below.

* _Default Currency_: If the customer has an unsupported currency selected it will be converted to the default currency upon customer checkout. The default currency must also be present in the _Accepted Currencies_ list (above).

* _Set Order Status_: The Zen Cart order status given to orders after the customer has completed checkout.

* _Auto Deliver Order_: Order payment plan will be delivered (sent out) to the customer by Svea if an order's status is set to this status. This may be done manually through the Zen Cart order admin, or you may use the Set Over Status (above) to this and the order will be autodelivered upon creation.

* _Ignore OT list_: if you experience problems with i.e. incompatible order total modules, the module name(s) may be entered here and will then be ignored by the invoice payment module.

* _Payment Zone_: if a zone is selected here, invoice payments will only be accepted from within that zone. See "Localisation and additional Zen Cart configuration requirements" below.

* _Sort order of display_: determines the order in which payment methods are presented to the customer on checkout. The method are listed in ascending order on the payment method selection page.

* Finally, remember to _save_ your settings.

#### Svea Card configuration

* In Zen Cart admin panel, go to _Modules -> Payment_.

* Locate _Svea Card_ in the list, _install_ and then _edit_ the module setting:

* _Enable Svea Card Payment Module_: if set to false, the module is disabled and won't show up in the customer list of available payment methods on checkout

* _Svea Card Merchant ID_ and _Svea Card Secret Word_: enter your provided merchant ID and secret word. These are provided to you by your Svea integration manager.

* _Svea Card Test Merchant ID_ and _Svea Card Test Secret Word_: enter your provided test merchant ID and secret word. Test credentials will be provided to you by Svea upon request.

* _Transaction mode_: Determines whether payments using this method go to Svea's test or production servers. Until you have been giving the go ahead by Svea, this should be set to Test. Then, in order to receive payments for production orders, this should be switched over to its Production setting.

* _Accepted Currencies_: The list of currencies which you accept as payment. These must all be defined in your Zen Cart settings, see "Localisation and additional Zen Cart configuration requirements" below.

* _Default Currency_: If the customer has an unsupported currency selected it will be converted to the default currency upon customer checkout. The default currency must also be present in the _Accepted Currencies_ list (above).

* _Set Order Status_: The Zen Cart order status given to orders after the customer has completed checkout. This will be overridden by _Auto Deliver Order_, if set (see below).

* _Ignore OT list_: if you experience problems with i.e. incompatible order total modules, the module name(s) may be entered here and will then be ignored by the invoice payment module.

* _Payment Zone_: if a zone is selected here, invoice payments will only be accepted from within that zone. See "Localisation and additional Zen Cart configuration requirements" below.

* _Sort order of display_: determines the order in which payment methods are presented to the customer on checkout. The method are listed in ascending order on the payment method selection page.

* Finally, remember to _save_ your settings.

#### Svea Direct Bank configuration
* In Zen Cart admin panel, go to _Modules -> Payment_.

* Locate _Svea Direct Bank_ in the list, _install_ and then _edit_ the module setting:

* _Enable Svea Direct Bank Payment Module_: if set to false, the module is disabled and won't show up in the customer list of available payment methods on checkout

* _Svea Direct Bank Merchant ID_ and _Svea Direct Bank Secret Word_: enter your provided merchant ID and secret word. These are provided to you by your Svea integration manager. 

* _Svea Direct Bank Test Merchant ID_ and _Svea Direct Bank Test Secret Word_: enter your provided test merchant ID and secret word. Test credentials will be provided to you by Svea upon request.

* _Transaction mode_: Determines whether payments using this method go to Svea's test or production servers. Until you have been giving the go ahead by Svea, this should be set to Test. Then, in order to receive payments for production orders, this should be switched over to its Production setting.

* _Accepted Currencies_: The list of currencies which you accept as payment. These must all be defined in your Zen Cart settings, see "Localisation and additional Zen Cart configuration requirements" below.

* _Default Currency_: If the customer ha an unsupported currency selected it will be converted to the default currency upon customer checkout. The default currency must also be present in the _Accepted Currencies_ list (above).

* _Set Order Status_: The Zen Cart order status given to orders after the customer has completed checkout. This will be overridden by _Auto Deliver Order_, if set (see below).

* _Ignore OT list_: if you experience problems with i.e. incompatible order total modules, the module name(s) may be entered here and will then be ignored by the invoice payment module.

* _Payment Zone_: if a zone is selected here, invoice payments will only be accepted from within that zone. See "Localisation and additional Zen Cart configuration requirements" below.

* _Sort order of display_: determines the order in which payment methods are presented to the customer on checkout. The method are listed in ascending order on the payment method selection page.

* Finally, remember to _save_ your settings.

##Localisation and additional Zen Cart configuration requirements

### Country specific requirements
* In NL and GE stores, the postal code needs to be set to required for customer registrations. It is used by the invoice and payment plan modules for credit check information et al.

### Currencies settings
* Under _Localisation -> Currencies_, all currencies from the various modules _Accepted Currencies_ lists must be defined or the modules will not work properly.

* Under _Localisation -> Currencies_, the _Decimal Places_ setting must be set to two (2) for _Euro_.

### Order Total settings
* Under _Modules -> Order Total_, in the Svea Invoice handling fee module, _Fee_ must be specified excluding any taxes (VAT).

* The recommended order total modules sort order is: sub-total (lowest), svea invoice fee, shipping, coupon, taxes, store credit, voucher and total.

## Svea order administration actions
It is possible to perform basic Svea order administration actions by setting the corresponding order status in the Zen Cart order administration interface. The supported Svea administration actions are: Deliver and Cancel invoice and payment plan orders and Credit invoice orders.

To administrate an order and change its order status, go to admin/customers/orders, select the order and press the "edit" button:

![Invoice payment settings] (https://github.com/sveawebpay/zencart-module/raw/develop/docs/image/admin_order_list.PNG "Zen Cart admin orders")

### Deliver invoice or payment plan order
When an order is marked as delivered by Svea, the invoice or payment plan will be created and delivered to the customer. The timing of this should usually correspond with the ordered goods being shipped out from your store to the customer. 

Only orders being delivered in their entirety can be administrated through the Zen Cart interface, if you wish to partially deliver an order you have to administer the order through the Svea administration interface instead.

To deliver an order, go to the order and select the status corresponding to the payment method Auto Deliver setting from the Status dropdown menu (here: "Delivered [3]"). You may also use the "Svea: Delivered [1703]" setting directly. Then press the "update" button: 

![Deliver order] (https://github.com/sveawebpay/zencart-module/raw/develop/docs/image/deliver_order.PNG "Deliver order")

The new order status will show up in the order status history along with a comment stating the Svea order id. The current order status will also show in the order list view. Note that a setting corresponding to the Auto Deliver setting will be converted to the "Svea: Delivered" status.

### Cancel (close) invoice or payment plan order
Use this if you wish to cancel a non-delivered invoice or payment plan order.

![Cancel order] (https://github.com/sveawebpay/zencart-module/raw/develop/docs/image/close_order.PNG "Cancel order")

### Credit invoice plan order
Use this if you wish to credit a delivered invoice order in full.

Should you get an error message "WARNING: Credit invoice failed, status not changed. Error: The invoice to credit is not approved.", the invoice has not been approved by Svea. You may approve the invoice manually via the Svea administration interface. You may also turn on auto-approval of invoices, please contact your Svea integration manager to this effect.

![Credit order] (https://github.com/sveawebpay/zencart-module/raw/develop/docs/image/credit_order_2.PNG "Credit order")

##Troubleshooting and recommendations
Always check that you have set up your settings correctly before posting issues or contacting Svea support. Specifically, the following settings must all be in place for the payment modules to work correctly in the various countries:

### Check your Svea customer credentials
* Your _username, password_, and _client no_ for Invoice and Part Payment are correct.

* Your _secret word_ and _merchant id_ for Card and Direct bank payments are correct.

### Check correlated Zen Cart settings and localisations
* Under _Locations/Taxes_ and _Localisation_, the correlating _Tax classes, Tax rates_, _Currencies_, _Zone_ and _Zone Definitions_ settings are correct.

* Under _Modules -> Order Totals_, double check that the sort order et al is correct.

* You are using the correct test case credentials when conducting test purchases.

### General FAQ

**Q:** *What is this Svea administration interface that you talk about?*

**A:** This is where you administrate invoices and payment plans created from your customer orders. You should have been provided with login information and instructions from your Svea integration manager, please contact them if not so. 

An example screenshot of the interface is found in the next section under "Invoice orders".

### Specific payment method problems FAQ

#### Invoice orders

**Q:** *I receive the error message "WARNING: Credit invoice failed, status not changed. Error: The invoice to credit is not approved." when attempting to credit an order.*

**A:** The invoice has not been approved by Svea. You may approve the invoice manually via the Svea administration interface. You may also turn on auto-approval of invoices, please contact your Svea integration manager to this effect.

![Svea administration interface] (https://github.com/sveawebpay/zencart-module/raw/develop/docs/image/svea_admin.PNG "Svea administration interface")

(Intentionally left blank.)

### Release history

* 4.1    (20131128) Able to perform basic Svea order administration actions by setting the order status in the Zen Cart order administration interface. Changes to AutoDeliver order settings for invoice and payment plan payment methods.
* 4.0.1  (20131118) Fix for wrong client order id used in request.
* 4.0.0  (20131112) Rewrite of module to build on Svea php integration package and support the new eu payment flow. Supports ZenCart 1.5.1.
