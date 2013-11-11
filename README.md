# Zen Cart - Svea payment module
## Version 4.0.0
* Supports Zen Cart version 1.5.1 and 1.3.9

This module supports Svea invoice and payment plan payments in Sweden, Finland, Norway, Denmark, Netherlands and Germany, as well as creditcard and direct bank payments from all countries.

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

#### Svea invoice configuration

* Log in to your Zen Cart admin panel.
* Browse to _modules -> payments_ where the various Svea payment methods should appear in the list.
* Select the Svea payment method (here: Svea Invoice), it should now show the module settings in the right hand panel.
* Click the _install_ link of the payment method you want to install. For now, select install the Svea Invoice payment method.

![Invoice payment settings] (https://github.com/sveawebpay/zencart-module/raw/develop/docs/image/install_payment_method.PNG "Installing payment method")

* You will now see a view of current payment method settings. Select the _edit_ button to the modify the payment method settings. 
* _Enable Svea Invoice Module: if set to false, the module is disabled and won't show up in the customer list of available payment methods on checkout.
* _Svea Username_, _Svea Password_ and _Svea Client no_: enter the username and password that corresponds to your client number for the country in question. You can only accept invoice payments from countries for which you have entered credentials, other country fields should be left empty. Test credentials will be provided to you by your Svea integration manager upon request. 

![Invoice payment settings] (https://github.com/sveawebpay/zencart-module/raw/develop/docs/image/invoice_settings_1.PNG "Method invoice settings 1")

* _Transaction mode_: Determines whether payments using this method go to Svea's test or production servers. Until you have been giving the go ahead by Svea, this should be set to Test. Then, in order to receive payments for production orders, this should be switched over to its Production setting.
* _Accepted Currencies_: The list of currencies which you accept as payment. These must all be defined in your Zen Cart settings, see "Localisation and additional Zen Cart configuration requirements" below.
* _Default Currency_: If the customer ha an unsupported currency selected it will be converted to the default currency upon customer checkout. The default currency must also be present in the _Accepted Currencies_ list (above).
* _Set Order Status_: The Zen Cart order status given to orders after the customer has completed checkout. This will be overridden by _Auto Deliver Order_, if set (see below).
* _Auto Deliver Order_: If set to True, Svea invoices will automatically be delivered (sent out to) the customer. This means that you don't have to manually accept and deliver invoices via Svea's admin interface, which is the case if this is set to False.
* _Invoice Distribution Type_: If _Auto Deliver Order_ (above) is set to true, this setting must match the corresponding setting in Svea's admin interface. Ask your Svea integration manager is unsure.
* _Ignore OT list_: if you experience problems with i.e. incompatible order total modules, the module name(s) may be entered here and will then be ignored by the invoice payment module.
* _Payment Zone_: if a zone is selected here, invoice payments will only be accepted from within that zone. See "Localisation and additional Zen Cart configuration requirements" below.
* _Sort order of display_: determines the order in which payment methods are presented to the customer on checkout. The method are listed in ascending order on the payment method selection page.
* Finally, remember to _save_ your settings.

![Invoice payment settings] (https://github.com/sveawebpay/zencart-module/raw/develop/docs/image/invoice_settings_2.PNG "method invoice settings 2")

#### Next we set up the Svea handling fee (used by Svea Invoice payment method )

* Browse to _extensions -> order totals_.
* Locate _Svea handling fee_ in the list, choose _install_ and then _edit_:
* Set the _order total_ field to a large number, 99999999 is recommended.
* Set the _fee_ to the amount charged.
* Set the corresponding _tax class_.
* Also, set the _status_ field to _enabled_.
* Finally, the _sort order_ field must be set to apply before taxes.

![Invoice fee additional settings] (https://github.com/sveawebpay/zencart-module/raw/develop/docs/image/InvoiceFee.PNG "Invoice fee additional settings")

See also "Localisation and additional OpenCart configuration requirements" below.

### Other payment methods
For the other Svea payment methods (payment plan, card payment and direct bank payment), see below.


### Release history

* 4.0.0  (20131111) Rewrite of module to build on Svea php integration package and support the new eu payment flow. Supports ZenCart 1.5.1.
